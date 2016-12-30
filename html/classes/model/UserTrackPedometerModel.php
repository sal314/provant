<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");
class UserTrackPedometerModel {
	private $dbOb=null;
	private $data=array();
	
	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();
		$this->id=$id;		
	}
	
	public function getLastEnteredStride(){
	 	$sql="SELECT stride 
			  FROM u_tracker_pedometer
			  WHERE z_user_id='".$this->id."' AND is_active=1 
			  ORDER BY date_added DESC";
	 	$rec=$this->dbOb->getRow($sql);
	 	return $rec;	 	
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
		$sql="SELECT u_tracker_pedometer.*,DATE_FORMAT(date_entered,'%m/%d/%Y')as de, concat(z_users.first_name,' ',z_users.last_name) AS enter_name,  
			(entered_by=z_user_id) as SE, ROUND(steps * stride /5280,2) AS miles
			  FROM u_tracker_pedometer 
			  JOIN z_users ON z_users.id=u_tracker_pedometer.z_user_id 
			  WHERE u_tracker_pedometer.z_user_id='".$this->id."' AND u_tracker_pedometer.is_active=1 
			  ".$limit."  
			  ORDER BY u_tracker_pedometer.date_entered ".$dir;
		return $this->dbOb->query($sql);
	}
	public function getSummaryData(){
		$sql="SELECT DATE_FORMAT(date_entered,'%m/%Y')as de, SUM(steps) as sum_steps, SUM(calories) as sum_calories,
					 SUM(ROUND(steps * stride /5280,2)) AS sum_miles
			  FROM u_tracker_pedometer 			  
			  WHERE u_tracker_pedometer.z_user_id='".$this->id."' AND u_tracker_pedometer.is_active=1 			  
			  GROUP BY   DATE_FORMAT(u_tracker_pedometer.date_entered,'%Y-%m')
			  ORDER BY u_tracker_pedometer.date_entered ASC";
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
    		$stride=$this->vc->exists('stride',$arr,"integer",array("rangex_low"=>0, "rangex_high"=>100),false,true);
				$this->data['stride'] = $stride / 12;
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}

		try{
    		$this->data['steps']=$this->vc->exists('steps',$arr,"integer",array("rangex_low"=>10, "rangex_high"=>75000),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	

    	try{
    		if(isset($arr["override"])){
    			$this->data['calories']=$this->vc->exists('calories',$arr,"integer",array("rangex_low"=>0, "rangex_high"=>9000),false,true);
    			$this->data['override']=1;
    		}else{    			
    			$userWeight=isset($arr['userweight'])?intval($arr['userweight']):0;
	  			$miles = round($this->data['steps']*$this->data['stride'] / 52.80) / 100;
	  			$exDur = $miles  * 20;
 					$cals = 0.02625 * $exDur * $userWeight;
    			$this->data['calories']=round($cals);
    			$this->data['override']=0;
    		}
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}

    	$this->data['miles']=$this->data['stride']*$this->data['steps'];
    	$this->data['miles']=number_format($this->data['miles'],2);
    	return ($err)?$err:false;
	}
	 
	public function deleteEntry($id){
		$sql="UPDATE u_tracker_pedometer set is_active=0 WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}
	
	//we only allow one entry per day so the add and the update methods are the same as we need to insure only one entry per day
	public function addEntry(){
		require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
		$cred=UserCredentials::load();
		$sql="SELECT id FROM u_tracker_pedometer WHERE date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$rec=$this->dbOb->getOne($sql);
		if($rec){
			$sql="UPDATE u_tracker_pedometer 
					SET stride='".$this->dbOb->escape_string($this->data['stride'])."', 
				 		steps='".$this->dbOb->escape_string($this->data['steps'])."',  
				 		calories='".$this->dbOb->escape_string($this->data['calories'])."',
				 		miles='".$this->dbOb->escape_string($this->data['miles'])."',
				 		entered_by='".$this->dbOb->escape_string($this->dbOb->escape_string($cred->getId()))."',
				 		override='".$this->dbOb->escape_string($this->data['override'])."',
				 		is_active=1
				 	WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
			$this->dbOb->update($sql);
		}else{
			$sql="INSERT INTO u_tracker_pedometer(z_user_id,stride,steps,calories,miles,date_entered,entered_by,override) VALUES(
				'".$this->dbOb->escape_string($this->id)."',
				'".$this->dbOb->escape_string($this->data['stride'])."',
				'".$this->dbOb->escape_string($this->data['steps'])."',
				'".$this->dbOb->escape_string($this->data['calories'])."',
				'".$this->dbOb->escape_string($this->data['miles'])."',
				'".$this->dbOb->escape_string($this->data['date_entered'])."',
				'".$this->dbOb->escape_string($cred->getId())."',
				'".$this->dbOb->escape_string($this->data['override'])."'
				)";
			$this->dbOb->insert($sql);
			$im=new IncentivePointsModel();
			$im->addIncentivePointMA("TrackerPedometer","AddEntry",$this->data['date_entered']);
			$im->addIncentivePointMA("TrackerPedometer","stepslogged",$this->data['date_entered'],$this->data['steps']);
		}
	}
	public function getEntry($id){
		$sql="SELECT * FROM u_tracker_pedometer WHERE id='".$this->dbOb->escape_string($id)."' AND z_user_id='".$this->dbOb->escape_string($this->id)."'";
		return $this->dbOb->getRow($sql);
	}

	public function getTotalDataPoints() {
		$sql = "SELECT COUNT(*) FROM u_tracker_pedometer WHERE z_user_id = '" . $this->dbOb->escape_string($this->id) . "'";
		return $this->dbOb->getOne($sql);
	}

	public function getTotals($data){
		$log_totals=array("steps"=>0,"calories"=>0,"miles"=>0);
		$monthTotals=array();

		if($data){
			foreach($data as $datum){			
				$log_totals['steps']+=$datum['steps'];
				$log_totals['calories']+=$datum['calories'];			
				$log_totals['miles']+=$datum['miles'];
			}
		}
		$monthTotals=$this->getSummaryData();
		
		return array("log_totals"=>$log_totals,"month_totals"=>$monthTotals);
	}
}