<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/Validator.class.php");
require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");
require_once (ROOT_DIR."classes/model/ModuleBreakITModel.php");
require_once (ROOT_DIR."classes/model/ModuleControlITModel.php");
require_once (ROOT_DIR."classes/model/ModuleLoseITModel.php");
require_once (ROOT_DIR."classes/model/ModuleMoveITModel.php");
require_once (ROOT_DIR."classes/model/ModuleReduceITModel.php");

class IFocusModel {
	const START=0;//new health assesment
	const INPROGRESS=1;//assessment is less then 6months old!
	const COMPLETE=2;//assessment is less then 6months old!
	
	private $data=array();
	private $dbOb=null;
	private $vc=null;
	private $id=0;
	private $categories;    
	private $status=0;
	private $gina=false;
	private $wlq=false;
  private $nq;
  private $gender;
	private $acceptUpdates=true;


	/**
	 * Class constructor
	 *	$id - z_user_id of the caller
	 *	$createNewIfComplete - flag to create a new iFOCUS row
	 *	$specificReport - date of report to be displayed
	 */
	public function __construct($id,$createNewIfComplete=false,$specificReport=0){
		$this->categories=array(
        //'demographics'=>array('first_name','last_name','address1','address2','city','state','zipcode','email','dob','gender','race','marital_status','education','vision','hearing','language'),
      'demographics'=>array(),    	
    	'current_health_status'=>array('q4','q5_0','q5_1','q5_2','q5_3','q5_4','q5_5','q5_6','q5_7','q5_8','q5_9','q5_10','q5_11','q5_12','q5_13','q5_14','q5_15','q7','q9'),
    	'family_health'=>array('q10_1','q10_2','q10_3','q10_4','q10_5','q10_6'),
    	'preventative_health'=>array('q8','q11','q12','q13','q14','q15','q16','q17','q18','q19','q20'),
    	'weight_nutrition'=>array('q21','q22','q23_1','q23_2','q23_3','q23_4','q23_5','q23_6','q23_7','q23_8', 'qn24', 'qn25'),
    	'physical_activity'=>array('q24','q25','q26'),
    	'stress_management'=>array('q27','q28','q29','q30','q31','q32','q33'),
    	'mental_health'=>array('q38_1','q38_2','q38_3','q38_4','q38_5','q38_6','q38_7','q38_8'),
			'alcohol_use' => array('q39', 'q40'),
    	'self_care'=>array('q41','q42','q42a', 'q43'),
    	'tobaco_use'=>array('q44','q45','q46','q47'),
    	'readiness_to_change'=>array('q48_1','q48_2','q48_3','q48_4','q48_5','q48_6','q48_7'),
    	'wlq'=>array('wlq1a', 'wlq1b','wlq2a','wlq2b','wlq3','wlq4'),
    	'biometric_data'=>array('height','weight','bp_systolic','bp_diastolic','body_fat','bmi','waist','blood_glucose','glucose_test','hemoglobin','cotinine','cholesterol','triglycerides','hdl','ldl'),
    	);

//
//Database table columns that are free due to changes from client
//	q6, q34_1, q34_2, q35, q36, q37_1, q37_2
//

		//Number of questions in each category.
		$this->nq = array ('demographics'  => 3,
		                   'current_health_status' => 4,
		                   'family_health' => 1,
		                   'preventative_health_M' => 7,
		                   'preventative_health_F' => 8,
		                   'weight_nutrition' => 5,
		                   'physical_activity' => 3,
		                   'stress_management' => 7,
		                   'mental_health' => 1,
		                   'alcohol_use' => 2,
		                   'self_care' => 4,
		                   'tobaco_use' => 4,
		                   'readiness_to_change' => 1,
		                   'wlq' => 4,
		                   'biometric_data' => 12);

		foreach($this->categories as $cat=>$val){
			$this->data[$cat]=array();
		}

		$cred=UserCredentials::load();
		$this->acceptUpdates=$id==$cred->getId();

		$this->dbOb=Database::create();
		$this->retrieve($id,$createNewIfComplete,$specificReport);
		$this->vc= new Validator();

		$sql="SELECT is_gina,is_wlq FROM u_profile 
    		  JOIN p_company on u_profile.company_id=p_company.id 
    		  WHERE z_user_id='".$this->dbOb->escape_string($id)."'"; 	
		$r=$this->dbOb->getRow($sql);
		$this->gina=$r['is_gina']==1;
		$this->wlq=$r['is_wlq']==1;
	}


	public function getCurrentPage($page){
    	$count=1;
    	foreach($this->categories as $key=>$arr){
    		if(!$this->gina && $key=="family_health") continue; //no family health section if gina compliant
				if(!$this->wlq && $key=="wlq") continue; //no wlq if not wlq compliant
    		
    		if($page==$key){
    			return $count;    		
    		}
    		$count++;
    	}
    	return 0;//invalid page
	}


	/**
	 * 
	 */
	public function getTotalPages(){
    	return sizeof($this->categories)-($this->isGina()?0:1)+($this->isWLQ()?1:0);
	}


	/**
	 * Check to see if the category is valid
	 * @param string $cat
	 * @return boolean
	 */
	public function isValidCat($cat){
    	return isset($this->categories[$cat]); 
	}


	/**
	 * getId()
	 *	return the DB row id for this iFOCUS assessment
	 */
	public function getId() {
		return $this->data['id'];
	}

	/**
	 * Calculate the number of the first question on the
	 * requested page (by category).  The number based on
	 * the number questions on previous pages.
	 *	@param  $cat = string of the category name
	 *	@return $n = the number for the first question in
	 *	             in that category
	 */	 
	public function getFirstQuestionNo($cat) {
		$n = 0;

		// Start at the bottom and work your way back
		// adding the number of questions.
		switch($cat) {
			case 'biometric_data' :
				if ($this->wlq) {
					$n += $this->nq['wlq'];
				}
				else {
					$n += $this->nq['readiness_to_change'];
				}

			case 'wlq' :
				if ($this->wlq) {
					$n += $this->nq['readiness_to_change'];
				}

			case 'readiness_to_change' :
				$n += $this->nq['tobaco_use'];

			case 'tobaco_use' :
				$n += $this->nq['self_care'];

			case 'self_care' :
				$n += $this->nq['alcohol_use'];

			case 'alcohol_use' :
				$n += $this->nq['mental_health'];

			case 'mental_health' :
				$n += $this->nq['stress_management'];

			case 'stress_management' :
				$n += $this->nq['physical_activity'];

			case 'physical_activity' :
				$n += $this->nq['weight_nutrition'];

			case 'weight_nutrition' :
				$n += $this->nq['preventative_health_'.$this->gender];

			case 'preventative_health' :
				if ($this->gina) {
					$n += $this->nq['family_health'];
				}
				else {
					$n += $this->nq['current_health_status'];
				}

			case 'family_health' :
				if ($this->gina) {
					$n += $this->nq['current_health_status'];
				}

			case 'current_health_status' :
				$n += $this->nq['demographics'];

			case 'demographics' :
				$n += 1;
				break;
			}

			return $n;
		}


    /**
     * Retrieve a health assessment record
     * @param int $id - user id
     * @param boolean $createNewIfComplete - create a new record if the current records is marked as complete
     * @param int  $specificReport - id of a specific record the belongs to the user.
     */
	private function retrieve($id,$createNewIfComplete,$specificReport){
    	$this->id=$id;
    	//check to see if there is a curent ifocus record
    	$limiter=" AND h.date_completed='0000-00-00'";

    	if(!$specificReport){
	    	//get the current one     	
    		$record=$this->dbOb->getRow("SELECT * FROM u_mod_ifocus WHERE z_user_id='".$this->dbOb->escape_string($id)."' AND is_active = 1 ORDER BY id DESC");
    	}else{
    		$record=$this->dbOb->getRow("SELECT * FROM u_mod_ifocus WHERE z_user_id='".$this->dbOb->escape_string($id)."' AND is_active = 1 AND id='".$this->dbOb->escape_string($specificReport)."'");
    		if(!$record) throw new Exception("Error no such completed iFocus for user!");
    	}
    	
    	if(!$record){//no record found the iFocus module has not been started ever for the user!
    		$sql="INSERT INTO u_mod_ifocus(z_user_id) VALUE('".$this->dbOb->escape_string($id)."')";
    		$this->dbOb->insert($sql); 
    		$this->status=IFocusModel::START;    
    	}else if($record['date_updated']=="0000-00-00 00:00:00"){//we have an ifocus module started
    		$this->status=IFocusModel::START;
    	}
    	else if($record['date_completed']=="0000-00-00"){//we have an ifocus module in progress
    		$this->status=IFocusModel::INPROGRESS;
    	}else if($createNewIfComplete){//we have a completed module. Only start a new one if we are at the start page
    		$this->status=IFocusModel::START;
    		$sql="INSERT INTO u_mod_ifocus(z_user_id) VALUE('".$this->dbOb->escape_string($id)."')";
    		$newId=$this->dbOb->insert($sql);
    		$limiter=" AND h.date_completed='0000-00-00'";
    		unset($record["id"]);
    		unset($record["date_added"]);
    		unset($record["date_updated"]);
    		unset($record["date_completed"]);
    		unset($record["is_active"]);
    		unset($record["z_user_id"]);

    		//copy old answers over we can update them as we go
    		$sql="UPDATE u_mod_ifocus SET ";
    		foreach($record as $key=>$value){
    			$sql.="`".$key."`='".$this->dbOb->escape_string($value)."',";
    		}
    		$sql.=" is_active=1, date_updated=NOW() WHERE id='".$newId."'";
    		$this->dbOb->update($sql);
    		$record=null;
    	}else{//we  have a completed record so we must be at the total page
    		$this->status=IFocusModel::COMPLETE;
    		$limiter = " AND h.date_completed != '0000-00-00'";
    	}

			$this->data['id'] = $record['id'];
			$this->data['last_completed'] = $record['last_completed'];
			$this->data['date_completed'] = $record['date_completed'];
    	foreach($this->categories as $key=>$cat){
    		foreach ($cat as $field){
    			$this->data[$key][$field]=$record[$field];
    		}
    	}

//DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(z.date_of_birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(z.date_of_birth, '00-%m-%d')) AS age
		//get some of the previously enetered data from the user profile that the health assessment requires 

    	$sql="SELECT  z.*,u.*,h.vision,h.hearing,h.language,h.other, ((date_format(now(),'%Y') - date_format(u.dob,'%Y')) - (date_format(now(),'00-%m-%d') < date_format(u.dob,'00-%m-%d'))) AS age  
    		  FROM z_users AS z
    		  JOIN u_profile AS u ON u.z_user_id=z.id 
    		  JOIN u_mod_ifocus AS h ON h.z_user_id=z.id
    		  WHERE z.id='".$this->dbOb->escape_string($id)."' ".
    		 "AND h.is_active = 1 " . $limiter;

    	$this->demographics=$this->dbOb->getRow($sql);//get demographic info
			if (!isset($this->demographics['gender'])) {
				$sql = "SELECT gender FROM u_profile WHERE z_user_id=" . $this->dbOb->escape_string($id);
				$this->gender = $this->dbOb->getOne($sql);
			}
			else {
				$this->gender = $this->demographics['gender'];
			}

			if (!isset($this->demographics['language']) || strlen($this->demographics['language']) == 0) {
				if ($this->demographics['language_id'] > 10) {
					$this->demographics['language'] = "Other";
					$this->demographics['other'] = $this->demographics['language_id'] - 10;
				}
				else {
					if (isset($this->demographics['language_id']) && $this->demographics['language_id'] > 0) {
						$lang_id = $this->demographics['language_id'];
					}
					else {
						$sql = "SELECT language_id FROM u_profile WHERE z_user_id = " . $this->dbOb->escape_string($id);
						$lang_id = $this->dbOb->getOne($sql);
					}
					$sql = "SELECT name FROM p_user_option_language WHERE id = " . $lang_id;
					$this->demographics['language'] = $this->dbOb->getOne($sql);
					$this->demographics['other'] = 0;
				}
			}

    	$this->setBiometricDefaults();//get biometric info
	}


  public function getDateCompleted() {
  	return $this->data['date_completed'];
  }

