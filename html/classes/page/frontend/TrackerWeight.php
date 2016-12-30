<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

class TrackerWeight  extends PageBase{
	
	private $utm = null;

	public function __construct(){
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
		
		$this->myId=$this->cred->getId();
		
		if($this->cred->has("LOGIN_HEALTH_COACH")){						
			if(isset($_SESSION["MASK_USER"])){
				$this->myId=$_SESSION["MASK_USER"];
			}else{
				throw new Exception("Illegal access: NO User ID Specified");
			}
		}
		$this->utm = new UserTrackerModel();
	}
	/**
	 * Show tracker page
	 * @param unknown_type $param
	 */
	public function Index($param){	
		require_once (ROOT_DIR."classes/model/UserTrackMeasurementsModel.php");
		
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Weight/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);
		
		$Weight=new UserTrackWeightModel($this->myId);
		$template->addVar("log",$Weight->getData($log));		

		$rec=null;
		
		if(isset($_GET['edit'])){
			$rec=$Weight->getEntry($_GET['edit']);
			$t = strtotime($rec['date_entered']);
			$rec['disp_date']=date('M d, Y', $t);
		}else{
			$rec=$Weight->getLastEntry();
			$rec['date_entered']=date("Y-m-d");
			$rec['disp_date']=date("M d, Y");
		}
				
		if($rec){
			foreach($rec as $key=>$value){
		 		$_POST[$key]=$value;
			}
		}
		$template->addVar("post",$_POST);

		$dp = $Weight->getTotalDataPoints();
		$template->addVar("data_points", $dp);

		$rec=$Weight->getLastEntry();
		$template->addVar("current",$rec);

		$weight=($rec)?$rec['weight']:0;

		require_once (ROOT_DIR."classes/model/UserProfileModel.php");
		$upm=new UserProfileModel($this->myId);

		$height=($upm->get("height_ft")*12)+$upm->get("height_in");
		$bmi=round($weight/pow($height,2)*703,2);

		$mtm= new UserTrackMeasurementsModel($this->myId);

		$gw = $upm->get("goal_weight");
		$sign = "";
		if ($gw > $weight) $sign = "-";
		else if ($gw < $weight) $sign = "+";

		$template->addVar("bmi",$bmi);
		$template->addVar("goal_weight",$gw);
		$template->addVar("distance_from_goal_weight",$sign . abs($weight-$gw));

		$iw = $upm->get("initial_weight");
		$sign = "";
		if ($iw > $weight) $sign = "-";
		else if ($iw < $weight) $sign = "+";

		$template->addVar("initial_weight",$iw);
		$template->addVar("change_from_init_weight",$sign . abs($iw-$weight));
		$template->addVar("body_fat",$bfp=$mtm->calculateBodyFat());

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
	}

	/**
	 * Add entry to tracker
	 * @param unknown_type $param
	 */
	
	public function AddEntry($param){			
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Weight/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);
		
		$Weight=new UserTrackWeightModel($this->myId);
		$err=$Weight->validateData($_POST);
		
		if($err){
			return $this->Index($param);
		}
		$Weight->addEntry();
		header("Location: /TrackerWeight/Index?log=".$log);
  		exit();
	}
		/**
	 * Delete tracker entry
	 * @param unknown_type $param
	 */
	
	public function DeleteEntry($param){
		$log=isset($_GET['log'])?$_GET['log']:"";	
		$recId=isset($param[0])?$param[0]:"";
		$Weight=new UserTrackWeightModel($this->myId);
		$Weight->deleteEntry($recId);		
		header("Location: /TrackerWeight/Index?log=".$log);
  		exit();
	}
	/**
	 * Show graph of logged entries
	 */
	
