<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class UserTrackCardioModel {
	private $dbOb=null;
	private $data=array();
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$this->id=$id;		
	}
	
	public function getLastEntry(){
		$sql="SELECT * FROM u_tracker_weight WHERE zuser_id='".$this->id."' ORDER BY date_entered desc";
		$this->dbOb->getRow($sql);
	}
	
	public function getData($range=0){
		$limit="";
		switch($range){
			case 1: //last week
				$limit=" AND date_entered>=DATE_SUB(NOW(), INTERVAL 1 WEEK) ";
				brak;
			case 2: //last month
				$limit=" AND date_entered>=DATE_SUB(NOW(), INTERVAL 1 MONTH) ";
				brak;				
			default: $limit="";
		}
		$sql="SELECT u_tracker_t.*,DATE_FORMAT('%m/%d/%Y',date_entered), conact(first_name,' ',last_name) AS enter_name 
			  FROM u_tracker_exercises 
			  JOIN z_users ON z_users_id=entered_by
			  JOIN p_workout_exercises ON p_workout_exercises_id=p_workout_exercises.id
			  WHERE zuser_id=".$this->id."' AND is_active=1 AND  p_workout_exercises.category='cardio'
			  ".$limit." 
			  ORDER BY date_entered DESC";
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
    		$this->data['weight']=$this->vc->exists('weight',$arr,"numeric",array("percision"=>"5,1","rangex_low"=>10, "rangex_high"=>10000),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	return ($err)?$err:false;    	
	}
	 
	public function deleteEntry($id){
		$sql="UPDATE u_tracket_weight set is_active=0 WHERE id='".$this->dbOb->escape_string($id)."' AND zuser_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}
	
	//we only allow one entry per day so the add and the update methods are the same as we need to insure only one entry per day
	public function addEntry(){
		require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
		$cred=UserCredentials::load();		
		
		$sql="SELECT id FROM u_tracket_weight WHERE is_active=0 AND date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."' AND zuser_id='".$this->dbOb->escape_string($this->id)."'";
		$rec=$this->dbOb->getOne($sql);
		if($rec){
			$sql="UPDATE u_tracket_weight 
				 	SET weight='".$this->dbOb->escape_string($this->data['weight'])."' 
				 	entered_by='".$this->dbOb->escape_string($this->dbOb->escape_string($cred->getId())).",
				 WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
			$this->dbOb->update($sql);
		}else{
			$sql="INSERT INTO u_tracket_weight(zuser_id,weight,date_entered) VALUES(
				'".$this->dbOb->escape_string($this->id)."',
				'".$this->dbOb->escape_string($this->data['weight'])."',
				'".$this->dbOb->escape_string($this->data['date_entered'])."',
				'".$this->dbOb->escape_string($cred->getId())."')";
			$this->dbOb->insert($sql);
			$im=new IncentivePointsModel();
			$im->addIncentivePointMA("TrackerCholesterol","AddEntry",$this->data['date_entered']);
		}
	}
}