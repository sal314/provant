<?php
  require_once(LIB_ROOT."classes/base/PageBase.class.php");  
  require_once(LIB_ROOT."classes/common/Validator.class.php");
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class Page extends PageBase{
  	
  	
  public function Index($param,$err=null){}
  private function redirectIfNotLoggedIn(){
  	if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
	}
  }	
  	
  public function ITModules($param){
  		$this->redirectIfNotLoggedIn();
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ITModules/landing.tpt");
  		$id=$this->cred->getID();
  		$sql="SELECT company_id FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
  		$cid=$this->dbOb->getOne($sql);
  		$sql="SELECT p_modules.* FROM p_company_modules 
  			  JOIN p_modules on p_company_modules.p_module_id=p_modules.id 
  			  WHERE p_company_id='".$this->dbOb->escape_string($cid)."' AND p_modules.type='IT'";

  		$itModules=$this->dbOb->query($sql);
  		
		$template->addVar("modules",$itModules);
  		return $template;  		
  	}
  /**
  	 * Show Kits available/status page
  	 * @param $params
  	 */
  	public function Kits($params){  	
  		$this->redirectIfNotLoggedIn();
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Kits/landing.tpt");
  		$id=$this->cred->getID();
  		$sql="SELECT company_id FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
  		$cid=$this->dbOb->getOne($sql);
  		$sql="SELECT p_modules.* FROM p_company_modules 
  			  JOIN p_modules on p_company_modules.p_module_id=p_modules.id 
  			  WHERE p_company_id='".$this->dbOb->escape_string($cid)."' AND p_modules.type='KIT'";
  		
  		$itModules=$this->dbOb->query($sql);
  		
  		$template->addVar("modules",$itModules);
  		
  		require_once (ROOT_DIR."classes/model/HomeHealthScreeningKitModel.php");
  		require_once (ROOT_DIR."classes/model/LabVoucherKitModel.php");
  		
  		$hhsk=new HomeHealthScreeningKitModel();
  			$template->addVar("hhsk",$hhsk->getOrderStatus());  			
  			$template->addVar("hhsk_confirm_reception",$hhsk->getWaitStatus());

  		$lvk=new LabVoucherKitModel();
  			$template->addVar("lvk",$lvk->getOrderStatus());
  			$template->addVar("lvk_confirm_reception",$lvk->getWaitStatus());
  			
  		return $template;  		
  	}
  	
  /**
 * Display the landing page with the various trackers
 */  		
  	public function Trackers(){
  		$this->redirectIfNotLoggedIn();
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/index.tpt");
  		return $template;  			
  	}
  	
  	/**
  	 * Show tersm page
  	 * @param $params
  	 */
  	public function Terms($params){  	
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='Terms'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		return $template;
  	}
  	
  	/**
  	 * Show privacy page
  	 * @param $params
  	 */
  	
    public function Privacy($params){  	
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='Privacy'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		return $template;
  	}  		  		

    public function Legal($params){  	
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='Legal'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		return $template;
  	}  		  		

  	public function Help($params){
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='Help'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		return $template;
  	}  		 

  	public function Topic($params){
  		$this->redirectIfNotLoggedIn();
  		$page=isset($params[0])?$params[0]:"";
  		$page=$this->dbOb->escape_string($page);
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='".$page."'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		return $template;
  	}  		 
  	
  	public function popUp($params){
  		$this->redirectIfNotLoggedIn();
  		$page=isset($params[0])?$params[0]:"";
  		$page=$this->dbOb->escape_string($page);
  		$template=TemplateParser::create(TEMPLATE_DIR."frontend/pages/popup_page.tpt");
  		$sql="SELECT page FROM `p_site_pages` WHERE `page_type`='".$page."'";
  		$template->addVar("page",$this->dbOb->getOne($sql));
  		$template->parse();
  		exit();
  	}  
  	
  	/**
  	 * Show contact us page
  	 * @param $params
  	 */
  	
  	public function ContactUs($params){
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/contactus.tpt");
  		return $template;
  	}
  	
  	/**
  	 * Send contactus email request
  	 * @param $params
  	 */
  	
  	public function SubmitContactUs($params){
  		require_once(LIB_ROOT."classes/common/BlastEmailer.class.php");
  		$be=new BlastEmailer();
  		
  		$to=ADMIN_EMAIL;

  		$sUtil= new StringUtil();
  		$_POST['message']=$sUtil->sanitize_data($_POST['message'],2,null,false);
  		
  		$be->sendSimpleEmailTemplate(TEMPLATE_DIR."frontend/pages/email.tpt",$to, $_POST['email'], $_POST['email'],$_POST['subject'],$_POST);
  		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/messagesent.tpt");
  		return $template;
  	}
  	
  	public function HealthyHabits($param){
  		$this->redirectIfNotLoggedIn();
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/pages/healthy_habits.tpt");
  		
  		$id=$this->cred->getID();
  		$sql="SELECT company_id FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
  		$cid=$this->dbOb->getOne($sql);
  		
  		$sql="SELECT * FROM p_incentive_program WHERE company_id='".$this->dbOb->escape_string($cid)."' AND NOW()>start_date AND (end_date='0000-00-00' OR end_date>NOW()) ORDER BY start_date ";
  		$program=$this->dbOb->getRow($sql);
  		
  		if(!$program){
  			$sql="SELECT * FROM p_incentive_program WHERE company_id='".$this->dbOb->escape_string($cid)."' ORDER BY start_date ";
  			$program=$this->dbOb->getRow($sql);   
  			$template->addVar("active",false); 					
  		}else{
  			$template->addVar("active",true);
  		}
  		
  		if($program){
  			$sql=  "SELECT * FROM p_incentive_triggers AS pit 
  		  			JOIN p_incentive_activity AS pia ON pia.id=pit.incentive_activity_id
  		  			WHERE pit.incentive_program_id='".$this->dbOb->escape_string($program['id'])."' ORDER BY points ASC";
  		  	$activities=$this->dbOb->query($sql);
  		  	$template->addVar("program",$program);  			
  		}
  		$template->addVar("program",$program);
  		$template->addVar("activities",$activities);
		return $template;  		
  	}
 }