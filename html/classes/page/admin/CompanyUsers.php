<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

class CompanyUsers extends AdminPageBase{
  	
	private $altTableName="p_company_users";
	public function getBaseTableName(){return $this->altTableName;}	
	protected function altPermissionName(){return "P_COMPANY_USERS";}




	public function Index($param){		
		$this->SecurityCheck("READ",true,null);
		$querySort=null;

		$orderPath=null;
		$orderField=null;
		$orderDir="ASC";
		
		if(sizeof($param)>=3 && strtolower($param[0])=="sort"){      	      	  
			$orderField=$param[1];
			$dir=(trim(strtoupper($param[2]))=="ASC")?"ASC":"DESC";
			$reverse=($dir=="ASC")?"DESC":"ASC";
      	  
			$querySort=" ORDER BY `".$orderField."` ".$dir;
      	        	  		  
			$orderDir=$reverse;
			$orderPath="sort/".$orderField."/".$reverse."/";
      	  
			//reset the parameters array to the way the pager class expects them...
			//by removng the alt sort...
			$parameters=array_splice($param,3);      	        	  
		}else{
			$parameters=$param;
		}
      	      	
		$start=isset($parameters[0])?$parameters[0]:1;
      	
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a su restruct user list to that of the user's company!
			$companyId=$this->getCompanyId();

			$sql="SELECT z_users.id,z_users.first_name,z_users.last_name,z_users.login 
				  FROM u_profile 
				   JOIN z_users ON z_users.id=u_profile.z_user_id 
				   WHERE u_profile.company_id='".$this->dbOb->escape_string($companyId)."' AND z_users.is_active=1";			
		}else{ 						
			$companyId = isset($_GET['company_id']) ? trim($_GET['company_id']) : 0;
			if ($companyId > 0) {
				$comp = " AND u_profile.company_id = " . $this->dbOb->escape_string($companyId);
			}
			else {
				$comp = "";
			}
			$sql="SELECT z_users.id,z_users.first_name,z_users.last_name,z_users.login
				  FROM u_profile 
				   JOIN z_users ON z_users.id=u_profile.z_user_id 
				   WHERE z_users.is_active=1" . $comp;
		}
		
		$limit=$this->pager->page($sql,$start);
		$sql.=" ".$querySort." ".$limit;
		$records=$this->dbOb->query($sql);		

		$sql = "SELECT id AS value, company_name AS display FROM p_company WHERE is_active = 1";
		$companies = $this->dbOb->query($sql);
		array_unshift($companies, array('value' => 0, 'display' => "All companies"));
	      
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/CompanyUsers/index.tpt");
		$template->addVar('pager',$this->pager->getData());//pager
		$template->addVar('admin_ctls',$this->getAdminDefaults()&15);//edit ctrls for field object
		$template->addVar('pagename',$this->getName());//all avail fields
		$template->addVar('tablename',$this->getBaseTableName());//all avail fields				
		$template->addVar('prevent_search',"true");//edit ctrls for field object

		$template->addVar('orderpath',$orderPath);//we used an alt sort<br>
		$template->addVar('orderfield',$orderField);//we used an alt sort
		$template->addVar('orderdir',$orderDir);//we used an alt sort

