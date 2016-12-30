<?php
require_once ($_SERVER['DOCUMENT_ROOT']."/system/configuration.php");
require_once("/var/www/default/zmedia_3.0/lib/classes/common/Database.class.php");
require_once (ROOT_DIR."classes/model/EmailModel.php");
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/ModuleBreakITModel.php");


class CronJob {
	
	protected $dbOb;
	
	public function __construct(){
		$this->dbOb=Database::create();	
	}
	
	public function users(){
		
		$sql = "SELECT z.id FROM z_users z
				INNER JOIN z_user_role zr
				ON z.id = zr.user
				WHERE z.is_active = 1
				AND zr.role = 1
				";
		

		$result = $this->dbOb->query($sql);
		
		$itmodule = array("u_module_breakit","u_module_controlit","u_module_loseit","u_module_moveit","u_module_reduceit");
		
		foreach ($itmodule as $module){
		
				foreach ($result as $k => $v){
		
			
			
			
				 $this->update_week($module,$v['id']);
			
			
			
		}
		}
		
		
	}
	
	public function update_week($itmodule,$id){
		
		$sql = "SELECT * FROM ".$itmodule." WHERE z_user_id = ".$id;
		$result = $this->dbOb->query($sql);
		
		$time = date("Y-m-d",strtotime("-5 days"));
		$three_weeks = date("Y-m-d",strtotime("-21 days"));
		$reminder = date("Y-m-d",strtotime("-18"));
		
		$body = "You can continue working on next week.";
		
		$completed_week = $result[0]['last_completed'];
				
		switch ($completed_week) {
			case '1':
				if ($time == ($result[0]['week1_start'])){
					
				$email = $this->useremail($id);
			
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,$body,'Continue On Week 2 for '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				if ($reminder == ($result[0]['week1_start'])){
					
				$email = $this->useremail($id);
			
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,'You have three days left before we discontinue you from program','Reminder to Continue on your '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				
				if ($three_weeks == ($result[0]['week1_start'])){
					
					$sql = "UPDATE ".$itmodule." SET 
							week1_start = '0000-00-00', 
							week2_start = '0000-00-00', 
							last_completed = '0'
							WHERE z_user_id = ".$id;
					
					$this->dbOb->update($sql);
					
					$email = $this->useremail($id);
					
					$sendmail = new EmailModel();
				
					$sendmail->sendMail($email,'You have been discontinued from program','You have been discontinue from '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				break;
				
			case '2':
				if ($time == ($result[0]['week2_start'])){
				
				$email = $this->useremail($id);
				
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,$body,'Continue On Week 3 for '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				
				if ($reminder == ($result[0]['week2_start'])){
					
				$email = $this->useremail($id);
			
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,'You have three days left before we discontinue you from program','Reminder to Continue on your '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				
				if ($three_weeks == ($result[0]['week2_start'])){
					
					$sql = "UPDATE ".$itmodule." SET 
							week1_start = '0000-00-00', 
							week2_start = '0000-00-00', 
							week3_start = '0000-00-00', 
							last_completed = '0' 
							WHERE z_user_id = ".$id;
					
					$this->dbOb->update($sql);
					
					$email = $this->useremail($id);
					
					$sendmail = new EmailModel();
				
					$sendmail->sendMail($email,'You have been discontinued from program','You have been discontinue from '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				break;
				
			case '3':
				if ($time == ($result[0]['week3_start'])){
				
				$email = $this->useremail($id);
				
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,$body,'Continue On Week 4 for '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				
				if ($reminder == ($result[0]['week3_start'])){
					
				$email = $this->useremail($id);
			
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,'You have three days left before we discontinue you from program','Reminder to Continue on your '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				
				if ($three_weeks == ($result[0]['week3_start'])){
					
				$sql = "UPDATE ".$itmodule." SET 
						week1_start = '0000-00-00', 
						week2_start = '0000-00-00', 
						week3_start = '0000-00-00', 
						week4_start = '0000-00-00', 
						last_completed = '0' 
						WHERE z_user_id = ".$id;
					
					$this->dbOb->update($sql);
					
					$email = $this->useremail($id);
					
					$sendmail = new EmailModel();
				
					$sendmail->sendMail($email,'You have been discontinued from program','You have been discontinue from '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				break;
				
				
			case '4':
			if ($time == ($result[0]['week4_start'])){
				
				$email = $this->useremail($id);
				
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,$body,'Continue On Week 5 for '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				
				if ($reminder == ($result[0]['week4_start'])){
					
				$email = $this->useremail($id);
			
				$sendmail = new EmailModel();
				
				$sendmail->sendMail($email,'You have three days left before we discontinue you from program','Reminder to Continue on your '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
				}
				
				if ($three_weeks == ($result[0]['week4_start'])){
					
					$sql = "UPDATE ".$itmodule." SET 
							week1_start = '0000-00-00', 
							week2_start = '0000-00-00', 
							week3_start = '0000-00-00', 
							week4_start = '0000-00-00', 
							week5_start = '0000-00-00', 
							last_completed = '0' 
							WHERE z_user_id = ".$id;
					
					$this->dbOb->update($sql);
					
					
					$email = $this->useremail($id);
					
					$sendmail = new EmailModel();
				
					$sendmail->sendMail($email,'You have been discontinued from program','You have been discontinue from '.$this->string_replace($itmodule).' ','support@provantonline.com','Provant Support');
					
					
				}
				break;
		}	
		
	}
	
	public function useremail($userid){
		
		$sql = "SELECT email FROM z_users WHERE id = ".$userid." AND is_active = 1";
		
		$result = $this->dbOb->getOne($sql);
		
		return $result;
		
	}
	
	public function string_replace($val){
		
		$val = str_replace(array("u_module_breakit","u_module_controlit","u_module_loseit","u_module_moveit","u_module_reduceit"),
						  array("BREAK IT PROGRAM","CONTROL IT PROGRAM","LOSE IT PROGRAM","MOVE IT PROGRAM","REDUCE IT PROGRAM"),
						  $val);
	
	
	return $val;
	}
	
	
	
	
}



?>