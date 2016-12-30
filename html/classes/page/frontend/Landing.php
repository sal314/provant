<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
	require_once (ROOT_DIR."classes/model/UserNameModel.php");

/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
class Landing extends PageBase{
  	
  	/**
  	 * Show login page
  	 * @param unknown_type $param
  	 */
	public function Index($param){        	
		global $_SYSTEM;

		if($this->cred->getLoginStatus()){
			$this->redirect();
		}

		$path = "/var/www/provant/html/uploads/image/";

		$templateEngine=TemplateParser::create(TEMPLATE_DIR."frontend/login/login.tpt");

		$unm = new UserNameModel();
		$cid = $unm->getCompanyId();
		$_SYSTEM['company_id'] = $cid;
		$sql = "SELECT login_image, file_logo FROM p_company WHERE id = " . $cid . " AND is_active = 1";
		$img = $this->dbOb->getRow($sql);
		if ($img['login_image']) {
			$templateEngine->addVar('login_img', $img['login_image']);
		}
		else {
			$templateEngine->addVar('login_img', "");
		}

		if ($img['file_logo']) {
			$size = getimagesize($path . $img['file_logo']);
			if ($size[1] > 75) {
				$ht = 75;
			}
			else {
				$ht = $size[1];
			}
			$templateEngine->addVar('logo', $img['file_logo']);
			$templateEngine->addVar('logo_height', $ht);
		}
		else {
			$templateEngine->addVar('logo', "");
		}

		$templateEngine->addVar('authenticated',0);         
		$templateEngine->addVar('error_message','');
		$templateEngine->addVar('login','');
		$templateEngine->addVar('modelname','admin');
		TemplateQueue::enqueue($templateEngine);
		return $templateEngine;
	}
     
	/**
	 * Send user to appropriate home page when they log in
	 */
	private function redirect(){
		//send admins to the admin area        
		if($this->cred->hasRead("ADMIN_LOGIN")){
			header("Location: /admin/zAdmin");
			exit();
		}

		//health coach send them to their area
		if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
			header("Location: /HealthCoach/Index");
			exit();
		}

