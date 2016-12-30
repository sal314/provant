<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/Validator.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
	require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");
	require_once (ROOT_DIR."classes/model/EmailModel.php");

class ModuleBreakITModel {

	private $dbOb = null;
	private $preventUpdate = false;
	private $id = 0;
	private $lastCompleted = 0;
	private $data = array();
	private $dateUpdated = null;

	public function __construct($check=false) {
		$this->dbOb = Database::create();
		$cred = UserCredentials::load();
		$this->id = $cred->getId();

		$sql = "SELECT * FROM u_module_breakit ".
				"WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' ".
				"AND is_active = 1";
		$data = $this->dbOb->getRow($sql);
		$this->restore($data);
		if (!$data) {
			if (!$check) {
				$sql = "INSERT INTO u_module_breakit(z_user_id) VALUES('".$this->dbOb->escape_string($this->id)."')";
				$this->dbOb->insert($sql);
				$im = new IncentivePointsModel();
				$im->addIncentivePointMA("ModuleBreakIT", "start");
			}
			else {
//				$this->lastCompleted = -1;
				$this->lastCompleted = 0;
			}
		}
		else {
			$this->lastCompleted=$this->data['last_completed'];
		}	
	}

	/**
	 * getLastCompleted()
	 *	@param - none
	 *	return the last completed week number.  However, make sure that 7 days
	 *	have passed since the start of the last completed week.  If just starting
	 *	return the week zero.  If completed, return that it's done.  Otherwise do
	 *	the date check.
	 */
	public function getLastCompleted($mode=false) {
		if (($this->lastCompleted == 5) || ($this->lastCompleted <= 0)){
			return $this->lastCompleted;
		}

		if (($mode) || (IT_STUB)){									//override to get actual last completed (health coach)
			return $this->lastCompleted;
		}

		$sql = "SELECT week" . $this->lastCompleted . "_start FROM u_module_breakit WHERE z_user_id = " . $this->id;
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
		$sql = "SELECT week" . $week . "_start FROM	u_module_breakit WHERE z_user_id = " . $this->id;
		$sd = $this->dbOb->getOne($sql);
		if ($sd == "0000-00-00") {
			$sql = "UPDATE u_module_breakit SET week" . $week . "_start = '" . date("Y-m-d") . "' WHERE z_user_id = " . $this->id;
			$this->dbOb->update($sql);
		}
	}



	public function introComplete() {
		return $this->lastCompleted >= 0;
	}

	public function get($index) {
		if (isset ($this->data[$index])) {
			return $this->data[$index];
		}
		else {
			return null;
		}
	}

