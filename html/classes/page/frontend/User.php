<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");  
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  require_once (ROOT_DIR."classes/model/IFocusModel.php");
  require_once (ROOT_DIR."classes/model/MessageModel.php");
  require_once (ROOT_DIR."classes/model/RiskAssessment.php");
  require_once (ROOT_DIR."classes/model/HomeHealthScreeningKitModel.php");
  require_once (ROOT_DIR."classes/model/LabVoucherKitModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackWaterModel.php"); 			
  require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
  require_once (ROOT_DIR."classes/model/HealthArticlesModel.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class User extends PageBase{
  	public function __construct(){
  		parent::__construct();
  		if (!$this->cred->getLoginStatus()) {
				header("Location: /Landing/Index");
				exit(0);
			}
  	}
  	/**
  	 * Show user home page
  	 * @param unknown_type $param
  	 */
  	public function Index($param){
			$upm = new UserProfileModel($this->cred->getId());
//		$unm = new UserNameModel($this->cred->getId());
//			$status = $unm->getUserState();
//			if ($status == "pre-loaded") {
//				$url = $unm->getCompanyRegUrl();
//				header('Location: /Register/' . $url . '/' . $this->cred->getId());
//				exit();
//			}
//			else {
  		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/landing.tpt");
			$ham = new HealthArticlesModel();
			$ha = $ham->getFeaturedArticles(5);
  		$template->addVar("articles",$ha);

			$slider2open = 1;

			$ifm = new IFocusModel($this->cred->getId(), false);
  		$c = $ifm->isCompleted();
			if ($c) {
				$assess = $ifm->getCompleted();
				$scores = $ifm->score();
				$template->addVar('Total', sprintf("%.1f", $scores['total']));
				$template->addVar('Cert', "/iFocus/Certificate/" . $assess[0]['value']);
				$template->addVar('Summary', "/iFocus/Total/" . $assess[0]['value']);
				$template->addVar('DateComplete', $assess[0]['display']);

				$slider[0] = array ('link' => "/IFocus/Index",
				                    'alt'  => "iFocus Health Assessment",
				                    'img'  => "/assets/media/images/slide-ifocus.jpg",
				                    'handle' => "handle2",
				                    'Total' => 1);
			}
			else {
				$slider[0] = array(	'link' => "/IFocus/Index",
									'alt' => "iFocus Health Assessment",
									'img' => "/assets/media/images/slide-ifocus.jpg",
									'handle' => "handle2");
			}


  		if ($c) {
  			$slider2open = 2;
				$modules = $ifm->getSuggestedModules();
				$slider[1] = array ( 'list'   => $modules,
									 'handle' => "handle3");
  		}
  		else {
  			$slider[1] = array(	'link' => "/Page/ITModules",
									'alt' => "myFOCUS",
									'img' => "/assets/media/images/slide-my-focus.jpg",
									'handle' => "handle3");
 			}
			
			/**
			@author:	aware
			@added:		12/29
			@notes:		PlaceHolder accordion handle. Just duplicating the iFocusHealthAssessment for now
			*/
			
				$slider[2] = array(	'link' => "/HealthyAchievements/Index",
							'alt' => "Healthy Achievements",
							'img' => "/assets/media/images/slide-healthy.jpg",
							'handle' => "handle4");
							
							
				$slider[3] = array(	'link' => "/Page/Trackers",
							'alt' => "iFocus Health Assessment",
							'img' => "/assets/media/images/slide-tools.jpg",
							'handle' => "handle4");			
	
			/** END **/

			$template->addVar("slider", $slider);
			$template->addVar("slider2open", $slider2open);

			// This controls the displayed link on the home page for the user.
			// If the user has not taken the iFocus assessment, then post a link
			// to that exam(1).  If the user has taken it, but has not signed up for
			// health coaching, post a link to sign up(2).  If the user has taken the
			// exam and signed up to be coached, then post a 'connect with health
			// coach' link(3).
			// If this company did not opt for health coaching support, then post
			// the 'track your progress' link(4).
			$link1 = "1";
			if ($c) {
				$link1 = "2";
				$data = $upm->GetHCAgreement($this->cred->getId());
				if ($data['hc']) {
					if ($data['data']) {
						$link1 = "3";
					}
				}
				else {
					$link1 = "4";
				}
			}
			$template->addVar("hc_link", $link1);

			return $template;
//			}
  	}
  		
  		public function Index2($param){
  			  			
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/home.tpt");

  			$if=new IFocusModel($this->cred->getId(),false);
  			
  			$c=$if->getCompleted();
  			
  			$template->addVar("completed",$c);
  			if($c){
  				$template->addVar("last_completed","Completed on ".$c[0]['display']);
  			}else{
  				
  				switch($if->getProgress()){
  					case IFocusModel::START : $progress=" Take the health assessment";break;
  					case IFocusModel::INPROGRESS : $progress=" In propgress";break;
  					case IFocusModel::INPROGRESS : $progress=" Take the health assessment";break;
  					default: $progress="In progress";
  				}
  				$template->addVar("last_completed",$progress);
  			}
  			
  			$upm=new UserProfileModel($this->cred->getId());
  			$template->addVar("trackers",$upm->getUpdateTrackers());
  			$template->addVar("profile",$upm->getData());
  			$template->addVar("exercise_level",$upm->getExerciseLevel());
  			$template->addVar("weight_change",$upm->getWeightChange());
  			$template->addvar("incentive_points",$upm->get("incentive_points_total"));
  			$gwc=$upm->getGoalWeightChange();
  			if($gwc==0) $gwc="at goal weight";
  			else if($gwc>0) $gwc=" Over by ".$gwc." lb";
  			else $gwc=" Under by ".abs($gwc)." lb";
  			$template->addVar("goal_weight_change",$gwc);
  			$wm=new UserTrackWeightModel($this->cred->getId());
  			$le=$wm->getLastEntry();
  			$template->addVar("weight",$le['weight']);
  			$template->addVar("bmi",$upm->getBMI());
  			
  			$sql="SELECT pm.* FROM p_company_modules AS pcm 
  			  JOIN p_modules AS pm on pm.id=pcm.p_module_id 
  			  WHERE pcm.p_company_id='".$this->dbOb->escape_string($upm->get("company_id"))."'";
  			$modules=$this->dbOb->query($sql);
  			$template->addVar("modules",$modules);
  		
  			$utw=new UserTrackWaterModel($this->cred->getId());
  			$template->addVar("glasses",$utw->getGlasses());
  			
  			$mm=new MessageModel();
  			$template->addVar("new_messages",$mm->getNewMesageCount());
  			
  			$utwm=new UserTrackWeightModel($this->cred->getId());
  			$rec=$utwm->getLastEntry();
  			$weight=($rec)?$rec['weight']:0;
		
			$height=($upm->get("height_ft")*12)+$upm->get("height_in");
			$bmi=round($weight/pow($height,2)*703,2);
			$ra=new RiskAssessment($this->cred->getId());
			$ris=$ra->getBMIRisk($bmi);
			if(!$ra) $bmi="BMI below 25";
			else $bmi="BMI exceeds 25";
			
  			$template->addVar("bmi_warning",$bmi);
  			
  			$hhsk=new HomeHealthScreeningKitModel();
  			$template->addVar("hhsk",$hhsk->getOrderStatus());
  			$template->addVar("hhsk_confirm_reception",$hhsk->getWaitStatus());

  			$lvk=new LabVoucherKitModel();
  			$template->addVar("lvk",$lvk->getOrderStatus());
  			$template->addVar("lvk_confirm_reception",$lvk->getWaitStatus());
  			
  			return $template;
  		}

  		
/**
 * Add glass of water to daily water tracker
 */  		
  		public function AddGlass(){
  			$utw=new UserTrackWaterModel($this->cred->getId());
  			$utw->addGlass();
  			header("Location: /user/index");
  			exit();
  		}
/**
 * remove galss of water to daily water tracker
 */  		
  		
  		public function RemoveGlass(){
  			$utw=new UserTrackWaterModel($this->cred->getId());
  			$utw->removeGlass();
  			header("Location: /user/index");
  			exit();
  		}

/**
 * Show user stats
 */  		
  		
  		public function info($param,$err=null){
  			$pm=new UserProfileModel($this->cred->getId());  			
  			$ud=$pm->getData();
  			
  			if(!sizeof($_POST)){  				
  				foreach($ud as $key=>$value){
  					$_POST[$key]=$value;
  				}
  				$_POST['email_confirm']=$_POST['email'];
  			}

  			//$teamId=$this->dbOb->escape_string($ud['team_id']);

  			$companyId=$this->dbOb->escape_string($ud['company_id']);
  			$fitnessGoalId=$this->dbOb->escape_string($ud['activity_level_id']);
  			$activityLevelId=$this->dbOb->escape_string($ud['fitness_goal_id']);
  			$languageId=$this->dbOb->escape_string($ud['language_id']);
  			
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/info.tpt");

				$stat = isset($_GET['mode']) ? $_GET['mode'] : "none";
				$template->addVar("status", $stat);
  			
  			$template->addVar("height_ft",array(2,3,4,5,6,7,8));
  			$template->addVar("height_in",array(0,1,2,3,4,5,6,7,8,9,10,11));
  			
  			$race=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_race WHERE is_active=1 ORDER BY display_order ASC");
  			$template->addVar("race",$race);

  			$locations=$this->dbOb->query("SELECT location as display, id as value FROM p_company_locations WHERE company_id=".$companyId." AND is_active=1");
  			$template->addVar("locations", $locations);

  			//$teams=$this->dbOb->query("SELECT name as display, id as value FROM p_company_teams WHERE (is_active=1 or id='".$teamId."') AND company_id='".$companyId."'");
  			//$template->addVar("teams",$teams);
  			
  			$fitness_goal=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_fitness_goal WHERE is_active=1 or id='".$fitnessGoalId."'");
  			$template->addVar("fitness_goal",$fitness_goal);
  			
  			$activity_level=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_activity_level WHERE is_active=1 or id='".$activityLevelId."'");
  			$template->addVar("activity_level",$activity_level);
  			
  			$langs=$this->dbOb->query("SELECT name as display, id as value FROM p_user_option_language WHERE is_active=1 or id='".$languageId."'");
				$other = $this->dbOb->query("SELECT language as display, (id+10) as value FROM p_other_languages WHERE is_active=1");
				$languages = array_merge($langs, $other);
  			$template->addVar("languages",$languages);
  			
  			$template->addVar("post",$_POST);
  			$template->addVar("errors",$err);
  			
  			return $template;
  		}

/**
 * Save updated profile info
 * @param unknown_type $param
 */  		
  		public function saveinfo($param){
  			$upm=new UserProfileModel($this->cred->getId());
  		 	$err=$upm->validateUserInfo($_POST);

  		 	if($err){
  		 		return $this->info($param,$err);
			}

			$upm->updateUserEntry();
			header("Location: /User/info/?mode=success");
  			exit();
  		 	
  		}
/**
 * chnage user's password
 * @param unknown_type $params
 */  		
  		public function ChangePassword($params){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/changepassword.tpt");
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
  			$template->parse();
  			exit();
  		}
/**
 * Show quit site page
 */  		
  		public function DisableAccount($param){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/disableaccount.tpt");
  			return $template;
  		}
/**
 * perform the actual disable
 * @param unknown_type $param
 */  		
  		public function SubmitDisableAccount($param){
  			/*
  			 * not sure if we need to send an actual email to anyone.
  			*/
  			/*
  			require_once(LIB_ROOT."classes/common/BlastEmailer.class.php");
  			$be=new BlastEmailer();
  		
  			$to=ADMIN_EMAIL;

  			$sUtil= new StringUtil();
  			$_POST['message']=$sUtil->sanitize_data($_POST['message'],2,null,false);
  		
  			$be->sendSimpleEmailTemplate(TEMPLATE_DIR."frontend/users/quit_email.tpt",$to, $_POST['email'], $_POST['email'],$_POST['subject'],$_POST);
  			*/
  			require_once (ROOT_DIR."classes/model/MessageModel.php");
  			$mm=new MessageModel();
  			$arr=array("to"=>1,"subject"=>"Quitting Provant","message"=>$_POST["message"]);
  			
  			$sql="SELECT * FROM p_user_health_coach WHERE user_id='".$this->dbOb->escape_string($this->cred->getId())."'";
  			$coaches=$this->dbOb->query($sql);  			
  			if($coaches){
  				foreach($coaches as $coach){
  					$arr["to"]=$coach["health_coach_id"];
  					$mm->validateInfo($arr);
  					$mm->sendMessage();
  				}
  			}  			
  			$upm=new UserProfileModel($this->cred->getId());
  			$ump->resign();
  			$this->cred->logout();
  			header("Location: /Page/Quit");
  			exit();
  		}
  		
  	public function SetIntervals($param,$err=null){
  		$pm=new UserProfileModel($this->cred->getId());
  		$ud=$pm->getData();
  			
  		if(!sizeof($_POST)){  				
  			foreach($ud as $key=>$value){
  				$_POST[$key]=$value;
  			}
  		}
  			  			  			
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/interval.tpt");
  		$template->addVar("post",$_POST);
  		$template->addVar("errors",$err);
  			
  		return $template;
  	}


	public function saveInterval($param){
			$upm=new UserProfileModel($this->cred->getId());
			$err=$upm->validateInterval($_POST);
  		 	
			if($err){
				print_r($err);
				return $this->SetIntervals($param,$err);
			}
			$upm->updateInterval();
			header("Location: /User/SetIntervals/?mode=successs");
  		exit();
	}


	public function HealthCoachOptIn($params) {

		$ifm = new IFocusModel($this->cred->getId(),false);
		if ($ifm->isCompleted()) {
			$up = new UserProfileModel($this->cred->getId());
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/user/opt-in.tpt");

			$ifscore = $ifm->score();
			if ($ifscore < .3) {
				$hrisk = "High";
			}
			else if ($ifscore < .6) {
				$hrisk = "Moderate";
			}
			else {
				$hrisk = "Low";
			}

			$calls = 20; //&&&&&  We need how this gets set...number of phone calls allowed

			$cname = $up->getCompanyName();

			$template->addVar("score", $ifscore['total']);
			$template->addVar("risk", $hrisk);
			$template->addVar("calls", $calls);
			$template->addVar("company_name", $cname);
			$template->addVar("start_date", date('M d, Y'));

			return $template;
		}
		header("Location: /User/Index");
		exit();
	}

	public function HealthCoachSignUp($params) {
		$agree = isset($_POST['agree']) ? $_POST['agree'] : "off";
		if ($agree == "on") {
			$upm = new UserProfileModel($this->cred->getId());
			$upm->AddHCAgreement ($_POST['dayphone'], $_POST['evephone'], $_POST['weekday'],
									$_POST['startdate'], $_POST['besttime'], $_POST['tz']);
		}
		else {
			throw ($e = new Exception('Agree box not checked'));
		}

		header("Location: /User/Index");
		exit();
	}
}