/*	
	public function history($params){
		$progressReport=isset($params[0])&&$params[0]==1;
		
		$log=isset($_GET['log'])?$_GET['log']:"";
		if($progressReport) $log='';
		
		$Weight=new UserTrackWeightModel($this->myId);
		$dataPts=$Weight->getData($log,"ASC");		
		
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
 // Dataset definition    
 $DataSet = new pData;   
 foreach($dataPts as $d){
	$DataSet->AddPoint($d['weight'],'weight',$d['date_entered']);
}
  
 $gImWidth=760;
 $gImHeight=300;
 $gWidth=640;
 $gHeight=220;
 $gTitleLeft=585;
 $gFont=array(8,6,8,10);
 $gLeft=70;
 $gTop=30;
 if($progressReport){
	$gImWidth=330;
	$gImHeight=250;
	$gWidth=300;
	$gHeight=180;
	$gTitleLeft=292;
	$gFont=array(4,3,4,5);	
	$gLeft=45;
 	$gTop=30;	
 }
 
 $Test = new pChart($gImWidth,$gImHeight);
 $Test->setDateFormat("M d, y");
 
 $DataSet->AddAllSeries();   
 $DataSet->SetAbsciseLabelSerie();   
 $DataSet->SetSerieName("Total Weight","weight");   

 $DataSet->SetYAxisName("Weight");
 $DataSet->SetYAxisUnit(" lbs");

 $DataSet->SetXAxisName("Weight-in Date");  
 $DataSet->SetXAxisFormat("date");
 
 // Initialise the graph   
 
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",$gFont[0]);   
 $Test->setGraphArea($gLeft,$gTop,$gWidth,$gHeight);   
 
 $Test->drawFilledRoundedRectangle(7,7,$gImWidth-7,$gImHeight-7,5,240,240,240);
    
 $Test->drawRoundedRectangle(5,5,$gImWidth-5,$gImHeight-5,5,200,230,230);
    
 $Test->drawGraphArea(255,255,255,TRUE);
 
 $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,90,2);
    
 $Test->drawGrid(4,TRUE,230,230,230,50);
  
 // Draw the 0 line   
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",$gFont[1]);   
 $Test->drawTreshold(0,143,55,72,TRUE,TRUE);   
  
 // Draw the line graph
 $Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());   
 $Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);   
  
 // Finish the graph
  if(!$progressReport){
 	$Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",$gFont[2]);   
 	$Test->drawLegend($gWidth+5,35,$DataSet->GetDataDescription(),255,255,255);   
 	$Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",$gFont[3]);   
 	$Test->drawTitle(60,22,"Weight Readings",50,50,50,$gTitleLeft);   
  }
 $Test->Render();
 exit();
	}
*/	
	
	public function history($params){
		$progressReport=isset($params[0])&&$params[0]==1;
		
		$log=isset($_GET['log'])?$_GET['log']:"";
		if($progressReport) $log='';
	
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   

		if ($progressReport) {
			$gImWidth = 430;
			$gImHeight = 255;
			$gWidth = 390;
			$gHeight = 230;
			$gTitleLeft = 382;
			$nPts = 8;
		}
		else {
			$gImWidth=628;
 			$gImHeight=373;
			$gWidth=593;
 			$gHeight=350;
 			$gTtitleLeft = 585;
 			$nPts = 12;
 		}

		$Weight=new UserTrackWeightModel($this->myId);
		$dataPts=$Weight->getData($log,"ASC",$progressReport, $nPts);

 		$gFont=array(8,6,8,10);
 		$gLeft=70;
 		$gTop=30;

 		$font=ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf";
 	
		//Dataset definition
		$Test = new pChart($gImWidth,$gImHeight);
		$Test->drawGraphAreaGradient(236,233,233,-1,TARGET_BACKGROUND);
		$Test->addBorder(1, 180, 180, 180);

		if (count($dataPts) > 1) {
 			$DataSet = new pData;
	 		$label=array();
	 		$maxw = 0.0;
	 		$minw = 1000.0;
	 		foreach($dataPts as $d){
				if ($d['weight'] > $maxw) $maxw = $d['weight'];
				if ($d['weight'] < $minw) $minw = $d['weight'];
				$DataSet->AddPoint($d['weight'],'weight');
				$label[]=date('n/j/y', strtotime($d['de']));
			}
	  	$label=$DataSet->AddPoint($label,'label');
			$diff = ($maxw - $minw) / 3;

	 		$DataSet->AddSerie("weight");
	 		$DataSet->SetAbsciseLabelSerie("label");
	 		$DataSet->SetSerieName("Total Weight","weight");

			 // Initialise the graph
	 		$Test->setDateFormat("M d, y");

	 		$Test->setFontProperties($font,$gFont[0]);   
			 //$Test->setGraphArea($gLeft,$gTop,$gWidth,$gHeight);


			 // Prepare the graph area  
	 		$Test->setFontProperties($font,8);  
	 		$Test->setGraphArea(60,40,$gWidth,$gHeight);  

			 // Initialise graph area  
	 		$Test->setFontProperties($font,8);  

			$Test->setColorPalette(0, 0, 100, 0);
			 // Draw the Weight graph  
	 		$DataSet->SetYAxisName("Weight lbs");  

			$Test->setFixedScale($minw - $diff, $maxw + $diff);
	 		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,21,21,22,TRUE,0,0); 
	 		$Test->drawGraphAreaGradient(255,255,255,-10);  
	 		$Test->drawGrid(4,TRUE,180,180,180,10);
//	 		$Test->setShadowProperties(3,3,0,0,0,30,4);  
	 		$Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());  
//	 		$Test->clearShadow();  
// 			$Test->drawFilledCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription(),.1,30);  
 			$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);


		 	// Write the legend (box less)  
	  	$Test->setFontProperties($font,8);  
	  	$Test->drawLegend(530,5,$DataSet->GetDataDescription(),0,0,0,0,0,0,21,21,22,FALSE);    
 		}
// 		$Test->setFontProperties($font,18);  
// 		$Test->setShadowProperties(1,1,0,0,0);  
// 		$Test->drawTitle(0,0,"Weight Tracker",255,255,255,660,30,TRUE);  
		if (count($dataPts) < 2) {
			$Test->setFontProperties($font, 12);
			$Test->drawTitle(0,0,"No data points found",255,255,255,600,350,TRUE);
		}
// 		$Test->clearShadow();  

 		$Test->Render();
 		exit();
	}	
}