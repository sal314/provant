<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/UserTrackBloodGlucoseModel.php");
require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

class TrackerBloodGlucose  extends PageBase{

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
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/BloodGlucose/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);
				
		$BloodGlucose=new UserTrackBloodGlucoseModel($this->myId);
		$template->addVar("log",$BloodGlucose->getData($log));
		$template->addVar("fasting_data", $BloodGlucose->getTotalDataPoints("fasting"));
		$template->addVar("random_data", $BloodGlucose->getTotalDataPoints("random"));

		$rec=null;
		if(isset($_GET['edit'])){
			$rec=$BloodGlucose->getEntry($_GET['edit']);
			$t = strtotime($rec['date_entered']);
			$rec['disp_date']=date('M d, Y', $t);
		}
		else {
			$rec=$BloodGlucose->getLastEntry();
			$rec['date_entered']=date("Y-m-d");
			$rec['disp_date']=date("M d, Y");
		}
		
		if($rec){
			foreach($rec as $key=>$value){
		 		$_POST[$key]=$value;
			}
		}
		$template->addVar("post",$_POST);

		$dp = $BloodGlucose->getTotalDataPoints();
		$template->addVar("data_points", $dp);

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
	}
	/**
	 * Add entry to tracker
	 * @param unknown_type $param
	 */
	public function AddEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		
		$BloodGlucose=new UserTrackBloodGlucoseModel($this->myId);
		$err=$BloodGlucose->validateData($_POST);		
		if($err){
			return $this->Index($param);
		}
		$BloodGlucose->addEntry();
		header("Location: /TrackerBloodGlucose/Index?log=".$log);
  	exit();
	}
	/**
	 * Delete tracker entry
	 * @param unknown_type $param
	 */
	public function DeleteEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		$recId=isset($param[0])?$param[0]:"";
		$BloodGlucose=new UserTrackBloodGlucoseModel($this->myId);
		$BloodGlucose->deleteEntry($recId);		
		header("Location: /TrackerBloodGlucose/Index?log=".$log);
  		exit();
	}
	/**
	 * Show graph of logged entries
	 */
	

