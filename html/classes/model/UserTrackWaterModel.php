<?php
require_once (LIB_ROOT."classes/common/Database.class.php");

class UserTrackWaterModel {
	private $dbOb=null;	
	private $cred=null;
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->cred=UserCredentials::load();
				
		$sql="SELECT * FROM u_tracker_water WHERE z_user_id='".$this->dbOb->escape_string($id)."' AND date_entered=DATE_FORMAT(NOW(),'%Y-%m-%d')";		
		$rec=$this->dbOb->getRow($sql);		
		if(!$rec){
			$newRecId=$this->dbOb->insert("INSERT INTO u_tracker_water(z_user_id,date_entered,entered_by) VALUES ('".$this->dbOb->escape_string($id)."',NOW(),'".$this->dbOb->escape_string($this->cred->getId())."')");
			$this->glasses=0;
			$this->trackerId=$newRecId;
			
		}else{
			$this->glasses=$rec['glasses'];
			$this->trackerId=$rec['id'];
		}
	}
	
	public function getGlasses(){
		return $this->glasses;		
	}
	
	public function addGlass(){
		$sql="UPDATE u_tracker_water set glasses=glasses+1, entered_by='".$this->dbOb->escape_string($this->cred->getId())."' WHERE id='".$this->dbOb->escape_string($this->trackerId)."'";
		$this->dbOb->update($sql);
	}
	
	public function removeGlass(){
		if($this->glasses['glasses']>0){
			$sql="UPDATE u_tracker_water set glasses=glasses-1, entered_by='".$this->dbOb->escape_string($this->cred->getId())."' WHERE id='".$this->dbOb->escape_string($this->trackerId)."'";
			$this->dbOb->update($sql);
		}
	}	
}