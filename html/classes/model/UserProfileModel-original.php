<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
require_once (LIB_ROOT."classes/common/StringUtil.class.php");

class UserProfileModel {
    private $data=array();
    private $dbOb=null;
    private $cred=null;
    private $sUtil=null;
    private $vc=null;
    private $id=0;
    
    public function __construct($id){    	
    	    	    
    	$this->dbOb=Database::create();
    	$this->cred=UserCredentials::load();    	    
    	if(!$id){
    		$this->id=$this->cred->getId();
    	} 
    	else $this->id=$id;

    	if($this->id){
    		$this->retrieve();
    	}
    	
    	$this->vc= new Validator();    	
    	$this->sUtil=new StringUtil();    	
    }

    /**
     * Get the profile info for a user
     * @param unknown_type $id
     */
    private function retrieve(){    	
    	//get profile data
    	$this->data=$this->dbOb->getRow("SELECT * FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'");  		
  		
    	if(!$this->data){//no record found so create the initial record!
    		$sql="INSERT INTO u_profile(z_user_id) VALUE('".$this->dbOb->escape_string($this->id)."')";
    		$this->dbOb->insert($sql);
    		$this->data=$this->dbOb->getRow("SELECT * FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'"); 
    	}
    	
    	$sql="SELECT first_name,last_name,email FROM z_users WHERE id='".$this->dbOb->escape_string($this->id)."'";
    	$rec=$this->dbOb->getRow($sql);
    	if(!$rec)throw new Exception("Invalid user!");
    	foreach($rec as $key=>$value){
    		$this->data[$key]=$value;
    	}

    }
    
    /**
     * Get field
     * @param $idx
     * @return mixed
     */
	public function get($idx){
    	return $this->data[$idx];
    }
    /**
     * Get all fields
     * @return array
     */
    public function getData(){
    	return $this->data;
    }

