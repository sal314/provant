<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");

class UserTrackMeasurementsModel {
	private $dbOb=null;
	private $data=array();
	private $keys=array('bust','below_bust','abdomen','waist','hips','wrist','forearm','left_calf','right_calf','left_thigh','right_thigh','left_arm','right_arm');
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$this->id=$id;		
	}
	
	public function getLastEntry(){
		$sql="SELECT * FROM u_tracker_measurements WHERE z_user_id='".$this->id."' ORDER BY date_entered desc";
		return $this->dbOb->getRow($sql);
	}

	public function getTotalDataPoints() {
		$sql = "SELECT COUNT(*) FROM u_tracker_measurements WHERE z_user_id = '" . $this->dbOb->escape_string($this->id) . "'";
		return $this->dbOb->getOne($sql);
	}

	public function getData($range=0,$dir="DESC"){
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
		$sql="SELECT u_tracker_measurements.*,DATE_FORMAT(date_entered,'%m/%d/%Y')as de, concat(z_users.first_name,' ',z_users.last_name) AS enter_name,  (entered_by=z_user_id) as SE,
			(bust+below_bust+abdomen+waist+hips+wrist+forearm+left_calf+right_calf+left_thigh+right_thigh+left_arm+right_arm) as total
			  FROM u_tracker_measurements 
			  JOIN z_users ON z_users.id=u_tracker_measurements.z_user_id 
			  WHERE u_tracker_measurements.z_user_id='".$this->id."' AND u_tracker_measurements.is_active=1 
			  ".$limit."  
			  ORDER BY u_tracker_measurements.date_entered ".$dir;		
		return $this->dbOb->query($sql);
	}



	public function validateData($arr){
		$err=null;
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
			$err[]=$e->createErrorObject();
    	}

    	foreach($this->keys as $key){
			try{
					$rqd = false;
/*																//Waist and hips no longer required 12/8/10
					if ($key == "waist") {
						$rqd = true;
					}
					else if ($key == "hips") {
						$rqd = true;
					}
*/
    			$this->data[$key]=$this->vc->exists($key,$arr,"numeric",array("precision"=>"3,1"),true,$rqd);
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
    	}

    	return ($err)?$err:false;
	}

	 
	public function deleteEntry($id){
		$sql="UPDATE u_tracker_measurements set is_active=0 WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}

	
	public function getEntry($id){
		$sql="SELECT * FROM u_tracker_measurements WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);
	}


	public function getTodaysEntry(){
		$sql="SELECT * FROM u_tracker_measurements WHERE date_entered='".date("Y-m-d")."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);		
	}
	

	//we only allow one entry per day so the add and the update methods are the same as we need to insure only one entry per day
	public function addEntry() {
		require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
		$cred=UserCredentials::load();
		$sql="SELECT id FROM u_tracker_measurements WHERE date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$rec=$this->dbOb->getOne($sql);
		if($rec) {
			$sql="UPDATE u_tracker_measurements 
					SET ";
			foreach($this->keys as $key)
			  $sql.="`".$key."`='".$this->dbOb->escape_string($this->data[$key])."',";
			
			$sql.="	entered_by='".$this->dbOb->escape_string($cred->getId())."', " .
				 	"is_active=1 " .
				 	"WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
			$this->dbOb->update($sql);
		}
		else {
			foreach($this->keys as $key) {
			  $sqlPre.="`".$key."`,";
			  $sqlPost.="'".$this->dbOb->escape_string($this->data[$key])."',";
			}
			
			$sql="INSERT INTO u_tracker_measurements(";
			$sql.=$sqlPre;
			$sql.="z_user_id,date_entered,entered_by) VALUES(";
			$sql.=$sqlPost;
			$sql.="
				'".$this->dbOb->escape_string($this->id)."',
				'".$this->dbOb->escape_string($this->data['date_entered'])."',
				'".$this->dbOb->escape_string($cred->getId())."'";
			
			$sql.=")";
			$this->dbOb->insert($sql);
			$im=new IncentivePointsModel();
			$im->addIncentivePointMA("TrackerMeasurements","AddEntry",$this->data['date_entered']);
		}
	}


	public function calculateBodyFat(){
	 	$sql="SELECT gender  FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' ";
	 	$gender=$this->dbOb->getOne($sql);
	 	if(!$gender) return "N/A";
	 	$res=$this->getLastEntry();
	 	
	 	$wm = new UserTrackWeightModel($this->id);	 	
	 	$weight=$wm->getLastEntry();
	 	if($gender='F'){
	 		$f1=($weight['weight']*0.732)+9.987;
	 		$f2=$res['wrist']/ 3.140;
	 		$f3=$res['abdomen'] * 0.157;
	 		$f4=$res['hips'] * 0.249;
	 		$f5=$res['forearm'] * 0.434;
	 		$lbm=$f1+$f2+$f3+$f4+$f5;
	 		$bfw=$weight['weight']-$lbm;
	 		$bfp=($bfw * 100) / $weight['weight'];
	 	}else{
	 		$f1=($weight['weight'] * 1.082) + 94.42;
	 		$f2=$res['waist'] * 4.15;
	 		$lbm=$f1-$f2;
	 		$bfw=$weight['weight']-$lbm;
	 		$bfp=( $bfw * 100)/$weight['weight'];
	 	}
	 	return round($bfp,2);
	}
}
