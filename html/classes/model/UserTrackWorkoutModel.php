<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (LIB_ROOT."classes/common/StringUtil.class.php");
require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");

class UserTrackWorkoutModel {
	private $dbOb=null;
	private $vc = null;
	private $data=array();
	private $multiple=null;
	private $id;
	private $weight;


	public function __construct($id){
		$this->dbOb=Database::create();
		$this->vc= new Validator();

		$this->id=$this->dbOb->escape_string($id);

		$wt = new UserTrackWeightModel($id);
		$weight_entry = $wt->getLastEntry();
		if (!$weight_entry) {
			$sql = "SELECT initial_weight FROM u_profile WHERE z_user_id = " . $this->$id;
			$this->weight = $this->dbOb->getOne($sql);
		}
		else {
			$this->weight = $weight_entry['weight'];
		}		
	}
	
	public function getUserPlans() {
		$sql = "SELECT id AS value, name AS display FROM u_workout_plans " .
						"WHERE z_user_id = " . $this->id . " " .
						"AND is_active = 1";
		$plans = $this->dbOb->query($sql);
		if (!$plans) {
			$plans = array();
		}
		else {
			array_unshift($plans, array('value' => '0', 'display' => '- Select a plan -'));
		}

		return $plans;
	}


	public function getUserPlanName($pid) {
		$sql = "SELECT name FROM u_workout_plans WHERE id = " . $this->dbOb->escape_string($pid);
		return $this->dbOb->getOne($sql);
	}


	public function getPlanCategories() {
		$sql = "SELECT DISTINCT category FROM p_workout_plans";
		$cat = $this->dbOb->query($sql);
		$ret = array();
		foreach($cat as $c) {
			array_push($ret, array('value' => $c['category'], 'display' => $c['category']));
		}

		return $ret;
	}

	public function getPlanLevels() {
		$sql = "SELECT DISTINCT(level) FROM p_workout_plans";
		$lev = $this->dbOb->query($sql);
		$ret = array();
		foreach($lev as $l) {
			array_push($ret, array('value' => $l['level'], 'display' => $l['level']));
		}
		return $ret;
	}


	/*
	*	Get the list of exercises (including and supplemental) for each day (1 - 7)
	*
	*/
	public function getUserPlanDetails($pid) {
		$sql = "SELECT *, 0 as supplemental, ex.id AS ExID, ex.name AS ExName, ex.description AS ExDesc FROM u_workout_plans AS uwp " .
						"JOIN u_workout_plan_exercises AS uex ON uex.u_workout_plan_id = uwp.id " .
						"JOIN p_workout_exercises AS ex ON ex.id = uex.p_workout_exercises " .
						"WHERE uwp.id = " . $this->dbOb->escape_string($pid) . " " .
						"AND uwp.is_active = 1 " .
						"AND ex.is_active = 1 " .
						"ORDER BY uex.day";
		$plans = $this->dbOb->query($sql);

		$sql = "SELECT sup.*, 1 as supplemental, sup.type as category, sup.p_workout_exercises_id as ExID, sup.name as ExName, pwx.description AS ExDesc FROM u_workout_plan_supplemental AS sup " .
						"LEFT JOIN p_workout_exercises AS pwx ON pwx.id = sup.p_workout_exercises_id " .
						"WHERE sup.z_user_id = " . $this->id . " " .
						"AND sup.is_active = 1 " .
						"ORDER BY sup.day";
		$sup = $this->dbOb->query($sql);

		$list = array('1' => array(), '2' => array(), '3' => array(), '4' => array(), '5' => array(), '6' => array(), '7' => array());
		$rowCount = 0;
		if ($plans) {
			foreach($plans as $ex) {
				$list[$ex['day']][] = $ex;
				$rowCount += 1;
			}
		}

		if ($sup) {
			foreach ($sup as $ex) {
				$list[$ex['day']][] = $ex;
				$rowCount += 1;
			}
		}

		$results = array();
		$day = 0;
		
		foreach ($list as $group) {
			$exArray = array();
			foreach($group as $ex) {
				$day = $ex['day'];
				if (!$ex['ExID']) {
					$ex['ExID'] = $ex['id'];
				}
				if (isset($ex['ExDesc'])) {
					$description = $ex['ExDesc'];
				}
								
				array_push($exArray, array('name' => $ex['ExName'],
			                               'id' => $ex['ExID'],
			                               'category' => $ex['category'],
			                               'sets' => $ex['sets'],
			                               'reps' => $ex['reps'],
			                               'weight' => 0,
			                               'duration' => $ex['duration'],
			                               'METs' => $ex['METs'],
                                     'supplemental' => $ex['supplemental'],
                                     'description'	=> $description));
			}
			array_push($results, (array('day' => $day, 'ex' => $exArray)));
		}

		$retArray = array('vsize' => $rowCount, 'results' => $results);
		return $retArray;
	}


	public function getCannedPlan($pid) {
		$sql = "SELECT ex.*, wpx.*, wp.id AS PlanID, wp.name AS PlanName, wp.category AS PlanType, wp.level AS PlanLevel " .
						"FROM p_workout_plans AS wp " .
						"JOIN p_workout_plan_exercises AS wpx ON wp.id = wpx.p_workout_plan_id " .
						"JOIN p_workout_exercises AS ex ON ex.id = wpx.p_workout_exercises " .
						"WHERE wp.id = " . $this->dbOb->escape_string($pid) . " " .
						"AND wp.is_active = 1 " .
						"AND wp.enabled = 1 " .
						"AND ex.is_active = 1 " .
						"ORDER BY wpx.day";
		$plan = $this->dbOb->query($sql);

		$results = array('PlanID' => $pid, 'PlanName' => $plan[0]['PlanName'], 'PlanType' => $plan[0]['PlanType'], 'PlanLevel' => $plan[0]['PlanLevel'], 'days' => array());
		$did = 0;
		foreach ($plan as $p) {
			if ($did != $p['day']) {
				array_push($results['days'], array('day' => $p['day'], 'ex' => array()));
				$did = $p['day'];
			}

			array_push($results['days'][$p['day']-1]['ex'], array('name' => $p['name'],
			                                                      'id' => $p['id'],
			                                                      'description' => $p['description'],
			                                                      'sets' => $p['sets'],
			                                                      'reps' => $p['reps'],
			                                                      'duration' => $p['duration'],
			                                                      'METs' => $p['METs']));
		}
		return $results;
	}


	/**
	 *
	 *
	 */
	public function saveWorkoutPlan($task, $pid, $plan) {
		if ($task == 'add') {
			$cat = isset($plan['category']) ? $plan['category'] : "";
			$lev = isset($plan['level']) ? $plan['level'] : "";
			$des = isset($plan['description']) ? $plan['description'] : "";
			$sql = "INSERT INTO u_workout_plans (z_user_id, name, category, level, description) " .
							"VALUES (" .
							$this->id . ", '" .
							$this->dbOb->escape_string($plan['PlanName']) . "', '" .
							$this->dbOb->escape_string($cat) . "', '" .
							$this->dbOb->escape_string($lev) . "', '" .
							$this->dbOb->escape_string($des) . "')";
			$wpID = $this->dbOb->insert($sql);

			if ($wpID) {
				for ($i = 1; $i <= 7; $i++) {
					for ($j = 1;; $j++) {
						if (isset($plan['targetEid' . $i . '_' . $j])) {
							$sql = "INSERT INTO u_workout_plan_exercises (u_workout_plan_id, p_workout_exercises, sets, reps, duration, day) " .
											"VALUES (" .
											$wpID . ", " .
											$this->dbOb->escape_string($plan['targetEid' . $i . '_' . $j]) . ", " .
											$this->dbOb->escape_string($plan['targetSets' . $i . '_' . $j]) . ", " .
											$this->dbOb->escape_string($plan['targetReps' . $i . '_' . $j]) . ", " .
											$this->dbOb->escape_string($plan['targetDuration' . $i . '_' . $j]) . ", " .
											$i . ")";
							$this->dbOb->insert($sql);
						}
						else {
							break;
						}
					}
				}
				return $wpID;
			}
			else {
				return 0;
			}
		}
		else if ($task == 'upd') {

		}
		else if ($task == 'del') {
		}
	}

