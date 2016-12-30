<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (ROOT_DIR."classes/model/UserTrackWorkoutModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
	require_once (LIB_ROOT.'classes/common/Ajax.class.php');
	require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');


/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
class WorkoutPlan extends PageBase{

	private $myId = 0;
	private $wpm = null;
	private $utm = null;

	/**
	 * Show all work out plans
	 * @param $param
	 */
	public function __construct(){
		parent::__construct();
		if($this->cred->has("LOGIN_HEALTH_COACH")){
			if(isset($_SESSION["MASK_USER"])){
				$this->myId=$_SESSION["MASK_USER"];
			}else{
				throw new Exception("Illegal access: NO User ID Specified");
			}
		}else{
			$this->myId=$this->cred->getId();
		}

		$this->wpm = new UserTrackWorkoutModel($this->myId);
		$this->utm = new UserTrackerModel();

	}

		public function Index($param){
			if (!$this->cred->getLoginStatus()) {
				header("Location: /Landing/Index");
				exit(0);
			}
		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/index.tpt");
  		$template->addVar("uPlans",$this->wpm->getUserPlans());
  		$status = isset($_GET['status']) ? $_GET['status'] : "";
 			$template->addVar("status", $status);

			$track = $this->utm->getLinks();
			$template->addVar('tracker', $track);
  		return $template;
  	}


		/**
		* Display details of a user's workout plan
		*
		*/
		public function Display($param) {
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/plan_display.tpt");
			$planID = $_POST['ExPlanID'];
			$template->addVar('ExPlanID', $planID);

//			$planName = $this->wpm->getUserPlanName($planID);
//			$template->addVar('ExPlanName', $planName);

			$details = $this->wpm->getUserPlanDetails($planID);
			$vsize = ($details['vsize'] + 21 + 4 + 3) * 20;			//Estimated vertical size (#lines * 20 px/line)
			$template->addVar('vSize', $vsize);

			$exlog = $this->wpm->getExerciseLog(date('Y-m-d'));
			foreach ($details['results'] as &$day) {
				foreach($day['ex'] as &$act) {
					$act['logged'] = 0;
				}
			}
			if (count($exlog) > 0) {
				foreach ($exlog as $ex) {
					foreach ($details['results'] as &$day) {
						foreach($day['ex'] as &$act) {
							if ($ex['p_workout_exercises_id'] == $act['id']) {
								$act['logged'] = 1;
							}
						}
					}
				}
			}
			$template->addVar('ExPlan', $details['results']);

			$track = $this->utm->getLinks();
			$template->addVar('tracker', $track);

			return $template;
		}


		/**
		* Create a new user define workout plan
		*
		*/
		public function Create($param) {
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/create.tpt");
//			$epName = isset($_POST['UserExPlanName']) ? trim($_POST['UserExPlanName']) : "";
//			if ($epName == "") {
//				throw new Exception('Missing information - new plan name');
//			}

			$plans = $this->wpm->getAllPlans();
			$cat = $this->wpm->getPlanCategories();
			array_unshift($cat, array('value' => "0", 'display' => "- Select a category -"));
			$lev = $this->wpm->getPlanLevels();
			array_unshift($lev, array('value' => "0", 'display' => "- Select a level -"));

			$template->addVar('ePlans', $plans);
			$template->addVar('PlanCategory', $cat);
			$template->addVar('PlanLevel', $lev);
//			$template->addVar('UserPlanName', $epName);
  		$template->addVar("uPlans",$this->wpm->getUserPlans());

			$track = $this->utm->getLinks();
			$template->addVar('tracker', $track);

			return $template;
		}


		/**
		 * Get a list of pre-defined workout plans
		 *
		 */
		public function GetPlan() {
			$ajax = new Ajax();
			$src = isset($_POST['src']) ? $_POST['src'] : null;
			$str = isset($_POST['value']) ? $_POST['value'] : null;
			if (!$str) {
				$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
				$ajax->writeResponseXML();
				exit;
			}
			try {
				$model = new UserTrackWorkoutModel($this->myId);
				$data = $model->getCannedPlan($str);
				$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
				$ajax->addResponseData("values", $data);
				$ajax->addResponseData("src", $src);
			} catch(Exception $e) {
				$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
			}
			$ajax->writeResponseXML();
			exit;
		}



		/**
		 * Save the user's workout plan
		 *
		 */
		public function SaveUserPlan($param) {
			$wpID = $this->wpm->saveWorkoutPlan('add', 0, $_POST);
			$ret = "?status=success";
			if ($wpID == 0) {
				$ret = "?status=failure";
			}

			header("Location: /WorkoutPlan/Index" . $ret);
			exit;
		}



		/**
		 * Return the details of an exersice plan for display in an iFrame
		 *
		 */
		public function ShowPlan($param) {
			$template = TemplateParser::create(TEMPLATE_DIR."frontend/workoutplan/show_plan.tpt");
			$planID = isset($param[0]) ? $param[0] : "";
			$template->addVar('ExPlanID', $planID);

			$planName = $this->wpm->getUserPlanName($planID);
			$template->addVar('ExPlanName', $planName);

			$details = $this->wpm->getUserPlanDetails($planID);
			
			$exerciselog = "";
			$exerciselogged = "";
			
			
			
			$template->addVar('ExPlan', $details['results']);
			
			

			$template->parse();
			exit();
		}
		
		public function GetexerciseId($id,$date){
			
			$sql = "SELECT * FROM  p_workout_exercises AS ut
					INNER JOIN  u_tracker_exercises AS pw
					ON ut.id = pw.p_workout_exercises_id
					WHERE ut.is_active = 1 
					AND pw.is_active = 1 
					AND ut.id = ".$id." 
					AND pw.z_user_id = ".$this->myId. " 
					AND pw.date_entered = '".$date."' 
					";
			
			$result = $this->dbOb->query($sql);
			
			return $result;
		}

		/**
		 * Get the user's supplemental exercises
		 *
		 */
		public function Supplemental($param) {
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/supplemental.tpt");
			$err = isset($_GET['err']) ? $_GET['err'] : "";
			if ($err == 'noid') {
				$template->addVar('error', "Missing input parameter - Exercise identifier");
			}
			$ex = $this->wpm->getSupplementalExercises();
			$template->addVar('sExs', $ex);
			return $template;
		}


		/**
		 * Delete a supplemental exercise from the user's plan
		 *
		 */
		public function DeleteFromPlan($param) {
			if (isset($param[0])) {
				$seID = $param[0];
			}
			else {
				header('Location: /WorkoutPlan/Supplemental?err=noid');
				exit();
			}

			$this->wpm->deleteExerciseFromPlan($seID);
			header('Location: /WorkoutPlan/Supplemental');
			exit();
		}


		/**
		 * Log exercise main page - display the requested date's log
		 *
		 */
		public function ExerciseLog($param) {
			$date_entered = isset($_POST['date_entered']) ? $_POST['date_entered'] : (isset($_GET['date_entered']) ? $_GET['date_entered'] : date('Y-m-d'));
			$POST['date_entered'] = $date_entered;
			$tm = strtotime($date_entered);
			$POST['disp_date'] = date('M d, Y', $tm);

			$wtm = new UserTrackWeightModel($this->myId);
			$last_wt = $wtm->getLastEntry();											//User's last recorded weight
			$wt = $last_wt['weight'] / 2.2;												//Kilograms

			$exercises = $this->wpm->getExerciseLog($date_entered);

			$METs = array('stretch' => 2.5, 'upper body' => 8.0, 'lower body' => 8.0, 'core' => 8.0);
			$cals = 0;
			$strength = array();
			$cardio = array();
			if (count($exercises) > 0) {
				foreach($exercises as $ex) {
					if ($ex['category'] == 'cardio') {
						$cals += (($ex['METs'] * 3.5 * $wt) / 200.0) * $ex['duration'];
						array_push($cardio, $ex);
					}
					else {
						$dur = ($ex['sets'] * $ex['reps'] * 3.0) / 60.0;			//Assume 3 seconds per repetition
						$cals += (($METs[$ex['category']] * 3.5 * $wt) / 200.0) * $dur;
						array_push($strength, $ex);
					}
				}
			}

			$cc = $this->wpm->getComments($date_entered);

			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/ex_log.tpt");
			$template->addVar("POST", $POST);
			$template->addVar("calories", sprintf("%.1f", $cals));
			$template->addVar("exCount", count($exercises));
			$template->addVar("cardio", $cardio);
			$template->addVar("strength", $strength);
			$template->addVar("comment", $cc['comment']);

			$track = $this->utm->getLinks();
			$template->addVar('tracker', $track);

			return $template;
		}


		/**
		 * Add an exercise to the log
		 *
		 */
		public function AddExerciseLog($param) {
			$ex = array('id' => isset($_POST['ExID']) ? $_POST['ExID'] : 0,
			            'sets' => isset($_POST['Sets']) ? $_POST['Sets'] : 0,
			            'reps' => isset($_POST['Reps']) ? $_POST['Reps'] : 0,
			            'METs' => isset($_POST['METs']) ? $_POST['METs'] : 0,
			            'duration' => isset($_POST['Dur']) ? $_POST['Dur'] : 0,
			            'category' => isset($_POST['Category']) ? $_POST['Category'] : "",
			            'supplemental' => isset($_POST['Custom']) ? $_POST['Custom'] : 0,
			            'weight' => 0,
			            'date_entered' => isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d'));

			$this->wpm->saveExerciseLog('add', 0, $ex);

			header("Location: /WorkoutPlan/ExerciseLog?date_entered=" . $ex['date_entered']);
			exit();
		}


		/**
		 * Update Sets/Reps/Duration in the exercise log
		 *
		 */
		public function UpdExerciseLog($param) {
			$exID = isset($_POST['ExID']) ? $_POST['ExID'] : 0;
			$log = array();
			$log['date_entered'] = isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d');
			$log['category'] = isset($_POST['Category']) ? $_POST['Category'] : "";
			$log['supplemental'] = isset($_POST['Custom']) ? $_POST['Custom'] : 0;
			if ($log['category'] == 'cardio') {
				$log['METs'] = isset($_POST['METs']) ? $_POST['METs'] : 0;
				$log['duration'] = isset($_POST['Dur']) ? $_POST['Dur'] : 0;
				$log['sets'] = 0;
				$log['reps'] = 0;
				$log['weight'] = 0;				
			}
			else {
				$log['sets'] = isset($_POST['Sets']) ? $_POST['Sets'] : 0;
				$log['reps'] = isset($_POST['Reps']) ? $_POST['Reps'] : 0;
				$log['weight'] = isset($_POST['Weight']) ? $_POST['Weight'] : 0;
				$log['METs'] = 0;
				$log['duration'] = 0;
			}

			$this->wpm->saveExerciseLog('upd', $exID, $log);
			header("Location: /WorkoutPlan/ExerciseLog?date_entered=" . $log['date_entered']);
			exit();
		}


		/**
		 * Delete an entry from the exercise log
		 *
		 */
		public function DelExerciseLog($param) {
			$exID = isset($_POST['ExID']) ? $_POST['ExID'] : 0;
			$ldate = isset($_POST['date_entered']) ? $_POST['date_entered'] : date('Y-m-d');
			$log = array('supplemental' => isset($_POST['Custom']) ? $_POST['Custom'] : 0);
			$this->wpm->saveExerciseLog('del', $exID, $log);
			header("Location: /WorkoutPlan/ExerciseLog?date_entered=" . $ldate);
			exit();
		}


		/**
		 * Get a list of exercises that match the input parameter
		 *
		 */
		public function GetList($param) {
			$ajax = new Ajax();
			$src = isset($_POST['src']) ? $_POST['src'] : null;
			$str = isset($_POST['value']) ? $_POST['value'] : null;
			if (!$str) {
				$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
				$ajax->writeResponseXML();
				exit;
			}
			try {
				$data = $this->wpm->searchExercises($str);
				$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
				$ajax->addResponseData("values", $data);
				$ajax->addResponseData("src", $src);
			} catch(Exception $e) {
				$ajax->addResponseMessage("Error", Ajax::ERROR, $e->getMessage());
			}
		$ajax->writeResponseXML();
		exit;
	}

	public function AddCustomExercise($param) {
		$status = isset($_GET['error']) ? "Error adding supplemental exercise<br />" . $_GET['error'] : (isset($_GET['success']) ? "Successfully added supplemental exercise" : "");
		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/custom.tpt");
		$template->addVar("status", $status);

		$categories = array(array ('value' => '0', 'display' => '- Select a category -'),
		                    array ('value' => 'cardio', 'display' => 'cardio'),
		                    array ('value' => 'core', 'display' => 'core'),
		                    array ('value' => 'lower body', 'display' => 'lower body'),
		                    array ('value' => 'upper body', 'display' => 'upper body'),
		                    array ('value' => 'stretch', 'display' => 'stretching'));
		$template->addVar("categories", $categories);

		$days = array(array('value' => 1, 'display' => 1),
		              array('value' => 2, 'display' => 2),
		              array('value' => 3, 'display' => 3),
		              array('value' => 4, 'display' => 4),
		              array('value' => 5, 'display' => 5),
		              array('value' => 6, 'display' => 6),
		              array('value' => 7, 'display' => 7));
		$template->addVar("days", $days);

		return $template;
	}


	public function InsertCustom($param) {
		$name = isset($_POST['name']) ? trim($_POST['name']) : "";
		$cat = isset($_POST['category']) ? trim($_POST['category']) : "";
		$sets = isset($_POST['sets']) ? trim($_POST['sets']) : 0;
		$reps = isset($_POST['reps']) ? trim($_POST['reps']) : 0;
		$wt = isset($_POST['weight']) ? trim($_POST['weight']) : 0;
		$dur = isset($_POST['duration']) ? trim($_POST['duration']) : 0;
		$METs = isset($_POST['METs']) ? trim($_POST['METs']) : 0;
		$day = isset($_POST['day']) ? trim($_POST['day']) : 1;

		$ex = array('name' => $name,
		            'category' => $cat,
		            'sets' => $sets,
		            'reps' => $reps,
		            'weight' => $wt,
		            'duration' => $dur,
		            'METs' => $METs,
		            'day' => $day);

		try {
			$this->wpm->addSupplemental($ex);
		}
		catch (Exception $e) {
			header("Location: /WorkoutPlan/AddCustomExercise?error=" . $e->getMessage());
			exit();
		}
		header("Location: /WorkoutPlan/AddCustomExercise?success=true");
		exit();
	}


  	/**
  	 * Show description for an exercise
  	 * @param $param
  	 */
		public function ExerciseDescription($param){
  		$template=TemplateParser::create(TEMPLATE_DIR."frontend/workoutplan/ex_description.tpt");
  		$sql="SELECT * FROM p_workout_exercises WHERE id='".$this->dbOb->escape_string($param[0])."'";
  		$template->addVar("exercise",$this->dbOb->getRow($sql));
  		$template->parse();
  		exit();
  	}


  	/**
  	 * Add a plan to the user's registered plan
  	 * @param $param
  	 */
		public function AddPlan($param){
  		$sql="SELECT * FROM p_workout_plans WHERE id='".$this->dbOb->escape_string($param[0])."'";
  		$plan=$this->dbOb->getRow($sql);
  		if(!$plan)
  			throw new Exception("No such plan! Cannot add.");

  		$id=$this->myId;
  		$sql="SELECT * FROM u_workout_plan_subscriptions WHERE z_user_id='".$this->dbOb->escape_string($id)."' AND p_workout_plan_id='".$this->dbOb->escape_string($param[0])."'";
  		$rec=$this->dbOb->getRow($sql);
  		if($rec && $rec['is_active']==1){
  			throw new Exception("The user is already subscribed to this plan!");
  		}else if($rec){
  			$today = date('Y-m-d') . " 00:00:00";
  			$sql="UPDATE u_workout_plan_subscriptions SET is_active=1, date_updated='".$today."' WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
  			$this->dbOb->update($sql);
  		}else{
  			$sql="INSERT INTO u_workout_plan_subscriptions(z_user_id,p_workout_plan_id) VALUES ('".$this->dbOb->escape_string($id)."','".$this->dbOb->escape_string($param[0])."')";
  			$this->dbOb->insert($sql);
  		}
  		header("Location: /WorkoutPlan/Index");
  		exit();
  	}


  	/**
  	 * Remove a plan from the user's registered plans
  	 * @param unknown_type $param
  	 */
		public function RemovePlan($param){
  			$sql="SELECT * FROM p_workout_plans WHERE id='".$this->dbOb->escape_string($param[0])."'";  
  			$plan=$this->dbOb->getRow($sql);
  			if(!$plan)
  				throw new Exception("No such plan! Cannot remove.");

  			$id=$this->myId;
  			$sql="SELECT * FROM u_workout_plan_subscriptions WHERE z_user_id='".$this->dbOb->escape_string($id)."' AND p_workout_plan_id='".$this->dbOb->escape_string($param[0])."'";
  			$rec=$this->dbOb->getRow($sql);
  			if(!$rec || $rec['is_active']==0){
  				throw new Exception("The user is not currently subscribed to this plan!");
  			}else if($rec){
  				$today = date('Y-m-d') . " 00:00:00";
					$sql="UPDATE u_workout_plan_subscriptions SET is_active=0, date_updated='".$today."' WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
					$this->dbOb->update($sql);
  			}

  			header("Location: /WorkoutPlan/Index");
  			exit();
  		}


		/**
		 * Track exercises
		 * @param
		 */
		public function Track($param) {
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/exercise_log.tpt");
			$wm = new UserTrackWorkoutModel($this->myId);
			$template->addVar("planex", $wm->getPlanExercises());

			foreach ($planex as $ex) {}
			$cat = isset($_POST['category']) ? $_POST['category'] : "all";
			$template->addVar("exercises", $wm->getAllExercises($cat));
			$template->addVar("selected_cat", $cat);
			$template->addVar("categories", $wm->getAllCategories());

			$cexList = $wm->getCustomExercises();
			if ($cexList) {
				$cex = array_merge(array(array('value' => '1', 'display' => 'Custom Exercises')), $cexList);
			}
			else {
				$cex = array(array('value' => '1', 'display' => 'Custom Exercises'));
			}
			$template->addVar('custom_exercises', $cex);
			$template->addVar('logged', $wm->getLoggedExercises());
			return $template;
		}


		public function LogExercise() {
			$ajax = new Ajax();
			$exID = isset($_REQUEST['exID']) ? trim($_REQUEST['exID']) : "";
			$supp = isset($_REQUEST['supp']) ? trim($_REQUEST['supp']) : "";
			$cat = isset($_REQUEST['cat']) ? trim($_REQUEST['cat']) : "";
			$p1 = isset($_REQUEST['p1']) ? trim($_REQUEST['p1']) : "";
			$p2 = isset($_REQUEST['p2']) ? trim($_REQUEST['p2']) : "";

			$today = date('Y-m-d');
			if ($cat == 'cardio') {
				$sets = 0;
				$reps = 0;
				$duration = $p1;
				$weight = 0;
			}
			else {
				$sets = $p1;
				$reps = $p2;
				$duration = 0;
				$weight = 0;
			}
			try {
				$wpm = new UserTrackWorkoutModel($this->myId);
				$arr = array('id' => $exID,
				             'supplemental' => $supp,
				             'category' => $cat,
				             'sets' => $sets,
				             'reps' => $reps,
				             'duration' => $duration,
				             'weight' => $weight,
				             'date_entered' => $today);
				$wpm->saveExerciseLog('add', 0, $arr);
			}
			catch (Exception $e) {
				$ajax->addResponseMessage("Error",Ajax::ERROR,$e->message);
				$status = "Failed";
				$ajax->addResponseData("values", $status);
				$ajax->writeResponseXML();
				exit;
			}
			$ajax->addResponseMessage("Success", Ajax::SUCCESS, "");
			$status = "Success";
			$ajax->addResponseData("values", $status);
			$ajax->writeResponseXML();
			exit;
		}


		public function LogPlanExercises() {
			$wm = new UserTrackWorkoutModel($this->myId);
			foreach($_POST as $k => $v) {
				if (preg_match("/planx/", $k)) {
					$planid = substr($k, 5);
					if ($_POST['chkbox'.$planid] == 'checked') {
						$wm->LogExercise($planid, $_POST['extyp'.$planid], $_POST['']);
					}
				}
			}
		}


  	/**
  	 * Track a cardio exercise
  	 * @param unknown_type $param
  	 */
  		public function trackCardio($param){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/cardio_log.tpt");
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$template->addVar("planex",$wm->getPlanCardioExercises());
  			$template->addVar("exercises",$wm->getAllCardioExercises());
  			$cex=$wm->getLoggedCustomCardioExercises();
  			if($cex)
  				$ce=array_merge(array(array("display"=>"Custom Exercise","value="=>1)),$cex);
  			else 		
  				$ce=array(array("display"=>"Custom Exercise","value="=>1));
  			$template->addVar("custom_exercises",$ce);
  			$template->addVar("logged",$wm->getLoggedCardioExercises());
  			$date=isset($_POST['normal']['date_entered'])?$_POST['normal']['date_entered']:isset($_POST['custom']['date_entered'])?$_POST['custom']['date_entered']:date("Y-m-d");  			
  			$_POST['date_entered']=$date;
  			
  			$template->addVar("post",$_POST);
  			
  			return $template;  			  			
  		}
  	/**
  	 * Add a cardio exercise
  	 * @param $param
  	 */
  		public function addCardio($param){
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$type=(isset($param[0])&&strtoupper($param[0])=="CUSTOM")?"custom":"normal";
  			$err=$wm->validateCardioEntry($_POST[$type],$type=="custom");
  			if($err){
  				return $this->trackCardio($param);
  			}
  			$wm->addCardioEntry($type=="custom");
  			
  			header("Location: /WorkoutPlan/trackCardio");
  			exit();
  		}
  	/**
  	 * delete an exercise from the user's plan
  	 * @param unknown_type $param
  	 */	
  		public function DeleteEntry($param){
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$wm->removeEnteredExercise($param[0]);
  			if(stripos($_SERVER['HTTP_REFERER'],"/trackStrength")){
  				header("Location: /WorkoutPlan/trackStrength");
  			}else{
  				header("Location: /WorkoutPlan/trackCardio");
  			}
  			exit();
  		}
  	
  		/**
 		* Get an entered exercise
 		*/  		
  		
  		public function GetEntry($param){
  			require_once(LIB_ROOT."classes/common/Ajax.class.php");
  		 	$ajax=new Ajax();
  			$id=isset($_POST['id'])?$_POST['id']:null;
  			try{
  				$wm=new UserTrackWorkoutModel($this->myId);
  				$entry=$wm->getEnteredExercise($id);
  				$ajax->addResponseMessage("Success",Ajax::SUCCESS,"");
  		 		$ajax->addResponseData("entry",$entry);
  				
  			}catch(Execption $e){
  				$ajax->addResponseMessage("Error",Ajax::ERROR,$e->getMessage());
  			}
  			$ajax->writeResponseXML();
  		 	 exit;				  		 		  		 	
  			
  		}

			/**
			 * Add comment page
			 */
			public function AddComment($params) {
				$dte = isset($params[0]) ? $params[0] : date('Y-m-d');
				$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/comments.tpt");
				$cc = $this->wpm->getComments($dte);
				if ($cc) {
					$comment = $cc['comment'];
					$template->addVar("action", "upd");
				}
				else {
					$comment = "";
					$template->addVar("action", "add");
				}
				$template->addVar("comment", $comment);
				$template->addVar("date_entered", $dte);
				return $template;
			}

				
  		/**
  		 * Save a comment to the log
  		 * @param $params
  		 */
  		public function saveComment($params){
  			$this->wpm->addComment($_POST['action'], $_POST['date_entered'], $_POST['comment']);
  			
				header("Location: /WorkoutPlan/ExerciseLog?date_entered=".$_POST['date_entered']);
  			exit();
  		}
  		
  		/**
  		 * View comments
  		 * @param $params
  		 */
  		public function viewComments($params){
  			$type=isset($params[0])&&strtolower($params[0])=="cardio"?"cardio":"strength";	
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$comments=$wm->getComments($type);
  			$template=TemplateParser::create(TEMPLATE_DIR."frontend/workoutplan/comments.tpt");  			
  			$template->addVar("comments",$comments);
  			$template->parse();
  			exit();  			
  		}
  		
  		/**
  		 * Track a strength exercise
  		 * @param $param
  		 */
  		public function trackStrength($param){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/strength_log.tpt");
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$template->addVar("planex",$wm->getPlanStrengthExercises());  			  			
  			$template->addVar("logged",$wm->getLoggedStrengthExercises());
  			$date=isset($_POST['normal']['date_entered'])?$_POST['normal']['date_entered']:isset($_POST['custom']['date_entered'])?$_POST['custom']['date_entered']:date("Y-m-d");  			
  			$_POST['date_entered']=$date;  			
  			$template->addVar("post",$_POST);
  			
  			return $template;  			  			
  		}
  		
  		/**
  		 * add a strenght exercise to the log
  		 * @param $param
  		 */
	public function addStrength($param){
		$wm=new UserTrackWorkoutModel($this->myId);
		$type="custom";
			
		if(isset($_POST['normal'])){
			$err=array();
			$de=$_POST['normal']['date_entered'];
			$all=$_POST['normal'];  				  				
			unset($all['date_entered']);
  				
			foreach($all as $key => $record){  					
				if(isset($record['finished'])){//if the finished is not set we are not entering it!
					if(!$record['finished']){
						$record['p_workout_exercises_id']=$key;
						$record['date_entered']=$de;
						$em=$wm->validateStrengthEntry($record,null);
					}else{ //we have a user created supplemental exercise
						$record['p_workout_exercises_id']=$record['finished'];
						$record['date_entered']=$de;
						$em=$wm->validateStrengthEntry($record,$key);
					}
					if($em){
						$err=array_merge($err,$em);
 					}  						
				}
			}
			if(!sizeof($err)) $err=false;
		}else{
			$arr=$_POST[$type];
			$err=$wm->validateCustomStrengthEntry($arr,false);
		}
  			
		if($err){
			return $this->trackStrength($param);
		}

		$wm->addStrengthEntry();
  			
		header("Location: /WorkoutPlan/trackStrength");
		exit();  			  		
	}

  	/**
  	* Add an exercise to the plan form
  	* @param $param
  	*/
		public function AddExerciseToPlan($param){
	 		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/add_exercise.tpt");
			$wm=new UserTrackWorkoutModel($this->myId);
//			$ex=$wm->getAllStrengthExercisesByCat();
			$ex = $wm->getAllExercisesByCat();
			$template->addVar("exercises",$ex);
			return $template;
		}
  		
  		/**
  		 * add the entered exercise to the plan
  		 * @param $param
  		 */
  		public function AddExercise($param){
  			$wm=new UserTrackWorkoutModel($this->myId);  			
  			$err=$wm->validateAddExercise($_POST);
  			if($err){
  				return $this->AddExerciseToPlan($param);
  			}
  			$wm->addExerciseToPlan();
  			header("Location: /WorkoutPlan/AddExerciseToPlan");
  			exit();
  		}  		
  		
  		/**
  		 * Edit an entered strength exercise form
  		 * @param $params
  		 * @param $err
  		 */
  		public function EditStrengthEntry($params,$err=null){
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/edit_logged_strength_entry.tpt");
  			$id=isset($params[0])?$params[0]:0;
  			$wm=new UserTrackWorkoutModel($this->myId);
  			$ex=$wm->getStrengthEntry($id);
  			$template->addVar("exercises",$ex);
  			$template->parse();
  			exit();
  		}
  		/**
  		 * Update an existing strength exercise
  		 * @param $params
  		 */
  		public function UpdateStrengthLogEntry($params){
  			  $id=isset($_POST['id'])?$_POST['id']:0;
  			  $wm=new UserTrackWorkoutModel($this->myId);
  			  $ex=$wm->getStrengthEntry($id);
  			  $err=$wm->validateStrengthUpdate($_POST);
  			  if($err){  				
  				return $this->EditStrengthEntry($param,$err);
  			  }
  			  $wm->updateStrengthEntry($_POST);
  			  print "<script>top.document.location.reload();</script>";
  			  exit();
  		}
  		
  		/**
  		 * Show examples page for exercises
  		 * @param $param
  		 */
  		public function Examples($param){
  			$type=isset($param[0])?strtolower($param[0]):"";
  			switch($type){
  				case "core": $category=$type;break;
  				case "lower_body": $category="lower body";break;
  				case "upper_body": $category="upper body";break;
  				case "stretch": $category=$type;break;
  				case "":$type="core";$category="core";break;
  				default: throw new Exception("Invalid exercise category!");
  			}
  			$sql="SELECT * FROM p_workout_exercises WHERE is_active=1 AND category='".$category."'";
  			$ex=$this->dbOb->query($sql);
  			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/workoutplan/exercise_example.tpt");
  			$template->addVar("category",$type);
  			$template->addVar("exercises",$ex);
  		}


		public function Test($param) {
			$wm = new UserTrackWorkoutModel($this->myId);
			$recs = $wm->getPlanExercises();
			foreach($recs as $day => $rec) {
				echo "Day " . $day . "<br />";
				foreach($rec as $ex) {
					echo "&nbsp;&nbsp;&nbsp;Name: " . $ex['name'] . "<br />";
					echo "&nbsp;&nbsp;&nbsp;Id: " . $ex['id'] . "<br />";
					echo "&nbsp;&nbsp;&nbsp;Category: " . $ex['category'] . "<br />";
					echo "&nbsp;&nbsp;&nbsp;Sets: " . $ex['sets'] . "<br />";
					echo "&nbsp;&nbsp;&nbsp;Reps: " . $ex['reps'] . "<br />";
					if ($ex['supplemental'] == 1) {
						echo "&nbsp;&nbsp;&nbsp;Weight: " . $ex['weight'] . "<br />";
					}
					echo "<br />";
				}
			}
//			print_r($recs);
			exit();
		}

		public function Tester($param) {
			$wm = new UserTrackWorkoutModel($this->myId);
//			$plans = $wm->getCannedPlan(2);
			$data = $wm->searchExercises('rev');
			exit();
		}
	}