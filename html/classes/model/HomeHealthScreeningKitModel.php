<?php
require_once (LIB_ROOT . "classes/common/Database.class.php");   			
require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
require_once (ROOT_DIR."classes/model/UserTrackCholesterolModel.php");
require_once (ROOT_DIR."classes/model/UserTrackBPModel.php");
require_once (ROOT_DIR."classes/model/UserTrackBloodGlucoseModel.php");

class HomeHealthScreeningKitModel {
	private $dbOb;
	private $id;
	private $orderStatus;
	private $data;
	
	const CANNOTORDER=0;
	const CANORDER=1;
	const INPROCESS=2;
	const ORDERED=3;
	
	public function __construct($id=0) {
		$this->dbOb = Database::create ();
		if(!$id){
			$cred = UserCredentials::load ();		
			$this->id = $cred->getId ();
		}else{
			$this->id=$id;
		}
		
		//Did this user ordered a hhsk?
		$sql = "SELECT * FROM u_home_health_screening_kit_order WHERE z_user_id='" . $this->dbOb->escape_string ( $this->id ) . "'";
		$rec = $this->dbOb->getRow ( $sql );
		if ($rec) { //we order a kit are we waiting for it or have we received it?
					//should we hold off displaying the check box until 5 days after the update?			
			if ($rec['is_received'] == 0) {
				if ($rec['date_updated'] == "0000-00-00 00:00:00") {
					$this->orderStatus = 2;		//Kit ordered waiting for arrival
				}
				else {
					$this->orderStatus = 5;		//Kit did not arrive - reordered
				}
			}
			else {
				$this->orderStatus = 3;			//Kit arrived
			}

			if($this->orderStatus==3){
				$sql="SELECT * FROM u_home_health_screening_kit_results WHERE z_user_id='" . $this->dbOb->escape_string ( $this->id ) . "'";
				if($this->dbOb->getRow ( $sql )) $this->orderStatus = 4;	//recieved sent in and results are ready
			}
			return $this->orderStatus ;
		}
		
		//get the users company
		$sql = "SELECT company_id FROM u_profile WHERE z_user_id='" . $this->dbOb->escape_string ( $this->id ) . "'";
		$cid = $this->dbOb->getOne ( $sql );
		
		//did the company pa for the hhsk?  
			//get the HHSK id	
		$sql = "SELECT id FROM p_modules WHERE name='Home Health Screening Kit'";
		$moduleId = $this->dbOb->getOne ( $sql );
		   //see if the module is associated with the company
		$sql = "SELECT * FROM p_company_modules WHERE p_company_id='" . $this->dbOb->escape_string ( $cid ) . "'
    	 AND p_module_id='" . $this->dbOb->escape_string ( $moduleId ) . "'";
		
		$rec = $this->dbOb->getRow ( $sql );
		$this->orderStatus = ($rec) ? 1 : 0;
		return;
	}
	
	/**
	 * Get the status of the order or orderability if not ordered
	 */
	public function getOrderStatus() {
		return $this->orderStatus;
	}
	
