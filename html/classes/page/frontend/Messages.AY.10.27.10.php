<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (LIB_ROOT."classes/common/Pager.class.php");
  require_once (ROOT_DIR."classes/model/MessageModel.php");
  

/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class Messages extends PageBase{
  	
  	    public function __construct(){
  	    	parent::__construct();
			if (!$this->cred->getLoginStatus()) {
				header("Location: /Landing/Index");
				exit(0);
			}
  	    	$this->message= new MessageModel();
  	    }
  	    
  	    /**
  	     * Show inbox
  	     * @param $param
  	     */
  		public function Index($param){
  			return $this->Inbox($param);
  		}  	
  			  		
  		/**
  		 * Show inbox
  		 * @param $params
  		 */
  		public function Inbox($params){  			
  			$mode=MessageModel::ALL;
  			$page=1;
  			$orderBy=MessageModel::DATESENT;
  			$dir=MessageModel::DESC;
  			
  			$s=sizeof($params);
  			switch($s){
  				case 2: 
  					$orderBy=$params[0];
  					$page=$params[1];
  					$ordpath=$params[0];
  					break;
  				case 3:
  					$orderBy=$params[0];
  					$dir=$params[1];
  					$page=$params[2];
  					$ordpath=$params[0]."/".$params[1];
  					break;
  				default: 					
  					$ordpath=$orderBy;
  					break;  					
  			}

  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				$CSSclass = "coach_message";
  			}
  			else {
  				$CSSclass = "user_message";
  			}
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/inbox.tpt");
  			$template->addVar("messages",$this->message->getMessageHeaders(MessageModel::ALL,$page,$orderBy,$dir));
  			$template->addVar("pager",$this->message->getPagerData());
  			$template->addVar("pagetype", $this->message->getPagerTypes($ordpath, "Messages"));
  			$template->addVar("pagename","Messages");
  			$template->addVar("orderpath",$ordpath);
  			$template->addVar("classname", $CSSclass);
  		}
  		
  		public function Archive($params){
  			$mode=MessageModel::ALL;
  			$page=1;
  			$orderBy=MessageModel::DATESENT;
  			$dir=MessageModel::DESC;
  			
  			$s=sizeof($params);
  			switch($s){
  				case 2: 
  					$orderBy=$params[0];
  					$page=$params[1];
  					$ordpath=$params[0];
  					break;
  				case 3:
  					$orderBy=$params[0];
  					$dir=$params[1];
  					$page=$params[2];
  					$ordpath=$params[0]."/".$params[1];
  					break;
  				default: 					
  					$ordpath=$orderBy;
  					break;  					
  			}

  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				$CSSclass = "coach_message";
  			}
  			else {
  				$CSSclass = "user_message";
  			}
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/archive.tpt");
  			$template->addVar("messages",$this->message->getArchivedMessages(MessageModel::ALL,$page,$orderBy,$dir));
  			$template->addVar("pager",$this->message->getPagerData());
  			$template->addVar("pagetype", $this->message->getPagerTypes($ordpath, "Messages"));
  			$template->addVar("pagename","Messages");
  			$template->addVar("orderpath",$ordpath);
  			$template->addVar("classname", $CSSclass);
  			
  			
  			
  			
  		}
  		
  		/**
  		 * Show sent emails
  		 * @param $params
  		 */
  		public function Sent($params){  			
  			$mode=MessageModel::ALL;
  			$page=1;
  			$orderBy=MessageModel::DATESENT;
  			$dir=MessageModel::DESC;
  			
  			$s=sizeof($params);
  			switch($s){
  				case 2: 
  					$orderBy=$params[0];
  					$page=$params[1];
  					$ordpath=$params[0];
  					break;
  				case 3:
  					$orderBy=$params[0];
  					$dir=$params[1];
  					$page=$params[2];
  					$ordpath=$params[0]."/".$params[1];
  					break;
  				default: 					
  					$ordpath="";
  					break;  					
  			}

  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				$CSSclass = "coach_message";
  			}
  			else {
  				$CSSclass = "user_message";
  			}
  			
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/sent.tpt");
  			$template->addVar("messages",$this->message->getMessageHeaders(MessageModel::SENT,$page,$orderBy,$dir));
  			
  			$template->addVar("pager",$this->message->getPagerData());
  			$template->addVar("pagename","Messages");
  			$template->addVar("orderpath",$ordpath);
  			$template->addVar("classname", $CSSclass);
  		}
  		
  		/**
  		 * Show compose email page
  		 * @param $param
  		 * @param $err
  		 */
  		public function Compose($param,$err=null){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/compose.tpt");
  			$template->addVar("is_hc",$this->cred->hasRead("LOGIN_HEALTH_COACH"));
  			$mode=isset($param[0])?$param[0]:null;
  			
  			$uid=0;
  			$from=0;
  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				if(!isset($_POST['reply_to'])&&!isset($_POST['to'])){  //if we are replying or errored on sending to an email do not reset the "to"  				  			
  					$_POST['to']=0;
  				}else if(isset($_POST['reply_to'])){
  					$sql="SELECT from FROM p_messages WHERE id='".$this->dbOb->escape_string($_POST['reply_to'])."'";
  					$from=$this->dbOb->getOne($sql);
  					if(!$from) throw new Exception("Invalid message id.");
  				}

  				if (!isset($_POST['company_id'])) {
  					$_POST['company_id'] = 0;
  				}
  				$sql = "SELECT id as value, company_name as display FROM p_company WHERE is_active = 1";
				$companies = $this->dbOb->query($sql);
				$template->addVar("companies", $companies);

  				$uid=($from>0)?$from:$_POST['to'];
  				if($uid){
  					$sql="SELECT concat(first_name,' ',last_name) FROM z_users WHERE id='".$this->dbOb->escape_string($uid)."'";
  					$name=$this->dbOb->getOne($sql);
  				}else{
  					$name="";
  				}
  				$CSSclass = "coach_message";
  				$template->addVar("to_name",$name);
  			}else{
  				//user can only send to email health coach! unless a mode flag is set
  				if(!isset($param[0]) || !$param[0]){
  					$sql="SELECT z_users.id as value, concat(z_users.first_name,' ',z_users.last_name) as display 
  						  FROM p_user_health_coach
  						  JOIN z_users ON z_users.id=p_user_health_coach.health_coach_id 
  						  WHERE p_user_health_coach.user_id='".$this->dbOb->escape_string($this->cred->getId())."'";
  					$to=$this->dbOb->query($sql);
  					if(!$to){
  						throw new Exception("Error Health Coach has not been assigned to user.");
  					}
  					$_POST['to']=0;
  					$template->addVar("to_select",$to);
  				}else{
  					$_POST['to']="1";//need appropriate admin user for each "mode" 
  				}
  				$CSSclass = "user_message";
  			}
  			$template->addVar("_POST",$_POST);
  			$template->addVar("mode",$mode);
  			$template->addVar("err",$err);
  			$template->addVar("classname", $CSSclass);
  		}
  		
  		/**
  		 * Process data from Compose form
  		 * @param $param
  		 */
  		public function Submit($param){
  			$err=$this->message->validateInfo($_POST);
  			if($err){///error in validation
  				return $this->Compose($param,$err);
  			}
  			$this->message->sendMessage();
  			header("Location: /Messages/Inbox?status=sent");
  			exit();
  		}
  		
  		/**
  		 * View an email
  		 * @param $param
  		 */
  		function View($param){
  			$mid=isset($param[0])?intval($param[0]):0;
  			if($mid==0) throw new Exception("Invalid message Id.");
  			$message=$this->message->getMessage($mid,true);
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/view.tpt");
  			$mode=($message['to']==$this->cred->getId())?"Inbox":"Sent";

  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				$CSSclass = "coach_message";
  			}
  			else {
  				$CSSclass = "user_message";
  			}
  			
  			$template->addVar("message",$message);
  			$template->addVar("mode",$mode);
  			$template->addVar("classname", $CSSclass);
  			return $template;
  		}
  		
  		/**
  		 * Reply to a received email
  		 * @param $param
  		 */
  		function Reply($param){
  			$mid=isset($param[0])?intval($param[0]):0;
  			if($mid==0) throw new Exception("Invalid message Id.");
  			$message=$this->message->getMessage($mid,true);
  			if(!$message['reply']) {
  				throw new Exception ("This message is marked 'No Reply'");
  			}
  			if($message['to']!=$this->cred->getId()){
  				throw new Exception ("Can not reply to a message that you were not the recipient of.");
  			}

  			if($this->cred->hasRead("LOGIN_HEALTH_COACH")){
  				$CSSclass = "coach_message";
  			}
  			else {
  				$CSSclass = "user_message";
  			}
  			
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/compose.tpt");
  			$_POST['subject']="RE:".$message['subject'];
  			$lines=preg_split("/\n/",$message['message']);
  			$text="\n\n".$message["sender_name"]." Wrote: \n>>  ".implode("\n>>  ",$lines);
  			$_POST['message']=$text;
  			$_POST['to']=$message['from'];
  			$_POST['sender_name']=$message['sender_name'];
  			$template->addVar("_POST",$_POST);
  			$template->addVar("mode","reply");
  			$template->addVar("classname", $CSSclass);
  			return $template;
  		}
  		
  		/**
  		 * Search for a person to send the email to
  		 */
  		public function FindRecipient($param){
			require_once(LIB_ROOT."classes/common/Ajax.class.php");
  		 	$ajax=new Ajax();
			$cid = $param[0];
  		 	$src=isset($_POST['src'])?$_POST['src']:null;
  		 	$str=isset($_POST['value'])?$_POST['value']:null;
  		 	if(!$src || !$str){
  		 		$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
  		 	 	$ajax->writeResponseXML();
  		 	 	exit;				
  		 	}
  		 	try{
				if ($cid == 0) {
  		 			$sql="SELECT concat(z_users.first_name,' ',z_users.last_name,' ',z_users.email,' [',p_company.company_name ,']') as display, concat(z_users.first_name,' ',z_users.last_name) as selected, z_users.id as value 
  		 				  FROM z_users 
  		 				  JOIN u_profile ON u_profile.z_user_id=z_users.id
  		 				  JOIN p_company ON p_company.id=u_profile.company_id
  		 				  JOIN p_user_health_coach AS hc ON hc.user_id=z_users.id
  		 				  WHERE hc.health_coach_id='".$this->dbOb->escape_string($this->cred->getId())."'
  		 				  AND concat(z_users.first_name,' ',z_users.last_name,' [',p_company.company_name ,']') like '%".$this->dbOb->escape_string($str)."%' AND z_users.is_active=1 ";
				}
				else {
					$sql = "SELECT concat(z_users.first_name,' ',z_users.last_name,' ',z_users.email,' [',p_company.company_name ,']') as display, concat(z_users.first_name,' ',z_users.last_name) as selected, z_users.id as value 
  		 				  FROM z_users 
  		 				  JOIN u_profile ON u_profile.z_user_id=z_users.id
  		 				  JOIN p_company ON p_company.id=u_profile.company_id
  		 				  JOIN p_user_health_coach AS hc ON hc.user_id=z_users.id
  		 				  WHERE hc.health_coach_id='".$this->dbOb->escape_string($this->cred->getId())."'
  		 				  AND p_company.id = '".$this->dbOb->escape_string($cid)."'
  		 				  AND concat(z_users.first_name,' ',z_users.last_name,' [',p_company.company_name ,']') like '%".$this->dbOb->escape_string($str)."%' AND z_users.is_active=1 ";
				}
  		 		$data=$this->dbOb->query($sql);
  		 		$ajax->addResponseMessage("Success",Ajax::SUCCESS,"");
  		 		$ajax->addResponseData("values",$data);
  		 		$ajax->addResponseData("src",$src);
  		 	}catch(Exception $e){
				$ajax->addResponseMessage("Error",Ajax::ERROR,$e->getMessage());
  		 	}
			$ajax->writeResponseXML();
			exit;
  		}

  		/**
  		 * Delete a message
  		 */
  		public function Delete($param) {
  			$mid=isset($param[0])?intval($param[0]):0;
  			if($mid==0) throw new Exception("Invalid message Id.");
			$mode=isset($param[1])?$param[1]:"Inbox";
  			$ret=$this->message->deleteMessage($mid, $this->cred->getId());
			if (!$ret) {
				throw new Exception("Delete message failed");
			}
			if ($mode == "Inbox") {
	  			header("Location: /Messages/Index");
  				exit(0);
			}
			else {
				header("Location: /Messages/Sent");
				exit(0);
			}
  		}

/*
 * New code to support distribution list maintenance (for health coaches)
 *

		/*
		 *  Add a user to a distribution list
		 *  
		public function AddUserToList($param) {

		}

		/*
		 *  Delete a user from a distribution list
		 *  
		public function DeleteUserFromList($param) {

		}

		/*
		 * Show the details of a distribution list
		 *
		public function EditDistributionList($param) {

		}

		/*
		 * Show a list of distribution lists
		 * 
  		public function DistributionList($param) {
			$dlists = $this->message->getDistLists($this->cred->getId());
			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/messages/distribution.tpt");
			$template->addVar("dlists", $dlists);
			return $template;
		}

		/*
		 * Create a new distribution list
		 * 
		public function CreateDistributionList ($param) {
			if (isset($_POST[name])) {
				$name = $_POST[name];
				$list = $_POST[ulist];
				$this->message->createDistList($name, $list);
				header("Location: /Messages/DistributionList");
				exit(0);
			}
			else {
				throw new Exception("No name given for distribution list");
			}
		}
*/
  }