public function history($params){
		$progressReport=isset($params[0])&&$params[0]==1;
		
		$log=isset($_GET['log'])?$_GET['log']:"";
		if($progressReport) $log='';

		$gType = isset($_GET['type'])? $_GET['type'] : "fasting";
//if ($gType == "non") sleep(1);
		$BloodGlucose=new UserTrackBloodGlucoseModel($this->myId);
		$dataPts=$BloodGlucose->getData($log,"ASC",12, $gType);

		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
 		$gImWidth=628;
 		$gImHeight=373;
 		$gWidth=573;
 		$gHeight=350;
 		$gTitleLeft=585;
 		$gFont=array(8,6,8,10);
 		$gLeft=70;
 		$gTop=30;
 
 		$font=ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf";
 	
		 // Initialise the graph   
 		$Test = new pChart($gImWidth,$gImHeight);
 		$Test->setFontProperties($font,$gFont[0]);   
		 //$Test->setGraphArea($gLeft,$gTop,$gWidth,$gHeight);
 		$Test->drawGraphAreaGradient(236,233,233,-1,TARGET_BACKGROUND);    
		$Test->addBorder(1, 180, 180, 180);

 		if (count($dataPts) > 1) {
	 		//Dataset definition
	 		$DataSet = new pData;
	 		$label=array();
	 		$data=array();
 		   
	 		$maxd = 0;
	 		$mind = 1000;
	 		$labels = array();
	 		$sdata = array();
	 		foreach($dataPts as $d){
				if ($d['blood_glucose'] > $maxd) $maxd = $d['blood_glucose'];
				if ($d['blood_glucose'] < $mind) $mind = $d['blood_glucose'];
	 			$dte = date("n/j/y", strtotime($d['date_entered']));
				array_push($labels, $dte);
				array_push($sdata, $d['blood_glucose']);
			}
			$diff = ($maxd - $mind) / 3;

			$DataSet->AddPoint($sdata, 'blood_glucose');
			$DataSet->AddPoint($labels,'label');
			$DataSet->AddSerie('blood_glucose');
	 		$DataSet->SetAbsciseLabelSerie("label");

			$Test->setFontProperties($font, 8);
			$Test->setGraphArea(60,40,573,350);

			if ($gType == "fasting") {
				$Test->setColorPalette(0, 0, 100, 0);
				$DataSet->SetSerieName("Fasting", "blood_glucose");
			}
			else {
				$Test->setColorPalette(0, 25, 70, 150);
				$DataSet->SetSerieName("Non-fasting", "blood_glucose");
			}
			$DataSet->SetYAxisName("ML");
			$Test->setFixedScale($mind - $diff, $maxd + $diff);

			$Test->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), SCALE_ADDALL, 21, 21,21,TRUE, 0, 0, TRUE);
			$Test->drawGraphAreaGradient(255,255,255,-10);
			$Test->drawGrid(4, TRUE, 180, 180, 180, 1);
	 		$Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());  
			$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);

	 		$Test->setFontProperties($font,8);  
	  	$Test->drawLegend(530,5,$DataSet->GetDataDescription(),0,0,0,0,0,0,21,21,21,FALSE);    
		
			$Test->Render();
	 		exit();
		}	
}


	public function history2() {
 		$font=ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf";
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   

		$d1 = array("", "", "", 90, "", 84, "", 99, "", 99);
		$d2 = array(100, 102, 98, "", 130, "", 122, "", 100, "");
		$d3 = array("7/5", "7/10", "7/15", "7/20", "7/25", "7/30", "8/5", "8/10", "8/15", "8/20");
		$d4 = array("7/5", "7/15", "7/25", "8/5", "8/15");

		$Data = new pData;
		$Data->AddPoint($d1, "fasting");
		$Data->AddPoint($d2, "random");
		$Data->AddPoint($d3, "dates");

		$Data->AddAllSeries();
		$Data->RemoveSerie("dates");
		$Data->SetAbsciseLabelSerie("dates");
		$Data->SetSerieName("Fasting", "fasting");
		$Data->SetSerieName("Random", "random");

		$Test = new pChart(633, 375);
		$Test->drawGraphAreaGradient(230,230,230,-1,TARGET_BACKGROUND);
		$Test->setFontProperties($font, 8);
		$Test->setGraphArea(60,40,573,350);

		$Test->setColorPalette(0, 0, 100, 0);
		$Test->setColorPalette(1, 25, 70, 150);
		$Data->SetYAxisName("ML");
		$Test->drawScale($Data->GetData(), $Data->GetDataDescription(), SCALE_ADDALL, 21, 21,21,TRUE, 0, 0, TRUE);
		$Test->drawGraphAreaGradient(255,255,255,-10);
		$Test->drawGrid(4, TRUE, 44, 44, 44, 1);
//		$Test->drawCubicCurve($Data->GetData(), $Data->GetDataDescription());
		$Test->drawStackedBarGraph($Data->GetData(), $Data->GetDataDescription());
		$Test->drawPlotGraph($Data->GetData(),$Data->GetDataDescription(),3,2,255,255,255);  

 		$Test->setFontProperties($font,8);  
 		$Test->setShadowProperties(1,1,0,0,0);  
	  $Test->drawLegend(530,5,$Data->GetDataDescription(),0,0,0,0,0,0,21,21,21,FALSE);    

		$Test->Render();
		exit();
	}		

	/*
	public function history(){
		$log=isset($_GET['log'])?$_GET['log']:"";
		$BloodGlucose=new UserTrackBloodGlucoseModel($this->myId);
		$dataPts=$BloodGlucose->getData($log,"ASC");		
		
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
 //Dataset definition    
 $DataSet = new pData();   
 
 foreach($dataPts as $d){
	$DataSet->AddPoint($d['blood_glucose'],$d['method'],$d['date_entered']);
 }
  
 $gImWidth=760;
 $gImHeight=300;
 $gWidth=640;
 $gHeight=220;
 
 $Test = new pChart($gImWidth,$gImHeight);
 $Test->setDateFormat("M d, y");
 
 $DataSet->AddAllSeries();   
 $DataSet->SetAbsciseLabelSerie();   
 $DataSet->SetSerieName("Fasting","fasting");   
 $DataSet->SetSerieName("Random","random");
 $DataSet->SetYAxisName("Readings");
 $DataSet->SetYAxisUnit("ml");

 $DataSet->SetXAxisName("Readings Date");  
 $DataSet->SetXAxisFormat("date");
 
 // Initialise the graph   
 
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",8);   
 $Test->setGraphArea(70,30,$gWidth,$gHeight);   
 $Test->drawFilledRoundedRectangle(7,7,$gImWidth-7,$gImHeight-7,5,240,240,240);   
 $Test->drawRoundedRectangle(5,5,$gImWidth-5,$gImHeight-5,5,200,230,230);   
 $Test->drawGraphArea(255,255,255,TRUE);
 $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,90,2);   
 $Test->drawGrid(4,TRUE,230,230,230,50);
 
  
 // Draw the 0 line   
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",6);   
 $Test->drawTreshold(0,143,55,72,TRUE,TRUE);   
  
 // Draw the line graph
 $Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());   
 $Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);   
  
 // Finish the graph   
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",8);   
 $Test->drawLegend($gWidth+5,35,$DataSet->GetDataDescription(),255,255,255);   
 $Test->setFontProperties(ROOT_DIR."classes/helper/pChart/Fonts/tahoma.ttf",10);   
 $Test->drawTitle(60,22,"Blood Glucose Readings",50,50,50,585);   
 $Test->Render();
 exit();
	}
	*/
}
