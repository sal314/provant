<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class Company extends AdminPageBase{  	
	public function getBaseTableName(){return "p_company";}	

	public function Index($param){
		$this->SecurityCheck("READ",true,null);		
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a super user but a company admin get the company id
			$companyId=$this->getCompanyId(); //get the curent user's company id 
			header("Location: /admin/Company/Edit/".$companyId);
			exit();
		}
		parent::Index($param);
	}
	
	public function Insert($param){		
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")) throw new Exception("Only Administrators Can perform this function!");
		return parent::Insert($param,TEMPLATE_DIR."admin/Company/insert.tpt");		
	}
	
	public function InsertRec($param){
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")) throw new Exception("Only Administrators Can perform this function!");
/*		$nl=$_POST["z_user"]['login'];
		$sql="SELECT * FROM z_users WHERE login='".$this->dbOb->escape_string($nl)."'";
		$rec=$this->dbOb->getRow($sql);
		if($rec){//check to see if login is not already used!
			throw new Exception("User ".$nl." is already in use. Aborting adding Company.");
		}
*/
		$cid=parent::InsertRec($param,false); //ad the company

		//add the admin for the company
/*		$sql="INSERT INTO z_users(`login`,`password`,`last_name`,`first_name`) VALUES(
		'".$this->dbOb->escape_string($_POST["z_user"]['login'])."',
		PASSWORD('".$this->dbOb->escape_string($_POST["z_user"]['password'])."'),
		'".$this->dbOb->escape_string($_POST["p_company"]['company_name'])."',
		'Administrator'
		)";
		$id=$this->dbOb->insert($sql);
		
		//add the company admin role to the new user
		
		//update the company with the user id
		$sql="UPDATE p_company SET admin_user='".$this->dbOb->escape_string($id)."' WHERE id='".$this->dbOb->escape_string($cid)."'";
		$this->dbOb->update($sql);
*/
		//add a default location based on the address1 of the company
		$location=$_POST["p_company"]["address1"];
		$this->dbOb->insert("INSERT INTO p_company_locations (is_active,company_id,location) VALUES(1,".$this->dbOb->escape_string($cid).",'".$this->dbOb->escape_string($location)."')");

		$this->forceRedirect();		
	}
	
  	public function Edit($param){
  		$full=true;
  		$this->SecurityCheck("UPDATE",true,null);
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a super admin ensure the user is the admin for the requested company
  			$full=false;
  			$companyId=$this->getCompanyId($param[0]); 			
  		}
  		else {
  			$companyId = $param[0];
  		}

  		$temp=($full)?TEMPLATE_DIR."admin/Company/edit.tpt":TEMPLATE_DIR."admin/Company/edit_restricted.tpt";
		$t=parent::Edit($param,$temp);
		
		if($full){//su can change the company admin user!
			$sql="SELECT z_users.id AS value, concat( z_users.first_name, ' ', z_users.last_name ) AS display FROM z_user_role JOIN z_users ON z_users.id = z_user_role.user WHERE z_user_role.role =4 ORDER BY z_users.last_name, z_users.first_name";
			$t->addVar("admin_users",$this->dbOb->query($sql));
		}
//		else {
			$sql="SELECT id,location FROM p_company_locations WHERE company_id=".$this->dbOb->escape_string($companyId)." AND is_active=1";
			$result=$this->dbOb->query($sql);
			if (!$result) {
				throw new Exception("Could not find locations for this company.");
			}
			$locations = array();
			foreach ($result as $row) {
				array_push($locations, $row);
			}

			$t->addVar("locations", $locations);
