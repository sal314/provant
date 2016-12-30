<?php

require_once (LIB_ROOT."classes/common/Database.class.php");

class IncentivePointsModel {

	private $dbOb=null;
	private $id=null;
	private $company=null;
	private $campaign=null;
	
	public function __construct($altId=0){
		$this->dbOb=Database::create();
		$cred=UserCredentials::load();
		if(!$altId)
			$this->id=$this->dbOb->escape_string($cred->getId());
		else 
			$this->id=$this->dbOb->escape_string($altId);
			
		//get company id
		$this->company=$this->dbOb->getOne("SELECT company_id FROM u_profile WHERE z_user_id='".$this->id."'");
		//is there a current incentive campaign for the company
		$sql="SELECT * FROM p_incentive_program WHERE company_id='".$this->dbOb->escape_string($this->company)."' AND (end_date<=DATE_FORMAT(NOW(),'%Y-%m-%d') || end_date='0000-00-00') AND is_active=1 ORDER BY start_date ASC";
		$this->campaign=$this->dbOb->getOne($sql);
	}
	
	
/**
 * Add incentivr points for module/action 
 * @param string $module -model name
 * @param string $action -action name
 * @param date   $dateEntered -date of entry
 */
	public function addIncentivePointMA($module,$action,$dateEntered=null){				
		if(!$this->campaign) return; //no active campaign

		$sql="SELECT id FROM p_incentive_activity WHERE module='".$this->dbOb->escape_string($module)."' AND action='".$this->dbOb->escape_string($action)."' AND is_active=1";
		$activityId=$this->dbOb->getOne($sql);
		if(!$activityId) return; //not a  points activity
		$this->addIncentivePoint($activityId,$dateEntered);
	} 
	
/**
 * Add Incentive point for an activity
 * @param int $activityId
 * @param date $dateEntered
 */	
	public function addIncentivePoint($activityId=0,$dateEntered=null){
		
		if(!$this->campaign) return; //no active campaign
		$sql="SELECT * FROM p_incentive_triggers WHERE incentive_activity_id='".$this->dbOb->escape_string($activityId)."' AND incentive_program_id='".$this->dbOb->escape_string($this->campaign)."'";
		
		$reward=$this->dbOb->getRow($sql);
		if(!$reward) return;//no pts awarded in this campaign
		
		//check to see if the date Entered is  within the allowable points range
		
		$now=time();

		if(!$dateEntered) $dateEntered=time();//use today if no date enetered was provided
		
		//get the last logged entry for this activiry
		$sql="SELECT * FROM p_incentive_triggers_log WHERE z_user_id='".$this->id."' AND p_incentive_triggers_id='".$this->dbOb->escape_string($reward['id'])."' ORDER BY date_added DESC";
		
		$entered=$this->dbOb->getRow($sql);		
		if($entered){
			//now we need to check to see if the entered is out of the range of the $reward["frequency"];
			switch($reward["frequency"]){
				case "daily": $cutOff=$now-24*36*36;break;
				case "weekly": $cutOff=$now-24*36*36*7;break;
				case "monthly": $cutOff=$now-24*36*36*30;break;
				case "onetime": return; //no additional points awarded for one time events
			}
			
			if($entered["date_added"]>$cutOff) return; //we were already rewarded
		}
		
		
		//need to log the event 
		$sql="INSERT INTO p_incentive_triggers_log(z_user_id,p_incentive_triggers_id,points) VALUES (
		'".$this->id."',
		'".$this->dbOb->escape_string($reward['id'])."',
		'".$this->dbOb->escape_string($reward['points'])."')";

		$this->dbOb->insert($sql);
		
