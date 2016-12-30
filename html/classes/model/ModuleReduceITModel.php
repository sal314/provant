<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/Validator.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
	require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class ModuleReduceITModel {

	private $dbOb = null;
	private $preventUpdate = false;
	private $id = 0;
	private $lastCompleted = 0;
	private $data = array();
	private $dateUpdated = null;


	/**
	 * __construct()
	 *	@param - $check is set to true to just perform a check of the database data
	 *	         otherwise it will insert a new empty row if not found
	 *	class constructor
	 *
	 */
	public function __construct($check=false) {
		$this->dbOb = Database::create();
		$cred = UserCredentials::load();
		$this->id = $cred->getId();

		$sql = "SELECT * FROM u_module_reduceit WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' AND is_active = 1";
		$data = $this->dbOb->getRow($sql);
		$this->restore($data);
		if (!$data) {
			if (!$check) {
				$sql = "INSERT INTO u_module_reduceit(z_user_id) VALUES('".$this->dbOb->escape_string($this->id)."')";
				$this->dbOb->insert($sql);
				$im = new IncentivePointsModel();
				$im->addIncentivePointMA("ModuleReduceIT", "start");
			}
			else {
//				$this->lastCompleted = -1;
				$this->lastCompleted = 0;				//Skip the intro
			}
		}
		else {
			$this->lastCompleted = $this->data['last_completed'];
		}
	}

	/**
	 * getLastCompleted()
	 *	@param - $mode is true to override the 5 day waiting period
	 *	If just starting or already completed, return the correct 'last completed' week number
	 *	Otherwise, check if the last week was completed at least 5 days ago.  If it was return
	 *	the correct 'last completed'.  If not, subtract one to keep them at bay.
	 *
	 */
	public function getLastCompleted($mode=false) {
		if (($this->lastCompleted == 5) || ($this->lastCompleted <= 0)){
			return $this->lastCompleted;
		}

		if (($mode) || (IT_STUB)){									//override to get actual last completed (health coach)
			return $this->lastCompleted;
		}

		$sql = "SELECT week" . $this->lastCompleted . "_start FROM u_module_reduceit WHERE z_user_id = " . $this->id;
		$date_started = $this->dbOb->getOne($sql);
		if ($date_started != '0000-00-00') {
			$today = time();
			$nextStart = strtotime($date_started) + (5 * 24 * 60 * 60);
			if ($today < $nextStart) {
				return $this->lastCompleted - 1;
			}
			else {
				return $this->lastCompleted;
			}
		}
		else {
			return $this->lastCompleted;
		}
	}

	/**
	 * recordStart()
	 *	@param - week number
	 *	save the date of when this week was started
	 */
	public function recordStart($week) {
		$sql = "SELECT week" . $week . "_start FROM	u_module_reduceit WHERE z_user_id = " . $this->id;
		$sd = $this->dbOb->getOne($sql);
		if ($sd == "0000-00-00") {
			$sql = "UPDATE u_module_reduceit SET week" . $week . "_start = '" . date("Y-m-d") . "' WHERE z_user_id = " . $this->id;
			$this->dbOb->update($sql);
		}
	}


	/**
	 * introComplete()
	 *	@param - none
	 *	return true/false whether the intro pages were completed.  (No longer used)
	 *
	 */
	public function introComplete() {
		return $this->lastCompleted >= 0;
	}


	/**
	 * get()
	 *	@param - $index is the intro/week index to return
	 *	returns an array of data relating to the input index
	 *
	 */
	public function get($index) {
		if (isset ($this->data[$index])) {
			return $this->data[$index];
		}
		else {
			return null;
		}
	}


	/**
	 * validate()
	 *	@param - $arr is an array of input data
	 *         - $topic is the week number
	 *         - $page is the page number
	 *         - $gotoIndex is set to true to force the flow back to the dashboard
	 *	validates all input from the web page based on week and page. If valid input,
	 *	populates a class array with the data.
	 *
	 */
	public function validate($arr, $topic, $page) {
		$err = array();
		$vc = new Validator();

		switch ($topic . "_" . $page) {

			case 'intro_2':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['intro_data'][$key] = isset($val) ? $val : "";
				}

				if ($this->lastCompleted < 0) $this->lastCompleted = 0;
				return false;
				break;

			case 'week1_1':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['week1_data'][$key] = isset($val) ? $val : "";
				}

				return false;
				break;

			case 'week1_5':
				// Number of exam questions
				$nQ = 5;
			  	$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week1_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
					}
					catch (ValidationException $e) {
						if ($e->getValidationCode() == 1) {		//Missing array element (question not answered)
							$retAnsAll = true;
						}
					}
				}

				if ($retAnsAll) {
					$errObj = new stdClass();
					$errObj->display_name = "";
					$errObj->name = "";
					$errObj->type = 5;
					$errObj->message = "You must answer all questions.";
					$err[] = $errObj;
				}

				if (!$err) {
					// The correct answers (array size = $nQ!!)
					$correct = array(3, 1, 1, 2, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week1_data']['E'.$j] != $correct[$i]) {
							$wrong++;
							$result['E'.$j] = $this->getIncorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "incorrect";
						}
						else {
							$result['E'.$j] = $this->getCorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "correct";
						}
					}

					$result['class'] = $class;
					$maxWrong = 0;
					$grade = sprintf("%.1f", ((($nQ - $wrong) / $nQ) * 100.0));
					$pass = sprintf("%.1f", ((($nQ - $maxWrong) / $nQ) * 100.0));
					if ($wrong > $maxWrong) {
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You must have a grade of " . $pass . "%. Your grade was " . $grade . "%. Please try again";
    					$err[]=$errOb;
					}
				}

				if (!$err) {
					if ($this->lastCompleted < 1)
						$this->lastCompleted = 1;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;

			case 'week2_1':
			case 'week2_2':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['week2_data'][$key] = isset($val) ? $val : "";
				}

				return false;
				break;

			case 'week2_7':
				// Number of exam questions
				$nQ = 5;
			  	$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					if (($i == 1) || ($i == 4)) {
						try {
							$this->data['week2_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2)), false, true);
						}
						catch (ValidataionException $e) {
							if ($e->getValidationCode() == 1) {
								$retAnsAll = true;
							}
						}
					}
					else {
						try {
							$this->data['week2_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
						catch (ValidationException $e) {
							if ($e->getValidationCode() == 1) {		//Missing array element (question not answered)
								$retAnsAll = true;
							}
						}
					}
				}

				if ($retAnsAll) {
					$errObj = new stdClass();
					$errObj->display_name = "";
					$errObj->name = "";
					$errObj->type = 5;
					$errObj->message = "You must answer all questions.";
					$err[] = $errObj;
				}

				if (!$err) {
					// The correct answers (array size = $nQ!!)
					$correct = array(1, 2, 4, 2, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week2_data']['G'.$j] != $correct[$i]) {
							$wrong++;
							$result['G'.$j] = $this->getIncorrectText($topic, $page, 'G'.$j);
							$class['G'.$j] = "incorrect";
						}
						else {
							$result['G'.$j] = $this->getCorrectText($topic, $page, 'G'.$j);
							$class['G'.$j] = "correct";
						}
					}

					$result['class'] = $class;
					$maxWrong = 0;
					$grade = sprintf("%.1f", ((($nQ - $wrong) / $nQ) * 100.0));
					$pass = sprintf("%.1f", ((($nQ - $maxWrong) / $nQ) * 100.0));
					if ($wrong > $maxWrong) {
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You must have a grade of " . $pass . "%. Your grade was " . $grade . "%. Please try again";
    					$err[]=$errOb;
					}
				}

				if (!$err) {
					if ($this->lastCompleted < 2)
						$this->lastCompleted = 2;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;

			case 'week3_2':
			case 'week3_4':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['week3_data'][$key] = isset($val) ? $val : "";
				}

				return false;
				break;

			case 'week3_5':
				// Number of exam questions
				$nQ = 5;
			  	$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					if (($i == 2) || ($i == 4)) {
						try {
							$this->data['week3_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2)), false, true);
						}
						catch (ValidataionException $e) {
							if ($e->getValidationCode() == 1) {
								$retAnsAll = true;
							}
						}
					}
					else {
						try {
							$this->data['week3_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
						catch (ValidationException $e) {
							if ($e->getValidationCode() == 1) {		//Missing array element (question not answered)
								$retAnsAll = true;
							}
						}
					}
				}

				if ($retAnsAll) {
					$errObj = new stdClass();
					$errObj->display_name = "";
					$errObj->name = "";
					$errObj->type = 5;
					$errObj->message = "You must answer all questions.";
					$err[] = $errObj;
				}

				if (!$err) {
					// The correct answers (array size = $nQ!!)
					$correct = array(3, 1, 1, 1, 2);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week3_data']['E'.$j] != $correct[$i]) {
							$wrong++;
							$result['E'.$j] = $this->getIncorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "incorrect";
						}
						else {
							$result['E'.$j] = $this->getCorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "correct";
						}
					}

					$result['class'] = $class;
					$maxWrong = 0;
					$grade = sprintf("%.1f", ((($nQ - $wrong) / $nQ) * 100.0));
					$pass = sprintf("%.1f", ((($nQ - $maxWrong) / $nQ) * 100.0));
					if ($wrong > $maxWrong) {
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You must have a grade of " . $pass . "%. Your grade was " . $grade . "%. Please try again";
    					$err[]=$errOb;
					}
				}

				if (!$err) {
					if ($this->lastCompleted < 3)
						$this->lastCompleted = 3;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;

			case 'week4_1':
			case 'week4_3':
			case 'week4_4':
			case 'week4_5':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['week4_data'][$key] = isset($val) ? $val : "";
				}

				return false;
				break;

			case 'week4_6':
				// Number of exam questions
				$nQ = 5;
			  	$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					if ($i == 2) {
						try {
							$this->data['week4_data']['F'.$i] = $vc->exists('F'.$i, $arr, "enum", array("values" => array(1,2)), false, true);
						}
						catch (ValidataionException $e) {
							if ($e->getValidationCode() == 1) {
								$retAnsAll = true;
							}
						}
					}
					else {
						try {
							$this->data['week4_data']['F'.$i] = $vc->exists('F'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
						catch (ValidationException $e) {
							if ($e->getValidationCode() == 1) {		//Missing array element (question not answered)
								$retAnsAll = true;
							}
						}
					}
				}

				if ($retAnsAll) {
					$errObj = new stdClass();
					$errObj->display_name = "";
					$errObj->name = "";
					$errObj->type = 5;
					$errObj->message = "You must answer all questions.";
					$err[] = $errObj;
				}

				if (!$err) {
					// The correct answers (array size = $nQ!!)
					$correct = array(4, 1, 3, 4, 1);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week4_data']['F'.$j] != $correct[$i]) {
							$wrong++;
							$result['F'.$j] = $this->getIncorrectText($topic, $page, 'F'.$j);
							$class['F'.$j] = "incorrect";
						}
						else {
							$result['F'.$j] = $this->getCorrectText($topic, $page, 'F'.$j);
							$class['F'.$j] = "correct";
						}
					}

					$result['class'] = $class;
					$maxWrong = 0;
					$grade = sprintf("%.1f", ((($nQ - $wrong) / $nQ) * 100.0));
					$pass = sprintf("%.1f", ((($nQ - $maxWrong) / $nQ) * 100.0));
					if ($wrong > $maxWrong) {
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You must have a grade of " . $pass . "%. Your grade was " . $grade . "%. Please try again";
    					$err[]=$errOb;
					}
				}

				if (!$err) {
					if ($this->lastCompleted < 4)
						$this->lastCompleted = 4;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;

			case 'week5_1':
			case 'week5_3':
			case 'week5_5':
			case 'week5_6':
				foreach ($arr as $key => $val) {
					if ($key != "submit")
						$this->data['week5_data'][$key] = isset($val) ? $val : "";
				}

				return false;
				break;

			case 'week5_8':
				// Number of exam questions
				$nQ = 5;
			  	$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					if ($i == 2) {
						try {
							$this->data['week5_data']['H'.$i] = $vc->exists('H'.$i, $arr, "enum", array("values" => array(1,2)), false, true);
						}
						catch (ValidataionException $e) {
							if ($e->getValidationCode() == 1) {
								$retAnsAll = true;
							}
						}
					}
					else if ($i == 5) {
						try {
							$this->data['week5_data']['H'.$i] = $vc->exists('H'.$i, $arr, "enum", array("values" => array(1,2,3,4,5)), false, true);
						}
						catch (ValidataionException $e) {
							if ($e->getValidationCode() == 1) {
								$retAnsAll = true;
							}
						}
					}
					else {
						try {
							$this->data['week5_data']['H'.$i] = $vc->exists('H'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
						catch (ValidationException $e) {
							if ($e->getValidationCode() == 1) {		//Missing array element (question not answered)
								$retAnsAll = true;
							}
						}
					}
				}

				if ($retAnsAll) {
					$errObj = new stdClass();
					$errObj->display_name = "";
					$errObj->name = "";
					$errObj->type = 5;
					$errObj->message = "You must answer all questions.";
					$err[] = $errObj;
				}

				if (!$err) {
					// The correct answers (array size = $nQ!!)
					$correct = array(1, 1, 4, 2, 5);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week5_data']['H'.$j] != $correct[$i]) {
							$wrong++;
							$result['H'.$j] = $this->getIncorrectText($topic, $page, 'H'.$j);
							$class['H'.$j] = "incorrect";
						}
						else {
							$result['H'.$j] = $this->getCorrectText($topic, $page, 'H'.$j);
							$class['H'.$j] = "correct";
						}
					}

					$result['class'] = $class;
					$maxWrong = 0;
					$grade = sprintf("%.1f", ((($nQ - $wrong) / $nQ) * 100.0));
					$pass = sprintf("%.1f", ((($nQ - $maxWrong) / $nQ) * 100.0));
					if ($wrong > $maxWrong) {
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You must have a grade of " . $pass . "%. Your grade was " . $grade . "%. Please try again";
    					$err[]=$errOb;
					}
				}

				if (!$err) {
					if ($this->lastCompleted < 5) {
						$this->lastCompleted = 5;
						$this->data['date_entered'] = date('Y-m-d');
						$im=new IncentivePointsModel();
						$im->addIncentivePointMA("ModuleReduceIT","complete",$this->data['date_entered']);
					}
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;

		}
	}

	/**
	 * update()
	 *	@param - none
	 *	stores the class array of input data into the database table
	 *
	 */
	public function update() {
		$sql = "UPDATE u_module_reduceit SET last_completed='".$this->dbOb->escape_string($this->lastCompleted)."',
			intro_data='".$this->dbOb->escape_string(serialize($this->data['intro_data']))."',
			week1_data='".$this->dbOb->escape_string(serialize($this->data['week1_data']))."',
			week2_data='".$this->dbOb->escape_string(serialize($this->data['week2_data']))."',
			week3_data='".$this->dbOb->escape_string(serialize($this->data['week3_data']))."',
			week4_data='".$this->dbOb->escape_string(serialize($this->data['week4_data']))."',
			week5_data='".$this->dbOb->escape_string(serialize($this->data['week5_data']))."'";

		if(!$this->preventUpdate) $sql.=", date_updated=NOW() "; //dont change the date once the module has been completed

		$sql .= " WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
		$im = new IncentivePointsModel();
		if($this->lastCompleted==0){
			$im->addIncentivePointMA("ModuleReduceIT","start");
		}
		if($this->lastCompleted==5){
			$im->addIncentivePointMA("ModuleReduceIT","complete");
		}
	}


	/**
	 * restore()
	 *	@param - $record is the database raw record data
	 *	breaks the serialized data up into individual data elements in the class data array
	 *	if the database holds data.  Otherwise initializes the array to nulls and zeros.
	 *
	 */
	public function restore ($record) {
		$this->data=array();
		if(isset($record['intro_data']) && $record['intro_data']){
			$this->data['intro_data'] = unserialize($record['intro_data']);
		}
		else {
			$this->data['intro_data']=array(
				'B1' => '',
				'B2' => '',
				'B3' => '',
				'B4' => '',
				'B5' => '',
				'B6' => '',
				'B7' => '',
				'B8' => '',
				'B9' => '',
				'B10' => '',
			);
		}

		if(isset($record['week1_data']) && $record['week1_data']){
			$this->data['week1_data']=unserialize($record['week1_data']);
		}else{
			$this->data['week1_data']=array(
				'A1' => '',
				'A2' => '',
				'A3' => '',
				'A4' => '',
				'A5' => '',
				'A6' => '',
				'E1' => 0,
				'E2' => 0,
				'E3' => 0,
				'E4' => 0,
				'E5' => 0
			);
		}
		
		if(isset($record['week2_data']) && $record['week2_data']){
			$this->data['week2_data']=unserialize($record['week2_data']);
		}else{
			$this->data['week2_data']=array(
				'A1' => 0,
				'A2' => 0,
				'A3' => 0,
				'A4' => 0,
				'A5' => 0,
				'A6' => 0,
				'A7' => 0,
				'A8' => 0,
				'A9' => 0,
				'A10' => 0,
				'A11' => 0,
				'A12' => 0,
				'A13' => 0,
				'A14' => 0,
				'A15' => 0,
				'A16' => 0,
				'A17' => 0,
				'A18' => 0,
				'A19' => 0,
				'A20' => 0,
				'A21' => 0,
				'A22' => 0,
				'A23' => 0,
				'A24' => 0,
				'A25' => 0,
				'A26' => 0,
				'A27' => 0,
				'B1' => '',
				'B2' => '',
				'B3' => '',
				'G1' => 0,
				'G2' => 0,
				'G3' => 0,
				'G4' => 0,
				'G5' => 0
			);
		}

		if(isset($record['week3_data']) && $record['week3_data']){
			$this->data['week3_data']=unserialize($record['week3_data']);
		}else{
			$this->data['week3_data']=array(
				'B1' => '',
				'B2' => '',
				'B3' => '',
				'D1' => '',
				'D2' => '',
				'D3' => '',
				'E1' => 0,
				'E2' => 0,
				'E3' => 0,
				'E4' => 0,
				'E5' => 0
			);
		}

		if(isset($record['week4_data']) && $record['week4_data']){
			$this->data['week4_data']=unserialize($record['week4_data']);
		}else{
			$this->data['week4_data']=array(
				'A1' => '',
				'A2' => '',
				'A3' => '',
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'D1' => '',
				'D2' => '',
				'D3' => '',
				'E1' => '',
				'E2' => '',
				'E3' => '',
				'F1' => 0,
				'F2' => 0,
				'F3' => 0,
				'F4' => 0,
				'F5' => 0
			);
		}

		if(isset($record['week5_data']) && $record['week5_data']){
			$this->data['week5_data']=unserialize($record['week5_data']);
		}else{
			$this->data['week5_data']=array(
				'A1' => '',
				'A2' => '',
				'A3' => '',
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'E1' => '',
				'E2' => '',
				'E3' => '',
				'F1' => '',
				'F2' => '',
				'F3' => '',
				'H1' => 0,
				'H2' => 0,
				'H3' => 0,
				'H4' => 0,
				'H5' => 0
			);
		}

		if ($record['last_completed'] == 5) {
			$this->preventUpdate = true;
		}
		$this->data['last_completed'] = $record['last_completed'];
		$this->dateUpdated = $record['date_updated'];
	}


	private function getCorrectText($week, $page, $question) {
		$sql = "SELECT correctText FROM p_modules_exams " .
				"WHERE ITModuleName = 'ReduceIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

	private function getIncorrectText($week, $page, $question) {
		$sql = "SELECT incorrectText FROM p_modules_exams " .
				"WHERE ITModuleName = 'ReduceIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

}
