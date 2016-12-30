<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class UserTrackBloodGlucoseModel {
	private $dbOb=null;
	private $data=array();
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$this->id=$id;		
	}
	
	public function getLastEntry(){
		$sql="SELECT blood_glucose,date_entered FROM u_tracker_blood_glucose " .
					"WHERE z_user_id='".$this->id."' AND method='fasting' ORDER BY date_entered desc";
		$fast = $this->dbOb->getRow($sql);
		$sql="SELECT blood_glucose,date_entered FROM u_tracker_blood_glucose " .
					"WHERE z_user_id='".$this->id."' AND method='random' ORDER BY date_entered desc";
		$rand = $this->dbOb->getRow($sql);
		$ret = array('fasting' => $fast,
								'random' => $rand);
		
		return $ret;
	}

	public function getEntry($id){
		$sql="SELECT * FROM u_tracker_blood_glucose WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);
	}
	
	public function getTotalDataPoints($method="") {
		$and = "";
		if ($method == "fasting") {
			$and = " AND method = 'fasting' ";
		}
		else if ($method == "random") {
			$and = " AND method = 'random' ";
		}
		$sql = "SELECT COUNT(*) FROM u_tracker_blood_glucose WHERE z_user_id = '".$this->dbOb->escape_string($this->id)."' AND is_active = 1" . $and;
		return $this->dbOb->getOne($sql);
	}


	public function getData($range=0,$dir="DESC", $max=0, $method=""){
		$limit="";
	
		switch($range){
			case 'week': //last week
				$limit=" AND bg.date_entered>=DATE_SUB(NOW(), INTERVAL 1 WEEK) ";
				break;
			case 'month': //last month
				$limit=" AND bg.date_entered>=DATE_SUB(NOW(), INTERVAL 1 MONTH) ";
				break;				
			default: $limit="";
		}

		// Optionally limit the number of data points returned
		$points = "";
		if ($max > 0) {
			$sql = "SELECT count(*) FROM u_tracker_blood_glucose WHERE z_user_id='" . $this->id . "' AND is_active=1" .
						$limit . " ORDER BY date_entered " . $dir;

			$rows = $this->dbOb->getOne($sql);
			if ($rows > $max) {
				if ($dir == "ASC") {
					$points = " LIMIT " . ($rows - $max) . ", " . $max;
				}
				else {
					$points = " LIMIT " . $max;
				}
			}
		}

		$m = "";
		if ($method == "fasting") {
			$m = " AND method = 'fasting'";
		}
		else if ($method == "non") {
			$m = " AND method = 'random'";
		}

		$sql="SELECT bg.*,DATE_FORMAT(bg.date_entered,'%b %d, %Y')as de,
				DATE_FORMAT(bg.time_entered,'%l:%i %p')as te,
				concat(z_users.first_name,' ',z_users.last_name) AS enter_name,
				(bg.entered_by=bg.z_user_id) as SE,
				wt.weight as tel
			  FROM u_tracker_blood_glucose AS bg
			  LEFT JOIN u_tracker_weight AS wt ON bg.z_user_id = wt.z_user_id AND bg.date_entered = wt.date_entered
			  JOIN z_users ON z_users.id=bg.entered_by
			  WHERE bg.z_user_id='".$this->id."'
			  AND bg.is_active=1
			  ".$limit.$m." 
			  ORDER BY bg.date_entered ".$dir . $points;

		$ret = $this->dbOb->query($sql);
		for ($i = 0; $i < count($ret); $i++) {
			if ($ret[$i]['method'] == "random") $ret[$i]['method'] = "non-fasting";
		}
				
		return $ret;
	}
	
	public function validateData($arr){
		
		require_once (LIB_ROOT."classes/common/StringUtil.class.php");
		$err=null;
//		print_r($_POST);
		try{
    		$this->data['method']=$this->vc->exists('method',$arr,"enum",array("values"=>array("fasting","random"),"casesensitive"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
		try{
    		$this->data['time_entered']=$this->vc->exists('time_entered',$arr,"time",null,false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['blood_glucose']=$this->vc->exists('blood_glucose',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>500),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
		/*try{
    		$this->data['medication']=$this->vc->exists('medication',$arr,"text",null,true,true);
    		$this->data['medication']=StringUtil::sanitize_data($this->data['medication'],StringUtil::STRIP_ALL_TAGS,null,false);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	} */   	
    	return ($err)?$err:false;
	}
	 
	public function deleteEntry($id){
		$sql="UPDATE u_tracker_blood_glucose set is_active=0 WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}
	
	//we only allow one entry per day so the add and the update methods are the same as we need to insure only one entry per day
	public function addEntry(){
		require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
		$cred=UserCredentials::load();
		$sql="SELECT id 
			  FROM u_tracker_blood_glucose 
			  WHERE date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."' 
				AND time_entered='".$this->dbOb->escape_string($this->data['time_entered'])."' 
				AND method='".$this->dbOb->escape_string($this->data['method'])."'
				AND z_user_id='".$this->dbOb->escape_string($this->id)."'";		
		$rec=$this->dbOb->getOne($sql);
		if($rec){
			$sql="UPDATE u_tracker_blood_glucose 
					SET blood_glucose='".$this->dbOb->escape_string($this->data['blood_glucose'])."', 
				 		medication='".$this->dbOb->escape_string($this->data['medication'])."',  
				 		entered_by='".$this->dbOb->escape_string($cred->getId())."',
				 		is_active=1
				 	WHERE id='".$this->dbOb->escape_string($rec)."'";
			$this->dbOb->update($sql);
		}else{
			$sql="INSERT INTO u_tracker_blood_glucose(z_user_id,blood_glucose,medication,method,date_entered,time_entered,entered_by) VALUES(
				'".$this->dbOb->escape_string($this->id)."',
				'".$this->dbOb->escape_string($this->data['blood_glucose'])."',
				'".$this->dbOb->escape_string($this->data['medication'])."',
				'".$this->dbOb->escape_string($this->data['method'])."',
				'".$this->dbOb->escape_string($this->data['date_entered'])."',
				'".$this->dbOb->escape_string($this->data['time_entered'])."',
				'".$this->dbOb->escape_string($cred->getId())."')";
			$this->dbOb->insert($sql);
			$im=new IncentivePointsModel();
			$im->addIncentivePointMA("TrackerBloodGlucose","AddEntry",$this->data['date_entered']);
		}
	}
}