		//send users to the user area
		if($this->cred->hasRead("LOGIN_USER")){
			
			$company = "SELECT p.url FROM p_company as p 
						INNER JOIN u_profile as u ON p.id = u.company_id
						WHERE u.z_user_id = ".$this->cred->getId();
			$url = $this->dbOb->query($company);
			
			
			$sql = "SELECT count(*) FROM u_profile WHERE z_user_id = " . $this->cred->getId() . " AND status like 'pre-loaded'";
			$is_preloaded = $this->dbOb->getOne($sql);
			
			header("Location: " . ($is_preloaded ? '/Register/'.$url[0]['url'].'/'.$this->cred->getId() : '/User/Index') );
			//header("Location: " . ($is_preloaded ? '/Register/'.$url[0]['url'] : '/User/Index') );
			exit();
		}
	}   

	/**
	 * Send user to PrivacyPolicy page
	 */
	public function PrivacyPolicy($param){
  		global $_SYSTEM;

		$path = "/var/www/provant/html/uploads/image/";

		$templateEngine=TemplateParser::enqueue(TEMPLATE_DIR."frontend/login/privacypolicy.tpt");

		$unm = new UserNameModel();
		$cid = $unm->getCompanyId();
		$_SYSTEM['company_id'] = $cid;
		$sql = "SELECT login_image, file_logo FROM p_company WHERE id = " . $cid . " AND is_active = 1";
		$img = $this->dbOb->getRow($sql);
		if ($img['login_image']) {
			$templateEngine->addVar('login_img', $img['login_image']);
		}
		else {
			$templateEngine->addVar('login_img', "");
		}

		if ($img['file_logo']) {
			$size = getimagesize($path . $img['file_logo']);
			if ($size[1] > 75) {
				$ht = 75;
			}
			else {
				$ht = $size[1];
			}
			$templateEngine->addVar('logo', $img['file_logo']);
			$templateEngine->addVar('logo_height', $ht);
		}
		else {
			$templateEngine->addVar('logo', "");
		}

		$templateEngine->addVar('authenticated',0);         
		$templateEngine->addVar('error_message','');
		$templateEngine->addVar('login','');
		$templateEngine->addVar('modelname','admin');
		TemplateQueue::enqueue($templateEngine);
		return $templateEngine;
  	}

	/**
	 * attempt to log a user in to the admin
	 * @param array URL params
	 * @param string alterbnate template
	 */
	public function Login($param,$alt=null){
		global $_SYSTEM;
		//name not case sensitive but password is.
		//global $show_admin; //this is a global var that is used determine if the admin stuff should be shown

		//
		// get the company id based on the Host
		//
		$unm = new UserNameModel();
		$cid = $unm->getCompanyId();
		$_SYSTEM['company_id'] = $cid;

		$error_message=null;                        		
		$authenticated=false;

		if($this->cred->getLoginStatus()){
			$this->redirect();
		}

		$query="SELECT z.*, r.name AS role_name FROM z_users AS z " .
						"JOIN z_user_role AS ur ON z.id = ur.user " .
						"JOIN z_roles AS r ON ur.role = r.id " .
						"WHERE  STRCMP(TRIM(LCASE(login)),LCASE(TRIM('".$this->dbOb->escape_string($_POST['login'])."')))=0 AND password=PASSWORD('".mysql_escape_string($_POST['password'])."')";

		$rec=$this->dbOb->getRow($query);
		if($rec && $rec['is_active']){
			if(isset($rec['id']))
				$this->cred->setId($rec['id']);
			else
				$this->cred->setId($rec['login']);

			if($_POST['login']=='admin'){//root user
				$this->cred->setIsAdmin(true);
				$this->cred->setLoginStatus(true);
				$this->loadPermissions();
				$authenticated=true;
			}else{//normal user
				if ($rec['role_name'] == "COMPANY_USER") {
					$sql = "SELECT count(*) FROM u_profile WHERE z_user_id = " . $rec['id'] . " AND company_id = " . $cid;

					$stat = $this->dbOb->getOne($sql);
					if ($stat == 0) {
						$authenticated = false;
						$this->cred->setLoginStatus(false);
						$error_message="Your username/password was incorrect. Please try again. Or, contact the administrator ";
					}
					else {
						$this->loadPermissions();
						$this->cred->setLoginStatus(true);
						$authenticated=true;
					}
				}
				else {
					$this->loadPermissions();
					//is a company admin
					if($this->cred->has("ADMIN_SUPERUSER")){//super user
						$this->cred->setIsAdmin(true);
					}
					if($this->cred->has("ADMIN_LOGIN") || 
					   $this->cred->has("LOGIN_USER") || 
					   $this->cred->has("LOGIN_HEALTH_COACH")){
						$this->cred->setLoginStatus(true);
						$authenticated=true;
					}else{
						$authenticated=false;
						$this->cred->setLoginStatus(false);
						$error_message="This account has been suspended. Please contact the administrator";
					}
				}
			}

			if($authenticated){
				$count = $rec["login_count"] + 1;
				$sql="UPDATE z_users set last_login=NOW(), login_count=".$this->dbOb->escape_string($count)." WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
				$this->dbOb->update($sql);                      	
				$this->cred->save();
				if($this->cred->getReturnToAfterLogin()!=""){
					header("Location: ".$this->cred->getReturnToAfterLogin());
					$this->cred->setReturnToAfterLogin("");
					$this->cred->save();
					exit();
				}

				$this->redirect();
				$error_message="This account has been suspended. Please contact the administrator";
			}

		}else{
			$this->cred->setLoginStatus(false);
			if(!$rec)
				$error_message="Your username/password was incorrect. Please try again. Or, contact the administrator ";
			else 
				$error_message="This account has been disabled. Please contact the administrator for further assistance."; 
		}
                
		$this->cred->save();//update the sesison

		$template=TemplateParser::create(TEMPLATE_DIR."frontend/login/login.tpt");

		$log=($this->cred->getLoginStatus())?"success":"fail";
		if($this->logger->getAutoLogging())
			$this->logger->log("LOGIN",$this->getName(),"Submit","users",0,$log,false);

		$path = "/var/www/provant/html/uploads/image/";

		$sql = "SELECT login_image, file_logo FROM p_company WHERE id = " . $cid . " AND is_active = 1";
		$img = $this->dbOb->getRow($sql);
		if ($img['login_image']) {
			$template->addVar('login_img', $img['login_image']);
		}
		else {
			$template->addVar('login_img', "");
		}

		if ($img['file_logo']) {
			$size = getimagesize($path . $img['file_logo']);
			if ($size[1] > 75) {
				$ht = 75;
			}
			else {
				$ht = $size[1];
			}
			$template->addVar('logo', $img['file_logo']);
			$template->addVar('logo_height', $ht);
		}
		else {
			$template->addVar('logo', "");
		}

		$template->addVar('authenticated',$authenticated);
		$template->addVar('initial_admin',defined("DEFAULT_ADMIN")?DEFAULT_ADMIN:"");
		$template->addVar('error_message',$error_message);
		$template->addVar('login','');
		$template->addVar('modelname','admin');
		TemplateQueue::enqueue($template);
		return;
	}

	/**
	 * load their permisisons
	 * @param object Database.class object
	 */
	protected function loadPermissions(){        	
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
	 * log a user out
	 * @param $param
	 */
	public function logout($param){
		$this->cred->logout(); //log them out
		return $this->Index($param); //send them back to the login screen        
	}
        
 
 
 	public function ContactUs($params){
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/login/contactus.tpt");
		return $template;
	}


	/**
	 * Show forgot password form
	 * @param $param
	 * @param $err
	 */
	public function ForgotPassword($param,$err=null){
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/login/forgot_password.tpt");
		$template->addVar("error",$err);
		$template->addVar("post",$_POST);
		return $template;
	}


	/**
	 * Send forgot password reminder
	 * @param $param
	 */
	public function SendReminder($param){
		$email=$_POST['email'];
		$sql="SELECT * FROM z_users WHERE login='".$this->dbOb->escape_string($email)."'";
		$record=$this->dbOb->getRow($sql);
		if($record && !$record["is_active"]){
			return $this->ForgotPassword($param,"No active user with the email ".$email."cound be found. Please contact your company administrator to activate the account.");	
		}else if(!$record){
			return $this->ForgotPassword($param,"No with the email ".$email."cound be found. Please check the email address and try again.");
		}
  			
		$sUtil= new StringUtil();
		$lid=$sUtil->createGUID();
		$sql="INSERT INTO p_password_reset (link_id,z_user) VALUES('".$this->dbOb->escape_string($lid)."','".$this->dbOb->escape_string($record['id'])."')";
		$this->dbOb->insert($sql);  			
  			
		require_once(LIB_ROOT."classes/common/BlastEmailer.class.php");
		$be=new BlastEmailer();  		
		$to=$email;
		$sUtil= new StringUtil();
		$_POST['link']=$lid;
		$be->sendSimpleEmailTemplate(TEMPLATE_DIR."frontend/login/password_reminder.tpt",$to, ADMIN_EMAIL, "Provant Admin","Login Reminder",$_POST);

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/login/password_sent.tpt");
		return $template;
	}


	/**
	 * Reset password page (followed link from reminder email)   
	 * @param unknown_type $param
	 */
	public function reset($param){
		$id=isset($param[0])?$param[0]:0;
		$sql="SELECT * FROM p_password_reset WHERE link_id='".$this->dbOb->escape_string($id)."' AND is_active=1 AND DATEDIFF(date_added,NOW())>-2";
		$rec=$this->dbOb->getRow($sql);
		if(!$rec) throw new Exception("This link has expired.");
		$sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($rec['z_user'])."'";
		$usr=$this->dbOb->getRow($sql);
		if($usr['is_active']==0){
			throw new Exception("Sorry but the password can not be reset as the account has been de-activated please contact your plan administrator to re-activae your account");
		}

		//let user enter a new user password must pass along the link id!
		$pass=true;
		if(!isset($_POST['password'])){//no password show the reset password form
			$err="";
			$pass=false;
		}
		else if($_POST['password']!=$_POST['password_confirm']){
			$err="Password and Confirm Password must match.";
			$pass=false;
		}else if(strlen($_POST['password'])<6){
			$err="Password muct be atleast 6 characters long";
			$pass=false;
		}
		if(!$pass){
			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/login/password_reset.tpt");
			$template->addvar("link",$id);
			$template->addvar("error_message",$err);
			return $template;
		}
		//password was sent reset it and log the user in.
		$sql="UPDATE z_users SET `password`=PASSWORD('".$this->dbOb->escape_string($_POST['password'])."'),last_login=NOW() WHERE id='".$this->dbOb->escape_string($rec['z_user'])."'";
		$this->dbOb->update($sql);

		$sql="UPDATE p_password_reset set is_active=0 WHERE link_id='".$this->dbOb->escape_string($id)."'";
		//need to create the login object now!

		$this->cred->setId($rec['z_user']);
		$this->cred->setLoginStatus(true);                    	
		if($_POST['login']=='admin'){//root user                    	
			$this->cred->setIsAdmin(true);
		}

		$this->loadPermissions();
		if($this->cred->has("ADMIN_SUPERUSER")){//super user
			$this->cred->setIsAdmin(true);
		}

		$this->cred->save();

		$this->redirect();
		throw new Exception("User does not have any valid credentials!");	        
	}
	
/**
	 * Change user status from pre-loaded to active (user accept privacy policy) 
	 * @param unknown_type $param
	 */
	public function AcceptPrivacy($param){

		$sql="UPDATE u_profile SET `status`='active' WHERE z_user_id = " . $this->cred->getId();
		$this->dbOb->update($sql);
			        
		header("Location: /User/Index");
		exit();
	}
	
}
