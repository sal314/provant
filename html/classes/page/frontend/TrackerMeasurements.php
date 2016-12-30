<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/UserTrackMeasurementsModel.php");
require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');

class TrackerMeasurements  extends PageBase{

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
		
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Measurements/index.tpt");
		$log=isset($_GET['log'])?$_GET['log']:"";
		$template->addVar("loglevel",$log);

		if (count($param) > 0) {
			$template->addVar("error", $param);
		}

		$Measurements=new UserTrackMeasurementsModel($this->myId);
		$mm = $Measurements->getData($log);
		if ($mm) {
			foreach($mm as &$m) {
				if ($m['waist'] > 0) {
					if ($m['hips'] > 0) {
						$m['wh_ratio'] = sprintf("%.2f", $m['waist'] / $m['hips']);
					}
					else {
						$m['wh_ratio'] = "";
					}
				}
				else {
					$m['wh_ratio'] = "";
				}
			}
		}
		$template->addVar("log", $mm);

		$rec=null;
		if(isset($_GET['edit'])){
			$rec=$Measurements->getEntry($_GET['edit']);
			$t = strtotime($rec['date_entered']);
			$rec['disp_date']=date('M d, Y', $t);
		}else{
			$rec=$Measurements->getLastEntry();
			$rec['date_entered']=date("Y-m-d");
			$rec['disp_date']=date("M d, Y");
		}

		if($rec){
			if (array_key_exists('hips', $rec)) {
				if ($rec['waist'] > 0) {
					if ($rec['hips'] > 0) {
						$rec['wh_ratio'] = sprintf("%.2f", $rec['waist'] / $rec['hips']);
					}
					else {
						$rec['wh_ratio'] = "";
					}
				}
				else {
					$rec['wh_ratio'] = "";
				}
			}
			else {
				$rec['hips'] = "";
				$rec['wh_ratio'] = "";
			}

			foreach($rec as $key=>$value){
				if ($value == 0.0) {
					$_POST[$key] = "";
				}
				else {
			 		$_POST[$key]=$value;
			 	}
			}
		}

		$dte = date('Y-m-d');
		$disp = date('M j, Y');
		$_POST['date_entered'] = $dte;
		$_POST['disp_date'] = $disp;

		$template->addVar("post",$_POST);
		$template->addVar("data_points", $Measurements->getTotalDataPoints());

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
	}

	/**
	 * Add entry to tracker
	 * @param unknown_type $param
	 */
	
	public function AddEntry($param){			
		$log=isset($_GET['log'])?$_GET['log']:"";

		$Measurements=new UserTrackMeasurementsModel($this->myId);
		$err=$Measurements->validateData($_POST);
		
		if($err){
			return $this->Index($err);
		}

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Measurements/index.tpt");
		$template->addVar("loglevel",$log);
		$Measurements->addEntry();
		header("Location: /TrackerMeasurements/Index?log=".$log);
		exit();
	}

	/**
	 * Delete tracker entry
	 * @param unknown_type $param
	 */
	
	public function DeleteEntry($param){
		$log=isset($_GET['log'])?$_GET['log']:"";	
		$recId=isset($param[0])?$param[0]:"";
		$Measurements=new UserTrackMeasurementsModel($this->myId);
		$Measurements->deleteEntry($recId);		
		header("Location: /TrackerMeasurements/Index/?log=".$log);
  		exit();
	}

	public function showWaist($params) {
		$template = TemplateParser::create(TEMPLATE_DIR."frontend/Tracker/Measurements/waist.tpt");
		$template->parse();
		exit();
	}
}