		//need to add points to the runing totals....
		$sql="UPDATE u_profile SET incentive_points_total=incentive_points_total+'".$this->dbOb->escape_string($reward['points'])."' WHERE z_user_id=".$this->id;
		$this->dbOb->update($sql);
	}
	
	public function addIncentivePointManual($activityId,$override_points,$override_comment){
		if(!$this->campaign) return; //no active campaign
		$sql="SELECT * FROM p_incentive_triggers WHERE incentive_activity_id='".$this->dbOb->escape_string($activityId)."' AND  incentive_program_id='".$this->dbOb->escape_string($this->campaign)."'";
		$reward=$this->dbOb->getRow($sql);
		if(!$reward) return;//no pts awarded in this campaign

		$points = $this->dbOb->escape_string($reward['points']);
		$comment = "";
		if ($activityId == 20) {
			$points = $this->dbOb->escape_string($override_points);
			$comment = $this->dbOb->escape_string($override_comment);
		}

		$sql="INSERT INTO p_incentive_triggers_log(z_user_id,p_incentive_triggers_id,points,comment) VALUES (
		'".$this->id."',
		'".$this->dbOb->escape_string($reward['id'])."',
		'".$points."','".$comment."')";
		$this->dbOb->insert($sql);
		
		//need to add points to the runing totals....
		$sql="UPDATE u_profile SET incentive_points_total=incentive_points_total+'".$points."' WHERE z_user_id=".$this->id;
		$this->dbOb->update($sql);
		
	}
	
	/**
	 * Add points for activity of they meet a minimum point total
	 * @param int $activityId
	 * @param date $dateEntered
	 * @param number $minTotal
	 */
	public function addIncentivePointMinReq($activityId=0,$dateEntered=null,$minTotal=0){
		if(!$this->campaign) return; //no active campaign
		$sql="SELECT * FROM p_incentive_triggers WHERE incentive_activity_id='".$this->dbOb->escape_string($activityId)."' AND is_active=1 AND incentive_program_id='".$this->dbOb->escape_string($this->campaign)."'";
		$reward=$this->dbOb->getRow($sql);
		if(!$reward) return;//no pts awarded in this campaign

		//check to see if the date Entered is  within the allowable points range
		
		$now=time();
		$maxPast=$now-($reward['days_back']*24*60*60);

		if(!$dateEntered) $dateEntered=time();
		if($maxPast<$dateEntered){
			return; //date entered was too far into the past to get rewards
		}
		
		$sql="SELECT * FROM p_incentive_triggers_log WHERE z_user_id='".$this->id."' AND p_incentive_triggers_id='".$this->dbOb->escape_string($reward['id'])."' ORDER BY date_added DESC";
		$entered=$this->dbOb->getRow($sql);		
		if($entered){
			//now we need to check to see if the entered is out of the range of the $reward["frequency"];
			switch($reward["frequency"]){
				case "daily": $cutOff=$now-24*36*36;break;
				case "weekly": $cutOff=$now-24*36*36*7;break;
				case "monthly": $cutOff=$now-24*36*36*30;break;
				case "onetime": return; //no additional points awarded for one time events
			}
			$de= strtotime($entered["date_added"]);			 
			if($de>$cutOff) return; //we were already rewarded
		}
		
		//need to log the event - do we retroactivly add points?? 
		$sql="INSERT INTO p_incentive_triggers_log(z_user_id,p_incentive_triggers_id,points_awarded) VALUES (
		'".$this->id."',
		'".$this->dbOb->escape_string($entered['id'])."',
		'".$this->dbOb->escape_string($entered['points'])."')";
		$this->dbOb->insert($sql);
		
		//need to add points to the runing totals....
		$sql="UPDATE u_profile SET incentive_points_total=incentive_points_total+'".$this->dbOb->escape_string($entered['points'])."' WHERE z_user_id=".$this->id;
		$this->dbOb->update($sql);		
	}
	
	
	/**
	 * subract the requested amount of points from the user
	 * @param int $points
	 */
	public function RedeemIncentivePoint($points=0){

		$sql="UPDATE u_profile SET incentive_points_total=incentive_points_total-'".$this->dbOb->escape_string($points)."' WHERE z_user_id=".$this->id;
		$this->dbOb->update($sql);				
	}
		
}