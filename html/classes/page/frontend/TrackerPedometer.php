<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/UserTrackPedometerModel.php");
require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

class TrackerPedometer  extends PageBase{

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
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Pedometer/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);
		
		$Pedometer=new UserTrackPedometerModel($this->myId);
		$logData=$Pedometer->getData($log);
		$template->addVar("log",$logData);
		
		$Weight=new UserTrackWeightModel($this->myId);
		$wt=$Weight->getLastEntry();
		$weight = $wt['weight'];

		//for testing
//		$weight="184.4";
		if(!$weight && !sizeof($_POST)){
			$_POST["override"]=1;
			$_POST["calories"]=0;
		}
		$template->addVar("userweight",$weight);

		$rec=null;
		if(isset($_GET['edit'])){
			$rec=$Pedometer->getEntry($_GET['edit']);
			$t = strtotime($rec['date_entered']);
			$rec['disp_date']=date('M d, Y', $t);
		}else{
			$rec=$Pedometer->getLastEnteredStride();
			$rec['date_entered']=date("Y-m-d");
			$rec['disp_date']=date("M d, Y");
			$rec['stride'] = round($rec['stride'] * 12);
		}
		if($rec){
			foreach($rec as $key=>$value){
				$_POST[$key]=$value;
			}
		}

		
		$template->addVar("post",$_POST);
		$template->addVar("totals",$Pedometer->getTotals($logData));
		$template->addVar("data_points", $Pedometer->getTotalDataPoints());

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
	}
		/**
	 * Add entry to tracker
	 * @param unknown_type $param
	 */
	
	public function AddEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Pedometer/index.tpt");
		$Pedometer=new UserTrackPedometerModel($this->myId);
		$err=$Pedometer->validateData($_POST);
		if($err){
			return $this->Index($param);
		}
		$Pedometer->addEntry();
		header("Location: /TrackerPedometer/Index/?log=".$log);
		exit();
	}
		/**
	 * Delete tracker entry
	 * @param unknown_type $param
	 */
	
	public function DeleteEntry($param){	
		$log=isset($_GET['log'])?$_GET['log']:"";
		$recId=isset($param[0])?$param[0]:"";
		$Pedometer=new UserTrackPedometerModel($this->myId);
		$Pedometer->deleteEntry($recId);		
		header("Location: /TrackerPedometer/Index/?log=".$log);
  		exit();
	}
	/**
	 * How to measure stride page
	 * @param unknown_type $params
	 */
	public function measure($params){
		$template=TemplateParser::create(TEMPLATE_DIR."frontend/Tracker/Pedometer/measure.tpt");
		$template->parse();
		exit();
	}
	/**
	 * Show graph of logged entries
	 */
public function history($params){
		$progressReport=isset($params[0])&&$params[0]==1;
		
		$log=isset($_GET['log'])?$_GET['log']:"";
		if($progressReport) $log='';
	
		$Pedometer=new UserTrackPedometerModel($this->myId);
		$dataPts=$Pedometer->getData($log,"ASC");		
								
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
 		$Test->drawGraphAreaGradient(236,233,233,-1,TARGET_BACKGROUND);    
   
 		if (count($dataPts) > 1) {
	 		//Dataset definition
	 		$DataSet = new pData;
	 		$label=array();
	 		$data=array();
 		   
	 		$count=0;
	 		$labels=array();
	 		foreach($dataPts as $d){ 			
	 			$labels[]= date('n/j/y', strtotime($d['date_entered']));
	 			$DataSet->AddPoint($d['steps'],'steps');
			}
			$DataSet->AddPoint($labels,'label');

	 		$DataSet->AddSerie("steps");
			$DataSet->SetAbsciseLabelSerie("label");
	 		$DataSet->SetSerieName("Steps","steps");
  		 		
	 		$Test->setDateFormat("M d, y");

			 // Prepare the graph area  
	 		$Test->setFontProperties($font,8);
	 		$Test->setGraphArea(60,40,$gWidth,$gHeight);
  
			 // Initialise graph area  
	 		$Test->setFontProperties($font,8);
 		
			 // Draw the graph
		   
	 		$DataSet->SetYAxisName("Per Day");
	 		$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,21,21,22,true,0,0);   
	 		$Test->drawGraphAreaGradient(255,255,255,-10);  
			$Test->addBorder(1, 180, 180, 180);

			$Test->setColorPalette(0,0,100,0);
	 		$Test->drawGrid(4,TRUE,180,180,180,10);
//	 		$Test->setShadowProperties(3,3,0,0,0,30,4);  		 		
 		
	 		$Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());  
//	 		$Test->clearShadow();  
//	 		$Test->drawFilledCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription(),.1,30);  
	 		$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);
	 	   // Clear the scale
	 		$Test->clearScale();
	 		$DataSet->RemoveSerie("steps");
 		 		
		 	// Write the legend (box less)  
	  		$Test->setFontProperties($font,8);  
	  		$Test->drawLegend(530,5,$DataSet->GetDataDescription(),0,0,0,0,0,0,21,21,22,FALSE);    
 		}

// 		$Test->setFontProperties($font,18);  
// 		$Test->setShadowProperties(1,1,0,0,0);  
// 		$Test->drawTitle(0,0,"Pedometer Tracker",255,255,255,660,30,TRUE);  
		if (count($dataPts) < 2) {
			$Test->setFontProperties($font, 12);
			$Test->drawTitle(0,0,"No data points found",255,255,255,600,350,TRUE);
		}
 		$Test->clearShadow();  

 		$Test->Render();
 		exit();
	}	
/*	
	public function history2(){
		$log=isset($_GET['log'])?$_GET['log']:"";
		$Pedometer=new UserTrackPedometerModel($this->myId);
		$dataPts=$Pedometer->getData($log,"ASC");		
		
		require_once(ROOT_DIR."classes/helper/pChart/pData.class.php");
 		require_once(ROOT_DIR."classes/helper/pChart/pChart.class.php");   
    
  
 // Dataset definition    
 $DataSet = new pData;   
 foreach($dataPts as $d){
 	$DataSet->AddPoint($d['steps'],'steps',$d['date_entered']);
 }
 
 
  
 $gImWidth=730;
 $gImHeight=300;
 $gWidth=640;
 $gHeight=220;
 
 $Test = new pChart($gImWidth,$gImHeight);
 $Test->setDateFormat("M d, y");
 
 $DataSet->AddAllSeries();   
 $DataSet->SetAbsciseLabelSerie();   
 $DataSet->SetSerieName("Steps","steps");         
 $DataSet->SetYAxisName("Readings");
 $DataSet->SetYAxisUnit("");

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
 $Test->drawTitle(60,22,"Steps Per Day",50,50,50,585);   
 $Test->Render();
 exit();
	}
*/	
}