		/**
		 * get the exercise log for the input date
		 *
		 */
		public function getExerciseLog($dte) {
			$sql = "SELECT log.*, pwx.name, pwx.METs, pwx.category, pwx.equipment FROM u_tracker_exercises AS log " .
							"JOIN p_workout_exercises AS pwx ON pwx.id = log.p_workout_exercises_id " .
							"WHERE log.z_user_id = " . $this->id . " " .
							"AND log.is_active = 1 " .
							"AND log.supplemental = 0 " .
							"AND log.date_entered = '" . $this->dbOb->escape_string($dte) . "'";
			$exercises = $this->dbOb->query($sql);

			$sql = "SELECT log.*, pex.name, pex.METs, sup.duration, pex.category, sup.equipment from u_tracker_exercises AS log " .
							"JOIN u_workout_plan_supplemental AS sup ON log.p_workout_exercises_id = sup.id " .
							"JOIN p_workout_exercises AS pex ON sup.p_workout_exercises_id = pex.id " .
							"WHERE log.z_user_id = " . $this->id . " " .
							"AND log.is_active = 1 " .
							"AND log.supplemental = 1 " .
							"AND sup.p_workout_exercises_id <> 0 " .
							"AND log.date_entered = '" . $this->dbOb->escape_string($dte) . "'";
			$sup = $this->dbOb->query($sql);

			$sql = "SELECT log.*, sup.name, sup.METs, sup.type as category, sup.equipment from u_tracker_exercises AS log " .
							"JOIN u_workout_plan_supplemental AS sup ON log.p_workout_exercises_id = sup.id AND log.supplemental = 1 " .
							"WHERE log.z_user_id = " . $this->id . " " .
							"AND log.is_active = 1 " .
							"AND log.date_entered = '" . $this->dbOb->escape_string($dte) . "'" .
							"AND sup.p_workout_exercises_id = 0";
			$cust = $this->dbOb->query($sql);

			if ($sup) {
				if ($exercises) {
					foreach($sup as $ex) {
						array_push($exercises, $ex);
					}
				}
				else {
					$exercises = $sup;
				}
			}

			if ($cust) {
				if ($exercises) {
					foreach ($cust as $ex) {
						array_push($exercises, $ex);
					}
				}
				else {
					$exercises = $cust;
				}
			}
	
			return $exercises;
		}


	/**
	 * Save a supplemental exercise as a custom exercise
	 *
	 */
	public function addSupplemental($arr) {
		if ($arr['weight'] == 0) {
			if (($arr['category'] == "core") ||
			    ($arr['category'] == "upper body") ||
			    ($arr['category'] == "lower body")) {
				$equip = "body weight";
			}
			else {
				$equip = "none";
			}
		}
		else {
			$equip = "dumbell";
		}

		$sql = "INSERT INTO u_workout_plan_supplemental " .
						"(z_user_id, is_active, p_workout_exercises_id, type, name, equipment, sets, reps, weight, duration, METs, day) " .
						"VALUES (" .
						$this->id . ", 1, 0, '" .
						$this->dbOb->escape_string($arr['category']) . "', '" .
						$this->dbOb->escape_string($arr['name']) . "', '" .
						$equip . "', " .
						$this->dbOb->escape_string($arr['sets']) . ", " .
						$this->dbOb->escape_string($arr['reps']) . ", " .
						$this->dbOb->escape_string($arr['weight']) . ", " .
						$this->dbOb->escape_string($arr['duration']) . ", " .
						$this->dbOb->escape_string($arr['METs']) . ", " .
						$this->dbOb->escape_string($arr['day']) . ")";
		$this->dbOb->insert($sql);
	}




	/**
	 * Get all exercise plans the user signed up for
	 * @return array
	 */
	public function getSubscribedPlans(){
		$sql="SELECT * FROM u_workout_plan_subscriptions as uw 
			  JOIN p_workout_plans as pp ON pp.id=uw.p_workout_plan_id 
			  WHERE uw.z_user_id='".$this->id."' AND uw.is_active=1 ORDER BY display_order";
		$plans=$this->dbOb->query($sql);
		$exp=null;
		if($plans){
			foreach($plans as $plan){
				$exp[$plan['id']]=$plan;
			}
		}
		return $exp;
	}
	
	/**
	 * Get all possible exerise plans
	 * @return array
	 */
	public function getAllPlans(){
		$sql="SELECT * FROM p_workout_plans  WHERE is_active=1 AND enabled=1 ORDER BY display_order";
		$plans=$this->dbOb->query($sql);
		$exp=null;
		if($plans){
			foreach($plans as $plan){
				$exp[$plan['id']]['plan']=$plan;
				$sql="SELECT ex.name,ex.category,CHAR_LENGTH(ex.description)>1 as has_desc,pe.* 
					 FROM p_workout_plan_exercises as pe
					 JOIN p_workout_exercises AS ex ON ex.id=pe.p_workout_exercises
					 WHERE ex.is_active=1 AND pe.p_workout_plan_id='".$this->dbOb->escape_string($plan['id'])."' ORDER BY pe.day,ex.name";
				$ex=$this->dbOb->query($sql);
				foreach($ex as $e){
					if(!isset($exp[$plan['id']]['exercises'][$e['day']])){
						$exp[$plan['id']]['exercises'][$e['day']]=array();
					}
					$exp[$plan['id']]['exercises'][$e['day']][]=$e;
				}
			}
		}
		return $exp;
	}
	
	/**
	 * Subscribe the user to a plan
	 * @param int $plan_id
	 */
	public function subscribePlan($plan_id){
	 	$sql="SELECT * FROM u_workout_plan_subscriptions WHERE  p_workout_plan_id='".$this->dbOb->escape_string($plan_id)."' AND z_user_id='".$this->id."'";
	 	$rec=$this->dbOb->getRow($sql);
	 	if($rec['is_active']==0){
	 		$sql="UPDATE u_workout_plan_subscriptions set is_active=1 WHERE  p_workout_plan_id='".$this->dbOb->escape_string($plan_id)."' AND z_user_id='".$this->id."'";
	 		$this->dbOb->update($sql);
	 		return true;
	 	}
	 	throw new Exception("User is already subscribed to plan!");
	 }
	  
	 /**
	  * Unscubscribe user fom plan
	  * @param int $plan_id
	  */
	public function unsubscribePlan($plan_id){
	 	$sql="SELECT * FROM u_workout_plan_subscriptions WHERE  p_workout_plan_id='".$this->dbOb->escape_string($plan_id)."' AND z_user_id='".$this->id."'";
	 	$rec=$this->dbOb->getRow($sql);
	 	if(!$rec) throw new Exception("User is not a member of the plan, cannot unsubscribe!");
	 	if($rec['is_active']==1){
	 		$sql="UPDATE u_workout_plan_subscriptions set is_active=1 WHERE  p_workout_plan_id='".$this->dbOb->escape_string($plan_id)."' AND z_user_id='".$this->id."'";
	 		$this->dbOb->update($sql);
	 		return true;
	 	}
	 	throw new Exception("User is already subscribed to plan!");
	 }

