<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");  
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  require_once (ROOT_DIR."classes/model/HomeHealthScreeningKitModel.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class HomeHealthScreeningKit extends PageBase{

  	public function __construct(){
  		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}

  		$this->myId=$this->cred->getId();
		
		if($this->cred->has("LOGIN_HEALTH_COACH")){		
				
			if(isset($_SESSION["MASK_USER"])){
				$this->myId=$_SESSION["MASK_USER"];
			}else{
				throw new Exception("Illegal access: NO User ID Specified");
			}
		}
	}
	
	/**
	 * Show results of kit page
	 * @param unknown_type $param
	 */
  		public function Index($param){
  			//$sql="SELECT * FROM u_home_health_screening_kit_results WHERE z_user_id='".$this->dbOb->escape_string($this->myId)."'";
  			//$results=$this->dbOb->getRow($sql);
  			$hhsk=new HomeHealthScreeningKitModel();
  			$results= $hhsk->getScreeningResults();
  			
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Kits/HomeHealthScreeningKit/screening_results.tpt");
  			$template->addVar("results",$results);
  			return $template;
  		}
  		
  		/**
  		 * Show kit order page
  		 * @param $param
  		 */
  		public function RequestKit($param){
			$hhsk=new HomeHealthScreeningKitModel();
  			$status=$hhsk->getOrderStatus();
  			
  			if($status==0) throw new Exception("Sorry the Home Health Screening Kit is not availble, as you company has not elected to subscribe to this option.");
  			if($status==2) throw new Exception("Your Home Health Screening Kit has already been ordered.");  		
  			if($status==3) throw new Exception("Your Home Health Screening Kit has already been ordered and delivered.");
  			$upm=new UserProfileModel($this->cred->getId());
  			if(isset($_POST['address1'])){
  				//validate values!
  				$err=$hhsk->validate($_POST);
  				if(!$err){
  					$hhsk->sendOrder();
  					//load order confimration page!
  					$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Kits/HomeHealthScreeningKit/screening_kit_ordered.tpt");
  					$template->addVar("post",$_POST);
  					return $template;
  				}
  				
  			}else{  				  			
  				$_POST['address1']=$upm->get("address1");
  				$_POST['address2']=$upm->get("address2");
  				$_POST['city']=$upm->get("city");
  				$_POST['state']=$upm->get("state");
  				$_POST['country']=$upm->get("country");
  				$_POST['zipcode']=$upm->get("zipcode");
  				$_POST['phone']="";
  				$sql="SELECT first_name,last_name,login from z_users WHERE id='".$this->dbOb->escape_string($this->cred->getId())."'";
  				$rec=$this->dbOb->getRow($sql);
  				$_POST['first_name']=$rec['first_name'];
  				$_POST['last_name']=$rec['last_name'];
  				$_POST['email']=$rec['login'];
  				$err=null;
  			}
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/Kits/HomeHealthScreeningKit/screening_kit_order_form.tpt");  			
  			$template->addVar("post",$_POST);
  			$template->addVar("errors",$err);
  			return $template;
  		}
  		
  		/**
  		 * Mark the ordered kit a received
  		 * @param $param
  		 */
  		function Received($param){
  			$sql="SELECT * FROM u_home_health_screening_kit_order WHERE z_user_id='".$this->dbOb->escape_string($this->cred->getId())."' AND is_received=0";
  			$rec=$this->dbOb->getRow($sql);
  			
  			if(!isset($_POST['recieved'])) throw new Exception("Invalid request!");
  			$recieved=($_POST['recieved']==1);
  			if($rec){
  				if($recieved){
  					$sql="UPDATE u_home_health_screening_kit_order SET is_received=1, date_updated=NOW() WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
  					$this->dbOb->update($sql);
  				}else{
  					//notify provant user has not received their kit.
  					//send e-mail to provant?
  				$date_ordered = $rec['date_added'];
  				$order_text = "Date Ordered: ";
  				if ($rec['date_updated'] != "0000-00-00 00:00:00") {
  					$date_ordered = $rec['date_updated'];
  					$order_text = "Date Reordered: ";
  				}	
$message="The following user has indicated that the Home Health Screening Kit has not arrived within 5 days of being ordered:
".$order_text.$date_ordered." 
First Name: ".$rec['first_name']."
Last Name: ".$rec['last_name']."
Address: ".$rec['address1']."
		 ".$rec['address2']."
City, State, Postal Code: ".$rec['city'].", ".$rec['state']." ".$rec['zipcode']." (".$rec['country'].")
E-mail: ".$rec['email']."
Phone: ".$rec['phone'];

				mail(ADMIN_EMAIL,"Missing Home Health Screening Kit",$message,null,"-fnoreply@provantonline.com");
  					//reset the kit order, so it will appear in the csv with the new orders
  				$sql="UPDATE u_home_health_screening_kit_order SET is_downloaded=0, is_received=0, date_updated=NOW()  WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
  				$this->dbOb->update($sql);
  				}
  			}
  			header("Location: /User/Index");
  			exit();
  		}
  }