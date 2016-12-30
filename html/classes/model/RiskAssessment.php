<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
class RiskAssessment{
  private $dbOb;
  
  public function __construct($id){
    $this->dbOb=Database::create();
    $this->id=$id;
    $sql="SELECT  ((date_format(now(),'%Y') - date_format(dob,'%Y')) - (date_format(now(),'00-%m-%d') < date_format(dob,'00-%m-%d'))) AS age,gender FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
    $rec=$this->dbOb->getRow($sql);
    $this->age=$rec['age']; 
    $this->gender=$rec['gender'];
  } 
  
  public function getTotalCholesterolRisk($total){
      if($total<200) return 0;
      if($total<240) return 1;
      return 1;
  }
  public function getHDLRisk($total){
    if($this->gender=="M"){
      if($total>40) return 0;
      return 2;
    }
    if($total>50) return 0;
    return 2;
  }
  
  public function getTCHDLRisk($total){
  	if($total<4) return 0;
  	return 2;
  }
  
  public function getLDLRisk($total){
  	if($total<100) return 0;
  	if($total<130) return 1;
  	return 2;
  }
  
  public function getTriglycerideRisk($total){
  	if($total<150) return 0;
  	if($total<200) return 1;
  	return 2;
  }
  
  public function getSystolicRisk($total){
   	if($total<120) return 0;
  	if($total<140) return 1;
  	return 2;
  }
  
   public function getGlucoseRisk($total,$fasting=false){
     if($fasting){
   		if($total<130) return 0;
  		if($total<175) return 1;
  		return 2;
  	}
  	if($total<100) return 0;
  	if($total<125) return 1;
  	return 2;  	
  }
  
  public function getDiastolicRisk($total){
   	if($total<80) return 0;
  	if($total<90) return 1;
  	return 2;  	
  }
  
  public function getBMIRisk($total){
  	if($this->gender=="M"){
  		if($this->age<40){
  			if($total<19.4) return 0;
   			if($total<23.5) return 1;
  			return 2;
  		}
  		if($this->age<60){
  			if($total<23.5) return 0;
   			if($total<24.5) return 1;
  			return 2;  		
  		}
		if($total<24.5) return 0;		
		return 2;  			  	
  	}
  	if($this->age<40){
  			if($total<23.5) return 0;
   			if($total<30.5) return 1;
  			return 2;
  		}
  		if($this->age<60){
  			if($total<30.5) return 0;
   			if($total<31.5) return 1;
  			return 2;  		
  		}
		if($total<31.5) return 0;		
		return 2;  	
  }
  
  public function getWaistToHipRatioRisk($total){
  	if($this->gender=="M"){
  		if($total<.96) return 0; 
  		if($total<1) return 1;
  		return 2;
  	}
	if($total<.81) return 0; 
	if($total<.86) return 1;
	return 2;
  	
  }
  
  public function getPSARisk($total){
  	if($total<2.6) return 0;
  	if($total<20) return 1;
  	return 2;
  }
  
  public function Vision($total){
	//Far sightedness is between 20/20 and 20/30						Far sightedness is between 20/40 and 20/50					Far sightedness is between 20/70 and 20/100	
	//Near sightedness is between 20/20 and 20/30						Near sightedness is between 20/40 and 20/50					Near sightedness is between 20/70 and 20/100	

  }
  
}