	/**
	 * Look up the last exercise date and total
	 * up the calories from all of the exercises
	 */
	public function getLastEntry() {
		$sql = "SELECT date_entered FROM u_tracker_exercises AS utx " .
						"WHERE utx.z_user_id = " . $this->id . " " .
						"AND utx.is_active = 1 " .
						"ORDER BY utx.date_entered DESC";
		$last = $this->dbOb->getOne($sql);

		if (!$last) {
			return array();
		}

		$exercises = $this->getExerciseLog($last);

		$METs = array('stretch' => 2.5, 'upper body' => 8.0, 'lower body' => 8.0, 'core' => 8.0);
		$retArray = array();
		$retCals = 0;
		$retDate = $exercises[0]['date_entered'];
		foreach($exercises as $ex) {
			if ($ex['category'] == 'cardio') {
				$retCals += (($ex['METs'] * 3.5 * $this->weight) / 200.0) * $ex['duration'];
			}
			else {
				$dur = ($ex['sets'] * $ex['reps'] * 3.0) / 60.0;			//Assume 3 seconds per repetition
				$retCals += (($METs[$ex['category']] * 3.5 * $this->weight) / 200.0) * $dur;
			}
		}

		return array('date_entered' => $retDate, 'calories' => $retCals);
	}

	 /**
	  * Get all cardio exercises for the user
	  * @return array
	  */
	public function getPlanCardioExercises(){
	 	$sql="SELECT *,CHAR_LENGTH(we.description)>1 as has_desc FROM u_workout_plan_subscriptions as uw 
			  JOIN p_workout_plans as pp ON pp.id=uw.p_workout_plan_id 
			  JOIN p_workout_plan_exercises as wpe ON wpe.p_workout_plan_id=pp.id
			  JOIN p_workout_exercises as we ON we.id=wpe.p_workout_exercises
			  WHERE we.category='cardio' AND uw.z_user_id='".$this->id."' AND uw.is_active=1 ORDER BY wpe.day,we.name";
	 	$recs=$this->dbOb->query($sql);
	 	$records=null;
	 	if($recs){
	 		foreach($recs as $rec){
	 			$records[$rec['day']][]=$rec;		
	 		}
	 	}
	 	return $records;	 	
	 }

	/**
	 * Get all strength exercises for the user
	 * @return array
	 */ 
	public function getPlanStrengthExercises(){
	 	$sql="SELECT *,CHAR_LENGTH(we.description)>1 as has_desc FROM u_workout_plan_subscriptions as uw 
			  JOIN p_workout_plans as pp ON pp.id=uw.p_workout_plan_id 
			  JOIN p_workout_plan_exercises as wpe ON wpe.p_workout_plan_id=pp.id
			  JOIN p_workout_exercises as we ON we.id=wpe.p_workout_exercises
			  WHERE we.category!='cardio' AND uw.z_user_id='".$this->id."' AND uw.is_active=1 ORDER BY wpe.day,we.name";
	 	$recs=$this->dbOb->query($sql);	 	
	 	$records=null;
	 	if($recs){
	 		foreach($recs as $rec){
	 			$rec['supplemental']=0;
	 			$records[$rec['day']][]=$rec;		
	 		}
	 	}
	 	$sql="SELECT * FROM u_workout_plan_supplemental WHERE z_user_id='".$this->id."'";
	 	$recs=$this->dbOb->query($sql);
	 	$fakeId=1000000;
	 	if($recs){
	 		foreach($recs as $rec){
	 			if($rec['p_workout_exercises_id']==0) $rec['supplemental']=$rec['id'];//save the rec id of the user created the ext
	 			$rec['id']=($rec['p_workout_exercises_id']==0)?$fakeId++:$rec['p_workout_exercises_id']; 
	 			$records[$rec['day']][]=$rec;
	 		}
	 		foreach($records as &$day){
	 			usort($day,"sortName");
	 		}
	 	}
	 	return $records;	 	
	 }
	 	 
	 /**
	  * Get all possible cardio exercises 
	  * @return array 
	  */
	public function getAllCardioExercises(){
	 	$sql="SELECT id as value,name as display FROM p_workout_exercises WHERE category='cardio' and is_active=1 ORDER BY name";
	 	return $this->dbOb->query($sql);
	 }


//////////////////////////////////////////////////////////////////////////////////////


	/**
	*	@name - getExercises()
	* @abstract - get stored exercises by cagegory (cardio, core, upper_body, etc)
	*							default is 'All' exercises
	* @param - $category
	*/
	public function getExercises($category = 'all') {
		if ($category == 'all') {
			$where = " ";
		}
		else {
			$where = " AND category = '" . $category . "' ";
		}

		$sql="SELECT id as value,name as display,category FROM p_workout_exercises WHERE is_active=1" . $where . "ORDER BY name";
		return $this->dbOb->query($sql);
	}