		$template->addVar('fields',$records);//datbase records
		$template->addVar('companies', $companies);
		$template->addVar('company_id', $companyId);	
		return $template;
	}
	
	public function Reinstate($param,$err=null){		
		$this->SecurityCheck("READ",true,null);
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/CompanyUsers/reinstate.tpt");
		$template->addVar("errors",$err);
		$template->addVar("post",$_POST);
		return $template;
	}
	
	public function Reactivate($parma){
		$this->SecurityCheck("UPDATE",true,null);
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){
			$sql="SELECT id FROM p_company WHERE admin_user='".$this->dbOb->escape_string($this->cred->getId())."'";
			$cid=$this->dbOb->getOne($sql);
			if(!$cid) throw new Exception("User is not an administrator of a company!");
			
			$sql="SELECT * FROM z_users
				  JOIN u_profile AS up ON up.z_user_id= z_users.id 
				  WHERE z_users.login='".$this->dbOb->escape_string($_POST['email'])."' AND
				  up.company_id='".$this->dbOb->escape_string($cid)."'
				";					
		}else{
			$sql="SELECT * FROM z_users WHERE z_users.login='".$this->dbOb->escape_string($_POST['email'])."'";
		}
		$id=$this->dbOb->getOne($sql);
		if(!$id){
			$err="No such for your company could be found using the email address ".$_POST['email'];
		} 
		else{
		    $sql="UPDATE z_users set is_active=1 WHERE id='".$this->dbOb->escape_string($id)."'";
		   $this->dbOb->update($sql);
		   $err="The accout corresponding to the email address ".$_POST['email']." has been re-activated";
		}
		return $this->Reinstate($parma,$err);
	}
	
  	public function Edit($param){  		  		
  		$this->SecurityCheck("UPDATE",true,null);
  		$full=true;
  		
  		$id=isset($param[0])?$param[0]:0;
  		if(intval($id)<2) throw new Exception("Invalid user id specified.");
  		
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a su
  			$full=false;
			$companyId=$this->getCompanyId();  	
			$this->checkIsMemberOfCompany($id,$companyId);					
  		}

  		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/CompanyUsers/edit_restricted.tpt");
  		
  		$sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($id)."'";
  		$record=$this->dbOb->getRow($sql);
		$template->addVar("record",$record);
		
		$template->addVar('admin_ctls',$this->getAdminDefaults());//edit ctrls for field object
		$template->addVar('pagename',$this->getName());//all avail fields
		$template->addVar('tablename',$this->getBaseTableName());//all avail fields		
		$template->addVar('prevent_insert',"true");//edit ctrls for field object		
		$template->addVar('prevent_search',"true");//edit ctrls for field object
		$template->addVar("zActionDefault","Change");
		
		return $template;		
	}
	
  	public function Change($param){  		
  		$this->SecurityCheck("UPDATE",true,null);
  		if(!isset($_POST['z_users']['id'])){
  			throw new Exception("Invalid data passed no login name was specified.  Can not update record.");
  		}
  		$id=$_POST['z_users']['id'];
  		$_POST['z_users']['email']=$_POST['z_users']['login'];
  		$pwd=$_POST['z_users']['password'];
  		
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a su
			$companyId=$this->getCompanyId();
			$this->checkIsMemberOfCompany($id,$companyId);			
  		}
  		
  		$this->altTableName="z_users"; //this admin realy a view of the z_users table!
  		  		
  		if(!$_POST['z_users']['password']){
  			unset($_POST['z_users']['password']);
  			$pwd=null;  			
  		}else if($_POST['z_users']['password_confirm']!=$_POST['z_users']['password']){
  			throw new Exception("Password and Password Confirm do not match!");
  		}
  		  		
  		unset($_POST['z_users']['password_confirm']);  		
  		
		parent::Change($param,false);		
		
		if($pwd){
			$sql="UPDATE z_users SET `password`=PASSWORD('".$this->dbOb->escape_string($pwd)."') WHERE id='".$this->dbOb->escape_string($id)."'";
			$this->dbOb->update($sql);
		}
	
		$this->forceRedirect();		
		exit();
  	}
  	
  	public function DeleteConfirm($param){
  		$this->SecurityCheck("DELETE",true,null);
  		$sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($param[0])."'";
		$user=$this->dbOb->getRow($sql);						
		if(!$user){				
			throw new Exception("Error: User does not exist!");
		}
  		
  	    if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){
  	    	$companyId=$this->getCompanyId();
			$this->checkIsMemberOfCompany($param[0],$companyId);			
  		}
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/CompanyUsers/delete_confirm.tpt");
  		$template->addVar('pagename',$this->getName());//all avail fields
		$template->addVar('tablename',"z_users");//all avail fields				  		
		$template->addVar('user',$user);
  	}
  	
  	public function Delete($param){
  		$this->SecurityCheck("DELETE",true,null);
  		$this->altTableName="z_users";
  		  		
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){
			$companyId=$this->getCompanyId();
			$this->checkIsMemberOfCompany($_POST["z_users"]["id"],$companyId);			
  		}  		
  		parent::Delete($param,true);	
  	}
  	
  	private function getCompanyId(){
  		$sql="SELECT * FROM p_company WHERE admin_user='".$this->dbOb->escape_string($this->cred->getId())."'";
		$rec=$this->dbOb->getRow($sql);			
		if(!$rec){//curent admin is not assigned to a company!
			if($this->logger->getAutoLogging())$this->logger->log("SECURITY",$this->getName(), "Index", $this->getBaseTableName(),"0",true,true);
			throw new Exception("User is not assigned to a company!");
		}
		return $rec['id'];
  	}
  	public function checkIsMemberOfCompany($uId,$companyId){
		$sql="SELECT * FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($uId)."' AND company_id='".$this->dbOb->escape_string($companyId)."'";
		if(!$this->dbOb->getRow($sql)){//userd doesn't belong to current admin's company
			throw new Exception("User ID is not a member of the admin's company!");
		}
  	}  	
  	
  	/*
	public function Insert($param){
		$this->SecurityCheck("INSERT",true,null);
		
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/CompanyUsers/insert.tpt");		
		$template->addVar('admin_ctls',$this->getAdminDefaults());//edit ctrls for field object
		$template->addVar('pagename',$this->getName());//all avail fields
		$template->addVar('tablename',$this->getBaseTableName());//all avail fields
		$template->addVar('zActionDefault','InsertRec');
		return $template;		
	}
	
	public function InsertRec($param){		
		$this->SecurityCheck("INSERT",true,null);
		
		$sql="SELECT * FROM p_company WHERE admin_user='".$this->dbOb->escape_string($this->cred->getId())."'";
		$rec=$this->dbOb->getRow($sql);			
		if(!$rec){				
			throw new Exception("Error: Curent User is not assigned to a company!");
		}
		$companyId=$rec['id'];
		
		$_POST["login"]=$_POST["email"];		
		$this->altTableName="z_users";
				
		$id=parent::InsertRec($param,false);
		
		$this->dbOb->insert("INSERT INTO z_user_role(user,role) VALUES('".$this->dbOb->escape_string($id)."',1)");		
		
		$sql="INSERT INTO u_profile(z_user_id,p_company) VALUES ('".$this->dbOb->escape_string($id)."','".$this->dbOb->escape_string($companyId)."')";
		$this->dbOb->insert($sql);
		
		$this->forceRedirect();
	}
	*/
}