	public function getUserName() {
		return $this->demographics['first_name'] . " " . $this->demographics['last_name'];
	}

	public function getUserAge() {
		return $this->demographics['age'];
	}

    /** 
     * Has this record been completed
     * @return boolean
     */
	public function getCompleted(){
     $sql="SELECT id as value ,DATE_FORMAT(date_completed,'%M %d, %Y') as display FROM u_mod_ifocus WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' AND is_active = 1 AND date_completed!='0000-00-00' ORDER BY date_completed DESC";
     return $this->dbOb->query($sql);
	}
 
	public function getProgress(){return $this->status;}

	/**
	 * Return a list of IT modules that might suit this user
	 * 
	 */
	public function getSuggestedModules() {

// array of which modules are valid for this company
		$valid = array('ReduceIT' => false,
										'MoveIT'	=> false,
										'LoseIT'	=> false,
										'BreakIT'	=> false);

// get the subscribed modules and set the valid array
		$sql = "SELECT m.name FROM p_modules AS m " .
						"JOIN p_company_modules AS comp ON comp.p_module_id = m.id " .
						"JOIN u_profile AS prof ON prof.company_id = comp.p_company_id " .
						"WHERE prof.z_user_id = " . $this->dbOb->escape_string($this->id) . " " .
						"AND m.type = 'IT'";
		$list = $this->dbOb->query($sql);
		foreach($list as $mn) {
			$name = preg_replace("/ /", "", $mn['name']);
			$valid[$name] = true;
		}

// get iFOCUS scores for each valid module
		$modules = array();
		foreach($valid as $key => $value) {
			if ($value) {
				if ($key == 'ReduceIT') {
					$score = $this->score_stress_management();
					$modules['ReduceIT'] = $score->total;
				}
				else if ($key == 'MoveIT') {
					$score = $this->score_physical_activity();
					$modules['MoveIT'] = $score->total;
				}
				else if ($key == 'LoseIT') {
					$score = $this->score_weight_nutrition();
					$modules['LoseIT'] = $score->total;
				}
				else if ($key == 'BreakIT') {
					$score = $this->score_tobaco_use();
					$modules['BreakIT'] = $score->total;
				}
				else {
					unset($valid[$key]);
				}
			}
		}

		$sql = "SELECT name, class_name, short_description, image FROM p_modules " .
						"WHERE name IN ('Break IT', 'Lose IT', 'Move IT', 'Reduce IT')";
		$pgm_list = $this->dbOb->query($sql);

// populate image and link for each module
/*
		$dets = array('ReduceIT'	=> array ('link'	=> "/ModuleReduceIT/Index",
    											'img'	=> "/assets/media/images/reduceit/slide_reduceit.jpg",
    											'alt'	=> "ReduceIT"),
    					'MoveIT'	=> array ('link'	=> "/ModuleMoveIT/Index",
    											'img'	=> "/assets/media/images/moveit/slide_moveit.jpg",
    											'alt'	=> "MoveIT"),
    					'LoseIT'	=> array ('link'	=> "/ModuleLoseIT/Index",
    											'img'	=> "/assets/media/images/loseit/slide_loseit.jpg",
    											'alt'	=> "LoseIT"),
    					'BreakIT'	=> array ('link'	=> "/ModuleBreakIT/Index",
    											'img'	=> "/assets/media/images/breakit/slide_breakit.jpg",
    											'alt'	=> "BreakIT")
    				);
*/
		$details = array();
		$keys = array();
		foreach($pgm_list as $pgm) {
			$arr = array($pgm['class_name'] => array('link' => "/" . $pgm['class_name'] . "/Index",
			                                         'img' =>  $pgm['image'],
			                                         'alt' =>  $pgm['name'],
			                                         'text' => $pgm['short_description']));
			array_push($details, $arr);
			array_push($keys, $pgm['class_name']);
		}


// if user has already taken the module, set the score to higher than the highest (5)
		$detail = array();
		foreach ($modules as $module => $score) {
			if ($module == "ReduceIT") {
				$model = new ModuleReduceITModel(true);
				if ($model->getLastCompleted() == 5) {
					$modules[$module] = 100.0;
				}
			}
			else if ($module == "MoveIT") {
				$model = new ModuleMoveITModel(true);
				if ($model->getLastCompleted() == 5) {
					$modules[$module] = 100.0;
				}
			}
			else if ($module == "LoseIT") {
				$model = new ModuleLoseITModel(true);
				if ($model->getLastCompleted() == 5) {
					$modules[$module] = 100.0;
				}
			}
			else if ($module == "BreakIT") {
				$model = new ModuleBreakITModel(true);
				if ($model->getLastCompleted() == 5) {
					$modules[$module] = 100.0;
				}
			}
		}

// sort modules by score
		$Arr = array();
		while (count($Arr) < count($modules)) {
			$low = 99.0;
			$k = "";
			foreach($modules as $key => $score) {
				if ($score < $low) {
					$low = $score;
					$k = $key;
				}
			}
			if ($k != "") {
				$Arr[$k] = $low;
				$modules[$k] = 100.0;
			}
		}

//Return the top three (if there's three)
		$count = 0;
		foreach ($Arr as $mod => $s) {
			$count += 1;
			if ($count > 3) break;
			if ($mod == "ReduceIT") {
				for ($i = 0; $i < 4; $i++) {
					if ($keys[$i] == "ModuleReduceIT")
    				array_push($detail, $details[$i]['ModuleReduceIT']);
    		}
 	  	}
    	else if ($mod == "MoveIT") {
 				for ($i = 0; $i < 4; $i++) {
					if ($keys[$i] == "ModuleMoveIT")
    	   	array_push($detail, $details[$i]['ModuleMoveIT']);
    	  }
   		}
	   	else if ($mod == "LoseIT") {
 				for ($i = 0; $i < 4; $i++) {
					if ($keys[$i] == "ModuleLoseIT")
			   		array_push($detail, $details[$i]['ModuleLoseIT']);
			  }
    	}
    	else if ($mod == "BreakIT") {
 				for ($i = 0; $i < 4; $i++) {
					if ($keys[$i] == "ModuleBreakIT")
		    		array_push($detail, $details[$i]['ModuleBreakIT']);
		    }
    	}
    }

		return $detail;
	}

	/**
	 * Looks at the answers to quetions regarding physical activity and determines
	 * if user is 'Sedentary, Lightly Active, Moderately Active, or Very Active.
	 * Then a constant for each 'Life Style' is returned to the caller (see FoodLogModel.php
	 */ 
	public function getLifeStyleMultiplier() {

		//Get the latest iFOCUS results.  If there are none, return the sedentary value
		$reports = $this->getCompleted();
		if ($reports) {
			$this->retrieve($this->id, false, $reports[0]['value']);
		}
		else {
			return (1.2);
		}

		$ndays = 0;																						//Number of days of exercise
		if ($this->data['physical_activity']['q24']) {				//Moderate activity level
			$q24 = $this->data['physical_activity']['q24'];
			if ($q24 == 1) {																		//None
				$ndays += 0;
			}
			else if ($q24 == 2) {																//1 day/week
				$ndays += 1;
			}
			else if ($q24 == 3) {																//2 days/week
				$ndays += 2;
			}
			else if ($q24 == 4) {
				$ndays += 3;
			}
			else if ($q24 == 5) {
				$ndays += 4;
			}
		}
		if ($this->data['physical_activity']['q25']) {				//Strength training
			$q25 = $this->data['physical_activity']['q25'];
			if ($q25 == 1) {																		//None
				$ndays += 0;
			}
			else if ($q25 == 2) {																//1 day/week
				$ndays += 1;
			}
			else if ($q25 == 3) {
				$ndays += 2;
			}
			else if ($q25 == 4) {
				$ndays += 3;
			}
			else if ($q25 == 5) {
				$ndays += 4;
			}
		}
		if ($this->data['physical_activity']['q26']) {				//Stretching
			$q26 = $this->data['physical_activity']['q26'];
			if ($q26 == 1) {																		//None
				$ndays == 0;
			}
			else if ($q26 == 2) {																//1 day/week
				$ndays += 1;
			}
			else if ($q26 == 3) {
				$ndays += 2;
			}
			else if ($q26 == 4) {
				$ndays += 3;
			}
			else if ($q26 == 5) {
				$ndays += 4;
			}
		}

		if ($ndays == 0) {																		//Sedentary
			$ret = 1.2;
		}
		else if (($ndays >= 1) && ($ndays <= 2)) {						//Light activity
			$ret = 1.375;
		}
		else if (($ndays >= 3) && ($ndays <= 5)) {						//Moderate activity
			$ret = 1.55;
		}
		else if ($ndays > 5) {																//Very active
			$ret = 1.725;
		}

		return $ret;
	}



  /**
   * Set the default values for biometric data with previously enetered data.
   */
	public function setBiometricDefaults(){
    	
    	if($this->data['biometric_data']['height']=="0"){    		
    		$this->data['biometric_data']['height']=(intval($this->demographics['height_ft'])*12)+$this->demographics['height_in'];    		
    	}    	
    	
    	//get last weight entered
    	if($this->data['biometric_data']['weight']=="0"){
    		require_once(ROOT_DIR."classes/model/UserTrackWeightModel.php");
    		$w=new UserTrackWeightModel($this->id); 
    		$weight=$w->getLastEntry();
    		$this->data['biometric_data']['weight']=($weight)?$weight['weight']:0;
    	}
    	
    		//get last bp entered
    	if($this->data['biometric_data']['bp_systolic']=="0"){
    		require_once(ROOT_DIR."classes/model/UserTrackBPModel.php");
    		$bp= new UserTrackBPModel($this->id);
    		$data=$bp->getLastEntry();    		
    		$this->data['biometric_data']['bp_systolic']=($data)?$data['systolic']:0;
    		$this->data['biometric_data']['bp_diastolic']=($data)?$data['diastolic']:0;    			    		
    	}
    	
    	//calculate current BMI
    	if($this->data['biometric_data']['bmi']==0){    		
    		if($this->data['biometric_data']['height']!=0){    			
    	  		$this->data['biometric_data']['bmi']=round($this->data['biometric_data']['weight']/pow($this->data['biometric_data']['height'],2)*703,2);    			
    		}
    		require_once(ROOT_DIR."classes/model/UserTrackMeasurementsModel.php");
				$mm = new UserTrackMeasurementsModel($this->id);
				$data = $mm->getLastEntry();
    		$this->data['biometric_data']['waist'] = ($data['waist']) ? $data['waist'] : 0;
    	}
    	
    	//get Last Entered Blood Glucose
    	if($this->data['biometric_data']['blood_glucose']==0){
    		require_once(ROOT_DIR."classes/model/UserTrackBloodGlucoseModel.php");
    		$bg= new UserTrackBloodGlucoseModel($this->id);
    		$data=$bg->getLastEntry();
    		if ($data['random']['date_entered'] > $data['fasting']['date_entered']) {
	    		$this->data['biometric_data']['blood_glucose']=$data['random']['blood_glucose'];
	    		$this->data['glucose_test'] = 'random';
	    	}
	    	else {
	    		$this->data['biometric_data']['blood_glucose'] = $data['fasting']['blood_glucose'];
	    		$this->data['glucose_test'] = 'fasting';
	    	}
				if (!isset($this->data['biometric_data']['hemoglobin']))
	    		$this->data['biometric_data']['hemoglobin'] = 0;
				if (!isset($this->data['biometric_data']['cotinine']))
	    		$this->data['biometric_data']['cotinine'] = 0;
    	}
    	
       //get Last Entered Cholesterol
    	if($this->data['biometric_data']['cholesterol']==0){
    		require_once(ROOT_DIR."classes/model/UserTrackCholesterolModel.php");
    		$c= new UserTrackCholesterolModel($this->id);
    		$data=$c->getLastEntry();    		
    		$this->data['biometric_data']['cholesterol']=($data)?$data['total']:0;
    		$this->data['biometric_data']['triglycerides']=($data)?$data['triglycerides']:0;
    		$this->data['biometric_data']['hdl']=($data)?$data['hdl']:0;
    		$this->data['biometric_data']['ldl']=($data)?$data['ldl']:0;
    	}
    		
	}
    
    /**
     * get the Demographic portion
     */
	public function getDemographics(){
    	return $this->demographics; 
	}

