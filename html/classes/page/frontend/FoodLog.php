<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (ROOT_DIR."classes/model/FoodLogModel.php");
	require_once (ROOT_DIR."classes/model/CustomFoodsModel.php");
  require_once (LIB_ROOT."classes/common/Ajax.class.php");
	require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');
  
  /**
   * FoodLog.php
   * presents the food log/meal plan functions
   * 
   */

class FoodLog extends PageBase {

	private $flm;
	private $cfm;
	private $utm;


	public function __construct () {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
		
		$this->flm = new FoodLogModel($this->cred->getId());
		$this->cfm = new CustomFoodsModel($this->cred->getId());
		$this->utm = new UserTrackerModel();
	}

	public function Index($param){

		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/foodlog/index.tpt");
		if (isset($param[0])) {
			$template->addVar("page", $param[0]);
		}

		$date_entered = isset($_POST['date_entered']) ? $_POST['date_entered'] : (isset($_GET['date_entered']) ? $_GET['date_entered'] : date('Y-m-d'));
		$meals = $this->flm->getMeals();
		$foods = $this->flm->getFoodLog($date_entered);
		$dri = $this->flm->getDailyRecommendedIntake();
		$total = 0;
		$carbs = 0;
		$fats = 0;
		$prot = 0;
		if ($foods) {
			foreach($foods as $f) {
				$total += $f['total_calories'];
				$carbs += $f['carbohydrates'];
				$fats += $f['fats'];
				$prot += $f['protein'];
			}
		}
		$diff = $dri['target'] - $total;
		if ($diff >= 0) {
			$rem = 1;
		}
		else {
			$diff = abs($diff);
			$rem = 0;
		}

		$total = sprintf("%.1f", $total);
		$dri['target'] = sprintf("%.1f", $dri['target']);
		$diff = sprintf("%.1f", $diff);

		$display = array();
		foreach($meals as $m) {
			$display[$m['MealID']] = "none";
		}
		if (count($foods) == 0) {
			$display['all'] = "none";
		}
		else {
			$display['all'] = "block";
			foreach($foods as $f) {
				$display[$f['MealID']] = "block";
			}
		}

		$cc = $this->flm->getComments($date_entered);
		if ($cc) {
			$comment = $cc['comment'];
		}
		else {
			$comment = "";
		}

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);

		$POST['date_entered'] = $date_entered;
		$tm = strtotime($date_entered);
		$POST['disp_date'] = date('M d, Y', $tm);
		$template->addVar('meals', $meals);
		$template->addVar('foods', $foods);
		$template->addVar('dri', $dri);
		$template->addVar('total', $total);
		$template->addVar('diff', $diff);
		$template->addVar('rem', $rem);
		$template->addVar('carbs', $carbs);
		$template->addVar('fats', $fats);
		$template->addVar('protein', $prot);
		$template->addVar('display', $display);
		$template->addVar('POST', $POST);
		$template->addVar('comment', $comment);