	/**
	*	@name - searchExercises()
	*	@abstract - search the exercise table(s) for exercises that match
	*							the input text (using a LIKE clause
	*	@param - $str
	*/
	public function searchExercises($str) {
		$uex = strtoupper($str);
		$uex = preg_replace("/^\\s+/", "", $uex);
		$uex = preg_replace("/\\s+$/", "", $uex);

		$words = preg_split("/\\s+/", $uex);
		$where = "WHERE (";
		$and = "";
		foreach($words as $word) {
			$where .= $and . "UCASE(ex.name) LIKE '%" . $word . "%'";
			$and = " AND ";
		}
		$where .= ") ";
		try {
			$sql = "SELECT ex.*, ex.type as category, 1 as custom FROM u_workout_plan_supplemental AS ex " .
						$where .
						"AND ex.is_active = 1 " .
						"AND ex.p_workout_exercises_id = 0";
			$data1 = $this->dbOb->query($sql);

		}
		catch (Exception $e) {
			throw new Exception ($e->getMessage());
		}

		try {
			$sql = "SELECT ex.*, 0 as custom, 0 as sets, 0 as reps, 0 as duration FROM p_workout_exercises AS ex " .
						$where .
						"AND ex.is_active = 1";
			$data2 = $this->dbOb->query($sql);
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if (is_array($data1)) {
			if (is_array($data2)) {
				$data = array_merge($data1, $data2);
			}
			else {
				$data = $data1;
			}
		}
		else {
			if (is_array($data2)) {
				$data = $data2;
			}
		else {
			$data = array();
			}
		}
		return $data;
	}



	/**
	*	@name - getAllCategories()
	* @abstract - get all unique categories stored in the system
	*/
	public function getAllCategories() {
		$sql = "SELECT DISTINCT category AS value, LCASE(category) AS display FROM p_workout_exercises";
		$cat = $this->dbOb->query($sql);
		foreach($cat as &$c) {
			$c['display'] = ucfirst($c['display']);
		}
		array_unshift($cat, array('value' => 'all', 'display' => 'All'));
		return $cat;
	}

	/**
	*	@name - getPlanExercises() {
	*	@abstract - get all exercises from all of the subscribed workout plan
	*             for this user.  Also include any supplemental exercises that
	*             the user has added to his/her plan.
	*/
	public function getPlanExercises() {
		$sql = "SELECT pex.*, ex.* FROM u_workout_plan_subscriptions AS sub " .
						"JOIN p_workout_plan_exercises AS pex ON sub.p_workout_plan_id = pex.p_workout_plan_id " .
						"JOIN p_workout_exercises AS ex ON pex.p_workout_exercises = ex.id " .
						"WHERE sub.z_user_id = ". $this->id ." AND sub.is_active = 1 " .
						"ORDER BY pex.day, ex.category";
		$recs = $this->dbOb->query($sql);
		$records = null;
		if ($recs) {
			foreach ($recs as $rec) {
				$rec['supplemental'] = 0;
				$records[$rec['day']][] = $rec;
			}
		}

		$sql = "SELECT sup.p_workout_exercises_id, sup.type, sup.name AS sup_name, sup.sets, " .
		        "sup.reps, sup.weight, sup.day, ex.id, ex.name, ex.description, ex.category, " .
		        "ex.equipment, ex.calories_per_unit " .
		        "FROM u_workout_plan_supplemental AS sup " .
		        "LEFT JOIN p_workout_exercises AS ex ON sup.p_workout_exercises_id = ex.id " .
		        "WHERE sup.z_user_id='".$this->id."'";
		$recs = $this->dbOb->query($sql);
		$fakeId = 1000000;
		if ($recs) {
			foreach ($recs as $rec) {
				$rec['supplemental'] = 1;
				if ($rec['p_workout_exercises_id'] == 0) {
					$rec['name'] = $rec['sup_name'];
					$rec['category'] = $rec['type'];
					$rec['id'] = $fakeId++;
				}
				$records[$rec['day']][] = $rec;
			}
			foreach ($records as &$day) {
				usort($day, "sortName");
			}
		}
		return $records;
	}

//
// Formulae
//
//	Calories = ((METs * 3.5 * wt(kg)) / 200) * Duration
//	METs = ((Calories / Duration) * 200) / (3.5 * wt(kg))
//
///////////////////////////////////////////////////////////////////////////////////




		/**
		 * save/update/delete exercise log
		 *
		 */
		public function saveExerciseLog($action, $eid, $log) {
			if ($action == 'add') {
				//Don't allow duplicates on any given day
				$sql = "SELECT * FROM u_tracker_exercises " .
								"WHERE z_user_id = " . $this->id . " " .
								"AND p_workout_exercises_id = " . $this->dbOb->escape_string($log['id']) . " " .
								"AND date_entered = '" . $log['date_entered'] . "'";
				$ex = $this->dbOb->getRow($sql);
				if ($ex) {
					return;
				}

				$sql = "INSERT INTO u_tracker_exercises " .
								"(z_user_id, p_workout_exercises_id, supplemental, duration, sets, reps, weight, date_entered) " .
								"VALUES (" .
								$this->id . ", " .
								$this->dbOb->escape_string($log['id']) . ", " .
								$this->dbOb->escape_string($log['supplemental']) . ", " .
								$this->dbOb->escape_string($log['duration']) . ", " .
								$this->dbOb->escape_string($log['sets']) . ", " .
								$this->dbOb->escape_string($log['reps']) . ", " .
								$this->dbOb->escape_string($log['weight']) . ", '" .
								$log['date_entered'] . "')";
				$this->dbOb->insert($sql);
			}
			else if ($action == 'upd') {
				$sql = "UPDATE u_tracker_exercises SET " .
								"duration = " . $this->dbOb->escape_string($log['duration']) . ", " .
								"sets = " . $this->dbOb->escape_string($log['sets']) . ", " .
								"reps = " . $this->dbOb->escape_string($log['reps']) . ", " .
								"weight = " . $this->dbOb->escape_string($log['weight']) . ", " .
								"date_modified = '" . date('Y-m-d h:i:s') . "' " .
								"WHERE id = " . $eid . " " .
								"AND supplemental = " . $log['supplemental'];
				$this->dbOb->update($sql);
			}
			else if ($action == 'del') {
				$sql = "UPDATE u_tracker_exercises " .
								"SET is_active = 0, " .
								"date_modified = '" . date('Y-m-d h:i:s') . "' " .
								"WHERE id = " . $eid . " " .
								"AND supplemental = " . $log['supplemental'];
				$this->dbOb->update($sql);
			}
		}




	  /**
	  * Get all custom cardio exercises entered by the user 
	  * @return array 
	  */
	 public function getLoggedCustomCardioExercises(){
	 	$sql="SELECT custom_name as display, concat(custom_name,'|',calories,'|',units,'|',heart_rate) as value FROM u_tracker_exercises 
	 		  WHERE z_user_id='".$this->id."' and custom='cardio' GROUP BY custom_name";
	 	$rec=$this->dbOb->query($sql);
	 	return $rec;
	 }
	 
	 /**
	  * Get all non-custom cardi exercises entered by the user 
	  * @return array 
	  */
	 public function getLoggedCardioExercises(){
	 	$sql="SELECT tx.*,px.name FROM u_tracker_exercises AS tx
	 		  LEFT JOIN  p_workout_exercises AS px ON px.id=tx.p_workout_exercises_id
	 	 	  WHERE (px.category='cardio' OR tx.custom='cardio') AND tx.z_user_id='".$this->id."'";
	 	return $this->dbOb->query($sql);
	 }
	 /**
	  * Get all strength exercises entered by the user 
	  * @return array 
	  */
	public function getLoggedStrengthExercises(){
	 	$sql="SELECT tx.*,px.name,tx.id as pk  FROM u_tracker_exercises AS tx
	 		  LEFT JOIN  p_workout_exercises AS px ON px.id=tx.p_workout_exercises_id
	 	 	  WHERE (px.category<>'cardio' OR  tx.custom='strength')AND tx.supplemental=0 AND tx.z_user_id='".$this->id."'";
	 	$primary=$this->dbOb->query($sql);
	 	
	 	//$sql="SELECT tx.*, tx.id AS pk, tx.custom_name AS name FROM u_tracker_exercises AS tx WHERE tx.p_workout_exercises_id=0 AND tx.supplemental=0 AND tx.z_user_id='".$this->id."' AND tx.custom<>'cardio'";
	 	//$secondary=$this->dbOb->query($sql);	
	 	//$primary=array_merge($secondary,$primary);
	 	
	 	$sql="SELECT tx.*,uwp.name,tx.id as pk FROM u_tracker_exercises AS tx
	 	      RIGHT JOIN  u_workout_plan_supplemental AS uwp ON uwp.id=tx.p_workout_exercises_id
	 		  WHERE tx.supplemental=1 AND tx.z_user_id='".$this->id."' AND uwp.z_user_id='".$this->id."'";
	 	$supplemental=$this->dbOb->query($sql);
	 	if($supplemental)
	 		$results=array_merge($supplemental,$primary);
	 	else 
	 		$results=$primary;
	 	
	 	if($results) $r=usort($results,"sortDateName");

	 	return $results;
	 }
	 
	 /**
	  * Validate cadio entry data
	  * @param array $arr
	  * @param boolean $custom
	  * @return false or aray of error obj
	  */
	 public function validateCardioEntry($arr,$custom=false){	 
	 	$err=null;	 	
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$this->data['units']=$this->vc->exists('units',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
	 	try{
    		$this->data['heart_rate']=$this->vc->exists('heart_rate',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	
    	try{
    		$this->data['id']=$this->vc->exists('id',$arr,"integer",array("rangex_low"=>0),true,false);
    	}catch(ValidationException $e){
    		//$err[]=$e->createErrorObject();
    		//The id is only set on updates if we have an invalid id treat ot like a new entry!
    		$this->data['id']=0;
    	}
    	
    	if($custom){
    		$this->data['p_workout_exercises_id']=0;
    		try{
    			$this->data['custom_name']=$this->vc->exists('custom_name',$arr,"text",null,false,true);
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
    		try{
    			$this->data['calories']=$this->vc->exists('calories',$arr,"integer",array("rangex_low"=>0),false,true);
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
    		try{
    			$this->data['label']=$this->vc->exists('label',$arr,"text",null,true,true);
    			if($this->data['label']=="")$this->data['label']="minutes";
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
    	}else{
    		try{
    			$this->data['p_workout_exercises_id']=$this->vc->exists('p_workout_exercises_id',$arr,"integer",array("rangex_low"=>0),false,true);
    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}    		
	 		try{	 			
/*	 			$sql="SELECT calories_per_unit FROM p_workout_exercises WHERE id='".$this->dbOb->escape_string($this->data['p_workout_exercises_id'])."'";
	 			$cpu=$this->dbOb->getOne($sql);	 
	 			
	 			if(!$cpu){
	 				throw new ValidationException("Exercise does not exists",4,"p_workout_exercises_id");
	 			}

	 			$sql="SELECT weight FROM u_tracker_weight where z_user_id='".$this->id."' ORDER BY date_entered DESC LIMIT 0,1";
	 			$weight=$this->dbOb->getOne($sql);	 			
	 			
    			$this->data['calories']=$cpu*$this->data['units']*$weight;
*/
				$sql = "SELECT METs FROM p_workout_exercises WHERE id='".$this->dbOb->escape_string($this->data['p_workout_exercises_id'])."'";
				$mets = $this->dbOb->getOne($sql);
				if (!$mets) {
					throw new ValidationException("Exercise does not exist", 4, "p_workout_exercises_id");
				}

				$this->data['calories'] = (($mets * 3.5 * ($this->weight / 2.2)) / 200.0) * $this->data['duration'];

    		}catch(ValidationException $e){
    			$err[]=$e->createErrorObject();
    		}
    	}
    	return ($err)?$err:false;
	 }
	 
	 /**
	  * Add cardio data to log
	  * @param boolean$custom
	  */
	 public function addCardioEntry($custom=false){
	 	$rec=null;	 	
	 	if($this->data['id']>0){ //we have an update
	 		$sql="SELECT * FROM u_tracker_exercises WHERE id='".$this->dbOb->escape_string($this->data['id'])."' AND z_user_id='".$this->id."'";
	 		$rec=$this->dbOb->query($sql);
	 		if(!$rec) throw new Exception("Unable to update existing record, as the record does not belong to the requested user.");
	 		 
	 	}
	 	if(!$rec){
		 	$preSql="SELECT * FROM u_tracker_exercises 
	 				 WHERE z_user_id='".$this->id."' AND
	 				   p_workout_exercises_id='".$this->dbOb->escape_string($this->data['p_workout_exercises_id'])."' AND 
	 				   date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."'";
	 		if($custom){
	 			$preSql.=" AND custom_name='".$this->dbOb->escape_string($this->data['custom_name'])."'";
	 		}
	 		$rec=$this->dbOb->query($preSql);
	 	}
	 	
	 	if($custom){
	 		$str=new StringUtil();
	 		if(!$rec){
		 		$sql="INSERT INTO u_tracker_exercises(date_entered,z_user_id,custom_name,custom,units,label,heart_rate,calories) VALUES(
		 		'".$this->dbOb->escape_string($this->data['date_entered'])."',
	 			'".$this->id."',
	 			'".$str->sanitize_data($this->data['custom_name'],2)."',
	 			'cardio',
			 	'".$this->dbOb->escape_string($this->data['units'])."',
			 	'".$this->dbOb->escape_string($this->data['label'])."',
	 			'".$this->dbOb->escape_string($this->data['heart_rate'])."',
			 	'".$this->dbOb->escape_string($this->data['calories'])."')";
	 			$this->dbOb->insert($sql);
	 			require_once(ROOT_DIR."classes/model/IncentivePointsModel.php");
	 			$im= new IncentivePointsModel();	 			
	 			$im->addIncentivePointMA("WorkoutPlan","addCardio",$this->data['date_entered']);
	 		}else{	 			
	 			$sql="UPDATE u_tracker_exercises SET calories='".$this->dbOb->escape_string($this->data['calories'])."',
	 				 units='".$this->dbOb->escape_string($this->data['units'])."',
	 				 heart_rate='".$this->dbOb->escape_string($this->data['heart_rate'])."'
	 				 WHERE id='".$this->dbOb->escape_string($rec[0]['id'])."'";
	 			$this->dbOb->update($sql);
	 		}
	 	}else{
	 		if(!$rec){
			 	$sql="INSERT INTO u_tracker_exercises(date_entered,z_user_id,p_workout_exercises_id,units,heart_rate,calories) VALUES(
	 			'".$this->dbOb->escape_string($this->data['date_entered'])."',
			 	'".$this->id."',
	 			'".$this->dbOb->escape_string($this->data['p_workout_exercises_id'])."',
			 	'".$this->dbOb->escape_string($this->data['units'])."',
	 			'".$this->dbOb->escape_string($this->data['heart_rate'])."',
			 	'".$this->dbOb->escape_string($this->data['calories'])."')";
	 			$this->dbOb->insert($sql);
	 			require_once(ROOT_DIR."classes/model/IncentivePointsModel.php");
	 			$im= new IncentivePointsModel();
	 			$im->addIncentivePointMA("WorkoutPlan","addCardio",$this->data['date_entered']);
	 		}else{
	 			$sql="UPDATE u_tracker_exercises SET calories='".$this->dbOb->escape_string($this->data['calories'])."',
	 				 units='".$this->dbOb->escape_string($this->data['units'])."',
	 				 heart_rate='".$this->dbOb->escape_string($this->data['heart_rate'])."'
	 				 WHERE id='".$this->dbOb->escape_string($rec[0]['id'])."'";
	 			$this->dbOb->update($sql);
	 		}
	 	}
	 	
	 		
	 }
	 /**
	  * Remove logged extecise
	  * @param int $ex_id
	  */
	 public function removeEnteredExercise($ex_id){
	 	$sql="DELETE FROM u_tracker_exercises WHERE id='".$this->dbOb->escape_string($ex_id)."' AND z_user_id='".$this->id."'";
	 	$this->dbOb->update($sql);	 	
	 }
	 /**
	  * get al logged exercise
	  * @param int $ex_id
	  */
	 public function getEnteredExercise($ex_id){	 	
	 	$sql="SELECT * FROM u_tracker_exercises WHERE id='".$this->dbOb->escape_string($ex_id)."' AND z_user_id='".$this->id."'";
	 	return $this->dbOb->getRow($sql);
	 }
	 
	 /**
	  * validate user exercise comment
	  * @param array $arr
	  * @return false or aray of error obj
	  */
	public function validateComment($arr){
		$err=null;
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['type']=$this->vc->exists('type',$arr,"enum",array("values"=>array('cardio','strength')),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		try{
    		$this->data['comment']=$this->vc->exists('comment',$arr,"text",array("notempty"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
	 	return ($err)?$err:false;
	 }


	/**
	 * Add the comment to the database
	 *	@params $action - "add" or "upd"
	 *					$dte - date log was entered for
	 *					$comment - comment to add/update
	 */
	public function addComment($action, $dte, $comment){

		$str=new StringUtil();
		$comment = $str->sanitize_data($comment,2);

		if ($action == "add") {
			$sql = "INSERT INTO u_tracker_exercises_comments(z_user_id,comment,date_entered) VALUES (" .
		 		"'" . $this->id . "', " .
		 		"'" . $comment . "', " .
		 		"'" . $this->dbOb->escape_string($dte) . "')";
			$this->dbOb->insert($sql);
		}
		else if ($action == "upd") {
			$today = date('Y-m-d h:i:s');
			$sql = "UPDATE u_tracker_exercises_comments SET comment = " .
				"'" . $comment . "', " .
				"date_updated = '" . $today . "' " .
				"WHERE z_user_id = '" . $this->id . "' " .
				"AND date_entered = '" . $this->dbOb->escape_string($dte) . "'";
			$this->dbOb->update($sql);
		}
	}


	 /**
	  * Get a comment from the database
	  * @param $dte - date of comment
	  */
	 public function getComments($dte){
	    $sql="SELECT * FROM u_tracker_exercises_comments WHERE z_user_id='".$this->id."' AND date_entered='".$this->dbOb->escape_string($dte)."'";
	    return $this->dbOb->getRow($sql);	
	 }
	
	 /**
	  * validate the data for a non-custom strength exercise entry
	  * @param array $arr
	  * @param int $supplementalId
	  * @return false or aray of error obj
	  */
	public function validateStrengthEntry($arr,$supplementalId){
		$err=null;
		$obj=array();
		try{
    		$obj['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$obj['p_workout_exercises_id']=$this->vc->exists('p_workout_exercises_id',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$obj['sets']=$this->vc->exists('sets',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$obj['reps']=$this->vc->exists('reps',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$obj['weight']=$this->vc->exists('weight',$arr,"integer",array("range_low"=>0),true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
		if($supplementalId) $obj['supplemental']=$supplementalId;
		
    	$this->data['multiple'][]=$obj;
    	
	 	return ($err)?$err:false;
	 }
	 
	 /**
	  * Validate a custom strength exercise entry
	  * @param array $arr
	  * @return false or aray of error obj
	  */
     public function validateCustomStrengthEntry($arr){
		$err=null;		
		
		try{
    		$this->data['date_entered']=$this->vc->exists('date_entered',$arr,"date",array("datestamp"=>1),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['custom_name']=$this->vc->exists('custom_name',$arr,"text",array("notempty"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$this->data['sets']=$this->vc->exists('sets',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$this->data['reps']=$this->vc->exists('reps',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['weight']=$this->vc->exists('weight',$arr,"integer",array("range_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}    	
	 	return ($err)?$err:false;
	 }	 
	 
	 /**
	  * Add a strength extercise to the database log
	  */
	 function addStrengthEntry(){
	 		
	 		if(isset($this->data['multiple'])){
	 			foreach($this->data['multiple'] as $ent)
	 				$this->addStrength($ent);
	 		}else{
	 			$sql="SELECT * FROM u_tracker_exercises WHERE 
	 				z_user_id='".$this->id."' AND
	 				custom_name='".$this->dbOb->escape_string($this->data['custom_name'])."'";
	 			$rec=$this->dbOb->query($sql);
	 			if(!$rec){
	 				$sql="INSERT INTO u_tracker_exercises(z_user_id,custom_name,custom,sets,reps,weight,date_entered) VALUES(
	 				'".$this->id."',
	 				'".$this->dbOb->escape_string($this->data['custom_name'])."',
	 				'strength',
	 		 		'".$this->dbOb->escape_string($this->data['sets'])."',
	 				'".$this->dbOb->escape_string($this->data['reps'])."',
	 				'".$this->dbOb->escape_string($this->data['weight'])."',
	 				'".$this->dbOb->escape_string($this->data['date_entered'])."')";
	 				$this->dbOb->insert($sql);
	 				require_once(ROOT_DIR."classes/model/IncentivePointsModel.php");
	 				$im= new IncentivePointsModel();
	 				$im->addIncentivePointMA("WorkoutPlan","addStrength",$this->data['date_entered']);
	 			}else{
	 				$sql="UPDATE u_tracker_exercises SET 
	 					sets='".$this->dbOb->escape_string($this->data['sets'])."',
	 					reps='".$this->dbOb->escape_string($this->data['reps'])."',
	 					weight='".$this->dbOb->escape_string($this->data['weight'])."',
	 					date_entered='".$this->dbOb->escape_string($this->data['date_entered'])."'
	 					WHERE id='".$this->dbOb->escape_string($rec[0]['id'])."'";
	 				$this->dbOb->update($sql);
	 			}
	 		}	 
	 }
	 
	 /**
	  * Add a strength exercise to the usre plan
	  * @param array $entry
	  */
	 private function addStrength($entry){
	 	
	 	$sql="SELECT * FROM u_tracker_exercises 
	 		  WHERE p_workout_exercises_id='".$this->dbOb->escape_string($entry['p_workout_exercises_id'])."' AND
	 	  	  z_user_id='".$this->id."' AND ";
	 	if(isset($entry['supplemental'])){
	 	   $sql.=" supplemental=0 AND ";
	 	}
	 	$sql.="date_entered='".$this->dbOb->escape_string($entry['date_entered'])."'";
	 	$rec=$this->dbOb->getRow($sql);
	 	
	 	if($rec){
	 		$sql="UPDATE u_tracker_exercises SET	 			
	 			sets='".$this->dbOb->escape_string($entry['sets'])."',
	 			reps='".$this->dbOb->escape_string($entry['reps'])."',
	 			weight='".$this->dbOb->escape_string($entry['weight'])."' WHERE id='".$this->dbOb->escape_string($rec['id'])."'";
	 			$this->dbOb->update($sql);
	 			return;
	 	}
	 	$sql="INSERT INTO u_tracker_exercises(z_user_id,p_workout_exercises_id,supplemental,sets,reps,weight,date_entered) VALUES(
	 			'".$this->id."',
	 			'".$this->dbOb->escape_string($entry['p_workout_exercises_id'])."',
	 			".(isset($entry['supplemental'])?1:0).",
	 		 	'".$this->dbOb->escape_string($entry['sets'])."',
	 			'".$this->dbOb->escape_string($entry['reps'])."',
	 			'".$this->dbOb->escape_string($entry['weight'])."',
	 			'".$this->dbOb->escape_string($entry['date_entered'])."')";
	 	$this->dbOb->insert($sql);
	 	require_once(ROOT_DIR."classes/model/IncentivePointsModel.php");
	 	$im= new IncentivePointsModel();	 	
	 	$im->addIncentivePointMA("WorkoutPlan","addStrength",$this->data['date_entered']);
	 }
	 
	 /**
	  * Get all strength exercises order by category
	  * @return array
	  */
	 public function getAllStrengthExercisesByCat(){
	 	$sql="SELECT name,id,category,equipment FROM p_workout_exercises WHERE is_active=1 AND category!='cardio' ORDER BY category,equipment";
	 	$all=$this->dbOb->query($sql);
	 	$records=array('core'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'lower body'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'upper body'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'stretch'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array())
	 	);

	 	foreach($all as $record){
	 		$records[$record['category']][$record['equipment']][]=$record;
	 	}
	 	
	 	return $records;
	 }

	 /**
	  * Get all strength exercises order by category
	  * @return array
	  */
	 public function getAllExercisesByCat(){
	 	$sql="SELECT name,id,category,equipment,METs FROM p_workout_exercises WHERE is_active=1 ORDER BY category,equipment,name";
		$all = $this->dbOb->query($sql);
	 	$records=array('cardio' => array('body weight' => array(), 'cardio' => array(), 'column 2' => array(), 'column 3' => array()),
	 	         'core'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'lower body'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'upper body'=>array('body weight'=>array(),'dumbell'=>array(),'none'=>array()),
	 				   'stretch'=>array('body weight'=>array(),'dumbell'=>array(),'stretch'=>array())
	 	);

	 	foreach($all as $record){
	 		if ($record['category'] == 'cardio') {
	 			$a = substr($record['name'], 0, 1);
//	 			if ($a < 'H') {

	 			if ($a < 'R') {
	 				$eq = 'cardio';
	 			}
//	 			else if ($a < 'S') {
				else {
	 				$eq = 'column 2';
	 			}
//	 			else {
//	 				$eq = 'column 3';
//	 			}
	 		}
	 		else if ($record['category'] == 'stretch') {
	 			$eq = 'stretch';
	 		}
	 		else {
	 			$eq = $record['equipment'];
	 		}
	 		$records[$record['category']][$eq][]=$record;
	 	}

	 	return $records;
	 }
	 
	 /**
	  * Validate adding an exercise to the plan
	  * @param array $arr
	  * @return false or aray of error obj
	  */
	 public function validateAddExercise($arr){

	 	$err=null;		

    try{
    	$this->data['name']=$this->vc->exists('name',$arr,"text",array("notempty"=>0),false,true);
    }catch(ValidationException $e){
    	$err[]=$e->createErrorObject();
    }
		try {
			$this->data['category'] = $this->vc->exists('category', $arr, "text", array("notempty"=>0), false, true);
		}
		catch (ValidationException $e) {
			$err[] = $e->createErrorObject();
		}
		if ($this->data['category'] == "cardio") {
			try {
				$this->data['duration'] = $this->vc->exists('duration', $arr, "integer", array("range_low"=>1), false, true);
			}
			catch(ValidationException $e) {
				$err[] = $e->createErrorObject();
			}
			if ($arr['METs'] == 0) {
				try {
					$calories = $this->vc->exists('calories', $arr, "numeric", array("range_low" => 1.0), false, true);
				}
				catch(ValidationException $e) {
					$err[] = $e->createErrorObject();
				}
				if (!$calories) {
					$calories = 0;
				}
				$this->data['METs'] = (($calories / $this->data['duration']) * 200.0) / (3.5 * ($this->weight / 2.2));
			}
			else {
				try {
					$this->data['METs'] = $this->vc->exists('METs', $arr, "numeric", array("range_low" => 1.0), false, true);
				}
				catch(ValidationException $e) {
					$err[] = $e->createErrorObject();
				}
			}
			$this->data['sets'] = 0;
			$this->data['reps'] = 0;
			$this->data['weight'] = 0;
		}
		else {
		 	try{
   			$this->data['sets']=$this->vc->exists('sets',$arr,"integer",array("rangex_low"=>0),false,true);
   		}catch(ValidationException $e){
   			$err[]=$e->createErrorObject();
   		}
			try{
    		$this->data['reps']=$this->vc->exists('reps',$arr,"integer",array("rangex_low"=>0),false,true);
   		}catch(ValidationException $e){
   			$err[]=$e->createErrorObject();
   		}
   		if($arr['weight'] == "") $arr['weight'] = 0;
   		try{
    			$this->data['weight']=$this->vc->exists('weight',$arr,"integer",array("range_low"=>0),true,true);
    	}catch(ValidationException $e){
   			$err[]=$e->createErrorObject();
   		}
			$this->data['METs'] = 0.0;
			$this->data['duration'] = 0;
   	}
		try{
   		$this->data['day']=$this->vc->exists('day',$arr,"integer",array("range_low"=>1,"range_high"=>7),false,true);
		}catch(ValidationException $e){
   		$err[]=$e->createErrorObject();
   	}

	 	return ($err)?$err:false;
	}

	/**
	 * Get the user's supplemental exercises
	 */
		public function getSupplementalExercises() {
			$sql = "SELECT * FROM u_workout_plan_supplemental " .
							"WHERE is_active = 1 " .
							"AND z_user_id = " . $this->id . " " .
							"ORDER BY day";
			$ex = $this->dbOb->query($sql);
			return $ex;
		}



	 /**
	  * Add a custom exercise to the plan
	  */
	public function addExerciseToPlan(){

	 	$sql="SELECT * FROM p_workout_exercises WHERE TRIM(name) like TRIM('".$this->dbOb->escape_string($this->data['name'])."')";
	 	$rec=$this->dbOb->query($sql);
	 	
	 	if($rec){//It's not a custom ex, was it already assigned for the requested day?
	 		 $sql="SELECT * 
	 		 	   FROM u_workout_plan_subscriptions AS uwps
	 		 	   JOIN p_workout_plans AS pwp ON pwp.id=uwps.p_workout_plan_id
	 		 	   JOIN p_workout_plan_exercises AS pwpe ON pwpe.p_workout_plan_id=uwps.p_workout_plan_id
	 		 	   JOIN p_workout_exercises AS pwe ON pwe.id=pwpe.p_workout_exercises
	 		 	   WHERE uwps.z_user_id='".$this->id."'
	 		 	   AND pwe.id='".$this->dbOb->escape_string($rec[0]['id'])."' AND pwpe.day='".$this->dbOb->escape_string($this->data['day'])."'";
	 		 $record=$this->dbOb->query($sql); 
	 		 if($record){return;} //already assigned!
	 	}
	 	$sql="SELECT * FROM u_workout_plan_supplemental WHERE 
	 		 z_user_id='".$this->id."' AND
	 		 day='".$this->dbOb->escape_string($this->data['day'])."' AND 
	 		 TRIM(name) like TRIM('".$this->dbOb->escape_string($this->data['name'])."')";
	 	$record=$this->dbOb->query($sql);
	 	
	 	if($record){ return;} //do not add the exercise multiple times per day!}
		if($rec){
			$this->data['id']=$rec[0]['id'];
			if ($rec[0]['category'] == 'cardio') {
				$this->data['METs'] = $rec[0]['METs'];
				if ($this->data['duration'] == 0) {
					$this->data['duration'] = $rec[0]['duration'];
				}
			}
		}else{
			$this->data['id']=0;
		}
		
		$sql="INSERT INTO u_workout_plan_supplemental " .
			"(z_user_id,p_workout_exercises_id,name,type,sets,reps,weight,METs,duration,day) " .
			"VALUES('".$this->id."','" .
				$this->dbOb->escape_string($this->data['id']) . "','" .
				$this->dbOb->escape_string($this->data['name']) . "','" .
				$this->dbOb->escape_string($this->data['category']) . "','" .
				$this->dbOb->escape_string($this->data['sets']) . "','" .
				$this->dbOb->escape_string($this->data['reps']) . "','" .
				$this->dbOb->escape_string($this->data['weight']) . "','" .
				$this->dbOb->escape_string($this->data['METs']) . "','" .
				$this->dbOb->escape_string($this->data['duration']) . "','" .
				$this->dbOb->escape_string($this->data['day']) . "')";
		$this->dbOb->insert($sql);			 	
	 }


	/**
	 * Delete a supplemental exercise from the user's plan
	 *
	 */
	public function deleteExerciseFromPlan($id) {
		$sql = "UPDATE u_workout_plan_supplemental SET is_active = 0 " .
						"WHERE z_user_id = " . $this->id . " " .
						"AND id = " . $this->dbOb->escape_string($id);
		$this->dbOb->update($sql);
	}


	 /**
	  * Get a loged strength entry
	  * @param int $id
	  * @return array
	  */
	 function getStrengthEntry($id){
	 	$sql="SELECT * FROM u_tracker_exercises WHERE 
	 		z_user_id='".$this->id."' AND
	 		id='".$this->dbOb->escape_string($id)."'";
	 	$record=$this->dbOb->getRow($sql);
	 	if(!$record) throw new Exception("record does not exist for current user!");
	 	if($record['p_workout_exercises_id']!=0 && $record['supplemental']==0){
	 		$sql="SELECT * FROM p_workout_exercises WHERE id='".$this->dbOb->escape_string($record['p_workout_exercises_id'])."'";
	 		$data=$this->dbOb->getRow($sql);
	 		$record['custom_name']=$data['name'];
	 	}else if($record['p_workout_exercises_id']!=0 && $record['supplemental']==1){
	 		$sql="SELECT * FROM u_workout_plan_supplemental WHERE id='".$this->dbOb->escape_string($record['p_workout_exercises_id'])."'";
	 		$data=$this->dbOb->getRow($sql);
	 		$record['custom_name']=$data['name'];
	 	}
	 	return $record;
	 }
	 
	 /**
	  * Validate a strength entry
	  * @param array $arr
	  * @return false or aray of error obj
	  */
	public function validateStrengthUpdate($arr){	 
	 	$err=null;	 	
		try{
    		$this->data['id']=$this->vc->exists('id',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$this->data['sets']=$this->vc->exists('sets',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	try{
    		$this->data['reps']=$this->vc->exists('reps',$arr,"integer",array("rangex_low"=>0),false,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
    	try{
    		$this->data['weight']=$this->vc->exists('weight',$arr,"integer",array("range_low"=>0),true,true);
    	}catch(ValidationException $e){
    		$err[]=$e->createErrorObject();
    	}
	 	
    	return ($err)?$err:false;
	 }
	 
	 /**
	  * Add entry to log
	  */
	 public function updateStrengthEntry(){
	   $sql="UPDATE u_tracker_exercises SET 
	   		sets='".$this->dbOb->escape_string($this->data['sets'])."',
	 		reps='".$this->dbOb->escape_string($this->data['reps'])."',
	 		weight='".$this->dbOb->escape_string($this->data['weight'])."'
	 		WHERE id='".$this->dbOb->escape_string($this->data['id'])."'";
	   $this->dbOb->update($sql);	   
	 }
	 
	 public function getLastEnteredExercises($type="cardio"){
	 	$type=strtolower($type);
	 	$sql="SELECT date_entered FROM u_tracker_exercises WHERE z_user_id='".$this->id."' ORDER BY date_entered DESC LIMIT 0,1";
	 	$date=$this->dbOb->getOne($sql);
	 	$data=new stdClass();
	 	$data->calories=0;
	 	$data->exercises=null;
	 	if(!$date){
	 		return $data;
	 	}
	 	$sql="SELECT * FROM u_tracker_exercises WHERE z_user_id='".$this->id."' AND date_entered='".$this->dbOb->escape_string($date)."'";
	 	$exercises=$this->dbOb->query($sql);
	 	foreach($exercises as $entry){
	 		if($entry['custom']=="strength" && $type!="all" && $type!="strength") continue;
	 		if($entry['custom']=="cardio" && $type!="all" && $type!="cardio") continue;
	 		if($entry['p_workout_exercises_id']!=0){	 			
	 			$sql="SELECT name,category from p_workout_exercises WHERE id='".$this->dbOb->escape_string($entry['p_workout_exercises_id'])."'";
	 			$row=$this->dbOb->getRow($sql);
	 			if($row['category']=="cardio"){
	 				if($type!="all" && $type!="cardio") continue;
	 			}else{//if it is not a cardio exercise it is one of the strength types
	 				if($type!="all" && $type!="strength") continue;
	 			}
	 			$entry['custom_name']=$row['name'];
	 			$data->calories+=$entry['calories'];
	 			$data->exercises[]=$entry;
	 		}
	 	}
	 	
	 	return $data; 
	 }

	public function getEnteredExercises($type="cardio",$exp="1", $range="month"){
	 	$type=strtolower($type);
	 	switch($type){
	 		case 'all':
	 		case 'cardio':
	 		case 'strength': break;
	 		default: throw new Exception("Invalid type ".$type);
	 	}
	 	$range=strtoupper($range);
	 	$expr=intval($exp);
	 	if($expr<=0) throw new Exception("invalid range ".$expr);
	 	switch($range){
	 		case 'DAY':	 		
	 		case 'WEEK':
	 		case 'MONTH':
	 		case 'QUARTER':
	 		case 'YEAR': break;
	 		default: throw new Exception("INVALID expr ".$range);	 		
	 	}
	 	
	 	
	 	$sql="SELECT * FROM u_tracker_exercises WHERE z_user_id='".$this->id."' 
	 	AND  date_entered>=DATE_SUB(CURDATE(), INTERVAL ".$expr." ".$range.") ";
	 	if($type!="all"){
	 		$sql.=" AND( custom='no' || custom='".$type."') ";
	 	}
	 	$sql.=" ORDER BY date_entered DESC";
	 	$exercises=$this->dbOb->query($sql);	 		 	

	 	$maxUnit=-1;$minUnit=1000000;
	 	$maxCal=-1;$minCal=1000000;
	 	
	 	foreach($exercises as $entry){
	 		
	 		if($entry['custom']=="strength" && $type!="all" && $type!="strength") continue;
	 		if($entry['custom']=="cardio" && $type!="all" && $type!="cardio") continue;
	 		
	 		if($entry['p_workout_exercises_id']!=0){	 			
	 			$sql="SELECT name,category from p_workout_exercises WHERE id='".$this->dbOb->escape_string($entry['p_workout_exercises_id'])."'";
	 			$row=$this->dbOb->getRow($sql);
	 			
	 			if($row['category']=="cardio"){
	 				if($type!="all" && $type!="cardio") continue;
	 			}else{//if it is not a cardio exercise it is one of the strength types
	 				if($type!="all" && $type!="strength") continue;
	 			}
	 			$entry['custom_name']=$row['name'];
	 		}
	 		
	 		if($maxCal<$entry['calories']) $maxCal=$entry['calories'];
	 		if($minCal>$entry['calories']) $minCal=$entry['calories'];

	 		if($maxUnit<$entry['units']) $maxUnit=$entry['units'];
	 		if($minUnit>$entry['units']) $minUnit=$entry['units'];
	 			
	 		$data->calories+=$entry['calories'];
	 		$data->exercises[]=$entry;
	 	}
	 	
	 	$data->minCal=$minCal;
	 	$data->maxCal=$maxCal;
	 	$data->minUnit=$minUnit;
	 	$data->maxUnit=$maxUnit;	 	
	 	return $data; 
	 }

	public function getEnteredExercisesForWeek($date){
		if($date==""){
			$date=date("Y-m-d");
		}
		$endDate= strtotime("-6 days",strtotime($date));
		
		$enums=array();
		$dexercises=array();
		$key=array();
		
		for($x=0;$x<7;$x++){
			$dexercises[$x]=array();
			$enum=date("l F j, Y",strtotime("-".$x." days",strtotime($date)));
			$key[$enum]=$x;
		}

	 	$sql="SELECT *,DATE_FORMAT(date_entered,'%W %M %e, %Y') AS df FROM u_tracker_exercises  WHERE z_user_id='".$this->id."' 
	 	AND ( DATE_FORMAT(date_entered,'%Y%m%d')<=DATE_format('".$date."','%Y%m%d') 
	 	   OR DATE_FORMAT(date_entered,'%Y%m%d')>=DATE_format('".$endDate."','%Y%m%d')
	 	) ORDER BY date_entered DESC";
	 	
	 	
	 	$exercises=$this->dbOb->query($sql);	 		 	
	 	
	 	foreach($exercises as $entry){	 		
	 		if($entry['p_workout_exercises_id']!=0){	 			
	 			$sql="SELECT name,category from p_workout_exercises WHERE id='".$this->dbOb->escape_string($entry['p_workout_exercises_id'])."'";
	 			$row=$this->dbOb->getRow($sql);	 			
	 			$entry['custom_name']=$row['name'];
	 		}
	 			 		
	 		if(!isset($data->exercises[$entry['df']])){
	 			$data->exercises[$entry['df']]=array();
	 		}
	 		$dexercises[$key[$entry['df']]][]=$entry;
	 	}
	 	return $dexercises; 
	 }
}

/**
 * sort records by name field
 * @param unknown_type $a
 * @param unknown_type $b
 */
function sortName($a,$b){	 	
 	return strcmp($a['name'],$b['name']);
}

/**
 * Sort records by date entered,name
 * @param $a
 * @param $b
 */
function sortDateName($a,$b){
	$v=strcmp($a['date_entered'],$b['date_entered']);
	if($v==0) return strcmp($a['name'],$b['name']);
	return $v;
}
	 