<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  require_once (DOCUMENT_ROOT."/classes/model/IncentivePointsModel.php");
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class IncentiveProgram extends AdminPageBase{
	public function getBaseTableName(){return "p_incentive_program";}	
	

	public function Index($param) {
		$t=parent::Index($param,TEMPLATE_DIR."admin/IncentiveProgram/index.tpt");

		return $t;
	}


	public function Insert($param){
		$t=parent::Insert($param,TEMPLATE_DIR."admin/IncentiveProgram/insert.tpt");
		$sql="SELECT * FROM p_incentive_activity WHERE is_active=1 ORDER BY description";
		$t->addVar("activities",$this->dbOb->query($sql));
		$freq=array(
			array("display"=>"Daily","value"=>"daily"),
			array("display"=>"Weekly","value"=>"weekly"),
			array("display"=>"Monthly","value"=>"monthly"),
			array("display"=>"Once","value"=>"onetime"),
		);
		
		$t->addVar("frequency",$freq);
		return $t;
	}
	
   public function Edit($param){
		$t=parent::Edit($param,TEMPLATE_DIR."admin/IncentiveProgram/edit.tpt");
		$sql="SELECT * FROM p_incentive_activity WHERE is_active=1 ORDER BY description";
		$t->addVar("activities",$this->dbOb->query($sql));
		$freq=array(
			array("display"=>"Daily","value"=>"daily"),
			array("display"=>"Weekly","value"=>"weekly"),
			array("display"=>"Monthly","value"=>"monthly"),
			array("display"=>"Once","value"=>"onetime"),
		);
		
		$sql="SELECT * FROM p_incentive_triggers AS pit
			  JOIN p_incentive_activity AS pia ON pia.id=pit.incentive_activity_id
			  WHERE  incentive_program_id='".$this->dbOb->escape_string($param[0])."' 
			  AND pit.is_active=1 ORDER BY description";
		$rec=$this->dbOb->query($sql);
		
		$t->addVar("frequency",$freq);
		$t->addVar("point_activities",$rec);
		return $t;
	}
	
	public function InsertRec($param){
		$recId=parent::InsertRec($param,false);
		if(isset($_POST["p_incentive_triggers"])){
			foreach($_POST["p_incentive_triggers"] as $key=>$trig){
				$sql="INSERT INTO p_incentive_triggers (incentive_program_id,incentive_activity_id,points,frequency,days_back,minimum_value)
				VALUES(
				 '".$this->dbOb->escape_string($recId)."',
				 '".$this->dbOb->escape_string($key)."',
				 '".$this->dbOb->escape_string($trig['points'])."',
				 '".$this->dbOb->escape_string($trig['frequency'])."',
				 '".$this->dbOb->escape_string($trig['days_back'])."',
				 '".$this->dbOb->escape_string($trig['minimum_value'])."'
				)";
				$this->dbOb->insert($sql);
			}
		}		
		$this->forceRedirect();
	}
	
	
  	public function Change($param){
		parent::Change($param,false);
		$recId=$_POST['p_incentive_program']['id'];
		
		//we should do a check
		//if any of the data changed retire the old one..and create the new one
		
		
		$sql="UPDATE p_incentive_triggers SET is_active=0 WHERE incentive_program_id='".$this->dbOb->escape_string($recId)."'";		
		$this->dbOb->update($sql);
		
		if(isset($_POST["p_incentive_triggers"])){
			foreach($_POST["p_incentive_triggers"] as $key=>$trig){
				
				$sql="SELECT * FROM p_incentive_triggers 
					 WHERE incentive_program_id='".$this->dbOb->escape_string($recId)."'  
					   AND incentive_activity_id='".$this->dbOb->escape_string($key)."' 
					   AND frequency='".$this->dbOb->escape_string($trig['frequency'])."' 
					   ORDER BY id DESC";
				$new=$this->dbOb->getRow($sql);
				
				if(!$new){
					$sql="INSERT INTO p_incentive_triggers (incentive_program_id,incentive_activity_id,points,frequency,days_back,minimum_value)
					VALUES(
				 	'".$this->dbOb->escape_string($recId)."',
				 	'".$this->dbOb->escape_string($key)."',
				 	'".$this->dbOb->escape_string($trig['points'])."',
				 	'".$this->dbOb->escape_string($trig['frequency'])."',
				 	'".$this->dbOb->escape_string($trig['days_back'])."',
				 	'".$this->dbOb->escape_string($trig['minimum_value'])."'
					)";
					$this->dbOb->insert($sql);
				}else{
					$sql="UPDATE p_incentive_triggers  
						SET points='".$this->dbOb->escape_string($trig['points'])."', 
							days_back='".$this->dbOb->escape_string($trig['days_back'])."', 
							minimum_value='".$this->dbOb->escape_string($trig['minimum_value'])."',
							is_active=1 
						WHERE id='".$this->dbOb->escape_string($new['id'])."'";
					$this->dbOb->update($sql);
				}
			}
		}		
		$this->forceRedirect();
	}
	
	public function UserPoints($param){
	  $uid=isset($param[0])?intval($param[0]):0;
	  if(!$uid){
	  	throw new Exception("Invalid User id");	  
	  }
	  
	  $template=TemplateParser::enqueue(TEMPLATE_DIR."admin/IncentiveProgram/user.tpt");
	  
	  $sql="SELECT * FROM u_profile where id='".$this->dbOb->escape_string($uid)."'";
	  $upi=$this->dbOb->getRow($sql);
	  if(!$upi['is_active']){
	  	throw new Exception("Request user is not an active participant.");
	  }
	  $sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($upi['z_user_id'])."'";
	  $zInfo=$this->dbOb->getRow($sql);
	  if(!$zInfo['is_active']){
	  	throw new Exception("Request user is not an active participant.");
	  }
	  
	  $sql="SELECT * FROM p_company where id='".$this->dbOb->escape_string($upi['company_id'])."'";
	  $company=$this->dbOb->getRow($sql);
	  
	  if(!$company){
	  	throw new Exception("Requested user's company could not be found.");
	  }
	  if(!$company['is_active']){
	  	throw new Exception("Requested user's company is no longer active participant.");
	  }
	  
	  	$sql="SELECT * FROM p_incentive_activity WHERE is_active=1 AND module like 'External Source' ORDER BY description";
		$template->addVar("activities",$this->dbOb->query($sql));
		$freq=array(
			array("display"=>"Daily","value"=>"daily"),
			array("display"=>"Weekly","value"=>"weekly"),
			array("display"=>"Monthly","value"=>"monthly"),
			array("display"=>"Once","value"=>"onetime"),
		);
		
		$sql="SELECT * FROM p_incentive_triggers AS pit
			  JOIN p_incentive_activity AS pia ON pia.id=pit.incentive_activity_id
			  WHERE  incentive_program_id='".$this->dbOb->escape_string($company['id'])."'
			  AND pia.module like 'External Source' 
			  ORDER BY description";   
		$rec=$this->dbOb->query($sql);
		
		$template->addVar("frequency",$freq);
		$template->addVar("point_activities",$rec);
		$template->addVar("User",$rec);	
		$template->addVar("zInfo",$zInfo);
		$template->addVar("company",$company);
		$template->addVar("profile",$upi);
		
		
		$sql="SELECT pitl.points,pitl.date_added,pia.description,pitl.comment " .
				"FROM p_incentive_triggers_log AS pitl " . 
			 	"JOIN  p_incentive_triggers as pit ON pit.id=pitl.p_incentive_triggers_id " .  
			 	"JOIN  p_incentive_activity as pia ON pia.id=pit.incentive_activity_id " .
			 	"WHERE pitl.z_user_id='".$this->dbOb->escape_string($upi['z_user_id']).
			 	"' ORDER BY pitl.date_added";
		$history=$this->dbOb->query($sql);

		$template->addVar("history",$history);
	  return $template;
	}
	
	public function AddPoints($param){
	 	$uid=isset($_POST['id'])?intval($_POST['id']):0;
	 	
	  	if(!$uid){
	  		throw new Exception("Invalid User id");	  
	  	}
	  	  
	  
	  $sql="SELECT * FROM u_profile where z_user_id='".$this->dbOb->escape_string($uid)."'";
	  $upi=$this->dbOb->getRow($sql);
	  if(!$upi['is_active']){
	  	throw new Exception("Request user is not an active participant.");
	  }
	  $sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($uid)."'";
	  $zInfo=$this->dbOb->getRow($sql);
	  if(!$zInfo['is_active']){
	  	throw new Exception("Request user is not an active participant.");
	  }
	  
	  $sql="SELECT * FROM p_company where id='".$this->dbOb->escape_string($upi['company_id'])."'";
	  $company=$this->dbOb->getRow($sql);
	  
	  if(!$company){
	  	throw new Exception("Requested user's company could not be found.");
	  }
	  if(!$company['is_active']){
	  	throw new Exception("Requested user's company is no longer active participant.");
	  }

	  require_once(ROOT_DIR."classes/model/IncentivePointsModel.php");
	  $ipm=new IncentivePointsModel($uid);
	  
	  if(isset($_POST['addPoints'])){
	  	foreach($_POST['addPoints'] as $key=>$value){
	  		if ($key == 20) {
	  			$override_points=isset($_POST['generic_points'])?intval($_POST['generic_points']):0;
		  		$override_comment=isset($_POST['generic_comment'])?$_POST['generic_comment']:"";
	  		}
	  		else {
	  			$override_points = 0;
	  			$override_comment = "";
	  		}
	  		$ipm->addIncentivePointManual($key,$override_points,$override_comment);
 	  	}
	  }
	  header("Location: /admin/Company/ListUsers/?company_id=".$upi['company_id']);
	  exit();
	}

	public function AwardCSV($param) {
		if (count($_FILES) == 0) throw new Exception("No file uploaded");
		if ($_FILES["AwardCSVfile"]["error"]) throw new Exception("Error during file upload");

		$fname=$_FILES["AwardCSVfile"]["tmp_name"];
		$fh=fopen($fname, "r");
		if (!$fh) throw new Exception("Can not open CSV file");

		//
		// Expected input format
		//
		//	[0] Company name
		//	[1] User email address
		//	[2] Activity description
		//	[3] Points (ignored unless Activity Id = 20) (Generic External Event)
		//	[4] Comment (ignored unless Activity Id = 20) (Generic External Event)
		//
		//

		$log=tmpfile();
		fwrite ($log,"Award CSV processing started at ".date('Y-m-d H:i:s')."\n");
		$count=0;
		while ($input = fgetcsv($fh, 0, ',', '"')) {
			$count += 1;
			// make sure we have an active company
			$sql="SELECT id FROM p_company WHERE company_name='".$this->dbOb->escape_string($input[0])."' AND is_active=1";
			$row=$this->dbOb->getRow($sql);
			if (!$row) {
				fwrite($log, "line " . $count .": No active company with the name ".$input[0]."\n");
				continue;
			}
			$company_id = $row['id'];

			// make sure this company has an active incentive program
			$sql="SELECT id FROM p_incentive_program WHERE company_id=".$company_id.
					" AND is_active=1".
					" AND start_date < DATE_FORMAT(NOW(),'%Y-%m-%d')".
					" AND (end_date='0000-00-00' OR end_date > DATE_FORMAT(NOW(),'%Y-%m-%d'))";
			$row=$this->dbOb->getRow($sql);
			if (!$row) {
				fwrite($log, "line " . $count .": There is no active incentive program for ".$input[0]."\n");
				continue;
			}
			$program_id=$row['id'];

			// make sure input activity is valid for this company's program
			$sql="SELECT pit.points,pit.id AS tid,pia.id AS aid,pit.frequency,pit.days_back".
					" FROM p_incentive_triggers AS pit,".
					" p_incentive_activity AS pia".
					" WHERE pia.description='".$this->dbOb->escape_string($input[2]).
					"' AND pia.module LIKE 'External Source'".
					" AND pia.is_active=1 AND pit.incentive_activity_id=pia.id".
					" AND pit.is_active=1 AND pit.incentive_program_id=".$program_id;
			$row=$this->dbOb->getRow($sql);
			if (!$row) {
				fwrite($log, "line ". $count .": There is no activity called ".$input[2]." for ".$input[0]."\n");
				continue;
			}
			$points=$row['points'];
			$pit_id=$row['tid'];
			$pia_id=$row['aid'];
			$freq=$row['frequency'];
			$days=$row['days_back'];
			
			// make sure input user is active in this company
			$sql="SELECT zu.id AS zid,up.id AS pid FROM z_users AS zu, u_profile AS up".
					" WHERE zu.email='".$this->dbOb->escape_string($input[1]).
					"' AND zu.id=up.z_user_id".
					" AND zu.is_active=1".
					" AND up.is_active=1".
					" AND up.company_id=".$company_id;
			$row=$this->dbOb->getRow($sql);
			if (!$row) {
				fwrite($log, "line ". $count .": There is no active user with email of ".$input[1]." for ".$input[0]."\n");
				continue;
			}
			$zu_id = $row['zid'];
			$up_id = $row['pid'];



			// All looks good, insert a log entry and update the user's total points

			// override points from input if it's a "Generic External Event"
			if ($pia_id == 20) {
				$points = intval($this->dbOb->escape_string($input[3]));
				$comment = $this->dbOb->escape_string($input[4]);
			}
			else {
				$comment = "";
			}

			$sql="INSERT INTO p_incentive_triggers_log".
					" (z_user_id, p_incentive_triggers_id, points, comment)".
					" VALUES (".$zu_id.",".$pit_id.",".$points.",'".$comment."')";
			$this->dbOb->insert($sql);
			$sql="UPDATE u_profile SET incentive_points_total=incentive_points_total+".$points.
					" WHERE id=".$up_id;
			$this->dbOb->update($sql);
		}
		fclose($fh);
		fwrite($log, "Finished processing at ".date('Y-m-d H:i:s')."\n");

		rewind($log);
		$logfile = array();
		while ($recd = fgets($log)) {
			array_push($logfile, $recd);
		}
		fclose($log);

		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/IncentiveProgram/showlog.tpt");
		$template->addVar("logfile", $logfile);
		return $template;
	}

/**
 * 
 * Landing for admin to redeem points
 * @param $params
 * 
 * $_POST array
 * 		email - user's email redeeming points
 * 		points - number of points to use
 */
	public function redeemPoints($params) {
		// Get input user's email address
		$email = isset($_POST['email'])?$_POST['email']:"";
		if ($email == "") {
			throw new Exception("User's email was not provided");
		}

		// Get the user's id and point total
		$sql = "SELECT z.id,u.incentive_points_total FROM z_users AS z, u_profile AS u ".
				"WHERE z.email='" . $this->dbOb->escape_string($email) . "' ".
				"AND z.is_active=1 ".
				"AND z.id=u.z_user_id";
		$uid = $this->dbOb->getRow($sql);
		if (!$uid) {
			throw new Exception("No user was found with the email address = ".$email);
		}

		// Check to see if the user has enough points
		$points = isset($_POST['points'])?intval($_POST['points']):0;
		if ($points > intval($uid['incentive_points_total'])) {
			throw new Exception("User (".$email.") only has ". $uid['incentive_points_total'] . " points");
		}

		// Deduct the points from the user's total
		$model = new IncentivePointsModel($uid['id']);
		$model->RedeemIncentivePoint($points);

		header("Location: /admin/IncentiveProgram/Index?mode=success");
	}
  }

  