		return $template;
	}


	public function GetList($param) {
		$ajax = new Ajax();
		$src = isset($_POST['src']) ? $_POST['src'] : null;
		$str = isset($_POST['value']) ? $_POST['value'] : null;
//		if (!$src || !$str) {
		if (!$str) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
			$ajax->writeResponseXML();
			exit;
		}
		try {
			$cfm = new CustomFoodsModel();
			$data1 = $cfm->searchCustomFoods($str);

			$flm = new FoodLogModel();
			$data2 = $flm->searchFoods($str);

			if ($data1) {
				if ($data2) {
					$data = array_merge($data1, $data2);
				}
				else {
					$data = $data1;
				}
			}
			else {
				if ($data2) {
					$data = $data2;
				}
				else {
					$data = null;
				}
			}
			$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
			$ajax->addResponseData("values", $data);
			$ajax->addResponseData("src", $src);
		} catch(Exception $e) {
			$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
		}
		$ajax->writeResponseXML();
		exit;
	}

	public function AddFoodLog ($param) {
		$log = array();
		$log['date_entered'] = isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d');
		$log['selected_meal'] = isset($_POST['selected_meal']) ? $_POST['selected_meal'] : 0;
		$log['foodID'] = isset($_POST['foodID']) ? $_POST['foodID'] : 0;
		$log['serving'] = isset($_POST['servingSize']) ? $_POST['servingSize'] : 1;
		$log['unit'] = isset($_POST['UnitID']) ? $_POST['UnitID'] : 0;
		$log['custom'] = isset($_POST['custom']) ? $_POST['custom'] : 0;

		$this->flm->saveFoodLog("add", 0, $log);
		header("Location: /FoodLog/Index?date_entered=".$log['date_entered']);
		exit;
	}


	public function UpdateFoodLog ($param) {
		$fid = isset($_POST['foodID']) ? $_POST['foodID'] : 0;
		$log = array();
		$log['date_entered'] = isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d');
		$log['srv'] = isset($_POST['qty']) ? $_POST['qty'] : 0;
		$this->flm->saveFoodLog("upd", $fid, $log);
		header("Location: /FoodLog/Index?date_entered=".$log['date_entered']);
		exit;
	}


	public function DeleteFoodLog ($param) {
		$fid = isset($_POST['foodID']) ? $_POST['foodID'] : 0;
		$logdate = isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d');
		$this->flm->saveFoodLog("del", $fid);
		header("Location: /FoodLog/Index?date_entered=" . $logdate);
		exit;
	}
	
	public function GetAllResults($param){
	
		$template = TemplateParser::create(TEMPLATE_DIR."frontend/foodlog/total_results.tpt");
		
		$date_entered = isset($param[0]) ? $param[0] : date('Y-m-d');
		
		$this->flm = new FoodLogModel();
		$foods = $this->flm->getTotalFoodLog($date_entered);
		
		$acc = array();
		$acc = array_shift($foods);
		
		
		foreach ($foods as $val){
			foreach ($val as $key => $val){
				$acc[$key] += $val;
			}
		}
		
		$template->addVar("total",$acc);
		

		
		$dri = $this->flm->getDailyRecommendedIntake();
		
		
		$total_array = $acc + $dri;
		
		
		$extra_array = array();
		
		$extra_array['carbs'] = 100 *$total_array['carbohydrates']/ $total_array[3];
			
		$extra_array['carbs'] =  sprintf("%.2f",$extra_array['carbs']);
		
		$extra_array['prot'] = 100*$total_array['protein']/$total_array[2];
		
		$extra_array['prot'] = sprintf("%.2f",$extra_array['prot']);
		
		$extra_array['fat'] = 100*$total_array['fats']/$total_array[3];
		
		$extra_array['fat'] = sprintf("%.2f",$extra_array['fat']);
		
		if ($total_array[7] != 0) {
			
		
		
		$extra_array['satfat'] = 100*$total_array['saturated fat']/$total_array[7];
		
		$extra_array['satfat'] = sprintf("%.2f",$extra_array['satfat']);
		} else {
			
		$extra_array['satfat'] = 0;	
		
		}
		
		$extra_array['n3_poly'] = 100*$total_array['Linolenic']/$total_array[27];
		
		$extra_array['n3_poly'] = sprintf("%.2f",$extra_array['n3_poly']);
		
		
		$extra_array['n6_poly'] = 100*$total_array['Linoleic']/$total_array[26];
		
		$extra_array['n6_poly'] = sprintf("%.2f",$extra_array['n6_poly']);
		
		
		$extra_array['histid'] = 100*$total_array['Histidine']/$total_array[96];
		
		$extra_array['histid'] = sprintf("%.2f",$extra_array['histid']);
		
		$extra_array['isoleu'] = 100*$total_array['Isoleucine']/$total_array[87];
		
		$extra_array['isoleu'] = sprintf("%.2f",$extra_array['isoleu']);
		
		$extra_array['leuc'] = 100*$total_array['Leucine']/$total_array[88];
		
		$extra_array['leuc'] = sprintf("%.2f",$extra_array['leuc']);
			
		$extra_array['lysi'] = 100*$total_array['Lysine']/$total_array[89];
		
		$extra_array['lysi'] = sprintf("%.2f",$extra_array['lysi']);
		
		$extra_array['methio'] = 100*$total_array['Methionine']/$total_array[90];
		
		$extra_array['methio'] = sprintf("%.2f",$extra_array['methio']);
		
		$extra_array['phenyl'] = 100*$total_array['Phenylalanine']/$total_array[92];
		
		$extra_array['phenyl'] = sprintf("%.2f",$extra_array['phenyl']);
		
		$extra_array['threo'] = 100*$total_array['Threonine']/$total_array[86];
		
		$extra_array['threo'] = sprintf("%.2f",$extra_array['threo']);
		
		$extra_array['trypto'] = 100*$total_array['Tryptophan']/$total_array[85];
		
		$extra_array['trypto'] = sprintf("%.2f",$extra_array['trypto']);
		
		$extra_array['vali'] = 100*$total_array['Valine']/$total_array[94];
		
		$extra_array['vali'] = sprintf("%.2f",$extra_array['vali']);
		
		$extra_array['vitaminA'] = 100*$total_array['Vit AIU']/$total_array[38];
		
		$extra_array['vitaminA'] = sprintf("%.2f",$extra_array['vitaminA']);
		
		$extra_array['vitaminC'] = 100*$total_array['Vitamin C']/$total_array[45];
		
		$extra_array['vitaminC'] = sprintf("%.2f",$extra_array['vitaminC']);
		
		$extra_array['vitaminD'] = 100*$total_array['Vit DIU']/$total_array[49];
		
		$extra_array['vitaminD'] = sprintf("%.2f",$extra_array['vitaminD']);
		
		$extra_array['vitaminE'] = 100*$total_array['Vit EIU']/$total_array[51];
		
		$extra_array['vitaminE'] = sprintf("%.2f",$extra_array['vitaminE']);
		
		$extra_array['vitaminK'] = 100*$total_array['Vit K']/$total_array[62];
		
		$extra_array['vitaminK'] = sprintf("%.2f",$extra_array['vitaminK']);
		
		$extra_array['thiam'] = 100*$total_array['Thiamin']/$total_array[53];
		
		$extra_array['thiam'] = sprintf("%.2f",$extra_array['thiam']);
		
		$extra_array['ribofl'] = 100*$total_array['Riboflavin']/$total_array[54];
		
		$extra_array['ribofl'] = sprintf("%.2f",$extra_array['ribofl']);
		
		
		$extra_array['niac'] = 100*$total_array['Niacin']/$total_array[55];
		
		$extra_array['niac'] = sprintf("%.2f",$extra_array['niac']);
		
		$extra_array['vitaminB6'] = 100*$total_array['Vit B6']/$total_array[57];
		
		$extra_array['vitaminB6'] = sprintf("%.2f",$extra_array['vitaminB6']);
		
		$extra_array['folat'] = 100*$total_array['Folate']/$total_array[58];
		
		$extra_array['folat'] = sprintf("%.2f",$extra_array['folat']);
		
		$extra_array['vitaminB12'] = 100*$total_array['Vit B12']/$total_array[59];
		
		$extra_array['vitaminB12'] = sprintf("%.2f",$extra_array['vitaminB12']);
		
		$extra_array['panto'] = 100*$total_array['Panto Acid']/$total_array[61];
		
		$extra_array['panto'] = sprintf("%.2f",$extra_array['panto']);
		
		$extra_array['biot'] = 100*$total_array['Biotin']/$total_array[60];
		
		$extra_array['biot'] = sprintf("%.2f",$extra_array['biot']);
		
		$extra_array['calc'] = 100*$total_array['Calcium']/$total_array[46];
		
		$extra_array['calc'] = sprintf("%.2f",$extra_array['calc']);
		
		$extra_array['chrom'] = 100*$total_array['Chromium']/$total_array[71];
		
		$extra_array['chrom'] = sprintf("%.2f",$extra_array['chrom']);
		
		$extra_array['copp'] = 100*$total_array['Copper']/$total_array[67];
		
		$extra_array['copp'] = sprintf("%.2f",$extra_array['copp']);
		
		$extra_array['fluo'] = 100*$total_array['Fluoride']/$total_array[70];
		
		$extra_array['fluo'] = sprintf("%.2f",$extra_array['fluo']);
		
		$extra_array['iod'] = 100*$total_array['Iodine']/$total_array[64];
		
		$extra_array['iod'] = sprintf("%.2f",$extra_array['iod']);
		
		$extra_array['iro'] = 100*$total_array['Iron']/$total_array[47];
		
		$extra_array['iro'] = sprintf("%.2f",$extra_array['iro']);
		
		$extra_array['magnes'] = 100*$total_array['Magnesium']/$total_array[65];
		
		$extra_array['magnes'] = sprintf("%.2f",$extra_array['magnes']);
		
		$extra_array['manga'] = 100*$total_array['Manganese']/$total_array[68];
		
		$extra_array['manga'] = sprintf("%.2f",$extra_array['manga']);
		
		$extra_array['molyb'] = 100*$total_array['Molybdenum']/$total_array[72];
		
		$extra_array['molyb'] = sprintf("%.2f",$extra_array['molyb']);
		
		$extra_array['phos'] = 100*$total_array['Phosphorus']/$total_array[63];
		
		$extra_array['phos'] = sprintf("%.2f",$extra_array['phos']);
		
		$extra_array['selen'] = 100*$total_array['Selenium']/$total_array[69];
		
		$extra_array['selen'] = sprintf("%.2f",$extra_array['selen']);
		
		$extra_array['zin'] = 100*$total_array['Zinc']/$total_array[66];
		
		$extra_array['zin'] = sprintf("%.2f",$extra_array['zin']);
		
		$extra_array['potass'] = 100*$total_array['Potassium']/$total_array[35];
		
		$extra_array['potass'] = sprintf("%.2f",$extra_array['potass']);
		
		$extra_array['sod'] = 100*$total_array['Sodium']/$total_array[34];
		
		$extra_array['sod'] = sprintf("%.2f",$extra_array['sod']);
		
		
		
		
			
		$template->addVar("percent", $extra_array);
		$template->parse();
		exit();
		
		
		
		return $template;
		
		
	}

	public function nutrition ($param) {
		$flid = isset($param[0]) ? $param[0] : 0;
		if ($flid == 0) {
			exit();
		}

		$template = TemplateParser::create(TEMPLATE_DIR."frontend/foodlog/nutrition.tpt");
		$this->flm = new FoodLogModel();
		$data = $this->flm->getNutritionData($flid);
		$food = $this->flm->getFoodInfo($flid);
		$template->addVar("foodName", $food['name']);
		$template->addVar("amount", $food['serving_amount']);
		$template->addVar("unit", $food['serving_unit']);


//
// percent daily requirement.
//
		$dri = $this->flm->getDailyRecommendedIntake();

		$pcent = array();
		foreach($data['raw'] as $k => $v) {
			if ($dri[$k]) {
				if ($dri[$k] > 0) {
					$pc = 100.0 * $v / $dri[$k];
					if (($pc < .1) && ($v > 0)) {
						$pcent[$k] = sprintf("%.2f", $pc);
					}
					else {
						$pcent[$k] = sprintf("%.1f", $pc);
					}
				}
				else {
					$pcent[$k] = "0";
				}
			}
			else {
				$pcent[$k] = "0";
			}
		}
		
		$template->addVar("data", $data['display']);
		$template->addVar("percent", $pcent);
		$template->parse();
		exit();
	}

