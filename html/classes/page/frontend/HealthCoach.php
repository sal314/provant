<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (LIB_ROOT."classes/common/Pager.class.php");
  require_once (ROOT_DIR."classes/model/HCCallLogModel.php");
  require_once (LIB_ROOT."classes/common/Ajax.class.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class HealthCoach extends PageBase{
  		public function Index($param,$altTemplate=null,$forceRedirect=true,$restrictSQL=null){
			if (!$this->cred->getLoginStatus()) {
				header("Location: /Landing/Index");
				exit(0);
			}
  			
			header("Location: /Messages/Index");
			exit();

//			$templateEngine=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/home.tpt");
  		}
  		/**
  		 * View the call log
  		 * @param array $param
  		 */
  		public function CallLog($param){
  			if (!isset($_POST['company_id'])) {
				$sql="SELECT id as value, company_name as display FROM p_company WHERE is_active = 1";
				$companies = $this->dbOb->query($sql);
				$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_select.tpt");
				$template->addVar('companies', $companies);
				return $template;
			}
			else {
				$company_id = $_POST['company_id'];
				if ($company_id == 0) {
					$cname = "All Companies";
				}
				else {
					$sql = "SELECT company_name FROM p_company WHERE id = " . $this->dbOb->escape_string($company_id);
					$cname = $this->dbOb->getOne($sql);
				}
				if (!isset($_POST['user_id']) || $_POST['user_id'] == "") {
					if ($company_id == 0) {
						$cwhere = "";
					}
					else {
						$cwhere = "AND u.company_id = " . $this->dbOb->escape_string($company_id) . " ";
					}
					$sql="SELECT z.id as value, concat(z.last_name, ', ', z.first_name) as display FROM z_users as z " .
							"JOIN u_profile AS u ON z.id = u.z_user_id " .
							"WHERE z.is_active = 1 " .
							$cwhere .
							"ORDER BY display ASC";
					$users = $this->dbOb->query($sql);
					$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_select.tpt");
					$template->addVar('company_id', $company_id);
					$template->addVar('company_name', $cname);
					$template->addVar('users', $users);
					return $template;
				}
				else {
					$user_id = $_POST['user_id'];
					$sql = "SELECT concat(z.first_name, ' ', z.last_name) as name, u.dob as dob FROM z_users as z " .
							"JOIN u_profile as u on z.id = u.z_user_id " .
							"WHERE z.id = " . $this->dbOb->escape_string($user_id);
					$user = $this->dbOb->getRow($sql);

					$sql="SELECT h.*,concat(z.first_name, ' ', z.last_name) as coach, " .
							"concat(h.contact_date, h.contact_time) as listorder " .
							"FROM h_call_log as h " .
							"JOIN z_users as z ON z.id=h.health_coach " .
							"WHERE h.patient='".$this->dbOb->escape_string($user_id)."' " .
							"ORDER BY listorder DESC";
		  			$p= new Pager();
		  			$page=isset($param[0])&&$param[0]?$param[0]:1;
		  			$limit=$p->page($sql,$page);
		  			$sql.=$limit;
		  			$history=$this->dbOb->query($sql);
		  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log.tpt");
		  			$template->addVar("history",$history);
		  			$template->addVar("company_name", $cname);
		  			$template->addVar("user", $user);
		  			$template->addVar("company_id", $company_id);
		  			$template->addVar("user_id", $user_id);
		  			return $template;
				}
			}
  		}
  		
  		/**
  		 * Show add  an entry to the call log page
  		 * @param $params
  		 */
  		public function AddLogEntry($params){
  			if($params[0]==""){
  				header("Location: /HealthCoach/FindUser");
  				exit();
  			}
  			$sql="SELECT z_users.* FROM z_users  WHERE  z_users.id='".$this->dbOb->escape_string($params[0])."'";
  			$u=$this->dbOb->getRow($sql);
  			$u['call_log_id']=0;

  			$hours = array();
  			for ($i = 0; $i < 12; $i++) {
  				$val = sprintf("%02d", $i+1);
  				array_push($hours, array ('value' => $i+1, 'display' => $val));
  			}

			$minutes = array();
			for ($i = 0; $i < 60; $i++) {
				$val = sprintf("%02d", $i);
				array_push($minutes, array('value' => $i, 'display' => $val));
			}

			$ampm = array(array ('value' => "AM", 'display' => "AM"),
						  array ('value' => "PM", 'display' => "PM"));

  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_entry.tpt");
  			$template->addVar("mode","InsertEntry");
  			$template->addVar("user",$u);
  			$template->addVar("hours", $hours);
  			$template->addVar("minutes", $minutes);
  			$template->addVar("ampm", $ampm);
  			$model=new HCCallLogModel();
  			$template->addVar("method_options",$model->getOptions("method"));  			
  			$template->addVar("intervention_options",$model->getOptions("intervention"));
  			$template->addVar("risk_options",$model->getOptions("risk"));
  			$template->addVar("migration_options",$model->getOptions("migration"));
  			$template->addVar("participation_options",$model->getOptions("participation"));
  			$template->addVar("company_id", $params[1]);
			$template->addVar("user_id", $params[0]);
  		}
  		
  		/**
  		 * Show Edit a call log entry page
  		 * @param $param
  		 */
  		public function EditLogEntry($param){
  			if($param[0]==""){
  				header("Location: /HealthCoach/CallLog");
  				exit();
  			}

  			$sql="SELECT * FROM h_call_log WHERE id='".$this->dbOb->escape_string($param[0])."'";
  			
  			$rec=$this->dbOb->getRow($sql);
  			$sql="SELECT z_users.* FROM z_users  WHERE  z_users.id='".$this->dbOb->escape_string($rec['patient'])."'";
  			$u=$this->dbOb->getRow($sql);
  			$rec['call_log_id']=$rec['id'];
  			
  			$hours = array();
  			for ($i = 0; $i < 12; $i++) {
  				$val = sprintf("%02d", $i+1);
  				array_push($hours, array ('value' => $i+1, 'display' => $val));
  			}

			$minutes = array();
			for ($i = 0; $i < 60; $i++) {
				$val = sprintf("%02d", $i);
				array_push($minutes, array('value' => $i, 'display' => $val));
			}

			$ampm = array(array ('value' => "AM", 'display' => "AM"),
						  array ('value' => "PM", 'display' => "PM"));

			$hour = substr($rec['contact_time'], 0, 2);
			$rec['time_ampm'] = "AM";
			if ($hour > 11) {
				$rec['time_ampm'] = "PM";
				if ($hour > 12) {
					$hour -= 12;
				}
			}
			else if ($hour == 0) {
				$hour = 12;
			}
			$rec['time_hours'] = $hour;
			$rec['time_min'] = substr($rec['contact_time'], 3, 2);

  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_entry.tpt");
  			$template->addVar("user",$u);
  			$template->addVar("mode","UpdateEntry");
  			$template->addVar("hours", $hours);
  			$template->addVar("minutes", $minutes);
  			$template->addVar("ampm", $ampm);
  			$model=new HCCallLogModel();
  			$template->addVar("method_options",$model->getOptions("method"));  			
  			$template->addVar("intervention_options",$model->getOptions("intervention"));
  			$template->addVar("risk_options",$model->getOptions("risk"));
  			$template->addVar("migration_options",$model->getOptions("migration"));
  			$template->addVar("participation_options",$model->getOptions("participation"));
  			$template->addVar("_POST",$rec);
  			$template->addVar("company_id", $param[2]);
  			$template->addVar("user_id", $param[1]);
  		}  		
  		
  		/**
  		 * Add an entry to the database
  		 * @param $params
  		 */
  		public function InsertEntry($params){
  			if(!$_POST['patient']){
  				throw new Exception("Invalid post missing expected data!");
  			}
  			$model=new HCCallLogModel();

  			$hour = $_POST['time_hours'];
  			if ($_POST['time_ampm'] == "PM") {
  				$hour += 12;
  				if ($hour == 24) {
  					$hour = 12;
  				}
  			}
  			else {
  				if ($hour == 12) {
  					$hour = 0;
  				}
  			}
  			$_POST['contact_time'] = sprintf("%02d:%02d:00", $hour, $_POST['time_min']);

  			$err=$model->validateInfo($_POST);
  			if($err){  	
  				$sql="SELECT z_users.* FROM z_users  WHERE  z_users.id='".$this->dbOb->escape_string($model->get("patient"))."'";
  				$u=$this->dbOb->getRow($sql);
  				$u['call_log_id']=0;
  				
  				$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_entry.tpt");
  				$template->addVar("user",$u);
  				$template->addVar("mode","InsertEntry");
  				$template->addVar("method_options",$model->getOptions("method"));  			
  				$template->addVar("intervention_options",$model->getOptions("intervention"));
  				$template->addVar("risk_options",$model->getOptions("risk"));
  				$template->addVar("migration_options",$model->getOptions("migration"));
  				$template->addVar("participation_options",$model->getOptions("participation"));
  				$template->addVar("_POST",$_POST);
  				$template->addVar("err",$err);
				return $template;
  			}
  			
  			$model->insert();
  			
//  			header("Location: /HealthCoach/CallLog");
//  			exit();
			$template = $this->CallLog($params);
  			return $template;
  		}
  		
  		/**
  		 * Show Update an existing entry page 
  		 * @param $params
  		 */
  		public function UpdateEntry($params){
  			
  			if(!$_POST['patient']){
  				throw new Exception("Invalid post missing expected data!");
  			}
  			$model=new HCCallLogModel();

  			$hour = $_POST['time_hours'];
  			if ($_POST['time_ampm'] == "PM") {
  				$hour += 12;
  				if ($hour == 24) {
  					$hour = 12;
  				}
  			}
  			else {
  				if ($hour == 12) {
  					$hour = 0;
  				}
  			}
  			$_POST['contact_time'] = sprintf("%02d:%02d:00", $hour, $_POST['time_min']);
  			
  			$err=$model->validateInfo($_POST,true);
  			
  			if($err){  	
  				$sql="SELECT z_users.* FROM z_users  WHERE  z_users.id='".$this->dbOb->escape_string($model->get("patient"))."'";
  				$u=$this->dbOb->getRow($sql);  				
  				
  				$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/log_entry.tpt");
  				$template->addVar("user",$u);
  				$template->addVar("mode","UpdateEntry");
  				$template->addVar("method_options",$model->getOptions("method"));  			
  				$template->addVar("intervention_options",$model->getOptions("intervention"));
  				$template->addVar("risk_options",$model->getOptions("risk"));
  				$template->addVar("migration_options",$model->getOptions("migration"));
  				$template->addVar("participation_options",$model->getOptions("participation"));
  				$template->addVar("_POST",$_POST);
  				$template->addVar("err",$err);
				return $template;
  			}
  			
  			$model->Update();
  			
//  			header("Location: /HealthCoach/CallLog");
//				exit;
			$template = $this->CallLog($params);
  			return $template;
  		}
  		

	public function GetUserList() {
		$ajax = new Ajax();
		$src = isset($_POST['src']) ? $_POST['src'] : null;
		$str = isset($_POST['value']) ? $_POST['value'] : null;
//		if (!$src || !$str) {
		if (!$str) {
			$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
			$ajax->writeResponseXML();
			exit;
		}
		try {
			$model = new HCCallLogModel();
			$data = $model->searchUsers($str);
			$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
			$ajax->addResponseData("values", $data);
			$ajax->addResponseData("src", $src);
		} catch(Exception $e) {
			$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
		}
		$ajax->writeResponseXML();
		exit;
	}
  		/**
  		 * Search the database for users matching the given char string
  		 * @param $params
  		 */
  	public function GetUsers($params){
  		$pager=new Pager($this);;
  		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/user_search_2.tpt");
  		
  		$sql="SELECT company_name as display, id as value FROM p_company where is_active=1";  		  		
  		$comp=$this->dbOb->query($sql);

  		if(!isset($_GET['mode']) || $_GET['mode']=="log"){//on mode=log get all users
//  			$sql="SELECT * FROM z_users
//  				  JOIN p_user_health_coach AS puh ON puh.user_id=z_users.id
//  				  JOIN u_profile AS up ON up.z_user_id=z_users.id
//  		 	  	WHERE puh.health_coach_id='".$this->dbOb->escape_string($this->cred->getId())."' AND z_users.is_active=1 ";
			$sql = "SELECT * FROM z_users AS zu " .
						"JOIN u_profile AS up ON up.z_user_id = zu.id " .
						"JOIN z_user_role AS r ON r.user = zu.id " .
						"WHERE zu.is_active = 1 AND r.role = 1";
  		}else{//other wise restrict users to those who let provant look at their health info
//  			$sql="SELECT * FROM z_users
//  				  JOIN p_user_health_coach AS puh ON puh.user_id=z_users.id  			  
//  				  JOIN u_profile AS up ON up.z_user_id=z_users.id
//  		 	  	   WHERE puh.health_coach_id='".$this->dbOb->escape_string($this->cred->getId())."' AND z_users.is_active=1 AND up.allow_provant_access=1 ";
			$sql = "SELECT * FROM z_users as zu " .
						"JOIN u_profile AS up ON up.z_user_id = zu.id " .
						"JOIN z_user_role AS r ON r.user = zu.id " .
						"WHERE zu.is_active = 1 AND up.allow_provant_access = 1 AND r.role = 1";
  		}
  		$fname="";
  		$lname="";
  		$company="";

  		if(isset($_GET['first_name']) && $_GET['first_name']){
  			$fname=$_GET['first_name'];
  			$template->addVar("first_name",$_GET['first_name']);
  			$sql.=" AND zu.first_name like '%".$this->dbOb->escape_string($_GET['first_name'])."%'";
  		}
  		if(isset($_GET['last_name']) && $_GET['last_name']){
  			$lname= $_GET['last_name'];
  			$template->addVar("last_name",$_GET['last_name']);
  			$sql.=" AND zu.last_name like '%".$this->dbOb->escape_string($_GET['last_name'])."%'";
  		}
  		if(isset($_GET['company']) && $_GET['company']){
  			$company=$_GET['company'];
  			$template->addVar("company",$_GET['company']);
  			$sql.=" AND up.company_id ='".$this->dbOb->escape_string(intval($_GET['company']))."'";
  		}
  		
  		$lastName="ASC";
  		$firstName="ASC";
  		$sz=sizeof($params);  
  		$start=0;
  		if($sz>3){
  			//not valid number of arguments abort sorting!	
  		}else if($sz==0){
  			$sort="last_name/ASC/";
  			$sql.=" ORDER BY zu.last_name ASC";
  			$lastName="DESC";
  		}else if($sz==1){
  			$start=intval($params[0]);
  			$sort="last_name/ASC/";
  			$sql.=" ORDER BY zu.last_name ASC";
  			$lastName="DESC";  			
  		}else{   			
  			$skip=false;
  			$key=strtolower($params[0]);  			
  			if($key!="last_name" && $key!="first_name"){ //bad sort fields
  				$sort="last_name/ASC/";
  				$sql.=" ORDER BY zu.last_name ASC";
  				$lastName="DESC";  			
  				$start=0;
  				$skip=true;
  			}
  			
  			$dir=strtolower($params[1]);
  			if(!$skip && $dir!="asc" && $dir!="desc"){//bad sort direction
  				$sort="last_name/ASC/";
  				$sql.=" ORDER BY zu.last_name ASC";
  				$lastName="DESC";  			
  				$start=0; 
  				$skip=true; 					
  			}
  			  					
  			if(!$skip){
  				$start=($sz>2)?intval($params[2]):0;
  				$sort=$key."/".$dir."/";
  				if($key=="last_name"){
  					$lastName=($dir=="asc")?"DESC":"ASC";
  					$firstName="ASC";
  				}else{
  					$firstName=($dir=="asc")?"DESC":"ASC";
  					$lastName="ASC";			
  				}
  			}	
  		}

  		$limit=$pager->page($sql,$start);
  		$sql.=" ".$limit;


  		$records=$this->dbOb->query($sql);

  		$template->addVar("users",$records);
  		
  		$log=isset($_GET['mode'])?$_GET['mode']:null;
  		$template->addVar('querystring',"first_name=".urlencode($fname)."&last_name=".urldecode($lname)."&company=".urldecode($company)."&mode=".$log);

  		$template->addVar("orderpath",$sort);
  		
  		$sort.=($start+1);
  		$template->addVar("last_name",$lastName);
  		$template->addVar("first_name",$firstName);
  		$template->addVar('pager',$pager->getData());//pager

  		$template->addVar("company",$comp);
  		$template->addVar("_GET",$_GET);
  		
  		return $template;
  	}	

  	/**
  	 * Get user profile info  for a selected user
  	 * @param $param
  	 */
  	public function UserInfo($param){
  		$id=isset($param[0])?$param[0]:null;
  		if(!$id) throw new Exception("Invalid user id");
  		
  		$_SESSION["MASK_USER"]=$id;
  		
//  		$sql="SELECT * FROM p_user_health_coach WHERE user_id='".$this->dbOb->escape_string($id)."' AND health_coach_id='".$this->dbOb->escape_string($this->cred->getId())."'";
//  		if(!$this->dbOb->query($sql))throw new Exception("Error: User is not assigned to you, can not show details.");
  		
  		$sql="SELECT * FROM z_users  
  			  JOIN u_profile ON u_profile.z_user_id=z_users.id 
  			  WHERE z_users.id='".$this->dbOb->escape_string($id)."'";
  		$user=$this->dbOb->getRow($sql);
  		if($user["is_active"]!="1"){
  			throw new Exception("The requested user is not active or does not exist.");
  		}
  		
  		$sql="SELECT * FROM p_company_modules AS pcm 
  			  JOIN p_modules AS pm on pm.id=pcm.p_module_id 
  			  WHERE pcm.p_company_id='".$this->dbOb->escape_string($user["company_id"])."'  AND pm.type<>'KIT'";
  		$modules=$this->dbOb->query($sql);
  		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/user_info.tpt");
  		$template->addVar("modules",$modules);
  		$template->addVar("profile",$user);
  		
  		require_once (ROOT_DIR."classes/model/IFocusModel.php");
  		require_once (ROOT_DIR."classes/model/MessageModel.php");
  		require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  		require_once (ROOT_DIR."classes/model/UserTrackWaterModel.php");
  		
  		$if=new IFocusModel($id,true);
  		$template->addVar("ifocus",$if->isCompleted());
  		$c=$if->getCompleted();
  			
  		$template->addVar("completed",$c);
  		if($c){
  			$template->addVar("last_completed","Completed on ".$c[0]['display']);
  		}else{
  			$template->addVar("last_completed","In progress");
  		}
  		  		
  		$upm=new UserProfileModel($id);
  		$template->addVar("trackers",$upm->getUpdateTrackers());
  		$template->addVar("profile",$upm->getData());
  		$template->addVar("exercise_level",$upm->getExerciseLevel());
  		$template->addVar("weight_change",$upm->getWeightChange());
  		$template->addVar("bmi",$upm->getBMI());
  			
  		$utw=new UserTrackWaterModel($id);
  		$template->addVar("glasses",$utw->getGlasses());
  		
  		
  		$sql="SELECT * FROM u_home_health_screening_kit_results WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
  		$r=$this->dbOb->getOne($sql);  		
  		$template->addVar("hhsk",($r)?true:false);

  		$sql="SELECT * FROM u_lab_voucher_kit_results WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
  		$r=$this->dbOb->getRow($sql);  		
  		$template->addVar("lvsk",($r)?true:false);
  		
  		return $template;
  	}
  	
  	/**
  	 * Show page/ Change the password page for the current health coach
  	 * @param $params
  	 */
  	public function ChangePassword($params){
  		require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/changepassword.tpt");
  		if(count($_POST)){
  			$upm=new UserProfileModel($this->cred->getId());
  			$err=$upm->validatePassword($_POST);  				
  			if($err){
  				$template->addVar("errors",$err);
  			}else{
  				$err=$upm->updatePassword($_POST);
  				$template->addVar("success",1);
  			}
  		}  		
  		return $template;
  	}
  }