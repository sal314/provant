<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class UserTrackCholesterolModel {
	private $dbOb=null;
	private $data=array();
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$this->id=$id;		
	}
	
	public function getLastEntry(){
		$sql="SELECT total,hdl,ldl,triglycerides,date_entered FROM u_tracker_cholesterol WHERE z_user_id='".$this->id."' ORDER BY date_entered desc";
		return $this->dbOb->getRow($sql);
	}

	public function getTotalDataPoints() {
		$sql="SELECT COUNT(*) FROM u_tracker_cholesterol WHERE z_user_id = '".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getOne($sql);
	}

	public function getData($range=0,$dir="DESC",$progRpt=false,$max=0){
		$limit="";		
		switch($range){
			case 'week': //last week
				$limit=" AND date_entered>=DATE_SUB(NOW(), INTERVAL 1 WEEK) ";
				break;
			case 'month': //last month
				$limit=" AND date_entered>=DATE_SUB(NOW(), INTERVAL 1 MONTH) ";
				break;				
			default: $limit="";
		}

		if ($progRpt) {
			$fmt = "%m/%d";
		}
		else {
			$fmt = "%m/%d/%Y";
		}

		$points = "";
		if ($max > 0) {
			$sql = "SELECT count(*) FROM u_tracker_cholesterol WHERE z_user_id = " . $this->id . " AND is_active = 1 " . $limit;
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

		$sql="SELECT u_tracker_cholesterol.*,DATE_FORMAT(date_entered,'".$fmt."')as de, concat(z_users.first_name,' ',z_users.last_name) AS enter_name,  (entered_by=z_user_id) as SE
			  FROM u_tracker_cholesterol 
			  JOIN z_users ON z_users.id=u_tracker_cholesterol.z_user_id 
			  WHERE u_tracker_cholesterol.z_user_id='".$this->id."' AND u_tracker_cholesterol.is_active=1 
			  ".$limit."  
			  ORDER BY u_tracker_cholesterol.date_entered ".$dir . $points;
		return $this->dbOb->query($sql);
	}

	public function validateData($arr){
		$err=null;
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
		try{
    		$this->data['total']=$this->vc->exists('total',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>500),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['hdl']=$this->vc->exists('hdl',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>500),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['ldl']=$this->vc->exists('ldl',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>500),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
    	try{
    		$this->data['triglycerides']=$this->vc->exists('triglycerides',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>500),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
    	
    	return ($err)?$err:false; 	
	}
	 
	public function deleteEntry($id){
		$sql="UPDATE u_tracker_cholesterol set is_active=0 WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}
	
	public function getEntry($id){
		$sql="SELECT * FROM u_tracker_cholesterol WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);
	}
	public function getTodaysEntry(){
		$sql="SELECT * FROM u_tracker_cholesterol WHERE date_entered='".date("Y-m-d")."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);		
	}
	
	//we only allow one entry per day so the add and the update methods are the same as we need to insure only one entry per day
	public function addEntry(){
		require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
		$cred=UserCredentials::load();
		$sql="SELECT id FROM u_tracker_cholesterol WHERE date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$rec=$this->dbOb->getOne($sql);
		if($rec){
			
			$sql="UPDATE u_tracker_cholesterol 
					SET total='".$this->dbOb->escape_string($this->data['total'])."', 
				 		ldl='".$this->dbOb->escape_string($this->data['ldl'])."',  
				 		hdl='".$this->dbOb->escape_string($this->data['hdl'])."',
				 		triglycerides='".$this->dbOb->escape_string($this->data['triglycerides'])."',
				 		entered_by='".$this->dbOb->escape_string($cred->getId())."',
				 		is_active=1
				 	WHERE id='".$this->dbOb->escape_string($rec)."'";
			$this->dbOb->update($sql);
		}else{
			$sql="INSERT INTO u_tracker_cholesterol(z_user_id,total,ldl,hdl,triglycerides,date_entered,entered_by) VALUES(
				'".$this->dbOb->escape_string($this->id)."',
				'".$this->dbOb->escape_string($this->data['total'])."',
				'".$this->dbOb->escape_string($this->data['ldl'])."',
				'".$this->dbOb->escape_string($this->data['hdl'])."',
				'".$this->dbOb->escape_string($this->data['triglycerides'])."',
				'".$this->dbOb->escape_string($this->data['date_entered'])."',
				'".$this->dbOb->escape_string($cred->getId())."')";
			$this->dbOb->insert($sql);
			$im=new IncentivePointsModel();
			$im->addIncentivePointMA("TrackerCholesterol","AddEntry",$this->data['date_entered']);
		}
	}
}