    /**
     * Validate user profile data
     * @param $arr
     * @return false if no error else array of error objs
     */
    public function validateUserInfo($arr){
    	$this->data=null;
        $err=null;
        $name=$this->dbOb->getRow("SELECT * FROM z_users where id='".$this->dbOb->escape_string($this->id)."'");
        try{
                $this->data['first_name']=$this->vc->exists('first_name',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['last_name']=$this->vc->exists('last_name',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
     	try{
                $this->data['email']=$this->vc->exists('email',$arr,"email",array(),false,true);
                $confirm_email=$this->vc->exists('email_confirm',$arr,"email",array(),false,true);
                if($this->data['email']!=$confirm_email){
                	throw new ValidationException("Confirmation email must match the email.",4,"confirmation_email");
                }
                $sql="SELECT id FROM z_users WHERE login='".$this->dbOb->escape_string($confirm_email)."'";
                $zId=$this->dbOb->getOne($sql);
                if($zId && $zId!=$this->id){//check to see if the email is used by another user
                	throw new ValidationException("Email is already in use in the system.",4,"email");
                }
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        
        /*
        try{
                $this->data['address1']=$this->vc->exists('address1',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);                
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['address2']=$this->vc->exists('address2',$arr,"text",array("sanitize"=>array(0,null,false)),true,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['city']=$this->vc->exists('city',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['country']=$this->vc->exists('country',$arr,"country-abbrv",array(),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['state']=$this->vc->exists('state',$arr,"state-abbrv",array("country_code"=>$this->data['country']),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }

        try{
                $this->data['zipcode']=$this->vc->exists('zipcode',$arr,"zipcode",array("country_code"=>$this->data['country']),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
		*/
        try{
        		$this->data['location_id']=$this->vc->exists('location_id',$arr,"integer",array("rangex_low"=>0,"rangex_high"=>9999999999),false,true);
        }catch(ValidationException $e) {
        	$err[]=$e->createErrorObject();
        }
        
        try{

                $this->data['dob']=$this->vc->exists('dob',$arr,"date",array("date_stamp"=>1),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['height_ft']=$this->vc->exists('height_ft',$arr,"integer",array("rangex_low"=>0,"rangex_high"=>9),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['height_in']=$this->vc->exists('height_in',$arr,"integer",array("range_low"=>0,"range_high"=>11),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['initial_weight']=$this->vc->exists('initial_weight',$arr,"numeric",array("precision"=>"5,2", "rangex_low"=>0,"rangex_high"=>1000),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['goal_weight']=$this->vc->exists('goal_weight',$arr,"numeric",array("precision"=>"5,2", "rangex_low"=>0,"rangex_high"=>1000),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        
        try{
                $this->data['gender']=$this->vc->exists('gender',$arr,"enum",array("values"=>array("M","F"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }        
        try{
            	$this->data['marital_status']=$this->vc->exists('marital_status',$arr,"enum",array("values"=>array("S","M","P","D","O"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
        try{
                $this->data['education']=$this->vc->exists('education',$arr,"enum",array("values"=>array("I","H","S","C","G"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
    	try{
                $this->data['goal']=$this->vc->exists('goal',$arr,"enum",array("values"=>array("maintain","gain_1pw","gain_2pw","lose_1pw","lose_2pw","other"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
    	try{
                $this->data['pregnancy']=$this->vc->exists('pregnancy',$arr,"enum",array("values"=>array("no","trimester_1","trimester_2","trimester_3"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
    	try{
                $this->data['lactating']=$this->vc->exists('pregnancy',$arr,"enum",array("values"=>array("no","0-6","6-12","12+"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
        
        
    	try{
                $this->data['race_id']=$this->vc->exists('race_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['language_id']=$this->vc->exists('language_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        /* removed 6-21 per 6-15 discussion
    	try{
                $this->data['team_id']=$this->vc->exists('team_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        */
    	try{
                $this->data['activity_level_id']=$this->vc->exists('activity_level_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['fitness_goal_id']=$this->vc->exists('fitness_goal_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }

        $this->data['allow_provant_access']=(!isset($arr["allow_provant_access"]) || $arr["allow_provant_access"]!="1")?"0":"1";
        
        try{
            	$this->data['vision']=$this->vc->exists('vision',$arr,"enum",array("values"=>array("Y","N"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
        try{
            	$this->data['hearing']=$this->vc->exists('hearing',$arr,"enum",array("values"=>array("Y","N"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
        }
        
        return ($err)?$err:false;
    }
    
    /**
     * Update  the user profile data 
     */
    public function updateUserEntry(){
    	  $sql="UPDATE z_users 
    	  		SET first_name='".$this->dbOb->escape_string($this->data['first_name'])."',
    	  		    last_name='".$this->dbOb->escape_string($this->data['last_name'])."',
    	  		    email='".$this->dbOb->escape_string($this->data['email'])."',
    	  		    login='".$this->dbOb->escape_string($this->data['email'])."'
    	  		WHERE id='".$this->dbOb->escape_string($this->id)."'";
    	   $this->dbOb->update($sql);
    	   unset($this->data['email']);
    	   unset($this->data['first_name']);
    	   unset($this->data['last_name']);
    	   $sql="UPDATE u_profile SET ";
    	   $sqlEnt=array();
    	   foreach($this->data as $key=>$value){
    	   	$sqlEnt[]="`".$key."`='".$this->dbOb->escape_string($value)."'";
    	   }
    	   $sql.=implode(",",$sqlEnt)." WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
    	   $this->dbOb->update($sql);
    } 
    
    /**
     * Validate the registration info
     * @param array $arr - data
     * @param array $companyInfo -the record for the company this user will belong to
     * @return false if no error else array of error objs
     */
	public function validateUserRegistration($arr,$companyInfo){
    	$this->data=null;
        $err=null;
        $requiredRegistrationCode=$companyInfo["registration_code"];
        $this->data["company_id"]=$companyInfo["id"];
        $this->data["company_info"]=$companyInfo;
        
		if(!isset($arr["registration_code"]) || $requiredRegistrationCode!=$arr["registration_code"]){
  			//we have an validation issues!
  			$ee=new ValidationException("Invalid or missing registration code ",4,"registration_code");
  			$err[]=$ee->createErrorObject();
  		}
	    if(!isset($arr["eula"]) || $arr["eula"]!="accept"){
  			//we have an validation issues!
  			$ee=new ValidationException("You must accept the license agreement.",4,"eula");
  			$err[]=$ee->createErrorObject();
  		}	
        
		try{
                $this->data['email']=$this->vc->exists('email',$arr,"email",array(),false,true);
                $confirm_email=$this->vc->exists('email_confirm',$arr,"email",array(),false,true);
                if($this->data['email']!=$confirm_email){
                	throw new ValidationException("Confirmation email must match the email.",4,"confirmation_email");
                }
                $zId=null;
                $sql="SELECT id FROM z_users WHERE login='".$this->dbOb->escape_string($confirm_email)."'";
                $zId=$this->dbOb->getOne($sql);
                if($zId){//check to see if the email is used by another user
                	throw new ValidationException("Email is already in use in the system.",4,"email");
                }
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        
		try{
                $this->data['password']=$this->vc->exists('password',$arr,"text",array("min_length"=>6),false,true);
                $confirm_password=$this->vc->exists('password_confirm',$arr,"text",array("min_length"=>6),false,true);
                if($this->data['password']!=$confirm_password){
                	throw new ValidationException("Confirmation password must match the password.",4,"confirmation_password");
                }                
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        
        try{
                $this->data['first_name']=$this->vc->exists('first_name',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['last_name']=$this->vc->exists('last_name',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
     	
        /*
        try{
                $this->data['address1']=$this->vc->exists('address1',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['address2']=$this->vc->exists('address2',$arr,"text",array("sanitize"=>array(0,null,false)),true,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['city']=$this->vc->exists('city',$arr,"text",array("sanitize"=>array(0,null,false)),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['country']=$this->vc->exists('country',$arr,"country-abbrv",array(),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['state']=$this->vc->exists('state',$arr,"state-abbrv",array("country_code"=>$this->data['country']),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }

        try{
                $this->data['zipcode']=$this->vc->exists('zipcode',$arr,"zipcode",array("country_code"=>$this->data['country']),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
	*/
        try{
        		$this->data['location_id']=$this->vc->exists('location_id',$arr,"integer",array("rangex_low"=>0,"rangex_high"=>9999999999),false,true);
        }catch(ValidationException $e) {
        	$err[]=$e->createErrorObject();
        }

        try{
        				if (strlen($arr['dob_month']) == 1) $arr['dob_month'] = "0" . $arr['dob_month'];
        				if (strlen($arr['dob_day']) == 1) $arr['dob_day'] = "0" . $arr['dob_day'];
        				$arr['dob'] = $arr['dob_year'] . "-" . $arr['dob_month'] . "-" . $arr['dob_day'];

                $this->data['dob']=$this->vc->exists('dob',$arr,"date",array("datestamp"=>1),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
		try{
                $this->data['gender']=$this->vc->exists('gender',$arr,"enum",array("values"=>array("M","F"),"case_sensitive"=>false),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['height_ft']=$this->vc->exists('height_ft',$arr,"integer",array("rangex_low"=>0,"rangex_high"=>9),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['height_in']=$this->vc->exists('height_in',$arr,"integer",array("range_low"=>0,"range_high"=>11),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
				try{
                $this->data['race_id']=$this->vc->exists('race_id',$arr,"integer",array("rangex_low"=>0),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        
        if (isset($arr['initial_weight'])) {
	        try{
                $this->data['initial_weight']=$this->vc->exists('initial_weight',$arr,"numeric",array("precision"=>"5,2", "rangex_low"=>0,"rangex_high"=>1000),false,true);
  	      }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
    	    }
    	  }
    	  else {
    	  	$this->data['initial_weight'] = 0;
    	  }

				if (isset($arr['goal_weight'])) {
	        try{
                $this->data['goal_weight']=$this->vc->exists('goal_weight',$arr,"numeric",array("precision"=>"5,2", "rangex_low"=>0,"rangex_high"=>1000),false,true);
  	      }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
    	    }
        }
        else {
        	$this->data['goal_weight'] = 0;
        }
/*
		$this->data['measurement']=array();

			if (isset($arr['chest'])) {
        try{
                $this->data['measurement']['chest']=$this->vc->exists('chest',$arr['measurement'],"numeric",array("precision"=>"3,1", "range_low"=>0,"rangex_high"=>100),true,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
      }
      else {
      	$this->data['measurement']['chest'] = 0;
      }

			if (isset($arr['waist'])) {
        try{
                $this->data['measurement']['waist']=$this->vc->exists('waist',$arr['measurement'],"numeric",array("precision"=>"3,1", "range_low"=>0,"rangex_high"=>100),true,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
      }
      else {
      	$this->data['measurement']['waist'] = 0;
      }

			if (isset($arr['hips'])) {
        try{
                $this->data['measurement']['hips']=$this->vc->exists('hips',$arr['measurement'],"numeric",array("precision"=>"3,1", "range_low"=>0,"rangex_high"=>100),true,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
      }
      else {
      	$this->data['measurement']['hips'] = 0;
      }

				if (isset($data['gender'])) {
	        if($this->data['gender']=='M'){
  	      	$this->data['measurement']['forearm']=0;
    	    	$this->data['measurement']['wrist']=0;        	
      	  }else{
						if (isset($arr['wrist'])) {
	        		try{
  	       	      $this->data['measurement']['wrist']=$this->vc->exists('wrist',$arr['measurement'],"numeric",array("precision"=>"3,1", "range_low"=>0,"rangex_high"=>100),true,true);
	  	      	}catch(ValidationException $e){
  	              $err[]=$e->createErrorObject();
    		    	}
    		    }
    		    else {
    		    	$this->data['measurement']['wrist'] = 0;
    		    }
    		    if (isset($arr['forearm'])) {
							try{
  	              $this->data['measurement']['forearm']=$this->vc->exists('forearm',$arr['measurement'],"numeric",array("precision"=>"3,1", "range_low"=>0,"rangex_high"=>100),true,true);
	    	    	}catch(ValidationException $e){
      	          $err[]=$e->createErrorObject();
        			}
        		}
        		else {
        			$this->data['measurement']['forearm'] = 0;
        		}
        	}
        }
*/
		   try{
                $this->data['goal']=$this->vc->exists('goal',$arr,"enum",array("values"=>array("maintain","gain_1pw","gain_2pw","lose_1pw","lose_2pw","other"),"case_sensitive"=>false),false,true);
    	  }catch(ValidationException $e){
                $err[]=$e->createErrorObjectMissingRadio();
      	}

		$this->data['allow_provant_access']=(!isset($arr["allow_provant_access"]) || $arr["allow_provant_access"]!="1")?"0":"1";
      
        return ($err)?$err:false;
    }



    public function validateInterval($arr){
    	$this->data=null;
        $err=null;
        try{
                $this->data['update_bp']=$this->vc->exists('update_bp',$arr,"integer",array("rage_low"=>0,"range_high"=>365),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['update_blood_glucose']=$this->vc->exists('update_blood_glucose',$arr,"integer",array("rage_low"=>0,"range_high"=>365),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        try{
                $this->data['update_cholesterol']=$this->vc->exists('update_cholesterol',$arr,"integer",array("rage_low"=>0,"range_high"=>365),false,true);
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        return ($err)?$err:false;
    }

	public function updateInterval(){
     $sql="UPDATE u_profile SET 
     		`update_bp`='".$this->dbOb->escape_string($this->data['update_bp'])."',
     		`update_blood_glucose`='".$this->dbOb->escape_string($this->data['update_blood_glucose'])."',
     		`update_cholesterol`='".$this->dbOb->escape_string($this->data['update_cholesterol'])."'  
     	   WHERE z_user_id='".$this->dbOb->escape_string($this->cred->getId())."'";
     return $this->dbOb->update($sql);
    }

    /**
     * Create a new user nad log them into the system
     */
    public function registerUser(){
    	//add user to master user table!
    	$sql="INSERT INTO z_users(first_name,last_name,email,login,password) VALUES 
    	  		('".$this->dbOb->escape_string($this->data['first_name'])."',
    	  		'".$this->dbOb->escape_string($this->data['last_name'])."',
    	  		'".$this->dbOb->escape_string($this->data['email'])."',
    	  		'".$this->dbOb->escape_string($this->data['email'])."',
    	  		PASSWORD('".$this->dbOb->escape_string($this->data['password'])."'))";    	  		
    	   
    	$this->id=$this->dbOb->insert($sql);
    	$this->data['z_user_id']=$this->id;
    	   
    	
    	unset($this->data['email']);
    	unset($this->data['first_name']);
    	unset($this->data['last_name']);
    	unset($this->data['password']);
    	
    	$co=$this->data['company_info'];
    	unset($this->data['company_info']);
    	   
    	$sqlEntPre=array();
    	$sqlEntPost=array();
/*
			if (($this->data['measurement']['chest'] > 0) ||
			    ($this->data['measurement']['waist'] > 0) ||
			    ($this->data['measurement']['hips'] > 0) ||
			    ($this->data['measurement']['wrist'] > 0) ||
			    ($this->data['measurement']['forearm'] > 0)) {
	    	//add the measurements to the measurement tracker
	    	$sql="INSERT INTO u_tracker_measurements(z_user_id,date_entered,entered_by,bust,waist,hips,wrist,forearm) VALUES(
  	  		'".$this->dbOb->escape_string($this->id)."',
    			NOW(),
    			'".$this->dbOb->escape_string($this->id)."',
	    		'".$this->dbOb->escape_string($this->data['measurement']['chest'])."',
  	  		'".$this->dbOb->escape_string($this->data['measurement']['waist'])."',
    			'".$this->dbOb->escape_string($this->data['measurement']['hips'])."',
    			'".$this->dbOb->escape_string($this->data['measurement']['wrist'])."',
    			'".$this->dbOb->escape_string($this->data['measurement']['forearm'])."')";

	    	$this->dbOb->insert($sql);
			}
    	$this->data['measurement']=null;
    	unset($this->data['measurement']);
*/
			if ($this->data['initial_weight'] > 0) {
	    	//add the weight to the weight tracker
	    	$sql="INSERT INTO u_tracker_weight(z_user_id,date_entered,entered_by,weight) VALUES(
	    		'".$this->dbOb->escape_string($this->id)."',
	    		NOW(),
	    		'".$this->dbOb->escape_string($this->id)."',
					'".$this->dbOb->escape_string($this->data['initial_weight'])."')";

	    	$this->dbOb->insert($sql);
			}
    	    	    	
    	//create a profile for the user
    	$sql="INSERT INTO u_profile(";
    	foreach($this->data as $key=>$value){
    		$sqlEntPre[]="`".$key."`";
    		$sqlEntPost[]="'".$this->dbOb->escape_string($value)."'";
    	}
//    	$sqlEntPre[]="`status`";
//    	$sqlEntPost[]="'active'";

    	$sql.=implode(",",$sqlEntPre).") VALUES (".implode(",",$sqlEntPost).")";
    	
    	$this->dbOb->update($sql);
    	
    	//add user role to user's entitlements
    	$sql="INSERT INTO z_user_role(user,role) VALUES('".$this->dbOb->escape_string($this->id)."',1)";
    	$this->dbOb->insert($sql);
    	
    	
    	/** This assigns the health coaches round robin style 
    	 * There may come a point where you may assign users based on company
    	 * geographic location, or some other criteria.
    	 * This is the spot to change the code.
    	 * */
    	$sql="SELECT count(*) as count,health_coach_id  FROM p_user_health_coach GROUP BY health_coach_id ORDER BY count ASC";
    	$coaches=$this->dbOb->query($sql);
    	
    	if(!$coaches){
    		$sql="SELECT user FROM z_user_role WHERE role=2";    		
    		$coach=$this->dbOb->getOne($sql);
    	}else{    		
    		$coach=$coaches[0]['health_coach_id'];
    	}
    	
    	
    	
    	$sql="INSERT INTO p_user_health_coach(user_id,health_coach_id) VALUES (
    	'".$this->dbOb->escape_string($this->id)."',
    	'".$this->dbOb->escape_string($coach)."')";
    	$this->dbOb->insert($sql);
    	
    	//set the user as logged in    	
    	$this->cred->setId($this->id);
    	$this->cred->setLoginStatus(true);
    	$this->loadPermissions();
    	$this->cred->setReturnToAfterLogin("");
		$this->cred->save();
    } 
    
    /**
     * Get privilges for the user
     */
    private  function loadPermissions(){        	
                $query="SELECT * FROM z_user_permission as u JOIN z_action as a on u.action=a.id WHERE u.user='".$this->cred->getId()."'";                
                $rec=$this->dbOb->query($query);               
                if($rec){
                   foreach ($rec as $roll){
						$this->cred->addPermission($roll);
                   }
                }
                
                try{
                	$query="SELECT * FROM z_user_role AS zur 
                			JOIN z_role_action_permission AS zrap ON zur.role=zrap.role 
                			JOIN z_action AS za on zrap.action=za.id 
                			WHERE zur.user='".$this->cred->getId()."'";                
                	$rec=$this->dbOb->query($query);  
					if($rec){
                        foreach ($rec as $roll){
							$this->cred->addPermission($roll);
                        }
                	}                	             
                }catch(Exception $e){}                
                $this->cred->save();                
        } 
        
     /**
      * get the data from the trackers
      */
     public function getUpdateTrackers(){
       	$keys=array("update_bp","update_cholesterol","update_blood_glucose");
       	$ret=array();
       	foreach($keys as $key){
        	$ret[$key]=$this->data[$key];
       	}
        return $ret;
    }
    
    /**
     * Get the user's workout level
     */
    public function getExerciseLevel(){
    	$sql="SELECT * FROM p_user_option_activity_level WHERE id='".$this->dbOb->escape_string($this->data["activity_level_id"])."'";
    	$rec=$this->dbOb->getRow($sql);
    	return $rec?$rec["level"]:"Low";
    	
    }
    
     /**
     * Get the user's weight change from initila weight 
     */    
    public function getWeightChange(){
    	$sql="SELECT * FROM u_tracker_weight WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' ORDER BY date_entered DESC";
    	$rec=$this->dbOb->getRow($sql);
    	if(!$rec) return 0;
    	return $this->data['initial_weight']-$rec['weight'];
    }


		public function getGoalWeightChange(){
    	$sql="SELECT * FROM u_tracker_weight WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' ORDER BY date_entered DESC";
    	$rec=$this->dbOb->getRow($sql);
    	if(!$rec) return 0;
    	return $rec['weight']-$this->data['goal_weight'];
    }


    /**
     * Get the user's BMI
     */
    public function getBMI(){
    	$sql="SELECT * FROM u_tracker_weight WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' ORDER BY date_entered DESC";
    	$rec=$this->dbOb->getRow($sql);
    	if(!$rec) return 0;
    	$height2=pow(($this->data['height_ft']*12)+$this->data['height_in'],2);
    	$weight=$rec['weight']*703;
		if($height2);    	  			
    	return round($weight/$height2,2);
    }


		public function getGoalWeight() {
			return $this->data['goal_weight'];
		}


		public function getIncentiveTotal() {
			return $this->data['incentive_points_total'];
		}


	/**
	* Get the company name for this user
	*/
		public function getCompanyName() {
			$sql = "SELECT company_name FROM p_company AS c " .
				"JOIN u_profile AS u ON u.company_id = c.id " .
				"WHERE u.z_user_id = " . $this->cred->getId() . " " .
				"AND u.is_active = 1 " .
				"AND c.is_active = 1";
		return $this->dbOb->getOne($sql);
	}

		/**
		 * Get the minimum frequency for taking the health assessment for this user's company
		 *
		 */
		public function getIFocusFreq() {
			$sql = "SELECT ifocus_freq FROM p_company AS c " .
						"JOIN u_profile AS u ON u.company_id = c.id " .
						"WHERE u.z_user_id = " . $this->dbOb->escape_string($this->id) . " " .
						"AND u.is_active = 1 " .
						"AND c.is_active = 1";
			return $this->dbOb->getOne($sql);
		}


	/**
	 * Insert a new agreement record.  Make sure all previous ones for this user
	 * are nullified.
	 * 
	 * Inputs:	daytime phone number
	 * 			evening phone number
	 * 			best day to call
	 * 			coaching start date
	 * 			best time to call
	 * 			timezone
	 */
		public function AddHCAgreement($day, $eve, $bestday, $start, $besttime, $tz) {
			$id = $this->cred->getId();
			$today = date("Y-m-d h:i:s");
			$data = $this->GetHCAgreement($id);
			if ($data) {
				$sql = "UPDATE u_health_coach_agreement SET is_active = 0, date_updated = '" . $today . "' " .
						"WHERE z_user_id = " . $this->dbOb->escape_string($id);
				$this->dbOb->update($sql);
			}

			$sql = "INSERT INTO u_health_coach_agreement " .
						"(z_user_id, dayphone, evephone, best_day, best_time, timezone, start_date) " .
						"VALUES (" . $this->dbOb->escape_string($id) . ", '" .
						$this->dbOb->escape_string($day) . "', '" .
						$this->dbOb->escape_string($eve) . "', '" .
						$this->dbOb->escape_string($bestday) . "', '" .
						$this->dbOb->escape_string($besttime) . "', '" .
						$this->dbOb->escape_string($tz) . "', '" .
						$this->dbOb->escape_string($start) . "')";
			$this->dbOb->insert($sql);
			return;
		}

	/**
	 * GetHCAgreement
	 * 
	 * Return the health coach agreement data for the input userid
	 */

	public function GetHCAgreement($id) {
		$ret = array();
		$sql = "SELECT c.enable_hc FROM p_company AS c " .
						"JOIN u_profile AS u ON u.company_id = c.id " .
						"WHERE u.z_user_id = " . $this->dbOb->escape_string($id) . " " .
						"AND c.is_active = 1 " .
						"AND u.is_active = 1";
		$hc = $this->dbOb->getOne($sql);
		if ($hc) {
			$sql = "SELECT * FROM u_health_coach_agreement " .
					"WHERE z_user_id = " . $this->dbOb->escape_string($id) .
					" AND is_active = 1";
			$data =  $this->dbOb->getRow($sql);
		}
		else {
			$data = "";
		}

		$ret['data'] = $data;
		$ret['hc'] = $hc;
		return $ret;
	}

    /**
     * Validate the userss change password request
     * @param array $arr
     * @return false if no err, else array or error objs
    */    
    public function validatePassword($arr){
    	$err=null;
    	try{
    		$old=$this->vc->exists('old_password',$arr,"text",array("min_length"=>6),false,true);
    		$sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($this->cred->getId())."' AND `password`=PASSWORD('".$this->dbOb->escape_string($old)."')";
    		if(!$this->dbOb->getRow($sql)){
    			throw new ValidationException("Current password is incorrect.",4,"old_password");
    		}
    	}catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
    	try{
                $this->data['password']=$this->vc->exists('password',$arr,"text",array("min_length"=>6),false,true);
                $confirm_password=$this->vc->exists('password_confirm',$arr,"text",array("min_length"=>6),false,true);
                if($this->data['password']!=$confirm_password){
                	throw new ValidationException("Confirmation password must match the password.",4,"confirmation_password");
                }                
        }catch(ValidationException $e){
                $err[]=$e->createErrorObject();
        }
        return ($err)?$err:false;
    }
    
    /**
     * Update the usre password
     */
    public function updatePassword(){
     $sql="UPDATE z_users SET `password`=PASSWORD('".$this->dbOb->escape_string($this->data['password'])."') WHERE id='".$this->dbOb->escape_string($this->cred->getId())."'";
     return $this->dbOb->update($sql);
    }
    
    /**
     * Deactivate the user
     */
    public function resign(){
    	$sql="UPDATE z_users SET is_active=0, date_updated=NOW() WHERE id='".$this->dbOb->escape_string($id)."'";
    	$this->dbOb->update($sql);
    }    
}