	public function getWaitStatus() {
		if($this->orderStatus==2){
			$sql="SELECT date_added>date_sub(CURRENT_DATE(), INTERVAL 6 DAY) FROM u_home_health_screening_kit_order WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
			return ($this->dbOb->getOne($sql))?false:true;
		}
		else if ($this->orderStatus == 5) {
			$sql = "SELECT date_updated>date_sub(CURRENT_DATE(), INTERVAL 6 DAY) FROM u_home_health_screening_kit_order WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
			return ($this->dbOb->getOne($sql))?false:true;
		}
		return true;
	}
	/**
	 * Validate user submitted data
	 * @param array $arr
	 * @return mixed false if no error, array of error obj if error occured
	 */
	public function validate($arr) {
		$err = null;
		require_once (LIB_ROOT."classes/common/Validator.class.php");
		$vc = new Validator();
		try {
			$this->data ['first_name'] = $vc->exists ( 'first_name', $arr, "text", array ("sanitize" => array (0, null, false ) ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['last_name'] = $vc->exists ( 'last_name', $arr, "text", array ("sanitize" => array (0, null, false ) ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		
		try {
			$this->data ['address1'] = $vc->exists ( 'address1', $arr, "text", array ("sanitize" => array (0, null, false ) ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['address2'] = $vc->exists ( 'address2', $arr, "text", array ("sanitize" => array (0, null, false ) ), true, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['city'] = $vc->exists ( 'city', $arr, "text", array ("sanitize" => array (0, null, false ) ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['country'] = $vc->exists ( 'country', $arr, "country-abbrv", array (), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['state'] = $vc->exists ( 'state', $arr, "state-abbrv", array ("country_code" => $this->data ['country'] ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		
		try {
			$this->data ['zipcode'] = $vc->exists ( 'zipcode', $arr, "zipcode", array ("country_code" => $this->data ['country'] ), false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['phone'] = $vc->exists ( 'phone', $arr, "phone", null, false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		try {
			$this->data ['email'] = $vc->exists ( 'email', $arr, "email", null, false, true );
		} catch ( ValidationException $e ) {
			$err [] = $e->createErrorObject ();
		}
		return ($err) ? $err : false;
	}
	
	/**
	 * Save the order
	 */
	public function sendOrder() {
		
		if ($this->orderStatus < 3) {
		
			//get company name
			$sql="SELECT company_name FROM u_profile JOIN p_company ON u_profile.company_id=p_company.id WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
			$companyName=$this->dbOb->getOne($sql);
		
			//get profile data
			$sql="SELECT dob,gender FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
			$record=$this->dbOb->getRow($sql);		
		
			$keys[]='`company_name`';
			$keys[]='`dob`';
			$keys[]='`gender`';
			$values[]="'".$this->dbOb->escape_string($companyName)."'";
			$values[]="'".$this->dbOb->escape_string($record['dob'])."'";
			$values[]="'".$this->dbOb->escape_string($record['gender'])."'";
		
			//add the user submitted data
			foreach($this->data as $key=>$value){
				$keys[]="`".$key."`";
				$values[]="'".$this->dbOb->escape_string($value)."'";
			}
		
			$k=implode(",",$keys);
			$v=implode(",",$values);
			//add to database
			$sql = "INSERT INTO u_home_health_screening_kit_order(z_user_id,".$k.") VALUES (
							'".$this->dbOb->escape_string($this->id)."',".$v.")";		
			$this->dbOb->insert ( $sql );
		}
		else {
			$sql = "UPDATE u_home_health_screening_kit_order SET is_received = 0, is_downloaded = 0 " .
							"WHERE z_user_id = " . $this->dbOb->escape_string($this->id);
			$this->dbOb->update($sql);
		}
	}


	public function getScreeningResults(){
		$sql="SELECT * FROM u_home_health_screening_kit_results WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
  		$results=$this->dbOb->getRow($sql);
		
		if(!$results){
			$results=array(
				"date_entered"=>"0",
				"height"=>"0",
				"weight"=>"0.00",
				"systolic"=>"0.00",
				"diastolic"=>"0.00",
				"heart_rate"=>"0",
				"total_cholesterol"=>"0",
				"ldl"=>"0",
				"triglycerides"=>"0",
				"cholesterol_ratio"=>"0.00",
				"c_reactive_protein"=>"0.00",
				"glocose_fasting"=>"0.00",
				"glucose_random"=>"0.00",
				"HgA1c"=>"0.00",
				"fvc"=>"0.00",
				"fev1"=>"0.00",
				"lung_age"=>"0",
				"psa"=>"0.00",
				"bone_density"=>"0.00"
			);
		}
		
		$upm=new UserProfileModel($this->id);  		  		  		
  		$wm=new UserTrackWeightModel($this->id);
  		$cm=new UserTrackCholesterolModel($this->id);
  		$bpm=new UserTrackBPModel($this->id);
  		$bgm=new UserTrackBloodGlucoseModel($this->id);
  		
  		
		
  		$pd=$upm->getData();
  		//get curent BMI
		$results['bmi']=$upm->getBMI();
		
		//get current cholesterol
		$c=$cm->getLastEntry();
		$results['toal_cholesterol']=$c['total'];
		$results['hdl']=$c['hdl'];
		$results['ldl']=$c['ldl'];
		$results['triglycerides']=$c['triglycerides'];
		$results['cholesterol_ratio']=($c['total'])?$c['hdl']/$c['total']:"n/a";
		
		//get current BP
		$bp=$bpm->getLastEntry();
		$results['systiolic']=$bp['systolic'];
		$results['diastiolic']=$bp['diastolic'];
		
		
		//get current blood glucose
		$bg=$bgm->getLastEntry();
		$results['glucose_random']=$bg['random']['blood_glucose'];
		$results['glucose_fasting'] = $bg['fasting']['blood_glucose'];

		//get current weight
		$weight=$wm->getLastEntry();
		$results['weight']=$weight['weight'];
		
		//get weight change
  		$gwc=$upm->getGoalWeightChange();
  		if($gwc==0) $gwc="at goal weight";
  		else if($gwc>0) $gwc=" Over by ".$gwc." lb";
  		else $gwc=" Under by ".abs($gwc)." lb";
  		$results['goal_weight_change']=$gwc;
  		  		
		//get goal weight
		$results['goal_weight']=$pd["goal_weight"];
		//get target calories
		//get exercise level
		$results['exercise_level']=$upm->getExerciseLevel();
		return $results;
	}
	
}