<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");

class HCCallLogModel {
	private $dbOb=null;
	private $data=array();
	
	public function __construct(){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$cred=UserCredentials::load();	
		$this->id=$cred->getId();	
	}
	
	/**
	 * Validate posted data
	 * @param array $arr portion of POST data that contains the info to validate    
	 * @param boolean $editMode Are we inserting or editing an existing record?
	 * @return mixed false if no error occurred, an array of error objects if any errors occur.
	 */
	public function validateInfo($arr,$editMode=false){
		$err=null;
		if($editMode){
			try{
    			$this->data['call_log_id']=$this->vc->exists('call_log_id',$arr,"integer",array("rangex_low"=>0),false,true);
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
		}
		
		try{
    		$this->data['patient']=$this->vc->exists('patient',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		
		try{
    		$this->data['contact_date']=$this->vc->exists('contact_date',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['contact_time']=$this->vc->exists('contact_time',$arr,"time",null,false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
    	try{
    		$this->data['method']=$this->vc->exists('method',$arr,"enum",array("values"=>$this->getOptions("method"),"case_sensitive"=>false),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		    	
    	try{
    		$this->data['primary']=$this->vc->exists('primary',$arr,"enum",array("values"=>$this->getOptions("intervention"),"case_sensitive"=>false),true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['secondary']=$this->vc->exists('secondary',$arr,"enum",array("values"=>$this->getOptions("intervention"),"case_sensitive"=>false),true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['risk']=$this->vc->exists('risk',$arr,"enum",array("values"=>$this->getOptions("risk"),"case_sensitive"=>false),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['migration']=$this->vc->exists('migration',$arr,"enum",array("values"=>$this->getOptions("migration"),"case_sensitive"=>false),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['participation']=$this->vc->exists('participation',$arr,"enum",array("values"=>$this->getOptions("participation"),"case_sensitive"=>false),true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['comment']=$this->vc->exists('comment',$arr,"text",null,true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
    	return ($err)?$err:false;    	 
	}


	public function searchUsers($cid) {
		if ($cid == -1) {
			$sql = "SELECT concat(z.first_name, ' ', z.last_name) as display, z.id as value " .
						"FROM z_users as z " .
						"JOIN u_profile as u on z.id = u.z_user_id " .
						"JOIN z_user_role as r on z.id = r.user " .
						"WHERE z.is_active = 1 " .
						"AND r.role = 1";
		}
		else {
			$sql = "SELECT concat(z.first_name, ' ', z.last_name) as display, z.id as value " .
						"FROM z_users as z " .
						"JOIN u_profile as u on z.id = u.z_user_id " .
						"JOIN z_user_role as r on z.id = r.user " .
						"WHERE z.is_active = 1 " .
						"AND r.role = 1 " .
						"AND u.company_id = " . $this->dbOb->escape_string($cid);
		}
		$data = $this->dbOb->query($sql);

		return $data;
	}

	
	/**
	 * Return the value of a particular field
	 * @param string  $str field name
	 */
	public function get($str){		
		if(!isset($this->data[$str])){				
			throw new Exception($str." is not a member of the model");
		}
		return $this->data[$str];
	}
	
	/**
	 * insert date into a new record
	 */
	public function insert(){
					
		$sql="INSERT INTO h_call_log(`health_coach`,`patient`,`contact_date`,`contact_time`,`method`,`primary`,`secondary`,`risk`,`migration`,`participation`,`comment`)
			VALUES('".$this->dbOb->escape_string($this->id)."',
			'".$this->dbOb->escape_string($this->data['patient'])."',
			'".$this->dbOb->escape_string($this->data['contact_date'])."',
			'".$this->dbOb->escape_string($this->data['contact_time'])."',
			'".$this->dbOb->escape_string($this->data['method'])."',
			'".$this->dbOb->escape_string($this->data['primary'])."',
			'".$this->dbOb->escape_string($this->data['secondary'])."',
			'".$this->dbOb->escape_string($this->data['risk'])."',
			'".$this->dbOb->escape_string($this->data['migration'])."',
			'".$this->dbOb->escape_string($this->data['participation'])."',
			'".$this->dbOb->escape_string($this->data['comment'])."')";
		$this->dbOb->insert($sql);
	}
	
	/**
	 * Update an existing record.
	 */
	public function update(){
		$sql="UPDATE h_call_log SET
		 `contact_date`='".$this->dbOb->escape_string($this->data['contact_date'])."',
		 `contact_time`='".$this->dbOb->escape_string($this->data['contact_time'])."',
		 `method`='".$this->dbOb->escape_string($this->data['method'])."',
		 `primary`='".$this->dbOb->escape_string($this->data['primary'])."',
		 `secondary`='".$this->dbOb->escape_string($this->data['secondary'])."',
		 `risk`='".$this->dbOb->escape_string($this->data['risk'])."',
		 `migration`='".$this->dbOb->escape_string($this->data['migration'])."',
		 `participation`='".$this->dbOb->escape_string($this->data['participation'])."',
		 `comment`='".$this->dbOb->escape_string($this->data['comment'])."'
		 WHERE `id`='".$this->dbOb->escape_string($this->data['call_log_id'])."'";
		 
		$this->dbOb->update($sql);
	}
	
	/**
	 * Retunr the List of available selectoptions for a category
	 * @param string  $cat
	 * @returns array
	 */
	public function getOptions($cat){
		$arr=array("method"=>array('yes','no','voice mail'),
  			"intervention"=>array('','DIABETES','PHYSICAL ACTIVITY','SMOKE CESSATION','STRESS MANAGEMENT','WEIGHT LOSS','OTHER','LIPIDS','HYPERTENSION'),
  			"risk"=>array('high','moderate','low'),
  			"migration"=>array('NO MIGRATION','LOW TO MOD','LOW TO HIGH','MOD TO LOW','MOD TO HIGH','HIGHT TO MOD','HIGH TO LOW'),
  			"participation"=>array('','NON-COMPLIANT','NO LONGER EMPLOYED','OPTED OUT','OTHER')
		);
		
		return $arr[$cat];
	} 
}