//		}

		return $t;		
	}
	
  	public function Change($param){  		
  		$this->SecurityCheck("UPDATE",true,null);
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//ensure that non super user is the admin for the edited company
  			$companyId=$this->getCompanyId($_POST["p_company"]["id"]);
  		}
  		parent::Change($param);
  	}
  	
  	public function ChangePassword($param){
  		//this functon is for company admin users only  	
  		if($this->cred->isAdmin() || $this->cred->has("SITE_ADMIN")) throw new Exception("Site Admin can not change their password here!");
  		$this->SecurityCheck("UPDATE",true,null);
  		$this->getCompanyId();
  		//update the admin user's password! 	
  		$sql="UPDATE z_users SET `password`=PASSWORD('".$this->dbOb->escape_string($_POST['password'])."') WHERE id='".$this->dbOb->escape_string($this->cred->getId())."'";
  		$this->dbOb->update($sql);
  		$this->forceRedirect();
  	}
  	
  	private function getCompanyId($compare=null){
  		$sql="SELECT * FROM p_company WHERE admin_user='".$this->dbOb->escape_string($this->cred->getId())."'";
		$rec=$this->dbOb->getRow($sql);			
		if(!$rec){//curent admin is not assigned to a company!
			if($this->logger->getAutoLogging())$this->logger->log("SECURITY",$this->getName(), "Index", $this->getBaseTableName(),"0",true,true);
			throw new Exception("User is not assigned to a company!");
		}
		$id=$rec['id'];
		if($compare && $id!=$compare){			
		 	if($this->logger->getAutoLogging())$this->logger->log("SECURITY",$this->getName(), "Index", $this->getBaseTableName(),"0",true,true);
			throw new Exception("User is not admin for specified company");
		}
		return $id;
  	}
  	
  	public function ListUsers($params){
  		$cid=isset($_GET['company_id'])?intval($_GET['company_id']):0;
  		if(!$cid) throw new Exception("Invalid company id value.");
  		
  		$sql="SELECT z_users.*,u_profile.id AS upid FROM u_profile
  			  JOIN z_users ON z_users.id=u_profile.z_user_id
  			  JOIN z_user_role ON z_users.id = z_user_role.user 
  			  WHERE u_profile.company_id ='".$cid."'
  			  AND z_users.is_active=1  AND u_profile.is_active=1
  			  AND z_user_role.role = 1
  			  ORDER BY last_name,first_name";  		
		$pageNum=isset($params[0])?intval($params[0]):0;
		$limit=$this->pager->page($sql,$pageNum);		
		$sql.=$limit;
		
  		$res=$this->dbOb->query($sql);
  		
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/Company/users.tpt");
		$template->addVar('pagename',$this->getName());//all avail fields
		$template->addVar('pmethod',"ListUsers");//all avail fields
		$template->addVar('pager',$this->pager->getData());//pager
		$template->addVar('records',$res);//pager
		$template->addVar('querystring',"company_id=".$cid);
		return $template;  		
  	}


  	public function addLocation($params) {
//		print_r($_POST);
//		die();
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//ensure that non super user is the admin for the edited company
  			$cid=$this->getCompanyId($_POST["company_id"]);
  		}
  		else {
  			$cid=$_POST["company_id"];
  		}

  		$location=$_POST["location"];
  		
  		$sql="SELECT * FROM p_company_locations WHERE company_id=".$this->dbOb->escape_string($cid)." AND location='".$this->dbOb->escape_string($location)."'";
  		$result=$this->dbOb->query($sql);
  		if ($result) {
  			$sql="UPDATE p_company_locations SET is_active=1, date_updated=NOW() WHERE id=".$this->dbOb->escape_string($result[0]['id']);
			$this->dbOb->update($sql);
  		}
  		else {
  			$sql="INSERT INTO p_company_locations (is_active,company_id,location) VALUES (1,".$this->dbOb->escape_string($cid).",'".$this->dbOb->escape_string($location)."')";
  			$this->dbOb->insert($sql);
  		}

  		header("Location: /admin/Company/edit/".$cid);
  		exit(0);
  	}


  	public function updateLocation($params) {
  		$cid=$_POST['company_id'];
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//ensure that non super user is the admin for the edited company
  			$this->getCompanyId($cid);
  		}
  		
		foreach($_POST['location'] as $key=>$value) {
  			$sql="UPDATE p_company_locations SET location='".$this->dbOb->escape_string($value)."', date_updated=NOW() WHERE id=".$this->dbOb->escape_string($key)." AND company_id=".$this->dbOb->escape_string($cid);
			$this->dbOb->update($sql);
		}

  		header("Location: /admin/Company/edit/".$cid);
  		exit(0);		
  	}


    public function deleteLocation($params) {
		// Get the location id that was requested
    	$lid=isset($params[0])?intval($params[0]):0;
    	if (!$lid) throw new Exception("Invalid location id value.");

		// Check to see if location exists
		$sql="SELECT * FROM p_company_locations WHERE id=".$this->dbOb->escape_string($lid);
		$result=$this->dbOb->getRow($sql);
		if (!$result) {
			throw new Exception("Location not found");
		}

		// Check to see if any users are still using this location
    	$sql="SELECT * from u_profile WHERE is_active=1 AND location_id=".$this->dbOb->escape_string($lid);
    	$row=$this->dbOb->getRow($sql);
    	if ($row) {
    		throw new Exception("There are users still assigned to this location.  Delete canceled.");
    	}

    	// Get the company id
		$cid=$result['company_id'];
  		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//ensure that non super user is the admin for the edited company
    		$this->getCompanyId($cid);
  		}

    	// Make sure there is at least one location for this company.
    	$sql="SELECT count(*) FROM p_company_locations WHERE company_id=".$this->dbOb->escape_string($cid)." AND is_active=1";
    	$result=$this->dbOb->getOne($sql);
    	if ($result <= 1) {
    		throw new Exception("This deletion would result no locations.  Delete canceled.");
    	}

    	// Mark the location as deleted.
    	$sql="UPDATE p_company_locations SET is_active=0, date_updated=NOW() WHERE id=".$this->dbOb->escape_string($lid);
    	$this->dbOb->update($sql);

  		header("Location: /admin/Company/edit/".$cid);
  		exit(0);
    }
  }