    /**
     * save the data for a specific category
     * @param string $cat
     */
	private function update($cat){
		if(!$this->acceptUpdates) return;//do not allow health coach to change answers
		if(!isset($this->categories[$cat])) throw new Exception("Invalid category");

		$sql="UPDATE `u_mod_ifocus` SET ";
		$comma=false;
		foreach($this->data[$cat] as $key=>$value){
			if($comma) $sql.=" , ";
			$sql.="`".$key."`='".$this->dbOb->escape_string($value)."'";
			$comma=true;
		}
		if(!$comma) return; //cant update a section we have no data for
		if(!$this->id) return; //can't update a record we haven't loaded

		$sql .= ", last_completed = '" . $cat . "'";

		if($cat=="biometric_data"){
			if(!$this->isCompleted()) $sql.=", date_completed=NOW() ";
			//upon completion reward points for Health Assessment Questions
			$im=new IncentivePointsModel();
			if($this->data["preventative_health"]["q12"]==1){
				$im->addIncentivePointMA("IFocusModel","FluShot");
			}
			$im->addIncentivePointMA("IFocusModel","Complete");
		}

		$sql .= " ,date_updated=NOW() WHERE id = '" . $this->data['id'] . "'";
		$this->dbOb->update($sql);
	}

    /**
     * Does this company require GINA compliance
     * @return boolean
     */
	public function isGina(){return $this->gina;}
	public function isWLQ(){return $this->wlq;}
    
    /**
     * get the data for a specific category
     * @param string $cat
     * @return array
     */
	public function get($cat){
    	if($cat=="demographics"){
    		return $this->demographics;
    	}
    	if(isset($this->categories[$cat])){
    		return $this->data[$cat];
    	}
    	throw new Exception("Invalid category!");
	}
    
    /**
     * Get the next and last pages for the current page
     * @param $cat
     * @return array
     */
	public function getNextAndLast($cat){
    		$last="";
    		$next="";
    		$found=false;
    		$current="";
    		
    		foreach($this->categories as $key=>$arr){    		
				if(!$this->gina && $key=="family_health") continue; //no family health section if gina compliant
				if(!$this->wlq && $key=="wlq") continue; //no wlq if not wlq compliant
    			if(!$found){
    				if($key==$cat){
    					$found=true;
    				}
    				$last=$current;
    				$current=$key;
    			}else{
    				$next=$key;
    				break;
    			}
    		}
    		return array($last,$next);
	}
    
    /**
     * Get the next topic that needs to be completed
     * @param mode if true return topic name, if false return topic index
     * @return mixed 
     */
	public function getLastCompletedTopic($mode=true){
    	if($this->status==IFocusModel::START)
    		return ($mode)?"demographics":1;

			$topic = $this->data['last_completed'];
			if (!$mode) {
				if ($topic == "current_health_status") $topic = 2;
				else if ($topic == "family_health") $topic = 3;
				else if ($topic == "preventative_health") $topic = ($this->isGina()) ? 4 : 5;
				else if ($topic == "weight_nutrition") $topic = 6;
				else if ($topic == "physical_activity") $topic = 7;
				else if ($topic == "stress_management") $topic = 8;
				else if ($topic == "mental_health") $topic = 9;
				else if ($topic == "alcohol_use") $topic = 10;
				else if ($topic == "self_care") $topic = 11;
				else if ($topic == "tobaco_use") $topic = 12;
				else if ($topic == "readiness_to_change") $topic = 13;
				else if ($topic == "biometric_data") $topic = 14;
			}

			
 /*   		
    	$topic=($mode)?"current_health_status":2;
    	if($this->data['current_health_status']['q4']!="0"){
    		if($this->isGina()){
    			$topic=($mode)?"family_health":3;
    		}else{
    			$topic=($mode)?"preventative_health":4;
    		}
    	}
    	if($this->isGina() && $this->data['family_health']['q10_1']!="0")
    		$topic=($mode)?"preventative_health":5;
    		
    	if($this->data['preventative_health']['q11']!="0000-00-00 00:00:00")
    		$topic=($mode)?"weight_nutrition":6;
    		
    	if($this->data['weight_nutrition']['q21']!="0")
    		$topic=($mode)?"physical_activity":7;
    	
    	if($this->data['physical_activity']['q24']!="0")
    		$topic=($mode)?"stress_management":8;

    	if($this->data['stress_management']['q27']!="0")
    		$topic=($mode)?"productivity":9;

    	if($this->data['productivity']['q34_1']!="0")
    		$topic=($mode)?"mental_health":10;

    	if($this->data['mental_health']['q38_1']!="0")
    		$topic=($mode)?"self_care":11;

    	if($this->data['self_care']['q41']!="0")
    		$topic=($mode)?"tobaco_use":12;
    		
    	if($this->data['tobaco_use']['q44']!="0")
    		$topic=($mode)?"readiness_to_change":13;
    		
    	if($this->data['readiness_to_change']['q48_1']!="0")
    		$topic=($mode)?"biometric_data":14;
*/
    	
    	return $topic;
	}
    
