<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (ROOT_DIR."classes/model/CustomFoodsModel.php");
  require_once (LIB_ROOT."classes/common/Ajax.class.php");


	/**
	* @abstract - This is the main page for maintaining custom foods
	* @author - S.LePage
	* @version - 1.1
	*/

class CustomFoods extends PageBase {

	private $cfm = null;


	public function __construct () {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
		
		$this->cfm = new CustomFoodsModel($this->cred->getId());
	}


	public function Index ($params) {

		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/customfood/index.tpt");
		return $template;
	}


	public function SearchFood ($params) {

		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/customfood/search.tpt");
		if (count($_POST) > 0) {
			$foods = $this->cfm->searchCustomFoods($_POST['search_text']);
			$template->addVar("foods", $foods);
		}

		return $template;
	}


	public function getClasses () {
		$ajax = new Ajax();
		$src = isset($_POST['src']) ? $_POST['src'] : null;
		$str = isset($_POST['value']) ? $_POST['value'] : null;
		if (!$str) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
			$ajax->writeResponseXML();
			exit;
		}
		try {
			$cfm = new CustomFoodsModel();
			$data = $cfm->getFoodClasses($str);
			$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
			$ajax->addResponseData("values", $data);
			$ajax->addResponseData("src", $src);
		} catch(Exception $e) {
			$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
		}
		$ajax->writeResponseXML();
		exit;
	}


	public function AddFood ($param) {
		$where = isset($param[0]) ? $param[0] : "";
		$goto = "";
		if ($where == "fl") {
			$goto = "/FoodLog/Index";
			$dest = "Food Log";
		}
		if ($where == "mp") {
			$goto = "/MealPlanner/Index";
			$dest = "Meal Planner";
		}

		$template = TemplateParser::enqueue(TEMPLATE_DIR."/frontend/customfood/add.tpt");
		$template->addVar("goto", $goto);
		$template->addVar("where", $dest);
		$template->addVar("gotag", $where);

		$err = array();

		if (count($_POST) > 0) {
			$food = array();
			$nutrients = array();

			$food['food_name'] = $_POST['food_name'];
			$food['food_class_id'] = isset($_POST['food_class']) ? $_POST['food_class'] : 0;
			if ($food['food_class_id'] == 0) {
				$food['food_class_id'] = isset($_POST['major_class']) ? $_POST['major_class'] : 0;
			}
			$food['unit_id'] = isset($_POST['unit']) ? $_POST['unit'] : 1;
			$food['serving_amount'] = isset($_POST['amount']) ? $_POST['amount'] : 1;
			$food['serving_gram_wt'] = isset($_POST['nut0']) ? $_POST['nut0'] : 0;	//Nutrient '0' is wt. in grams
			$food['serving_note'] = isset($_POST['notes']) ? $_POST['notes'] : "";

			foreach($_POST as $key => $val) {
				if (preg_match('/^nut/', $key)) {
					$nutId = substr($key, 3);
					$nutrients[$nutId] = $val;
				}
			}

			try {
				$this->cfm->addCustomFoods($food, $nutrients);
			}
			catch (Exception $e) {
				array_push($err, $e->getMessage());
			}
			if ($err == "") {
				array_push($err, $food['food_name'] . " successfully added");
			}
		}

		$nutList = $this->cfm->getNutrientsList();
		$nutSize = count($nutList);
		$foodClasses = $this->cfm->getFoodClasses();
		$units = $this->cfm->getUnits();

		$template->addVar("nutrients", $nutList);
		$template->addVar("nsize", $nutSize);
		$template->addVar("classes", $foodClasses);
		$template->addVar("units", $units);

		$template->addVar("errors", $err);
		return $template;
	}
}