// This function is called as a result as being referenced as an image
// in HTML code.  Therefore, once the chart is drawn (Render) it exits.
//
// Inputs:
//		$param[0] = total carbohydrates
//		$param[1] = total protein
//		$param[2] = total fats
//


	public function nutritionmeal ($param) {
		$flid = isset($param[0]) ? $param[0] : 0;
		if ($flid == 0) {
			exit();
		}

		$template = TemplateParser::create(TEMPLATE_DIR."frontend/foodlog/nutritionmeal.tpt");
		$this->flm = new FoodLogModel();
		$data = $this->flm->getNutritionDatameal($flid);
		$food = $this->flm->getFoodInfomeal($flid);
		$template->addVar("foodName", $food['name']);
		$template->addVar("amount", $food['serving_amount']);
		$template->addVar("unit", $food['serving_unit']);


//
// percent daily requirement.
//
		$dri = $this->flm->getDailyRecommendedIntake();

		$pcent = array();
		foreach($data['raw'] as $k => $v) {
			if ($dri[$k]) {
				if ($dri[$k] > 0) {
					$pc = 100.0 * $v / $dri[$k];
					if (($pc < .1) && ($v > 0)) {
						$pcent[$k] = sprintf("%.2f", $pc);
					}
					else {
						$pcent[$k] = sprintf("%.1f", $pc);
					}
				}
				else {
					$pcent[$k] = "0";
				}
			}
			else {
				$pcent[$k] = "0";
			}
		}
		
		$template->addVar("data", $data['display']);
		$template->addVar("percent", $pcent);
		$template->parse();
		exit();
	}



	public function AddComment($param) {
		$dte = isset($param[0]) ? $param[0] : date('Y-m-d');
		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/foodlog/comments.tpt");
		$cc = $this->flm->getComments($dte);
		if ($cc) {
			$comment = $cc['comment'];
			$template->addVar("action", "upd");
		}
		else {
			$comment = "";
			$template->addVar("action", "add");
		}
		$template->addVar("comment", $comment);
		$template->addVar("date_entered", $dte);
		return $template;
	}


	public function saveComment($param) {
		$this->flm->addComment($_POST['action'], $_POST['date_entered'], $_POST['comment']);

		header('Location: /FoodLog/Index?date_entered=' . $_POST['date_entered']);
		exit();
	}


	public function PieChart($param) {
		require_once (ROOT_DIR.'/classes/helper/pChart/pChart.class.php');
		require_once (ROOT_DIR.'/classes/helper/pChart/pData.class.php');

		$DS = new pData();
		$a1 = array($param[2], $param[1], $param[0]);
		$a2 = array('Fats', 'Protein', 'Carbohydrates');
		$DS->AddPoint($a1, "Serie1");
		$DS->AddPoint($a2, "Serie2");
		$DS->AddAllSeries();
		$DS->SetAbsciseLabelSerie("Serie2");

		$chart = new pChart(630,355);
		$chart->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf", 10);
		$chart->setColorPalette(0, 236, 178, 20);
		$chart->setColorPalette(1, 106, 166, 42);
		$chart->setColorPalette(2, 90, 144, 199);
		$chart->drawGraphAreaGradient(231,231,231,10,TARGET_BACKGROUND);

		$locX = 240;
		$locY = 165;
		$size = 160;
		$skew = 70;
		$height = 20;
		$space = 10;
		$chart->drawPieGraph($DS->GetData(), $DS->GetDataDescription(),$locX,$locY,$size, PIE_PERCENTAGE_LABEL,TRUE, $skew,$height,$space);
		$chart->drawPieLegend(490,20,$DS->GetData(), $DS->GetDataDescription(),200,200,200);
		$chart->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf", 14);
		$chart->drawTitle(0,0, '', 0,0,0);
		$chart->Render();
		exit();
	}

	public function Other($params) {
		$err[0] = "";
		if (isset($params[0])) {
			$err[0] = "Error 1";
		}

		$arr['tdate'] = "2010-00-00";				//Check if Validator class can handle M/Y type date
		require_once (LIB_ROOT."classes/common/Validator.class.php");
		$vc = new Validator();
		try {
			$rdate = $vc->exists('tdate', $arr, "date", array("datestamp"=>1, "allow_zeros"=>true), true, false);
		}
		catch (ValidationException $e) {
			$err[1] = "Did not validate!";
		}
		if (!isset($rdate)) {
			$rdate = "";
		}
		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/foodlog/results.tpt");
		$template->addVar("errors", $err);
		$template->addVar("input", $arr['tdate']);
		$template->addVar("output", $rdate);
		return $template;
	}

	public function Server ($params) {

		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/foodlog/result.tpt");
		$keys = array();
		foreach($_SERVER as $k => $v) {
			array_push ($keys, $k);
echo $k . " = " . $v . "<br />";
		}
echo "<br />";
		$template->addVar("server", $_SERVER);
		$template->addVar("keys", $keys);
		return $template;
	}
}