    /**
     * Set a update the answeres for a specific category
     * @param string $cat -cat name
     * @param array $arr  -data
     * returns false if data validated, else an array of error objs  
     */
	public function set($cat,$arr){
		if($cat=="demographics"){
			return $this->set_demographics($arr);
		}
    	if (isset($this->categories[$cat])){
    		$fn="set_".$cat;
    		$v=$this->$fn($arr);
    		if($v==false){
    			$this->update($cat);
    			return false;
    		}
    		return $v;
    	}
    	throw new Exception("Invalid category!");
	}
	
    
    /**
     * validate the data for demographic data
     * @param array $arr -data
     */
    private function set_demographics($arr){
			$qno = $this->getFirstQuestionNo('demographics');
    	$err=null;
 			/*
    	require_once (ROOT_DIR."classes/model/UserProfileModel.php");
    	$up= new UserProfileModel($this->id);
    	$this->demographics['first_name'] = $up->get('first_name');
			$this->demographics['last_name'] = $up->get('last_name');

    	$err1=$up->validateProfileInfo($arr);    	
    	$err2=$up->validateUserInfo($arr);
    	
    	if($err1)$err=$err1;
    	if($err2){
    		if(!$err){
    			$err=$err2;
    		}else{
    			foreach($err2 as $e){
    				$err[]=$e;
    			}
    		}
    	}
    	*/
    	
    	try{
    		$this->demographics['vision']=$this->vc->exists('vision',$arr,"enum",array("values"=>array("Y","N"),"case_sensitive"=>false),false,true);
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please select whether you have a vision impairment or not";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

    	$qno += 1;
    	try{
    		$this->demographics['hearing']=$this->vc->exists('hearing',$arr,"enum",array("values"=>array("Y","N","U"),"case_sensitive"=>false),false,true);
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please select wether you have a hearing impairment or not";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

			$qno += 1;
			try {
				$this->demographics['language'] = $this->vc->exists('language', $arr, "enum", array("values" => array("English", "Spanish", "Other"), "case_sensitive" => true), false, true);
			}
			catch (ValidationException $e) {
				$eob = $e->createErrorObject();
				$eob->message = "Please select a primary language";
				$eob->name = "Question " . $qno;
				$err[] = $eob;
			}

			if ($this->demographics['language'] == "Other") {
				try {
					$this->demographics['other'] = $this->vc->exists('other', $arr, "integer", array("rangex_low" => 0), false, true);
				}
				catch (ValidationException $e) {
					$eob = $e->createErrorObject();
					$eob->message = "Please specify from the drop down, a primary language";
					$eob->name = "Question " . $qno;
					$err[] = $eob;
				}
			}
			else {
				$this->demographics['other'] = 0;
			}

    	if ($err)return $err;

    	if(!$this->acceptUpdates) return false; //do not let health coach update info!

		 	$sql="UPDATE `u_mod_ifocus` SET " .
    		"vision='".$this->dbOb->escape_string($this->demographics['vision'])."', " .
    		"hearing='".$this->dbOb->escape_string($this->demographics['hearing'])."', " .
    		"language='".$this->dbOb->escape_string($this->demographics['language'])."', " .
    		"other='".$this->dbOb->escape_string($this->demographics['other'])."', " .
    		"date_updated=NOW() " .
				"WHERE id='".$this->dbOb->escape_string($this->data['id'])."'";
//    		"WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";

    	$this->dbOb->update($sql);    	
    	//$up->updateProfileInfo();
    	//$up->updateUserInfo();

			/*
			** Set the user's language in the profile table to the selection from iFOCUS
			*/
			if ($this->demographics['language'] == "Other") {
				$lang_id = $this->demographics['other'] + 10;
			}
			else {
				$sql = "SELECT id FROM p_user_option_language WHERE name = '" . $this->demographics['language'] . "'";
				$lang_id = $this->dbOb->getOne($sql);
			}
			
			$sql = "UPDATE `u_profile` SET language_id = " . $lang_id . " " .
							"WHERE z_user_id = " . $this->dbOb->escape_string($this->id);
			$this->dbOb->update($sql);


    	return false;
    }
    
    /**
     * validate the data for current_health_status data
     * @param array $arr -data
     */
    
	private function set_current_health_status($arr){
		$qno = $this->getFirstQuestionNo('current_health_status');
		$err=null;
		try{
    		$v=$this->vc->exists('q4',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['current_health_status']['q4']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please give your opinion of your overall health";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

			$qno += 1;
    	for($x=0;$x<=15;$x++){
    		try{
    			$v=$this->vc->exists('q5_'.$x,$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    			$this->data['current_health_status']['q5_'.$x]=($v=="")?0:$v;
    		}catch(ValidationException $e){
    			$eb=$e->createErrorObject();
    			$eb->message = "Be sure to select an answer for all conditions";
    			$eb->name = "Question " . $qno;
    		}
    	}
    	if (isset($eb)) {
    		$err[] = $eb;
    	}
/*    	
			$qno += 1;
    	try{
    		$v=$this->vc->exists('q6',$arr,"enum",array("values"=>array(1,2)),false,false);
    		$this->data['current_health_status']['q6']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer if you have medical condition that requires you to use your medical benefits";
    		$eob->name = "Question " . $qno;
    	}
*/
			$qno += 1;
    	try{
    		$v=$this->vc->exists('q7',$arr,"enum",array("values"=>array(1,2)),false,false);
    		$this->data['current_health_status']['q7']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please express if you understand your medical benefits";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}
/*
			$qno += 1;
    	try{
    		$v=$this->vc->exists('q8',$arr,"enum",array("values"=>array(1,2,3)),false,false);
    		$this->data['current_health_status']['q8']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer if you get a yearly physical examination";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}
*/
			$qno += 1;
    	try{
    		$v=$this->vc->exists('q9',$arr,"enum",array("values"=>array(1,2)),false,false);
    		$this->data['current_health_status']['q9']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer whether you can care for a minor injury";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}    	
    	return ($err)?$err:false;    	
    }


	/**
	 * validate the data for family_health data
	 * @param array $arr -data
	 */    
	private function set_family_health($arr){
		$qno = $this->getFirstQuestionNo('family_health');
		$err=null;
		for($x=1;$x<=6;$x++){
    		try{
    			$v=$this->vc->exists('q10_'.$x,$arr,"enum",array("values"=>array(1,2,3)),false,false);
    			$this->data['family_health']['q10_'.$x]=($v=="")?0:$v;
    		}catch(ValidationException $e){
    			$eob=$e->createErrorObject();
    			$eob->message = "Be sure to select an answer for all of the listed conditions";
    			$eob->name = "Question " . $qno;
    		}
    	}
    	if (isset($eob)) {
    		$err[] = $eob;
    	}
    	return ($err)?$err:false;
    }


	/*
	 * validdate WLQ data
	 * $arr - data
	 */
	private function set_wlq($arr){
		$qno = $this->getFirstQuestionNo('wlq');
		$err=null;
		$keys=array('wlq1','wlq2','wlq3','wlq4');
		foreach($keys as $key){
			if (($key == 'wlq1') ||
					($key == 'wlq2')) {
				for ($sub = 'a'; $sub <= 'b'; $sub++) {
					$k = $key . $sub;
					try{
    				$v=$this->vc->exists($k,$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    				$this->data['wlq'][$k]=($v=="")?0:$v;
		    	}catch(ValidationException $e){
    				$eob=$e->createErrorObject();
 		   			$eob->message = "Please select an answer";
    				$eob->name = "Question " . $qno;
    				$err[] = $eob;
    			}
    		}
			}
			else {
				try {
    			$v=$this->vc->exists($key,$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    			$this->data['wlq'][$key]=($v=="")?0:$v;
		    }catch(ValidationException $e){
    			$eob=$e->createErrorObject();
 		   		$eob->message = "Please select an answer";
    			$eob->name = "Question " . $qno;
    			$err[] = $eob;
    		}
    	}    	
    	$qno += 1;
		}
	}


	/**
	 * validate the data for preventative_health
	 * @param array $arr -data
	 */    
	private function set_preventative_health($arr){
		$qno = $this->getFirstQuestionNo('preventative_health');
		$err=null;
		try{//10. Do you obtain a yearly physical examination?
			$v=$this->vc->exists('q8',$arr,"enum",array("values"=>array(1,2)),true,false);    		
			$this->data['preventative_health']['q8']=($v=="")?0:$v;
		}catch(ValidationException $e){
			$eob=$e->createErrorObject();
			$eob->message = "Please answer whether you get an annual physical exam";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		}

		$qno += 1;
		try{//10. When was your last annual physical exam?
			$arr['q11'] = $arr['q11_year'] . "-" . sprintf("%02d", $arr['q11_month']) . "-00";
			$v=$this->vc->exists('q11',$arr,"date",array("datestamp"=>1,"allow_zeros"=>true),true,false);    		
			$this->data['preventative_health']['q11']=($v=="")?0:$v;
		}catch(ValidationException $e){
			$eob=$e->createErrorObject();
			$eob->message = "Please select a date for your last physical exam";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		}

		$qno += 1;
		try{//11. Have you received a seasonal flu vaccine in the last 12 months?
			$v=$this->vc->exists('q12',$arr,"enum",array("values"=>array(1,2)),false,false);
			$this->data['preventative_health']['q12']=($v=="")?0:$v;
		}catch(ValidationException $e){
			$eob=$e->createErrorObject();
			$eob->message = "Please answer whether you have had a flu shot this past year";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		}    	

		$qno += 1;
		try{//12. Have you received colorectal screening by a primary care physician?
			$v=$this->vc->exists('q13',$arr,"enum",array("values"=>array(1,2,3)),false,false);
			$this->data['preventative_health']['q13']=($v=="")?0:$v;
		}catch(ValidationException $e){
			$eob=$e->createErrorObject();
			$eob->message = "Please answer if you have had a colorectal screening";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		} 

		$qno += 1;
		if($this->demographics['gender']=="F"){    	
			try{//13. When was your last pap smear (cervical cancer screening)?
				$arr['q14'] = $arr['q14_year'] . "-" . sprintf("%02d", $arr['q14_month']) . "-00";
				$v=$this->vc->exists('q14',$arr,"date",array("datestamp"=>1, "allow_zeros"=>true),true,false);
				$this->data['preventative_health']['q14']=($v=="")?0:$v;
			}catch(ValidationException $e){
				$eob=$e->createErrorObject();
				$eob->message = "Please enter a date for your last pap smear";
				$eob->name = "Question " . $qno;
				$err[] = $eob;
			}

			$qno += 1;
			try{//14. Have you ever had an abnormal pap smear?
				$v=$this->vc->exists('q15',$arr,"enum",array("values"=>array(1,2)),true,false);
				$this->data['preventative_health']['q15']=($v=="")?0:$v;
			}catch(ValidationException $e){
				$eob=$e->createErrorObject();
				$eob->message = "Please answer if you have ever had an abnormal pap smear";
				$eob->name = "Question " . $qno;
				$err[] = $eob;
			}

			$qno += 1;
			try{//15. Have you ever had a mammogram?
				$v=$this->vc->exists('q16',$arr,"enum",array("values"=>array(1,2,3)),false,false);
				$this->data['preventative_health']['q16']=($v=="")?0:$v;
			}catch(ValidationException $e){
				$eob=$e->createErrorObject();
				$eob->message = "Please answer if you have had a mammogram";
				$eob->name = "Question " . $qno;
				$err[] = $eob;
			}

			$qno += 1;
			if ($this->data['preventative_health']['q16'] == 1) {	//Only validate if previous answer was 'YES'
				try{//16. If yes, what was the date of your last mammogram?
					$arr['q17'] = $arr['q17_year'] . "-" . sprintf("%02d", $arr['q17_month']) . "-00";
					$v=$this->vc->exists('q17',$arr,"date",array("datestamp"=>1, "allow_zeros"=>true),true,false);
					$this->data['preventative_health']['q17']=($v=="")?0:$v;
				}catch(ValidationException $e){
					$eob = $e->createErrorObject();
					$eob->message = "Please enter the date for your mammogram";
					$eob->name = "Question " . $qno;
					$err[]=$eob;
				}
			}
			else {
				$this->data['preventative_health']['q17'] = "0000-00-00";
			}

			$this->data['preventative_health']['q18']=0;
			$this->data['preventative_health']['q19']=0;
			$this->data['preventative_health']['q20']="0000-00-00";
    		
		}else{
			try{//17. Have you ever discussed a prostate exam?    			
				$v=$this->vc->exists('q18',$arr,"enum",array("values"=>array(1,2,3)),false,false);
				$this->data['preventative_health']['q18']=($v=="")?0:$v;
			}catch(ValidationException $e){
				$eob=$e->createErrorObject();
				$eob->message = "Please answer if you have ever had a prostate exam";
				$eob->name = "Question " . $qno;
				$err[] = $eob;
			}

			$qno += 1;
			if ($this->data['preventative_health']['q18'] == 1) {
				try{//18. Have you ever had a Prostate Specific Antigen (PSA) test?    			
					$v=$this->vc->exists('q19',$arr,"enum",array("values"=>array(1,2,3)),false,false);
					$this->data['preventative_health']['q19']=($v=="")?0:$v;
				}catch(ValidationException $e){
					$eob=$e->createErrorObject();
					$eob->message = "Please answer if you have ever had a PSA test";
					$eob->name = "Question " . $qno;
					$err[] = $eob;
				}
			}
			else {
				$this->data['preventative_health']['q19'] = 3;
			}

			$qno += 1;
			if ($this->data['preventative_health']['q19'] == 1) {
				$arr['q20'] = $arr['q20_year'] . "-" . sprintf("%02d", $arr['q20_month']) . "-00";
				try{//19. What was the date of your last PSA test?
					$v=$this->vc->exists('q20',$arr,"date",array("datestamp"=>1, "allow_zeros"=>true),true,false);
					$this->data['preventative_health']['q20']=($v=="")?0:$v;
				}catch(ValidationException $e){
					$eob=$e->createErrorObject();
					$eob->message = "Please enter a date for your last PSA test";
					$eob->name = "Question " . $qno;
					$err[] = $eob;
				} 
			}
			else {
				$this->data['preventative_health']['q20'] = "0000-00-00";
			}

			$this->data['preventative_health']['q14']="0000-00-00";
			$this->data['preventative_health']['q15']=0;
			$this->data['preventative_health']['q16']=0;
			$this->data['preventative_health']['q17']="0000-00-00";
		}
		return ($err)?$err:false;
	}


	/**
	 * validate the data for weight_nutrition
	 * @param array $arr -data
	 */
	private function set_weight_nutrition($arr){
		$qno = $this->getFirstQuestionNo('weight_nutrition');
		$err=null;
		try{//10. Do you eat breakfast?
    		$v=$this->vc->exists('q21',$arr,"enum",array("values"=>array(1,2,3)),false,false);
    		$this->data['weight_nutrition']['q21']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us if you eat breakfast on a daily basis";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//11. How often do you snack in between meals (chips, pastry, cookies, candy etc)?
    		$v=$this->vc->exists('q22',$arr,"enum",array("values"=>array(1,2,3)),false,false);
    		$this->data['weight_nutrition']['q22']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us if you snack between meals";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

			$qno += 1;
    	for($x=1;$x<=8;$x++){
    	//12. How many servings do you eat from the following food groups? - 
			try{			
    			$v=$this->vc->exists('q23_'.$x,$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    			$this->data['weight_nutrition']['q23_'.$x]=($v=="")?0:$v;
    		}catch(ValidationException $e){
    			$eb=$e->createErrorObject();
    			$eb->message = "Be sure to fill out all of the food groups";
    			$eb->name = "Question " . $qno;
    		}
		}
		if (isset($eb)) {
			$err[] = $eb;
		}

		$qno += 1;
		try {//13. How often do you add salt to your food or eat salty foods?
			$v = $this->vc->exists('qn24', $arr, "enum", array("values" => array(1,2,3,4,5)), false, false);
			$this->data['weight_nutrition']['qn24'] = ($v == "") ? 0 : $v;
		}
		catch (ValidationException $e) {
			$eob = $e->createErrorObject();
			$eob->message = "Please answer if you use salt or eat salty foods";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		}

		$qno += 1;
		try {//14. How often do you eat high fat foods?
			$v = $this->vc->exists('qn25', $arr, "enum", array("values" => array(1,2,3,4,5)), false, false);
			$this->data['weight_nutrition']['qn25'] = ($v == "") ? 0 : $v;
		}
		catch (ValidationException $e) {
			$eob = $e->createErrorObject();
			$eob->message = "Please tell us if you eat high fat foods";
			$eob->name = "Question " . $qno;
			$err[] = $eob;
		}

		return ($err)?$err:false;
	}

     /**
     * validate the data for physical_activity
     * @param array $arr -data
     */
	private function set_physical_activity($arr){
		$qno = $this->getFirstQuestionNo('physical_activity');
		$err=null;
		try{//10. How many days per week do you participate in at least 20-30 minutes of moderate physical activity (walking, jogging, swimming, aerobics, etc.)?
    		$v=$this->vc->exists('q24',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['physical_activity']['q24']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer how many days per week you engage in 20-30 minutes of moderate physical activity";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//11. How many days per week do you engage in strength training exercises?
    		$v=$this->vc->exists('q25',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['physical_activity']['q25']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how many days per week you engage in strength training";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//12. How many days per week do you engage in stretching and flexibility exercises (e.g. yoga, warm up stretching, Pilates, etc.)?			
    		$v=$this->vc->exists('q26',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['physical_activity']['q26']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer the number of days per week that you engage in stretching and flexibility exercises";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}
    	return ($err)?$err:false;
	}
	
	 /**
     * validate the data for stress_management
     * @param array $arr -data
     */	
	private function set_stress_management($arr){
		$qno = $this->getFirstQuestionNo('stress_management');
		$err=null;
		try{//10. How often do you feel you cope with day to day stressors?
    		$v=$this->vc->exists('q27',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q27']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer on how you cope with day to day stressors";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//11. How much stress do you feel from your job related activities?													
    		$v=$this->vc->exists('q28',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q28']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how much stress you fell from your job activities";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//12. How much stress do you feel from your family related activities and/or relationships?
    		$v=$this->vc->exists('q29',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q29']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer how much stress you feel from family and/or relationships";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//13. How often does stress from work and/or your family interfere with your daily job activities?																
    		$v=$this->vc->exists('q30',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q30']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please check how often stress from work and/or family interferes with your daily job activities";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//14. How often do you get at least eight (8) hours of sleep per night?													
    		$v=$this->vc->exists('q31',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q31']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how often you get eight hours of sleep per night";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//15. How often do you do something to help you relax such as reading, exercise, or other enjoyable activity?
    		$v=$this->vc->exists('q32',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q32']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how often you do something to help you relax";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//16. How often do you use drugs or medication to affect your mood to help you relax or sleep?			
    		$v=$this->vc->exists('q33',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['stress_management']['q33']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer how often you use drugs or medication to affect your mood";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}
    
		return ($err)?$err:false;
	}
	
	/**
     * validate the data for productivity
     * @param array $arr -data
     */	
/*
	private function set_productivity($arr){
		$qno = $this->getFirstQuestionNo('productivity');
		$err=null;
		//10. In the past 2 weeks, how much of the time did your physical health or emotional problems make it difficult for you to do the following?
		try{//a. get going easily at the beginning of the workday
    		$v=$this->vc->exists('q34_1',$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    		$this->data['productivity']['q34_1']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer, how much of the time did your physical or emotional health make it difficult to get going at the beginning of the day";
    		$eob->name = "Question " . $qno . "a";
    		$err[] = $eob;
    	}
		try{//b. start on your job as soon as you arrived at work
    		$v=$this->vc->exists('q34_2',$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    		$this->data['productivity']['q34_2']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer, how much of the time did your physical or emotional health make it difficult to start your job";
    		$eob->name = "Question " . $qno . "b";
    		$err[] = $eob;
    	}

			$qno += 1;
    	try{//11. In the past 2 weeks, how much of the time were you able to sit, stand, or stay in one position for longer than 15 minutes while working, without difficulty caused by physical health or emotional problems?
    		$v=$this->vc->exists('q35',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['productivity']['q35']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how much of the time you were able to sit, stand or stay in one position for longer than 15 minutes";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//12.  In the past 2 weeks, how much of the time were you able to repeat the same motions over and over again while working, without difficulty caused by physical health or emotional problems? 
    		$v=$this->vc->exists('q36',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['productivity']['q36']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please select how much of the time you were able to repeat the same motions over and over";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		//13. In the past 2 weeks, how much of the time did your physical health or emotional problems make it difficult for you to do the following?
    	try{//a. handle the workload
    		$v=$this->vc->exists('q37_1',$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    		$this->data['productivity']['q37_1']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please check how much of the time your physical or emotional health made it difficult to handle your workload";
    		$eob->name = "Question " . $qno . "a";
    		$err[] = $eob;
    	}
    	try{//b. finsh work on time
    		$v=$this->vc->exists('q37_2',$arr,"enum",array("values"=>array(1,2,3,4,5,6)),false,false);
    		$this->data['productivity']['q37_2']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how much of the time your physical or emotion health made it difficult to finish your work on time";
    		$eob->name = "Question " . $qno . "b";
    		$err[] = $eob;
    	}
    	
		return ($err)?$err:false;
	}
*/
	
	/**
     * validate the data for mental_health
     * @param array $arr -data
     */	
	private function set_mental_health($arr){
		$qno = $this->getFirstQuestionNo('mental_health');
		$err=null;
		for($x=1;$x<=8;$x++){
    	//10.  Over the last 2 weeks, how often have you been bothered by any of the following problems?
			try{
    			$v=$this->vc->exists('q38_'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,false);    			
    			$this->data['mental_health']['q38_'.$x]=($v=="")?0:$v;
    	}catch(ValidationException $e){
    			$e1=$e->createErrorObject();
    			$e1->message = "Please answer check all problems that have bothered you";
    			$e1->name = "Question " . $qno;
			}
		}
		if (isset($e1)) {
			$err[] = $e1;
		}

    return ($err)?$err:false;		
	}


	/**
	 * validate data for alchol_use
	 * @param array $arr -data
	 */
	private function set_alcohol_use($arr) {
		$qno = $this->getFirstQuestionNo('alcohol_use');
		$err = null;
    try{//11.  How many alcoholic drinks do you consume on average?
    	$v=$this->vc->exists('q39',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    	$this->data['alcohol_use']['q39']=($v=="")?0:$v;
    }catch(ValidationException $e){
    	$eob=$e->createErrorObject();
    	$eob->message = "Please answer approximately how many alcholic drinks you consume on average";
    	$eob->name = "Question " . $qno;
    	$err[] = $eob;
    }

		$qno += 1;
    try{//12. Have you ever had more than 5 drinks at one time in the past four (4) months?
    	$v=$this->vc->exists('q40',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    	$this->data['alcohol_use']['q40']=($v=="")?0:$v;
    }catch(ValidationException $e){
    	$eob=$e->createErrorObject();
   		$eob->message = "Please tell us if you have had more than 5 drinks at one time in the last 4 months";
   		$eob->name = "Question " . $qno;
   		$err[] = $eob;
    }

		return ($err) ? $err : false;
	}


	/**
	 * validate the data for self_care
	 * @param array $arr -data
	 */
	private function set_self_care($arr){
		$qno = $this->getFirstQuestionNo('self_care');
		$err=null;
		try{//10. How often do you wear a seat belt while driving?
    		$v=$this->vc->exists('q41',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['self_care']['q41']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer how often you use a seat belt while driving";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//11. Do you talk on your cell phone while driving?									
    		$v=$this->vc->exists('q42',$arr,"enum",array("values"=>array(1,2,3)),false,false);
    		$this->data['self_care']['q42']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer do you talk on your cell phone while driving";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//12. Do you text message while driving?									
    		$v=$this->vc->exists('q42a',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['self_care']['q42a']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer do you text message while driving";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//13. How often do you drive after you have consumed alcohol?						
    		$v=$this->vc->exists('q43',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['self_care']['q43']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us if you drive impaired after you have consumed alcohol or medications";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}
    	
		return ($err)?$err:false;
	}
	
	 /**
     * validate the data for tobaco_use
     * @param array $arr -data
     */
	private function set_tobaco_use($arr){		
		$qno = $this->getFirstQuestionNo('tobaco_use');
		$err=null;
		try{
    		$v=$this->vc->exists('q44',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		$this->data['tobaco_use']['q44']=($v=="")?0:$v;
    		$notasmoker=($v!=1);
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer whether you smoke or not";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//11. If you smoke cigarettes, how many cigarettes do you smoke per day?			
    		$v=$this->vc->exists('q45',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		if($notasmoker) $v=1;
    		$this->data['tobaco_use']['q45']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please say how many cigarettes you smoke per day";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//12. If you smoke, how many cigars or pipes do you smoke per day?
    		$v=$this->vc->exists('q46',$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
    		if($notasmoker) $v=1;
    		$this->data['tobaco_use']['q46']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please tell us how many cigars or pipes you smoke per day";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}

		$qno += 1;
		try{//13. How many times a day do you use smokeless tobacco (chew)?
    		$v=$this->vc->exists('q47',$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    		$this->data['tobaco_use']['q47']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please answer how many times a day you use smokeless tobacco";
    		$eob->name = "Question " . $qno;
    		$err[] = $eob;
    	}    	
		return ($err)?$err:false;
	}
	
	 /**
     * validate the data for readiness_to_change
     * @param array $arr -data
     */
	private function set_readiness_to_change($arr){
		$qno = $this->getFirstQuestionNo('readiness_to_change');
		$err=null;
		for($x=1;$x<=7;$x++){
    	//10. If you are planning on making changes in your behaviors and lifestyle to improve your health status please select the timeframe for anticipated change in the following categories:
			try{			
    			$v=$this->vc->exists('q48_'.$x,$arr,"enum",array("values"=>array(1,2,3,4,5)),false,false);
    			$this->data['readiness_to_change']['q48_'.$x]=($v=="")?0:$v;
    		}catch(ValidationException $e){
    			$eob=$e->createErrorObject();
    			$eob->message = "Please answer all areas where you are planning to make a change";
    			$eob->name = "Question " . $qno;
    		}
		}
		if (isset($eob)) {
			$err[] = $eob;
		}

		return ($err)?$err:false;
	}
	
	/**
     * validate the data for biometric_data
     * @param array $arr -data
     */
	
	private function set_biometric_data($arr){
		$err=null;
		try{
    		$hf=$this->vc->exists('height_ft',$arr,"integer",array("rangex_low"=>0,"rangex_high"=>8),true,false);
    		$hi=$this->vc->exists('height_in',$arr,"integer",array("range_low"=>0,"rangex_high"=>12),true,false);
    		
    		$this->data['biometric_data']['height']=($hf*12)+$hi;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please select your height in feet and inches";
    		$eob->name = "Height";
    		$err[] = $eob;
    	}    	

		try{
    		$v=$this->vc->exists('weight',$arr,"numeric",array("rangex_low"=>0),true,false);
    		$this->data['biometric_data']['weight']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter you weight in pounds";
    		$eob->name = "Weight";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('bp_systolic',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['bp_systolic']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your blood pressure systolic value";
    		$eob->name = "Systolic";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('bp_diastolic',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['bp_diastolic']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for you blood pressure diastolic value";
    		$eob->name = "Diastolic";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('body_fat',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['body_fat']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your percent body fat";
    		$eob->name = "Percent Body Fat";
    		$err[] = $eob;
    	}    	

    	$height=$this->data['biometric_data']['height'];
    	if($height>0){
    			$this->data['biometric_data']['bmi']=($this->data['biometric_data']['weight']*703)/pow($height,2);
    	}else{
    			$this->data['biometric_data']['bmi']=0;
    	}
    		
		try{
    		$v=$this->vc->exists('waist',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['waist']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your waist circumference in inches";
    		$eob->name = "Waist size";
    		$err[] = $eob;
    	}    	
    	try{
    		$v=$this->vc->exists('blood_glucose',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['blood_glucose']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your blood glucose level";
    		$eob->name = "Blood Glucose";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('hemoglobin',$arr,"numeric",array("precision"=>"3,3"),true,false);
    		$this->data['biometric_data']['hemoglobin']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number of your blood hemoglobin level";
    		$eob->name = "Hemoglobin";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('cotinine',$arr,"numeric",array("precision"=>"3,3"),true,false);
    		$this->data['biometric_data']['cotinine']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your blood cotinine (nicotine) level";
    		$eob->name = "Cotinine";
    		$err[] = $eob;
    	}    	
    	try{
    		$v=$this->vc->exists('cholesterol',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['cholesterol']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your total cholesterol level";
    		$eob->name = "Total cholesterol";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('triglycerides',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['triglycerides']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your level of triglycerides";
    		$eob->name = "Triglycerides";
    		$err[] = $eob;
    	}    	
		try{
    		$v=$this->vc->exists('hdl',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['hdl']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your HDL cholesterol level";
    		$eob->name = "HDL";
    		$err[] = $eob;
    	}    	    	
		try{
    		$v=$this->vc->exists('ldl',$arr,"numeric",array("range_low"=>0),true,false);
    		$this->data['biometric_data']['ldl']=($v=="")?0:$v;
    	}catch(ValidationException $e){
    		$eob=$e->createErrorObject();
    		$eob->message = "Please enter a number for your LDL cholesterol level";
    		$eob->name = "LDL";
    		$err[] = $eob;
    	}
    	return ($err)?$err:false;
	}
	
	public function getAllAssessments(){}
	public function getCompletedAssessment($id){}
	
	/**
	 * Tally up the assessment
	 */
	public function score(){
		$scores=array();
		
		if($this->gina){
			$scores['current_health_status']=array($this->score_current_health_status(),.09);
			//$scores['family_health']=array($this->score_family_health(),				0);
			$scores['preventative_health']=array($this->score_preventative_health(),	.06);
			$scores['weight_nutrition']=array($this->score_weight_nutrition(),			.18);
			$scores['physical_activity']=array($this->score_physical_activity(),		.18);
			$scores['stress_management']=array($this->score_stress_management(),		.07);
			$scores['mental_health']=array($this->score_mental_health(),				.045);
			$scores['alcohol_use'] = array($this->score_alcohol_use(), .045);
			$scores['self_care']=array($this->score_self_care(),						.08);
			$scores['tobaco_use']=array($this->score_tobaco_use(),						.18);
			$scores['readiness_to_change']=array($this->score_readiness_to_change(),	0);
			$scores['biometric_data']=array($this->score_biometric_data(),				0);
		}else{
			$scores['current_health_status']=array($this->score_current_health_status(),.09);
			$scores['family_health']=array($this->score_family_health(),				0);
			$scores['preventative_health']=array($this->score_preventative_health(),	.06);
			$scores['weight_nutrition']=array($this->score_weight_nutrition(),			.18);
			$scores['physical_activity']=array($this->score_physical_activity(),		.18);
			$scores['stress_management']=array($this->score_stress_management(),		.07);
			$scores['mental_health']=array($this->score_mental_health(),				.045);
			$scores['alcohol_use'] = array($this->score_alcohol_use(), .045);
			$scores['self_care']=array($this->score_self_care(),						.08);
			$scores['tobaco_use']=array($this->score_tobaco_use(),						.18);
			$scores['readiness_to_change']=array($this->score_readiness_to_change(),	0);
			$scores['biometric_data']=array($this->score_biometric_data(),				0);
		}

		$total=0;
		foreach($scores as &$score){
			$score[2]=$score[0]->total*$score[1];
			$total+=$score[2];
		}
		
		$scores['total']=$total*20;
		return $scores;		
	}

	public function getRisks(){
		$risks=array();		
		if($this->data['biometric_data']['cholesterol']<200)
			$risks['cholesterol']=1;
		else if($this->data['biometric_data']['cholesterol']<240)
			$risks['cholesterol']=2;
		else $risks['cholesterol']=3;
		
		if($this->data['biometric_data']['hdl']>59)
			$risks['hdl']=1;
		else if($this->data['biometric_data']['hdl']>=40)
			$risks['hdl']=2;
		else $risks['hdl']=3;
				
		if($this->data['biometric_data']['ldl']<130)
			$risks['ldl']=1;
		else if($this->data['biometric_data']['ldl']<160)
			$risks['ldl']=2;
		else $risks['ldl']=3;
		
		if($this->data['biometric_data']['triglycerides']<150)
			$risks['triglycerides']=1;
		else if($this->data['biometric_data']['triglycerides']<200)
			$risks['triglycerides']=2;
		else $risks['triglycerides']=3;
		
		if($this->data['biometric_data']['hdl']>0)
			$ratio=$this->data['biometric_data']['cholesterol']/$this->data['biometric_data']['hdl'];
		else $ratio=0;
		
		if($ratio<4.5)
			$risks['ratio']=1;		
		else $risks['ratio']=3;

		if($this->data['biometric_data']['bp_systolic']<120)
			$risks['bp_systolic']=1;
		else if($this->data['biometric_data']['bp_systolic']<140)
			$risks['bp_systolic']=2;
		else $risks['bp_systolic']=3;
		
		if($this->data['biometric_data']['bp_diastolic']<80)
			$risks['bp_diastolic']=1;
		else if($this->data['biometric_data']['bp_diastolic']<90)
			$risks['bp_diastolic']=2;
		else $risks['bp_diastolic']=3;
		
		
		if($this->demographics['age']>=40){			
			if($this->demographics['gender']=="F"){
				if($this->data['biometric_data']['body_fat']<23.5) $risks['body_fat']=1;
				else if($this->data['biometric_data']['body_fat']<30.5) $risks['body_fat']=2;
				else $risks['body_fat']=3;
			}else{
				if($this->data['biometric_data']['body_fat']<19.5) $risks['body_fat']=1;
				else if($this->data['biometric_data']['body_fat']<23.5) $risks['body_fat']=2;
				else $risks['body_fat']=3;				
			}
		}else if($this->demographics['age']>=60){			
			if($this->demographics['gender']=="F"){
				if($this->data['biometric_data']['body_fat']<30.5) $risks['body_fat']=1;
				else if($this->data['biometric_data']['body_fat']<31.5) $risks['body_fat']=2;
				else $risks['body_fat']=3;
			}else{
				if($this->data['biometric_data']['body_fat']<23.5) $risks['body_fat']=1;
				else if($this->data['biometric_data']['body_fat']<24.5) $risks['body_fat']=2;
				else $risks['body_fat']=3;				
			}
		}else{			
			if($this->demographics['gender']=="F"){
				if($this->data['biometric_data']['body_fat']<30.5) $risks['body_fat']=1;				
				else $risks['body_fat']=3;
			}else{
				if($this->data['biometric_data']['body_fat']<24.5) $risks['body_fat']=1;
				else $risks['body_fat']=3;				
			}
		}

		if ($this->demographics['gender'] == "F") {
			if ($this->data['biometric_data']['waist'] < 35.0) $risks['waist'] = 1;
			else $risks['waist'] = 3;
		}
		else {
			if ($this->data['biometric_data']['waist'] < 40.0) $risks['waist'] = 1;
			else $risks['waist'] = 3;
		}


		return $risks;
		
	}
	/**
	 * return the point value for the answered question
	 * @param array $scores
	 * @param int $value
	 * @return int
	 */
	private function scoreValue($scores,$value){
		if($value==0) return 0;
		if($value > sizeof($scores)) throw new Exception("Invalid index");
		return $scores[$value-1];
	}	


	/**
	 * Score the current_health_status section
	 * @return object
	 */
	private function score_current_health_status(){
		$data=$this->data['current_health_status'];

		try {
			$q4=$this->scoreValue(array(5,4,3,2,1),$data['q4']);;
		}
		catch (Exception $e) {
			throw new Exception ("current_health_status - " . $e->message);
		}

		$q5=0;
		for($x = 0; $x <= 15; $x++){								//&&& not sure that this is correct.
			if($data["q5_".$x] == 0) continue;
			if($data["q5_".$x] == 1) continue;
			$q5++;
		}
		if ($q5 > 4) $q5=1;
		else if ($q5 == 4) $q5 = 2;
		else if ($q5 == 3) $q5 = 3;
		else if ($q5 == 2) $q5 = 4;
		else if ($q5 == 1) $q5 = 4;
		else if ($q5 == 0) $q5 = 5;
		else $q5 = 0;

//
// Note: these do not currently enter into the score (only the 1st two questions do)
//
//		$q6=$this->scoreValue(array(1,5),$data['q6']);
		$q7=$this->scoreValue(array(5,1),$data['q7']);				//Yes or No
//		$q8=$this->scoreValue(array(5,1,3),$data['q8']);
		$q9=$this->scoreValue(array(5,1),$data['q9']);				//Yes or No

		$ob=new stdClass();
		$ob->total=0;
		$ob->data=array('q4'=>array($q4,.35),
		                'q5'=>array($q5,.65),
		                'q7'=>array($q7,0),
		                'q9'=>array($q9,0)
		               );
		foreach($ob->data as &$rec){
			$rec[2]=$rec[0]*$rec[1];
			$ob->total+=$rec[2];
		}

		return $ob;
	}

	/**
	 * Score the family_health section
	 * @return object
	 */
	private function score_family_health(){
		$data=$this->data['family_health'];
		$q10=0;
		for ($x = 1; $x <= 6; $x++){
			if($data["q10_".$x] == 0) continue;
			if($data["q10_".$x] == 2) continue;
			$q10++;
		}
		if ($q10 > 4) $q10=1;
		else if	($q10 == 4) $q10 = 2;
		else if ($q10 == 3) $q10 = 3;
		else if ($q10 == 2) $q10 = 4;
		else if ($q10 == 1) $q10 = 4;
		else if ($q10 == 0) $q10 = 5;
		else $q10 = 0;
		
		$ob=new stdClass();
		$ob->total=$q10;
		$ob->data=array('q10'=>array($q10,1,$q10));
		return $ob;
	}

	/**
	 * Score the preventative_health section
	 * @return object
	 */	
	private function score_preventative_health(){
		$data = $this->data['preventative_health'];
		$today = strtotime(date('Y-m')."-01");

		$q8 = $this->scoreValue(array(5,1), $data['q8']);

		if($data['q11'] != "0000-00-00"){
			$date = $data['q11'];
			list($year, $month, $day) = explode('-', $date);
			if (($month == "00") && ($year != "0000")) {
				$month = "01";
			}
			$q11=mktime(0, 0, 0, $month, "01", $year);
			$q11=ceil(($today-$q11)/(60*60*24*365));
			switch($q11){
				case 1: $q11=5;break;
				case 2: $q11=4;break;
				case 3: $q11=2;break;
				default: $q11=1;break;
			}				
		}else{			
			$q11=1;
		}

		$q12=$this->scoreValue(array(5,1),$data['q12']);
		if ($data['q13'] > 0) {
			$q13=$this->scoreValue(array(5,1,5),$data['q13']);
		}
		else {
			$q13 = 5;
		}

		if($this->demographics['gender']=="F"){
			if($data['q14'] != "0000-00-00"){
				$date = $data['q14'];
				list($year, $month, $day) = explode('-', $date);
				if (($month == "00") && ($year != "0000")) {
					$month = "01";
				}
				$q14=mktime(0, 0, 0, $month, "01", $year);
				$q14=ceil(($today-$q14)/(60*60*24*365));
				switch($q14){
					case 1: $q14=5;break;
					case 2: $q14=4;break;
					case 3: $q14=2;break;
					default: $q14=1;break;
				}				
			}else{
				if ($this->demographics['age'] < 21) {
					$q14=5;
				}
				else {
					$q14=1;
				}
			}

			$q15=$this->scoreValue(array(5,1),$data['q15']);
			if ($data['q16'] > 0) {
				$q16=$this->scoreValue(array(5,1,3),$data['q16']);
			}
			else {
				$q16 = 5;
			}

			if($data['q17'] != "0000-00-00"){
				$date = $data['q17'];
				list($year, $month, $day) = explode('-', $date);
				if (($month == "00") && ($year != "0000")) {
					$month = "01";
				}
				$q17=mktime(0, 0, 0, $month, "01", $year);
				$q17=ceil(($today-$q17)/(60*60*24*365));
				switch($q17){
					case 1: $q17=5;break;
					case 2: $q17=4;break;
					case 3: $q17=2;break;
					default: $q17=1;break;
				}
			}else{
				if ($this->demographics['age'] < 40) {
					$q17 = 5;
				}
				else {
					$q17=1;
				}
			}
		}else{
			$q14=0;
			$q15=0;
			$q16=0;
			$q17=0;
		}

		if($this->demographics['gender']=="M"){
			$q18=$this->scoreValue(array(5,1,5),$data['q18']);
			$q19=$this->scoreValue(array(5,1,5),$data['q19']);

			if($data['q20'] != "0000-00-00"){
				$date = $data['q20'];
				list($year, $month, $day) = explode('-', $date);
				if (($month == "00") && ($year != "0000")) {
					$month = "01";
				}
				$q20=mktime(0, 0, 0, $month, "01", $year);
				$q20=ceil(($today-$q20)/(60*60*24*365));
				switch($q20){
					case 1: $q20=5;break;
					case 2: $q20=4;break;
					case 3: $q20=2;break;
					default: $q20=1;break;
				}
			}else{
				if ($q19 == 1) {
					$q20 = 1;
				}
				else if ($q19 == 3) {
					$q20 = 5;
				}
				else {
					$q20 = 1;
				}
			}

		}else{
			$q18=0;
			$q19=0;
			$q20=0;
		}

// M - q8 (annual exam)					2/3 of score
//     q11 (date of last exam)
//     q12 (annual flu shot)
//     q13 (colorectal screening)

//     q18 (discussed prostate exam)	1/3 of score
//     q19 (PSA test)
//     q20 (date of last PSA test)

// F - q8 (anual exam)					2/3 of score
//     q11 (date of last exam)
//     q12 (annual flu shot)
//     q13 (colorectal screening)

//     q14 (date of last pap)		1/3 of score
//     q15 (ever abnormal pap)
//     q16 (ever had mamo)
//     q17 (date of last mamo)

		$ob=new stdClass();
		$ob->total=0;
		$ob->data=array('q8'=>array($q8,0),
						'q11'=>array($q11,0),
						'q12'=>array($q12,.25),
						'q13'=>array($q13,.25),
						'q14'=>array($q14,.17),
						'q15'=>array($q15,.17),
						'q16'=>array($q16,.16),
						'q17'=>array($q17,0),
						'q18'=>array($q18,.25),
						'q19'=>array($q19,.25),
						'q20'=>array($q20,0)
		);							
		foreach($ob->data as &$rec){
			$rec[2]=$rec[0]*$rec[1];
			$ob->total+=$rec[2];
		}
		return $ob;
	}

	/**
	 * Score the weight_nutrition section
	 * @return object
	 */
	private function score_weight_nutrition(){
		$data = $this->data['weight_nutrition'];

		$q21 = $this->scoreValue(array(5,1,3),$data['q21']);
		$q22 = $this->scoreValue(array(1,3,5),$data['q22']);

		$qn24 = $this->scoreValue(array(1,2,3,4,5), $data['qn24']);
		$qn25 = $this->scoreValue(array(1,2,3,4,5), $data['qn25']);

		//fruit & veg _1 & 2
		$q23a = 0;
		$f = $data['q23_1'];
		$v = $data['q23_2'];
		
		if ($f > 0) $f--;
		if ($v > 0) $v--;
		$table = array(
		  array(1,1,1,2,2),	
		  array(1,1,2,2,3),
		  array(1,2,2,3,3),
		  array(2,2,3,4,5),
		  array(2,3,3,5,5),
		);
		$q23a = $table[$v][$f];
		
		//Fats
		$q23b = 0;
		for($x = 3; $x <= 7; $x++){
			$q23b += $this->scoreValue(array(0,3,5,12,25),$data['q23_'.$x]);
		}
		$q23b = $q23b/5;
		if ($q23b > 10) $q23b = 1;
		else if ($q23b > 5.1) $q23b = 2;
		else if ($q23b > 3.2) $q23b = 3;
		else if ($q23b > 2) $q23b = 4;
		else $q23b = 5;
		
		///breads & Grains _8
		$q23c = $this->scoreValue(array(0,3,5,12,25),$data['q23_8']);
		if ($q23c > 12) $q23c = 5;
		else if ($q23c > 8) $q23c = 4;
		else if ($q23c > 4.9) $q23c = 3;
		else if ($q23c > 3) $q23c = 2;
		else $q23c = 1;
		
		$ob=new stdClass();
		$ob->total=0;
		$ob->data=array('q21'=>array($q21,.1),
						'q22'=>array($q22,.1),
						'q23_1'=>array($q23a,.25),
						'q23_2'=>array($q23b,.25),
						'q23_3'=>array($q23c,.1),
						'qn24'=>array($qn24,.1),
						'qn25'=>array($qn25,.1)
		);
		foreach($ob->data as &$rec){
			$rec[2]=$rec[0]*$rec[1];
			$ob->total+=$rec[2];
		}
		return $ob;
	}

	/**
	 * Score the physical_activity section
	 * @return object
	 */	
	private function score_physical_activity(){
		$data = $this->data['physical_activity'];
		$q24 = $this->scoreValue(array(0,3,6,12,24),$data['q24']);
		$q25 = $this->scoreValue(array(0,3,6,12,24),$data['q25']);
		$q26 = $this->scoreValue(array(0,3,6,12,24),$data['q26']);
		$w = ($q24 + $q25 + $q26) / 3;

		if ($w > 12) $raw = 5;
		else if ($w > 9) $raw = 4;
		else if ($w > 6) $raw = 3;
		else if ($w > 3) $raw = 2;
		else $raw = 1;
		$ob = new stdClass();
		$ob->total = $raw;
		$ob->data = array('q24-26' => array($raw,1,$raw));
		return $ob;
	}

	/**
	 * Score the stress_management section
	 * @return object
	 */		
	private function score_stress_management(){
		$data = $this->data['stress_management'];
		$q27 = $this->scoreValue(array(1,2,4,5),$data['q27']);
		$q28 = $this->scoreValue(array(5,4,2,1),$data['q28']);
		$q29 = $this->scoreValue(array(5,4,2,1),$data['q29']);
		$q30 = $this->scoreValue(array(5,4,2,1),$data['q30']);
		$q31 = $this->scoreValue(array(1,2,4,5),$data['q31']);
		$q32 = $this->scoreValue(array(1,2,4,5),$data['q32']);
		$q33 = $this->scoreValue(array(5,4,2,1),$data['q33']);
		
		$ob = new stdClass();
		$ob->total = 0;
		$ob->data = array('q27' => array($q27,.17),
						'q28' => array($q28,.17),
						'q29' => array($q29,.17),
						'q30' => array($q30,.17),
						'q31' => array($q31,.10),
						'q32' => array($q32,.05),
						'q33' => array($q33,.17)			
		);							
		foreach($ob->data as &$rec){
			$rec[2] = $rec[0] * $rec[1];
			$ob->total += $rec[2];
		}
		return $ob;	
	}

	/**
	 * Score the productivity section
	 * @return object
	 */
/*
	private function score_productivity(){
		$data=$this->data['productivity'];
		$q34_1=$this->scoreValue(array(1,2,3,4,5,0),$data['q34_1']);
		$q34_2=$this->scoreValue(array(1,2,3,4,5,0),$data['q34_2']);
		$q35=$this->scoreValue(array(5,4,3,2,1),$data['q35']);
		$q36=$this->scoreValue(array(5,4,3,2,1),$data['q36']);
		$q37_1=$this->scoreValue(array(1,2,3,4,5,0),$data['q37_1']);
		$q37_2=$this->scoreValue(array(1,2,3,4,5,0),$data['q37_2']);			
		
		$ob=new stdClass();
		$ob->total=0;
		$ob->data=array('q34_1'=>array($q34_1,.125),
						'q34_2'=>array($q34_2,.125),
						'q35'=>array($q35,.25),
						'q36'=>array($q36,.25),
						'q37_1'=>array($q37_1,.125),
						'q37_2'=>array($q37_2,.125)
		);							
		foreach($ob->data as &$rec){
			$rec[2]=$rec[0]*$rec[1];
			$ob->total+=$rec[2];
		}
		return $ob;			
	}
*/


	/**
	 * Score the mental_health section
	 * @return object
	 */	
	private function score_mental_health(){
		$data = $this->data['mental_health'];
		$q38 = 0;
		for($x = 1; $x <= 8; $x++){
			$q38 += $this->scoreValue(array(5,3,2,1),$data['q38_'.$x]);
		}
		$q38 = $q38 / 8;

		$ob = new stdClass();
		$ob->total = 0;
		$ob->data = array('q38' => array($q38,1.0));
		foreach($ob->data as &$rec){
			$rec[2] = $rec[0]*$rec[1];
			$ob->total += $rec[2];
		}
		return $ob;
	}

	/**
	 * Score the alcohol_use section
	 * @return object
	 */
	private function score_alcohol_use() {						//&&& no alcohol use section in scoring
		$data = $this->data['alcohol_use'];
		if($this->demographics['gender']=="M"){
			$q39=$this->scoreValue(array(5,4,3,2,1),$data['q39']);
		}else{
			$q39=$this->scoreValue(array(5,3,1,1,1),$data['q39']);
		}
		$q40=$this->scoreValue(array(1,5),$data['q40']);
		$ob=new stdClass();
		$ob->total=0;
		$ob->data=array('q39'=>array($q39,.6),
						'q40'=>array($q40,.4));

		foreach($ob->data as &$rec){
			$rec[2] = $rec[0] * $rec[1];
			$ob->total += $rec[2];
		}
		return $ob;
	}


	/**
	 * Score the self_care section
	 * @return object
	 */	
	private function score_self_care(){
		$data = $this->data['self_care'];
		$q41 = $this->scoreValue(array(5,4,3,1),$data['q41']);
		$q42 = $this->scoreValue(array(4,2,5),$data['q42']);
		$q42a = $this->scoreValue(array(5,3,2,1), $data['q42a']);
		$q43 = $this->scoreValue(array(1,2,3,5),$data['q43']);		//&&& scoring only has 2 options (Y/N)
		$ob = new stdClass();
		$ob->total = 0;
		$ob->data = array('q41' => array($q41,.25),
						'q42' => array($q42,.25),
						'q42a' => array($q42a, .25),
						'q43' => array($q43,.25));
		foreach($ob->data as &$rec){
			$rec[2] = $rec[0] * $rec[1];
			$ob->total += $rec[2];
		}
		return $ob;
	}
	
	/**
	 * Score the tobaco_use section
	 * @return object
	 */		
	private function score_tobaco_use(){
		$data = $this->data['tobaco_use'];
		$q44 = $this->scoreValue(array(1,4,3,5),$data['q44']);
//		$q45 = $this->scoreValue(array(4,4,3,2,1),$data['q45']);
//		$q46 = $this->scoreValue(array(4,4,3,2,1), $data['q46']);
//		if ($q44 == 5) {
//			$q45 = 5;
//			$q46 = 5;
//		}
/*
		$q44Raw = $q44;
		$q44 = ($q44 > $q45) ? $q44 : $q45;
		$q45 = $q44;
			
		$q46 = $this->scoreValue(array(3,3,2,2,1),$data['q46']);
		$q46 = ($q44Raw > $q46) ? $q44Raw : $q46;
*/			
//		$q47 = $this->scoreValue(array(5,3,2,1,1),$data['q47']);

		$ob = new stdClass();
		$ob->total = 0;
		$ob->data = array('q44' => array($q44,1));
//						'q45' => array($q45,.30),
//						'q46' => array($q46,.30),
//						'q47' => array($q47,.10));
		foreach($ob->data as &$rec){
			$rec[2] = $rec[0] * $rec[1];
			$ob->total += $rec[2];
		}
		return $ob;
	}
		
	/**
	 * Score the readiness_to_change section
	 * @return object
	 */	
	private function score_readiness_to_change(){
		$data = $this->data['readiness_to_change'];
		$ob = new stdClass();
		$ob->total = 0;

		$q48 = 0;
		for ($x = 1; $x <= 7; $x++)
			$q48 += $this->scoreValue(array(1,2,3,4,5),$data['q48_'.$x]);

		$ob->total = $q48 / 7;
		$ob->data = array('q48' => array($q48,1/7,$ob->total));

		return $ob;
	}
			
	/**
	 * Score the biometric_data section
	 * @return object  
	 */		
	private function score_biometric_data(){		
		$data = $this->data['biometric_data'];
		$bp_systolic = $data['bp_systolic'];
		if ($bp_systolic > 140) $bp_systolic = 1;
		else if ($bp_systolic > 120) $bp_systolic = 3;
		else if ($bp_systolic == 0) $bp_systolic = 0;
		else $bp_systolic = 5;
			
		if ($data['bp_diastolic'] > 90) $bp_diastolic = 1;
		else if ($data['bp_diastolic'] > 80) $bp_diastolic = 3;
		else if ($data['bp_diastolic'] == 0) $bp_diastolic = 0;
		else $bp_diastolic = 5;
		
		$body_fat = 5;
		if ($this->demographics['gender'] == "M") {
			if ($this->demographics['age'] >= 60){
				if ($data['body_fat'] > 24.1) $body_fat = 1;
				else if ($data['body_fat'] == 0) $body_fat = 0;
			}else if ($this->demographics['age'] >= 40) {
				if ($data['body_fat'] > 24.1) $body_fat = 1;
				if ($data['body_fat'] > 23.4) $body_fat = 3;
				else if ($data['body_fat'] == 0) $body_fat = 0;
			}else{
				if ($data['body_fat'] > 23.5) $body_fat = 1;
				if ($data['body_fat'] > 19.4) $body_fat = 3;
				else if ($data['body_fat'] == 0) $body_fat = 0;					
			}
		}else{
			if ($this->demographics['age'] >= 60) {
				if ($data['body_fat'] > 31.5) $body_fat = 1;
				else if ($data['body_fat'] == 0) $body_fat = 0;
			}else if ($this->demographics['age'] >= 40) {
				if ($data['body_fat'] > 31.5) $body_fat = 1;
				if ($data['body_fat'] > 30.4) $body_fat = 3;
				else if ($data['body_fat'] == 0) $body_fat = 0;
			}else{
				if ($data['body_fat'] > 30.5) $body_fat = 1;
				if ($data['body_fat'] > 23.4) $body_fat = 3;
				else if ($data['body_fat'] == 0) $body_fat = 0;					
			}				
		}

		$waist = 5;
		if ($this->demographics['gender'] == 'M') {
			if ($data['waist'] > 40) {
				$waist = 1;
			}
			else if ($data['waist'] == 0) {
				$waist = 0;
			}
		}
		else {
			if ($data['waist'] > 35) {
				$waist = 1;
			}
			else if ($data['waist'] == 0) {
				$waist = 0;
			}
		}

		$bmi = 3;
		if ($data['bmi'] > 30) $bmi = 1;
		else if ($data['bmi'] > 25) $bmi = 3;
		else if ($data['bmi'] > 18) $bmi = 5;
		else if ($data['bmi'] == 0) $bmi = 0;
		$data['bmi'] = sprintf("%.1f", $data['bmi']);

		$blood_glucose = 5;
		if ($data['blood_glucose'] > 200) $blood_glucose = 1;
		else if ($data['blood_glucose'] > 129) $blood_glucose = 3;			
		else if ($data['blood_glucose'] == 0) $blood_glucose = 0;
		
		$cholesterol = 5;
		if ($data['cholesterol'] > 239) $cholesterol = 1;
		else if ($data['cholesterol'] > 199) $cholesterol = 3;			
		else if ($data['cholesterol'] == 0) $cholesterol = 0;
					
		$triglycerides = 5;
		if ($data['triglycerides'] > 200) $triglycerides = 1;
		else if ($data['triglycerides'] > 149) $triglycerides = 3;			
		else if ($data['triglycerides'] == 0) $triglycerides = 0;
					
		$hdl = 1;
		if ($this->demographics['gender'] == 'F') {
			if ($data['hdl'] > 40) $hdl = 5;
			else if ($data['hdl'] > 30) $hdl = 3;
			else if ($data['hdl'] == 0) $hdl = 0;
		}
		else {
			if ($data['hdl'] > 50) $hdl = 5;
			else if ($data['hdl'] > 35) $hdl = 3;
			else if ($data['hdl'] == 0) $hdl = 0;
		}

//		if($data['hdl']>59) $hdl=5;
//		else if($data['hdl']>40) $hdl=3;
//		else if($data['hdl']==0) $hdl=0;

		$ldl = 5;
		if ($data['ldl'] > 140) $ldl = 1;
		else if ($data['ldl'] > 100) $ldl = 3;
		else if ($data['ldl'] == 0) $ldl = 0;

		if($data['hdl'] > 0 && $data['cholesterol'] > 0)
			$ratio = $data['cholesterol'] / $data['hdl'];
		else
			$ratio = 0;
		
		$ratio = sprintf("%.1f", $ratio);

		$tc_hdl = 5;
		if ($ratio > 4.5) $tc_hdl = 1;
		else if ($ratio == 0) $tc_hdl = 0;


		$hemoglobin = 5;
		if ($data['hemoglobin'] == 0)
			$hemoglobin = 0;
		else if (($data['hemoglobin'] < 4.0) || ($data['hemoglobin'] > 6.0)) {
			$hemoglobin = 1;
		}

		$cotinine = 5;
		if ($data['cotinine'] > 0) {
			$cotinine = 1;
		}

		$ob = new stdClass();
		$ob->total = 0;
		$ob->data = array('bp_systolic' => array($bp_systolic,.0625, 0.0, $data['bp_systolic']),
						'bp_diastolic' => array($bp_diastolic,.0625, 0.0, $data['bp_diastolic']),
						'body_fat' => array($body_fat,.125, 0.0, $data['body_fat']),
						'bmi' => array($bmi,.0625, 0.0, $data['bmi']),
						'waist' => array($waist, .0625, 0.0, $data['waist']),
						'blood_glucose' => array($blood_glucose,.0417, 0.0, $data['blood_glucose']),
						'hemoglobin' => array($hemoglobin,.0417, 0.0, $data['hemoglobin']),
						'cotinine' => array($cotinine,.0416, 0.0, $data['cotinine']),
						'cholesterol' => array($cholesterol,.1, 0.0, $data['cholesterol']),
						'triglycerides' => array($triglycerides,.1, 0.0, $data['triglycerides']),
						'hdl' => array($hdl,.1, 0.0, $data['hdl']),
						'ldl' => array($ldl,.1, 0.0, $data['ldl']),
						'tc_hdl' => array($tc_hdl,.1, 0.0, $ratio));
		foreach($ob->data as &$rec){
			$rec[2] = $rec[0] * $rec[1];
			$ob->total += $rec[2];
		}
		return $ob;
	}


	/**
	 * Get a list of 'other' languages as a drop down array
	 *
	 */
	public function getOtherLanguages() {
		$sql = "SELECT id AS value, language AS display FROM p_other_languages WHERE is_active = 1 ORDER BY language";
		return ($this->dbOb->query($sql));
	}

	/**
	 * Get the detailed information about a particular section of the iFocus Assessment
	 *
	 */
	public function getSectionDetails($section) {
		if ($section == 'Blood_glucose') {
			$title = "Blood Glucose";
			$text = "Glucose circulates in our blood stream and signals the pancreas to secrete insulin. Insulin is the body's \"key\" to release glucose. Glucose that enters into our cells is used by your body to create energy. Sometimes, certain conditions, such as excess body fat inhibit insulin ability to absorb glucose, thus leaving excess amounts of glucose in the bloodstream. This test measures the concentration of glucose in your blood.";
			$range = "Non-Fasting Less than 130 mg/dl<br />Fasting Less than 100 mg/dl";
			$type = $this->data['biometric_data']['glucose_test'];
			$score = $this->data['biometric_data']['blood_glucose'];
			$image = "green_chips.png";
			if ($type == "fasting") {
				if ($score > 100) {
					$image = "yellow_chips.png";
				}
				if ($score > 125) {
					$image = "red_chips.png";
				}
			}
			else if ($type == "non-fasting") {
				if ($score > 130) {
					$image = "yellow_chips.png";
				}
				if ($score > 200) {
					$image = "red_chips.png";
				}
			}
			$score = $score . " mg/dl (" . $type . ")";
		}

		else if ($section == 'Waist_circumference') {
			$title = "Waist Circumference";
			$text = "Waist Circumference and Body Mass Index (BMI) are interrelated; however waist circumference can provide an independent prediction of risk in addition to BMI. Body fat that accumulates around the stomach area poses a greater health risk than fat stored in the lower half of the body.";
			$range = "< 35 inches (female) | < 40 inches (male)";
			$score = $this->data['biometric_data']['waist'];
			$image = "green_chips.png";
			if ($this->demographics['gender'] == 'F') {
				if ($score >= 35.0) {
					$image = "red_chips.png";
				}
			}
			else if ($this->demographics['gender'] == 'M') {
				if ($score >= 40.0) {
					$image = "red_chips.png";
				}
			}
			$score = $score . " inches";			
		}

		else if ($section == "Body_mass_index") {
			$title = "Body Mass Index";
			$text = "Body Mass Index uses a mathematical formula that takes into account both a person´s height and weight. BMI is a person´s weight in kilograms divided by height in meters squared (BMI=kg/m2). BMI correlates with, but does not equal body fat. The relation between body shape and BMI changes with age and gender. BMI is one of many factors related to developing a chronic disease (such as heart disease, cancer or diabetes). Other factors that may be important to consider when assessing your risk for chronic disease include diet, physical activity, waist circumference, blood pressure, blood sugar level, cholesterol level, and family history of disease.";
			$range = "18.5 - 24.9";
			$score = $this->data['biometric_data']['bmi'];
			$image = "yellow_chips.png";
			if ($score > 30) {
				$image = "red_chips.png";
			}
			else if ($score > 24.9) {
				$image = "yellow_chips.png";
			}
			else if ($score >= 18.5) {
				$image = "green_chips.png";
			}
		}

		else if ($section == "Blood_pressure") {
			$title = "Blood Pressure";
			$text = "Blood Pressure is the pressure of the blood against the walls of the arteries. The top number, systolic pressure, the pressure in the arteries as the heart contracts to pump blood to the body. The bottom number, diastolic pressure, measures the pressure in the arterial walls between heart beats. High blood pressure makes your heart work harder than normal. Both the heart and arteries are then more prone to injury. High blood pressure increases the risk of heart attacks, strokes, and/or kidney failure.";
			$range = "Systolic: < 120 mm Hg | Diastolic: < 80 mm Hg";
			$sys = $this->data['biometric_data']['bp_systolic'];
			$dia = $this->data['biometric_data']['bp_diastolic'];
			if ($sys > 140) {
				$sysScore = 1;
			}
			else if ($sys > 120) {
				$sysScore = 3;
			}
			else {
				$sysScore = 5;
			}
			if ($dia > 90) {
				$diaScore = 1;
			}
			else if($dia > 80) {
				$diaScore = 3;
			}
			else {
				$diaScore = 5;
			}
			$tot = (($sysScore + $diaScore) / 2.0) * 20.0;
			if ($tot >= 80) {
				$image = "green_chips.png";
			}
			else if ($tot >= 50) {
				$image = "yellow_chips.png";
			}
			else {
				$image = "red_chips.png";
			}
			$score = $sys . " / " . $dia . " mm Hg";
		}

		else if ($section == "Cholesterol") {
			$title = "Total Cholesterol";
			$text = "Cholesterol is an essential fatty substance produced by the body used to create bile, hormones and fat soluble vitamins. Cholesterol is found in foods that come from animals such as meats, fish, poultry, eggs and dairy. High levels of circulating cholesterol may form deposits in artery walls leading to narrowing and hardening of the arteries and heart disease. Cholesterol does not dissolve in water and is combined with lipoproteins to assist in its transportation in the blood. Low-density lipoproteins (LDL), high-density lipoproteins (HDL), triglycerides, and other cholesterol levels such as VLDLS are all included in the total cholesterol value.";
			$range = "< 200 mg/dl";
			$score = $this->data['biometric_data']['cholesterol'];
			$image = "green_chips.png";
			if ($score > 239) {
				$image = "red_chips.png";
			}
			else if ($score > 199) {
				$image = "yellow_chips.png";
			}
			$score = $score . " mg/dl";
		}

		else if ($section == "Hdl") {
			$title = "High-Density Lipoprotein (HDL) Cholesterol";
			$text = "HDL cholesterol is nicknamed \"good\" cholesterol because it removes excess cholesterol from the body and transports it to the liver where it is eliminated from the body. This, in turn, prevents LDL cholesterol accumulation in the arterial walls and can reduce the risk for heart disease. Higher levels of HDL cholesterol are optimal.";
			$range = "> 50 mg/dl (female) | 40 mg/dl (male)";
			$score = $this->data['biometric_data']['hdl'];
			$image = "green_chips.png";
			if ($score < 30) {
				$image = "red_chips.png";
			}
			else if ($scoe < 40) {
				$image = "yellow_chips.png";
			}
			$score = $score . " mg/dl";
		}

		else if ($section == "Ldl") {
			$title = "Low Density Lipoprotein (LDL) Cholesterol";
			$text = "LDL cholesterol is nicknamed the \"bad\" cholesterol because in excess levels it can build plaque deposits on your arterial wall; a condition known as arteriolosclerosis. Low levels of LDL cholesterol are optimal.";
			$range = "< 100 mg/dl";
			$score = $this->data['biometric_data']['ldl'];
			$image = "green_chips.png";
			if ($score > 149) {
				$image = "red_chips.png";
			}
			else if ($score > 139) {
				$image = "yellow_chips.png";
			}
			$score = $score . " mg/dl";
		}

		else if ($section == "Triglycerides") {
			$title = "Triglycerides";
			$text = "Triglycerides are composed of fatty acids and glycerol. Like cholesterol, it circulates in your blood, but is stored in body fat and is used when the body requires extra energy. Triglyceride levels are very sensitive and can be affected by food consumption before a screening. Optimally, triglyceride levels should be performed on a fasting individual.";
			$range = "< 150 mg/dl";
			$score = $this->data['biometric_data']['triglycerides'];
			$image = "green_chips.png";
			if ($score > 199) {
				$image = "red_chips.png";
			}
			else if ($score > 149) {
				$image = "yellow_chips.png";
			}
			$score = $score . " mg/dl";
		}

		else if ($section == "TC_HDL_Ratio") {
			$title = "TC/HDL Cardiac Ratio";
			$text = "A ratio of total cholesterol divided by HDL cholesterol greater than 4.0 can indicate an increased risk of developing heart disease. A high ratio suggests there is a large amount of LDL cholesterol circulating in the blood that can stick to arterial walls and not enough HDL cholesterol to eliminate the excess.";
			$range = "< 4.0";
			$score = $this->data['biometric_data']['cholesterol'] / $this->data['biometric_data']['hdl'];
			$image = "green_chips.png";
			if ($score >= 4.0) {
				$image = "red_chips.png";
			}
		}

		$info = array('title' => $title,
			            'text' => $text,
			            'image' => $image,
			            'score' => $score,
		              'range' => $range
	              );

		return $info;
	}


	/**
	 * Check to see if the assessment is completed
	 * @return boolean
	 */
	function isCompleted(){
		return $this->status==IFocusModel::COMPLETE;
	}		
}