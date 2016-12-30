<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
require_once (LIB_ROOT."classes/common/Pager.class.php");
require_once (LIB_ROOT."classes/common/StringUtil.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");

class MessageModel{
	 const ALL=1;
	 const UNREAD=2;
	 const READ=3;
	 const SENT=4;
	 const DELETED=5;
	 
	 const DATESENT=1;
	 const SUBJECT=2;	 
	 const SENDERD=3;
	 
	 const ASC=1;
	 const DESC=2;
	 
	 const STATUS_READ=1;
	 const STATUS_DELETE=2;
	 
	 private $dbOb=null;
	 private $cred=null;
	 private $pagerData=null;
	 
	 public function __construct(){
	 	$this->dbOb=Database::create();
	 	$this->vc= new Validator();
	 	$this->cred=UserCredentials::load();	 	
	 }
	 
	 /**
	  * Validate form data
	  * @param array $arr
	  */
	 public function validateInfo($arr){
	 $err=null;
		try{
    		$this->data['to']=$this->vc->exists('to',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['subject']=$this->vc->exists('subject',$arr,"text",array("min_length"=>1,"max_length"=>100),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['message']=$this->vc->exists('message',$arr,"text",array("min_length"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	return ($err)?$err:false; 
	 }
	 
	 /**
	  * "send" the validate message
	  */
	 public function sendMessage($reply=true){
		$this->createMessage($this->data['to'],$this->data['subject'],$this->data['message'],$reply);	 	
	 }	 	
	 
	 /**
	  * Save the messgae to the database. 
	  * @param int $to
	  * @param string $subject
	  * @param string $message
	  */
	 public function createMessage($to,$subject,$message,$reply){	 		 	
	 	$to=$this->dbOb->escape_string($to);
	 	$from=$this->dbOb->escape_string($this->cred->getId());
	 	
	 	$message=StringUtil::sanitize_data($message,StringUtil::STRIP_ALL_TAGS,null,true);
	 	$subject=StringUtil::sanitize_data($subject,StringUtil::STRIP_ALL_TAGS,null,true);

	 	$sql="INSERT INTO p_messages(`to`,`from`,`subject`,`message`,`reply`) VALUES
	 		('".$to."','".$from."','".$subject."','".$message."','".$reply."')";
	 	$this->dbOb->insert($sql);

		// Send an email notification if the recipient is a Health Coach
	 	$sql="SELECT zr.name FROM z_roles AS zr, z_users AS zu, z_user_role AS zur ".
	 			"WHERE zu.id=".$to." AND zu.id=zur.user AND zr.id=zur.role AND zu.is_active=1 AND zr.is_active=1";
		$roles=$this->dbOb->query($sql);
		if ($roles) {
			foreach($roles as $role) {
				if (strcasecmp($role['name'], "HEALTH_COACH") == 0) {
					$this->notifyCoach($to);
					break;
				}
			}
		}
	 }

	 /**
	  * Get the message headers for the inbox/sent/deleted 
	  * @param int $mode
	  * @param int $start
	  * @param int $orderby
	  * @param int $dir
	  * @return array
	  */
	 public function getMessageHeaders($mode=1,$start=1,$orderby=1,$dir=1){
	 	
	 	$sqlSelect="SELECT p.*,concat(sender.first_name,' ',sender.last_name) as sender_name, sender.id as sender_id,
	 					   concat(recipient.first_name,' ',recipient.last_name) as recipient_name, recipient.id as recipient_id
	 				FROM p_messages AS p
	 				JOIN z_users as sender ON sender.id=p.from
	 				JOIN z_users as recipient ON recipient.id=p.to
	 				WHERE p.is_active=1 AND ";
					if($mode==1){
						$sqlSelect .= "p.is_archived=0 AND ";
					}
	 	switch($mode){
	 		case MessageModel::ALL:
	 			$sqlWhere="p.to='".$this->dbOb->escape_string($this->cred->getId())."' AND p.recipient_status!=".MessageModel::DELETED;
	 			break;	 			
	 		case MessageModel::UNREAD:
	 			$sqlWhere="p.recipient_status=".MessageModel::UNREAD." AND p.to='".$this->dbOb->escape_string($this->cred->getId())."' ";
	 			break;
	 		case MessageModel::READ:
	 			$sqlWhere="p.recipient_status=".MessageModel::READ." AND p.to='".$this->dbOb->escape_string($this->cred->getId())."' ";
	 			break;
	 		case MessageModel::SENT://do we want an automatic delete? Only show the messages for the last 30 days?
	 			$sqlWhere="p.sender_status=".MessageModel::SENT." AND p.from='".$this->cred->getId()."' ";
	 			break;
	 		case MessageModel::DELETED:
	 			$sqlWhere=" ((p.to='".$this->cred->getId()."' AND p.recipient_status='".MessageModel::DELETED."') OR
	 						 (p.from='".$this->cred->getId()."' AND p.sender_status='".MessageModel::DELETED."'))";
	 			break;
	 	}
	 	
	 	switch($orderby){
	 		case MessageModel::SUBJECT:  $sqlOrder=" ORDER BY p.subject ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;	 		
	 		case MessageModel::DATESENT: $sqlOrder=" ORDER BY p.date_added ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;	 		
	 		case MessageModel::SENDER:   $sqlOrder=" ORDER BY sender_name ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;
	 	}
	 	
	 	$sql=$sqlSelect.$sqlWhere.$sqlOrder;
	 	
	 	$p=new Pager();
	 	$limit=$p->page($sql,$start);
	 	$this->pagerData=$p->getData();
	 	$sql.=" ".$limit;
	 	return $this->dbOb->query($sql);
	 }
	 
	  public function getArchivedMessages($mode=1,$start=1,$orderby=1,$dir=1){
	 	
	 	$sqlSelect="SELECT p.*,concat(sender.first_name,' ',sender.last_name) as sender_name, sender.id as sender_id,
	 					   concat(recipient.first_name,' ',recipient.last_name) as recipient_name, recipient.id as recipient_id
	 				FROM p_messages AS p
	 				JOIN z_users as sender ON sender.id=p.from
	 				JOIN z_users as recipient ON recipient.id=p.to
	 				WHERE p.is_active=1 AND p.is_archived=1 AND ";
	 	switch($mode){
	 		case MessageModel::ALL:
	 			$sqlWhere="p.to='".$this->dbOb->escape_string($this->cred->getId())."' AND p.recipient_status!=".MessageModel::DELETED;
	 			break;	 			
	 		case MessageModel::UNREAD:
	 			$sqlWhere="p.recipient_status=".MessageModel::UNREAD." AND p.to='".$this->dbOb->escape_string($this->cred->getId())."' ";
	 			break;
	 		case MessageModel::READ:
	 			$sqlWhere="p.recipient_status=".MessageModel::READ." AND p.to='".$this->dbOb->escape_string($this->cred->getId())."' ";
	 			break;
	 		case MessageModel::SENT://do we want an automatic delete? Only show the messages for the last 30 days?
	 			$sqlWhere="p.sender_status=".MessageModel::SENT." AND p.from='".$this->cred->getId()."' ";
	 			break;
	 		case MessageModel::DELETED:
	 			$sqlWhere=" ((p.to='".$this->cred->getId()."' AND p.recipient_status='".MessageModel::DELETED."') OR
	 						 (p.from='".$this->cred->getId()."' AND p.sender_status='".MessageModel::DELETED."'))";
	 			break;
	 	}
	 	
	 	switch($orderby){
	 		case MessageModel::SUBJECT:  $sqlOrder=" ORDER BY p.subject ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;	 		
	 		case MessageModel::DATESENT: $sqlOrder=" ORDER BY p.date_added ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;	 		
	 		case MessageModel::SENDER:   $sqlOrder=" ORDER BY sender_name ".(($dir==MessageModel::ASC)?"ASC":"DESC");break;
	 	}
	 	
	 	$sql=$sqlSelect.$sqlWhere.$sqlOrder;
	 	
	 	$p=new Pager();
	 	$limit=$p->page($sql,$start);
	 	$this->pagerData=$p->getData();
	 	$sql.=" ".$limit;
	 	return $this->dbOb->query($sql);
	 }
	 
	 
	 public function getPagerData(){
	 	return $this->pagerData;
	 }

	 /*
	  * This method creates HTML elements for the bottom of the message list
	  * allowing to change pages (via an <a>) or just display the current page (<span>)
	  */
	 public function getPagerTypes($ord, $pg) {
	 	$ret = array();
	 	if ($ord == "") {
	 		$ord = "1";
	 	}
		for ($i = $this->pagerData['first_page']; $i <= $this->pagerData['last_page']; $i++) {
			if ($this->pagerData['current_page'] == $i) {
				$ret[$i] = "<span class=\"currentPage\">" . $i . "</span>";
			}
			else {
				$ret[$i] = "<a href=\"/" . $pg . "/Index/" . $ord . "/" . $i . "\">" . $i . "</a>";
			}
		}
		return $ret;
	 }
	 
	 /**
	  * Get a message
	  * @param int $id
	  * @param boolean $markAsRead\
	  * @return array
	  */
	 public function getMessage($id,$markAsRead=false){
	 	$sql="SELECT p.*,concat(sender.first_name,' ',sender.last_name) as sender_name, sender.id as sender_id,
				     concat(recipient.first_name,' ',recipient.last_name) as recipient_name, recipient.id as recipient_id
	 		  FROM p_messages AS p
	 		  JOIN z_users as sender ON sender.id=p.from
	 		  JOIN z_users as recipient ON recipient.id=p.to 
	 		  WHERE p.id='".$this->dbOb->escape_string($id)."' AND (p.to='".$this->dbOb->escape_string($this->cred->getId())."' OR p.from='".$this->dbOb->escape_string($this->cred->getId())."') AND p.is_active=1";
	 	$msg=$this->dbOb->getRow($sql);
	 	if($msg && $msg['recipient_status']=='0' && $msg['to']==$this->cred->getId() && $markAsRead){//only mark as read if it was asked, wasn't already read or deleted and was sent to me
	 		$sql="UPDATE p_messages SET recipient_status=".MessageModel::READ.",date_updated=NOW() WHERE id='".$this->dbOb->escape_string($msg['id'])."'";
	 		$this->dbOb->update($sql);	 		
	 	}
	 	return $msg;
	 }
	 
	 /**
	  * Delete a message 
	  * @param unknown_type $id
	  */
	 public function deleteMessage($mid, $id){
	 	$sql="SELECT `to`,`from` FROM p_messages WHERE id='".$this->dbOb->escape_string($mid)."'";
	 	$rec=$this->dbOb->getRow($sql);
	 	if($rec['to']==$id) $status="recipient";
	 	else if($rec['from']==$id) $status="sender";
	 	else throw new Exception("Message does not exist, aborting delete");
	 	
	 	$sql="UPDATE p_messages set `".$status."_status`=".MessageModel::DELETED." WHERE id='".$this->dbOb->escape_string($mid)."'";
	 	return $this->dbOb->update($sql);
	 }
	 
	  /**
	  * Archive a message 
	  * @param unknown_type $id
	  */
	 public function archiveMessage($mid, $id){
	 	$sql="SELECT `to`,`from`,`recipient_status` FROM p_messages WHERE id='".$this->dbOb->escape_string($mid)."'";
	 	$rec=$this->dbOb->getRow($sql);
	 	if($rec['to']==$id) $status="recipient";
	 	else if($rec['from']==$id) $status="sender";
	 	else throw new Exception("Message does not exist, aborting archive");
	 	
		$msgState="";
		if ($status == "recipient") {
			if ($rec['recipient_status'] == 0) {
				$msgState = "recipient_status = " . MessageModel::UNREAD;
			}
		}
	 	$sql="UPDATE p_messages set `is_archived`=1 " . $msgState . " WHERE id='".$this->dbOb->escape_string($mid)."'";
	 	return $this->dbOb->update($sql);
	 }
	 
	 /**
	  * Get the number of unread messages for the user
	  * @return int
	  */
	 public function getNewMesageCount(){
	 	$sql="SELECT count(*) FROM p_messages WHERE `to`='".$this->dbOb->escape_string($this->cred->getId())."' AND recipient_status=0";
	 	$count=$this->dbOb->getOne($sql);
	 	return $count?$count:0;
	 }

	 /**
	  * Send an email notification to the health coach
	  */
	 public function notifyCoach($id) {
	 	$sql="SELECT email FROM z_users WHERE id=".$id." AND is_active=1";
	 	$to=$this->dbOb->getOne($sql);
	 	if ((!$to) || ($to == "")) {
	 		return false;
	 	}

	 	$subj = "Message notification";
	 	$from = "noreply@".DOMAIN_NAME;
	 	$replyto = "webmaster@".DOMAIN_NAME;

	 	$msg = "<html>
  <head>
    <title>Message notification</title>
  </head>
  <body>
    <br />
    A message has been sent to your ".DOMAIN_NAME." inbox. <a href=\"http://".DOMAIN_NAME."/Landing/Index\">Login now.</a>
    <br />
  </body>
</html>
";

	 	$headers = "MIME-Version: 1.0\r\n";
	 	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	 	$headers .= "To: Health Coach <".$to.">\r\n";
	 	$headers .= "From: NoReply <".$from.">\r\n";

	 	return mail($to, $subj, $msg, $headers, "-f".$replyto);
	 }
}