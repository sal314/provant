<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/UserTrackBPModel.php");
require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

class TrackerBP  extends PageBase{

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
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/BP/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);
		
		$bp=new UserTrackBPModel($this->myId);
		$template->addVar("log",$bp->getData($log));

		$rec=null;
		if(isset($_GET['edit'])){
			$rec=$bp->getEntry($_GET['edit']);
			$t = strtotime($rec['date_entered']);
			$rec['disp_date']=date('M d, Y', $t);
		}else{
			$rec=$bp->getTodaysEntry();
			$rec['date_entered']=date("Y-m-d");
			$rec['disp_date']=date("M d, Y");
		}
		
		if($rec){
			foreach($rec as $key=>$value){
				$_POST[$key]=$value;
			}
		}
		
		$template->addVar("post",$_POST);

		$dp = $bp->getTotalDataPoints();
		$template->addVar('data_points', $dp);

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
	}
		/**
	 * Add entry to tracker
	 * @param unknown_type $param
	 */
	
	public function AddEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/BP/index.tpt");
		$bp=new UserTrackBPModel($this->myId);
		$err=$bp->validateData($_POST);
		if($err){
			return $this->Index($param);
		}
		$bp->addEntry();
		header("Location: /TrackerBP/Index/?log=".$log);
  		exit();
	}
		/**
	 * Delete tracker entry
	 * @param unknown_type $param
	 */
	
	public function DeleteEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		$recId=isset($param[0])?$param[0]:"";
		$bp=new UserTrackBPModel($this->myId);
		$bp->deleteEntry($recId);		
		header("Location: /TrackerBP/Index/?log=".$log);
  		exit();
	}
	/**
	 * Show graph of logged entries
	 */
	public function history($params){
		$progressReport=isset($params[0])&&$params[0]==1;
		
		$log=isset($_GET['log'])?$_GET['log']:"";
		if($progressReport) $log='';

		$bp=new UserTrackBPModel($this->myId);
		$dataPts=$bp->getData($log,"ASC",12);

		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
 		$gImWidth=628;
 		$gImHeight=373;
 		$gWidth=593;
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
//-- 		$Test->drawGraphAreaGradient(240,240,240,240,TARGET_BACKGROUND);    
   	$Test->drawGraphAreaGradient(236,233,233,-1,TARGET_BACKGROUND);
		$Test->addBorder(1, 180, 180, 180);

 		if (count($dataPts) > 1) {					//Need at least 2 data points to draw a graph
	 		//Dataset definition
	 		$DataSet = new pData;
	 		$label=array();
	 		$data=array();
 		   
	 		$count=0;
	 		$labels=array();
			$maxs = 0.0;
			$mins = 1000.0;
	 		foreach($dataPts as $d){ 			
	 			$labels[]=date('n/j/y', strtotime($d['date_entered']));
	 			$DataSet->AddPoint(intval($d['systolic']),'systolic');
	 			$DataSet->AddPoint(intval($d['diastolic']),'diastolic'); 			
				if ($d['systolic'] > $maxs) $maxs = $d['systolic'];
				if ($d['diastolic'] < $mins) $mins = $d['diastolic'];
			}
	  	$DataSet->AddPoint($labels,'label');
			$diff = ($maxs - $mins) / 3;

 
	 		$DataSet->AddSerie("systolic");
			$DataSet->SetAbsciseLabelSerie("label");
	 		$DataSet->SetSerieName("Systolic","systolic");
	 		$DataSet->SetSerieName("Diastolic","diastolic");  		
  		 		
	 		$Test->setDateFormat("M d, y");
 
			 // Prepare the graph area  
	 		$Test->setFontProperties($font,8);  
	 		$Test->setGraphArea(60,40,$gWidth,$gHeight);  
  
			 // Initialise graph area  
	 		$Test->setFontProperties($font,8);  

			 // Draw the graph
	   
	 		$DataSet->SetYAxisName("ML");
	 		$Test->setFixedScale($mins - $diff, $maxs + $diff);

//--	 		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,213,217,221,true,0,0);
			$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,21,21,22,true,0,0);
//--	 		$Test->drawGraphAreaGradient(40,40,40,-50);  
			$Test->drawGraphAreaGradient(255,255,255,-10);
	 		$Test->drawGrid(4,TRUE,180,180,180,10);  
//--	 		$Test->setShadowProperties(3,3,0,0,0,30,4);  		 		

			$Test->setColorPalette(0, 0, 100, 0);
	 		$Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());  
//--			$Test->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
	 		$Test->clearShadow();  
//--	 		$Test->drawFilledCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription(),.1,30);  
	 		$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255); 		
	 	   // Clear the scale
//	 		$Test->clearScale();
	 		$DataSet->RemoveSerie("systolic");

			$Test->setColorPalette(1, 25, 70, 150); 		
	 		$DataSet->AddSerie("diastolic"); 		
	 		//$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,213,217,221,true,0,0);  		 		
//--	 		$Test->setShadowProperties(3,3,0,0,0,30,4);  		
	 		$Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());  
//--			$Test->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());
	 		$Test->clearShadow();  
//--	 		$Test->drawFilledCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription(),.1,30);  
	 		$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);  


		 	// Write the legend (box less)  
	  		$Test->setFontProperties($font,8);  
	  		$Test->drawLegend(530,5,$DataSet->GetDataDescription(),0,0,0,0,0,0,21,21,21,FALSE);    
 		}

 		$Test->setFontProperties($font,18);  
 		$Test->setShadowProperties(1,1,0,0,0);  
// 		$Test->drawTitle(0,0,"Blood Pressure Tracker",255,255,255,660,30,TRUE);  
		if (count($dataPts) < 2) {
			$Test->setFontProperties($font, 12);
			$Test->drawTitle(0,0,"Not enough data points to graph",255,255,255,600,350,TRUE);
		}
 		$Test->clearShadow();

 		$Test->Render();
 		exit();
	}
		
	public function history2(){
		$log=isset($_GET['log'])?$_GET['log']:"";
		$bp=new UserTrackBPModel($this->myId);
		$dataPts=$bp->getData($log,"ASC",12);		
		
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
  
 // Dataset definition    
 $DataSet = new pData;   
 foreach($dataPts as $d){
 	$DataSet->AddPoint($d['systolic'],'systolic',$d['date_entered']);
 	$DataSet->AddPoint($d['diastolic'],'diastolic',$d['date_entered']);
 }
 
 
  
 $gImWidth=730;
 $gImHeight=300;
 $gWidth=640;
 $gHeight=220;
 
 $Test = new pChart($gImWidth,$gImHeight);
 $Test->setDateFormat("M d, y");
 
 $DataSet->AddAllSeries();   
 $DataSet->SetAbsciseLabelSerie();   
 $DataSet->SetSerieName("Systolic","systolic");   
 $DataSet->SetSerieName("Diastolic","diastolic");      
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
 $Test->drawTitle(60,22,"Blood Pressure Readings",50,50,50,585);   
 $Test->Render();
 exit();
	}
	
}