	public function validate($arr, $topic, $page) {
		$err = array();
		$vc = new Validator();
		$result = array();
		$class = array();

		switch ($topic . "_" . $page) {

			case 'intro_2':
				foreach ($arr as $key => $val) {
					$this->data['intro_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;

				break;

			case 'intro_3':
				foreach ($arr as $key => $val) {
					$this->data['intro_data'][$key] = isset($val) ? $val : "";
				}

				if($this->lastCompleted < 0) $this->lastCompleted = 0;
				$result['err'] = false;
				return $result;

				break;

				
			case 'week1_2':
			case 'week1_5':
				if ($page == 2) {
					if ($arr['B14'] != 1) {
						$arr['B15'] = "";
					}	
				}
				else if ($page == 5) {
					if ($arr['E9'] != 1) {
						$arr['E10'] = "";
					}
				}

			case 'week1_1':
			case 'week1_3':
			case 'week1_4':
			case 'week1_6':
				foreach ($arr as $key => $val) {
					$this->data['week1_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;
				
				break;

			case 'week1_7':
				// Number of exam questions
				//	This exam is a little different in that question #2 is a 'check all that apply' type answer
				//	therefore, the answers are being returned in a changed order:
				//
				//		Question			Variable
				//			1					G1
				//			3					G2
				//			4					G3
				//			5					G4
				//			2					G5 - G8
				//
				if (!isset($arr['G5'])) $arr['G5'] = 0;
				if (!isset($arr['G6'])) $arr['G6'] = 0;
				if (!isset($arr['G7'])) $arr['G7'] = 0;
				if (!isset($arr['G8'])) $arr['G8'] = 0;

				$nQ = 5;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						if ($i == 5) {
							for ($j = 5; $j <= 8; $j++) {
								$this->data['week1_data']['G'.$j] = $vc->exists('G'.$j, $arr, "enum", array("values" => array(0,1)), true, true);
							}
						}
						else {
							$this->data['week1_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
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
					// The correct answers (note that question 2 is a 'check all that apply' type)
					$correct = array(3, 4, 2, 1);
					$wrong = 0;
					for ($i = 0; $i < 4; $i++) {
						$j = $i + 1;
						if ($this->data['week1_data']['G'.$j] != $correct[$i]) {
							$wrong++;
							$result['G'.$j] = $this->getIncorrectText($topic, $page, 'G'.$j);
							$class['G'.$j] = "incorrect";
						}
						else {
							$result['G'.$j] = $this->getCorrectText($topic, $page, 'G'.$j);
							$class['G'.$j] = "correct";
						}
					}
					// Question 2 answers are at the end of the group.  It has multiple answers
					$q2_answers = array('G5' => 1, 'G6' => 0, 'G7' => 1, 'G8' => 1);
					$q2wrong = 0;
					foreach($q2_answers as $q => $a) {
						if ($this->data['week1_data'][$q] != $a) {
							$q2wrong++;
						}
					}
					if ($q2wrong > 0) {
						$wrong++;
						$result['G5'] = $this->getIncorrectText($topic, $page, 'G5');
						$class['G5'] = "incorrect";
					}
					else {
						$result['G5'] = $this->getCorrectText($topic, $page, 'G5');
						$class['G5'] = "correct";
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

//				return ($err) ? $err : false;
				return $result;

				break;


			case 'week2_5':
				if ($arr['E4'] != 2) {
					$arr['E5'] = "";
				}

			case 'week2_1':
				
				foreach ($arr as $key => $val) {
					$this->data['week2_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;

				break;


			case 'week2_7':
				// Number of exam questions
				$nQ = 5;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						if ($i == 2) {
							$this->data['week2_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2,3,4,5)), false, true);
						}
						else {
							$this->data['week2_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
					}
					catch (ValidationException $e) {
						if ($e->getValidationCode() == 1)
						$retAnsAll = true;
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
					$correct = array(4, 5, 2, 4, 1);
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


			case 'week3_1':
			case 'week3_2':
			case 'week3_3':
			case 'week3_5':
				foreach ($arr as $key => $val) {
					$this->data['week3_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;

				break;


			case 'week3_6':
				// Number of exam questions
				$nQ = 5;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week3_data']['F'.$i] = $vc->exists('F'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
					}
					catch (ValidationException $e) {
						if ($e->getValidationCode() == 1)
						$retAnsAll = true;
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
					$correct = array(4, 4, 3, 1, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week3_data']['F'.$j] != $correct[$i]) {
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
					if ($this->lastCompleted < 3)
						$this->lastCompleted = 3;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;

				break;

			case 'week4_3':
			case 'week4_4':
				foreach ($arr as $key => $val) {
					$this->data['week4_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;

				break;


			case 'week4_5':
				// This one has two 'check all that apply' style questions.
				// Make sure they are initialized
				if (!isset($arr['E4'])) $arr['E4'] = 0;
				if (!isset($arr['E5'])) $arr['E5'] = 0;
				if (!isset($arr['E6'])) $arr['E6'] = 0;
				if (!isset($arr['E7'])) $arr['E7'] = 0;
				if (!isset($arr['E8'])) $arr['E8'] = 0;
				if (!isset($arr['E9'])) $arr['E9'] = 0;
				if (!isset($arr['E10'])) $arr['E10'] = 0;
				if (!isset($arr['E11'])) $arr['E11'] = 0;
				
				$nQ = 5;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						if ($i == 4) {
							for ($j = 4; $j <= 7; $j++) {
								$this->data['week4_data']['E'.$j] = $vc->exists('E'.$j, $arr, "enum", array("values" => array(0,1)), true, true);
							}
						}
						else if ($i == 5) {
							for ($j = 8; $j <= 11; $j++) {
								$this->data['week4_data']['E'.$j] = $vc->exists('E'.$j, $arr, "enum", array("values" => array(0,1)), true, true);
							}
						}
						else {
							$this->data['week4_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
						}
					}
					catch (ValidationException $e) {
						if ($e->getValidationCode() == 1)
						$retAnsAll = true;
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
					// The correct answers for singel answer questions)
					$correct = array(2, 2, 3);
					$wrong = 0;
					for ($i = 0; $i < 3; $i++) {
						$j = $i + 1;
						if ($this->data['week4_data']['E'.$j] != $correct[$i]) {
							$wrong++;
							$result['E'.$j] = $this->getIncorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "incorrect";
						}
						else {
							$result['E'.$j] = $this->getCorrectText($topic, $page, 'E'.$j);
							$class['E'.$j] = "correct";
						}
					}

					$q2_answers = array('E4' => 1, 'E5' => 0, 'E6' => 1, 'E7' => 1);
					$q2wrong = 0;
					foreach($q2_answers as $q => $a) {
						if ($this->data['week4_data'][$q] != $a) {
							$q2wrong++;
						}
					}
					if ($q2wrong > 0) {
						$wrong++;
						$result['E4'] = $this->getIncorrectText($topic, $page, 'E4');
						$class['E4'] = "incorrect";
					}
					else {
						$result['E4'] = $this->getCorrectText($topic, $page, 'E4');
						$class['E4'] = "correct";
					}

					$q5_answers = array('E8' => 0, 'E9' => 1, 'E10' => 1, 'E11' => 1);
					$q5wrong = 0;
					foreach($q5_answers as $q => $a) {
						if ($this->data['week4_data'][$q] != $a) {
							$q5wrong++;
						}
					}
					if ($q5wrong > 0) {
						$wrong++;
						$result['E8'] = $this->getIncorrectText($topic, $page, 'E8');
						$class['E8'] = "incorrect";
					}
					else {
						$result['E8'] = $this->getCorrectText($topic, $page, 'E8');
						$class['E8'] = "correct";
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


			case 'week5_2':
			case 'week5_3':
				foreach ($arr as $key => $val) {
					$this->data['week5_data'][$key] = isset($val) ? $val : "";
				}
				$result['err'] = false;
				return $result;

				break;


			case 'week5_5':
				// Number of exam questions
				$nQ = 5;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week5_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
					}
					catch (ValidationException $e) {
						if ($e->getValidationCode() == 1)
						$retAnsAll = true;
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
					$correct = array(3, 3, 1, 4, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week5_data']['E'.$j] != $correct[$i]) {
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
					if ($this->lastCompleted < 5) {
						$this->lastCompleted = 5;
						$this->data['date_entered'] = date('Y-m-d');
						$im=new IncentivePointsModel();
						$im->addIncentivePointMA("ModuleBreakIT","complete",$this->data['date_entered']);
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

	public function update() {
		$sql = "UPDATE u_module_breakit SET last_completed='".$this->dbOb->escape_string($this->lastCompleted)."',
			intro_data='".$this->dbOb->escape_string(serialize($this->data['intro_data']))."',
			week1_data='".$this->dbOb->escape_string(serialize($this->data['week1_data']))."',
			week2_data='".$this->dbOb->escape_string(serialize($this->data['week2_data']))."',
			week3_data='".$this->dbOb->escape_string(serialize($this->data['week3_data']))."',
			week4_data='".$this->dbOb->escape_string(serialize($this->data['week4_data']))."',
			week5_data='".$this->dbOb->escape_string(serialize($this->data['week5_data']))."'";

		if(!$this->preventUpdate) $sql.=", date_updated=NOW() "; //dont change the date once the module has been completed
		
		$sql .= " WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' AND is_active = 1";
		$this->dbOb->update($sql);
		$im = new IncentivePointsModel();
		if($this->lastCompleted==0){
			$im->addIncentivePointMA("ModuleBreakIT","start");
		}
		if($this->lastCompleted==5){
			$im->addIncentivePointMA("ModuleBreakIT","complete");
		}
	}

	public function restore ($record) {
		$this->data=array();
		if(isset($record['intro_data']) && $record['intro_data']){
			$this->data['intro_data'] = unserialize($record['intro_data']);
		}
		else {
			$this->data['intro_data']=array(
				'B1' => 0,
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'C4' => ''
			);
		}
		if(isset($record['week1_data']) && $record['week1_data']){
			$this->data['week1_data']=unserialize($record['week1_data']);
		}else{
			$this->data['week1_data']=array(
				'A1' => 0,
				'A2' => 0,
				'A3' => 0,
				'A4' => 0,
				'A5' => 0,
				'B1' => 0,
				'B2' => 0,
				'B3' => 0,
				'B4' => 0,
				'B5' => 0,
				'B6' => 0,
				'B7' => 0,
				'B8' => 0,
				'B9' => 0,
				'B10' => 0,
				'B11' => 0,
				'B12' => 0,
				'B13' => 0,
				'B14' => 0,
				'B15' => '',
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'C4' => '',
				'C5' => '',
				'D1' => '',
				'D2' => '',
				'D3' => '',
				'D4' => '',
				'D5' => '',
				'E1' => '',
				'E2' => '',
				'E3' => '',
				'E4' => '',
				'E5' => '',
				'E6' => '',
				'E7' => '',
				'E8' => '',
				'E9' => '',
				'E10' => '',
				'F1' => '',
				'F2' => '',
				'F3' => '',
				'F4' => '',
				'F5' => '',
				'G1' => '',
				'G2' => '',
				'G3' => '',
				'G4' => '',
				'G5' => '',
				'G6' => '',
				'G7' => '',
				'G8' => ''			
			);
		}
		
		if(isset($record['week2_data']) && $record['week2_data']){
			$this->data['week2_data']=unserialize($record['week2_data']);
		}else{
			$this->data['week2_data']=array(
				'A1' => '',
				'A2' => '',
				'A3' => '',
				'E1' => 0,
				'E2' => 0,
				'E3' => 0,
				'E4' => 0,
				'E5' => '',
				'E6' => 0,
				'E7' => 0,
				'E8' => '',
				'E9' => '',
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
				'A1' => '',
				'A2' => '',
				'A3' => '',
				'A4' => '',
				'A5' => '',
				'B1' => '',
				'B2' => '',
				'B3' => '',
				'B4' => '',
				'B5' => '',
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'C4' => '',
				'C5' => '',
				'E1' => '',
				'E2' => '',
				'E3' => '',
				'E4' => '',
				'E5' => '',
				'E6' => '',
				'E7' => '',
				'F1' => 0,
				'F2' => 0,
				'F3' => 0,
				'F4' => 0,
				'F5' => 0
			);
		}
		
		if(isset($record['week4_data']) && $record['week4_data']){
			$this->data['week4_data']=unserialize($record['week4_data']);
		}else{
			$this->data['week4_data']=array(
				'C1' => '',
				'E1' => 0,
				'E2' => 0,
				'E3' => 0,
				'E4' => 0,
				'E5' => 0,
				'E6' => 0,
				'E7' => 0,
				'E8' => 0,
				'E9' => 0,
				'E10' => 0,
				'E11' => 0
			);
		}
		
		if(isset($record['week5_data']) && $record['week5_data']){
			$this->data['week5_data']=unserialize($record['week5_data']);
		}else{
			$this->data['week5_data']=array(
				'B1' => '',
				'C1' => '',
				'C2' => '',
				'C3' => '',
				'C4' => '',
				'C5' => '',
				'E1' => 0,
				'E2' => 0,
				'E3' => 0,
				'E4' => 0,
				'E5' => 0
			);
		}

		if ($record['last_completed'] == 5) {
			$this->preventUpdate = true;
		}
		$this->data['last_completed'] = $record['last_completed'];
		$this->lastCompleted = $this->data['last_completed'];
		$this->dateUpdated = $record['date_updated'];
		$this->data['date_updated'] = $record['date_updated'];
	}

	private function getCorrectText($week, $page, $question) {
		$sql = "SELECT correctText FROM p_modules_exams " .
				"WHERE ITModuleName = 'BreakIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

	private function getIncorrectText($week, $page, $question) {
		$sql = "SELECT incorrectText FROM p_modules_exams " .
				"WHERE ITModuleName = 'BreakIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}
	
	

}