<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");  
  require_once(LIB_ROOT."classes/common/Validator.class.php");
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class Register extends PageBase{
  	
  	/**
  	 * Start of the registration process
  	 * @param unknown_type $param
  	 * @param unknown_type $err
  	 */
  	public function Index($param,$err=null){
		// If already logged in, Landing.php will redirect them to the right page
  		if ($this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}

  		$company=isset($param[0])?$param[0]:"";
			$zid = isset($param[1]) ? $param[1] : 0;
			if ($zid != 0) {
				// Load the user info from the pre-loaded data
				// into the template
			}

  		$sql="SELECT * FROM p_company WHERE trim(url) like trim('".$this->dbOb->escape_string($company)."')";
  		$c=$this->dbOb->getRow($sql);
  		if(!$c || !$c['is_active']){
  			throw new Exception404("Sorry, but the requested company is not a Provant subscriber.");
  		}

			if ($_SERVER['HTTP_HOST'] != $c['host']) {
				throw new Exception404("Sorry, but the requested company was not found.");
			}

  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/register.tpt");
  		$template->addVar("company",$company);
			$template->addVar("logo", $c['file_logo']);

			$path = "/var/www/provant/html/uploads/image/";
			if ($c['file_logo']) {
				$size = getimagesize($path . $c['file_logo']);
				if ($size[1] > 75) {
					$ht = 75;
				}
				else {
					$ht = $size[1];
				}
			}
			else {
				$ht = 75;
			}
			$template->addVar("logo_height", $ht);

			if (!file_exists("/var/www/provant/html/assets/css/colors/color_scheme_" . $c['id'] . ".css")) {
				$css = "/assets/css/colors/color_scheme_" . $c['id'] . ".css";
			}
			else {
				$css = "/assets/css/green.css";
			}
			$template->addVar("css", $css);

  		$template->addVar("height_ft",array(2,3,4,5,6,7,8));
  		$template->addVar("height_in",array(0,1,2,3,4,5,6,7,8,9,10,11));

  		$race=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_race WHERE is_active=1 ORDER BY display_order ASC");
  		$template->addVar("race",$race);

  		$fitness_goal=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_fitness_goal WHERE is_active=1");
  		$template->addVar("fitness_goal",$fitness_goal);

			$months = array(array('value' => 0, 'display' => "- select month -"),
											array('value' => 1, 'display' => "January"),
											array('value' => 2, 'display' => "February"),
											array('value' => 3, 'display' => "March"),
											array('value' => 4, 'display' => "April"),
											array('value' => 5, 'display' => "May"),
											array('value' => 6, 'display' => "June"),
											array('value' => 7, 'display' => "July"),
											array('value' => 8, 'display' => "August"),
											array('value' => 9, 'display' => "September"),
											array('value' => 10, 'display' => "October"),
											array('value' => 11, 'display' => "November"),
											array('value' => 12, 'display' => "December"));

			if (isset($_POST['dob_month'])) {
				$m = $_POST['dob_month'];
				if ($m == 1) {
					$nd = 31;
				}
				else if ($m == 2) {
					if (isset($_POST['dob_year'])) {
						$full = $_POST['dob_year'] . "-01-01";
						if ((date('L', strtotime($full))) == 1) {		//Leap year check
							$nd = 29;
						}
						else {
							$nd = 28;
						}
					}
					else {
						$nd = 28;
					}
				}
				else if ($m == 3) {
					$nd = 31;
				}
				else if ($m == 4) {
					$nd = 30;
				}
				else if ($m == 5) {
					$nd = 31;
				}
				else if ($m == 6) {
					$nd = 30;
				}
				else if ($m == 7) {
					$nd = 31;
				}
				else if ($m == 8) {
					$nd = 31;
				}
				else if ($m == 9) {
					$nd = 30;
				}
				else if ($m == 10) {
					$nd = 31;
				}
				else if ($m == 11) {
					$nd = 30;
				}
				else if ($m == 12) {
					$nd = 31;
				}
				else {
					$nd = 0;
				}
			}
			else {
				$nd = 0;
			}

			$days = array();
			for ($i = 0; $i <= $nd; $i++) {
				if ($i == 0) {
					$disp = "--";
				}
				else {
					$disp = $i;
				}
				$val = $i;
				array_push($days, array('value' => $val, 'display' => $disp));
			}

			$curr = date("Y");
			$start = $curr - 15;	// as young as 15 years old
			$end = $curr - 100;		// as old as 100 years old
			$years = array();
			array_push($years, array('value' => 0, 'display' => "- select year -"));
			for ($i = $start; $i >= $end; $i--) {
				array_push($years, array('value' => $i, 'display' => $i));
			}
			$template->addVar("months", $months);
			$template->addVar("days", $days);
			$template->addVar("years", $years);

  		$activity_level=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_activity_level WHERE is_active=1");
  		$template->addVar("activity_level",$activity_level);

  		$locations = $this->dbOb->query("SELECT p_company_locations.location as display, p_company_locations.id as value " .
  							"FROM p_company_locations " .
  							"WHERE company_id ='".$this->dbOb->escape_string($c['id'])."' ".  							
  							"AND is_active=1");

  		$template->addVar("locations",$locations);

  		$template->addVar("post",$_POST);
  		$template->addVar("errors",$err);
  		return $template;
  	}
  		
  	/**
  	 * Add a new user to the system
  	 * @param $param
  	 */
  	public function AddUser($param){  		  	  			
  			$company=isset($param[0])?$param[0]:"";
  			$sql="SELECT * FROM p_company WHERE trim(url) like trim('".$this->dbOb->escape_string($company)."')";
  			$c=$this->dbOb->getRow($sql);
  			if(!$c || !$c['is_active']){
  				throw new Exception404("Sorry, but the requested company is not a Provant subscriber.");
  			}
  			if(!count($_POST)){
  				header("Location: /Register/Index/".$company);
    			exit();	
  			}
  			
  			//return $this->Index($param);
  			$upm=new UserProfileModel(0);
  			$err=$upm->validateUserRegistration($_POST,$c);
  			
  			if($err){
  				return $this->Index($param,$err);
  			}
  			
  			$upm->registerUser();
  			
  			header("Location: /User/Index");
    		exit();  			
  		}
  		
  		/**
  		 * Show the EULA (perhapse this should me moved to the Page.php class)
  		 * @param $param
  		 */
  		public function get_eula($param){
  			$template=TemplateParser::create(TEMPLATE_DIR."frontend/user/eula.tpt");
  			$template->parse();
  			exit();
  		}
  		
  		
  }