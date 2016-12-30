<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/Validator.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
	require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class ModuleControlITModel {

	private $preventUpdate=false;
	private $id=0;
	private $lastCompleted=0;
	private $data=array();
	private $dateUpdated=null;
	
	public function __construct($check=false) {
		$this->dbOb=Database::create();
		$cred=UserCredentials::load();
		$this->id=$cred->getId();
		$this->lastCompleted=-1;
		
		$this->preventUpdate=false;
		$sql="SELECT * FROM u_module_controlit WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' AND is_active = 1";
		$data=$this->dbOb->getRow($sql);
		$this->restore($data);
		if(!$data){
			if (!$check) {
				$sql="INSERT INTO u_module_controlit(z_user_id) VALUES('".$this->dbOb->escape_string($this->id)."')";
				$this->dbOb->insert($sql);			
				$im=new IncentivePointsModel();
				$im->addIncentivePointMA("ModuleControlIT","start");
			}
			else {
//				$this->lastCompleted = -1;
				$this->lastCompleted = 0;
			}
		}else{
			$this->lastCompleted=$this->data['last_completed'];
		}
	}	

	public function getLastCompleted ($mode=false) {
		if (($this->lastCompleted == 5) || ($this->lastCompleted <= 0)){
			return $this->lastCompleted;
		}

		if (($mode) || (IT_STUB)){									//override to get actual last completed (health coach)
			return $this->lastCompleted;
		}

		$sql = "SELECT week" . $this->lastCompleted . "_start FROM u_module_controlit WHERE z_user_id = " . $this->id;
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
		$sql = "SELECT week" . $week . "_start FROM	u_module_controlit WHERE z_user_id = " . $this->id;
		$sd = $this->dbOb->getOne($sql);
		if ($sd == "0000-00-00") {
			$sql = "UPDATE u_module_controlit SET week" . $week . "_start = '" . date("Y-m-d") . "' WHERE z_user_id = " . $this->id;
			$this->dbOb->update($sql);
		}
	}



	public function introComplete() {
		return $this->lastCompleted >= 0;
	}

	public function get($index) {
		if (isset($this->data[$index])) {
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
			case "intro_3":
				foreach ($arr as $key=>$val) {
					if ($key != "submit")
						$this->data['intro_data'][$key] = isset($val) ? $val : "";
				}

				if($this->lastCompleted<0) $this->lastCompleted=0;
				$result['err'] = false;
				return $result;
				break;

			case "week1_1":
				foreach($arr as $key=>$val) {
					if ($key != "submit")
						$this->data['week1_data'][$key] = ($val == 1) ? 1 : 0;
				}
				$result['err'] = false;
				return $result;
				break;

			case "week1_7":
				// Number of exam questions
				$nQ = 6;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week1_data']['G'.$i] = $vc->exists('G'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
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
					$correct = array(2, 1, 4, 1, 3, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
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

			case "week2_6":
				// Number of questions
				$nQ = 6;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						if ($i == 1) {
							$this->data['week2_data']['F'.$i] = $vc->exists('F'.$i, $arr, "enum", array("values" => array(1,2)), false, true);
						}
						else {
							$this->data['week2_data']['F'.$i] = $vc->exists('F'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
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
					// The correct answers (size = $nQ!!)
					$correct = array(2, 2, 3, 1, 3, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week2_data']['F'.$j] != $correct[$i]) {
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
					if ($this->lastCompleted < 2)
						$this->lastCompleted = 2;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}

				return $result;
				break;
					

			case "week3_9":
				$nQ = 6;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week3_data']['I'.$i] = $vc->exists('I'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
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
					// The correct answers (size = $nQ!!)
					$correct = array(2, 4, 3, 1, 1, 4);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week3_data']['I'.$j] != $correct[$i]) {
							$wrong++;
							$result['I'.$j] = $this->getIncorrectText($topic, $page, 'I'.$j);
							$class['I'.$j] = "incorrect";
						}
						else {
							$result['I'.$j] = $this->getCorrectText($topic, $page, 'I'.$j);
							$class['I'.$j] = "correct";
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

			case "week4_11":
				$nQ = 6;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week4_data']['K'.$i] = $vc->exists('K'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
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
					// The correct answers (size = $nQ!!)
					$correct = array(2, 4, 1, 3, 2, 1);
					$wrong = 0;
					for ($i = 0; $i < $nQ; $i++) {
						$j = $i + 1;
						if ($this->data['week4_data']['K'.$j] != $correct[$i]) {
							$wrong++;
							$result['K'.$j] = $this->getIncorrectText($topic, $page, 'K'.$j);
							$class['K'.$j] = "incorrect";
						}
						else {
							$result['K'.$j] = $this->getCorrectText($topic, $page, 'K'.$j);
							$class['K'.$j] = "correct";
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

			case "week5_5":
				$nQ = 6;
				$retAnsAll = false;
				for ($i = 1; $i <= $nQ; $i++) {
					try {
						$this->data['week5_data']['E'.$i] = $vc->exists('E'.$i, $arr, "enum", array("values" => array(1,2,3,4)), false, true);
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
					// The correct answers (size = $nQ!!)
					$correct = array(3, 1, 4, 2, 4, 1);
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
						$im->addIncentivePointMA("ModuleControlIT","complete",$this->data['date_entered']);
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


	public function update(){
		$sql="UPDATE u_module_controlit SET last_completed='".$this->dbOb->escape_string($this->lastCompleted)."',
			intro_data='".$this->dbOb->escape_string(serialize($this->data['intro_data']))."',
			week1_data='".$this->dbOb->escape_string(serialize($this->data['week1_data']))."',
			week2_data='".$this->dbOb->escape_string(serialize($this->data['week2_data']))."',
			week3_data='".$this->dbOb->escape_string(serialize($this->data['week3_data']))."',
			week4_data='".$this->dbOb->escape_string(serialize($this->data['week4_data']))."',
			week5_data='".$this->dbOb->escape_string(serialize($this->data['week5_data']))."'";

		if(!$this->preventUpdate) $sql.=", date_updated=NOW() "; //dont change the date once the module has been completed
		
		$sql.=" WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
		$im=new IncentivePointsModel();
		if($this->lastCompleted==0){
			$im->addIncentivePointMA("ModuleControlIT","start");
		}
		if($this->lastCompleted==5){
			$im->addIncentivePointMA("ModuleControlIT","complete");
		}
	}


	public function restore($record){
		$this->data=array();
		if(isset($record['intro_data']) && $record['intro_data']){
			$this->data['intro_data']=unserialize($record['intro_data']);
		}else{
			$this->data['intro_data']=array(
				'C1'=>'',
				'C2'=>'',
				'C3'=>''
			);
		}
		if(isset($record['week1_data']) && $record['week1_data']){
			$this->data['week1_data']=unserialize($record['week1_data']);
		}else{
			$this->data['week1_data']=array(
				'A1'=>'',
				'A2'=>'',
				'A3'=>'',
				'A4'=>'',
				'A5'=>'',
				'A6'=>'',
				'A7'=>'',
				'A8'=>'',
				'G1'=>0,
				'G2'=>0,
				'G3'=>0,
				'G4'=>0,
				'G5'=>0,
				'G6'=>0
			);
		}
		
		if(isset($record['week2_data']) && $record['week2_data']){
			$this->data['week2_data']=unserialize($record['week2_data']);
		}else{
			$this->data['week2_data']=array(
				'F1'=>0,
				'F2'=>0,
				'F3'=>0,
				'F4'=>0,
				'F5'=>0,
				'F6'=>0
			);
		}
		
		if(isset($record['week3_data']) && $record['week3_data']){
			$this->data['week3_data']=unserialize($record['week3_data']);
		}else{
			$this->data['week3_data']=array(
				'I1'=>0,
				'I2'=>0,
				'I3'=>0,
				'I4'=>0,
				'I5'=>0,
				'I6'=>0
			);
		}
		
		if(isset($record['week4_data']) && $record['week4_data']){
			$this->data['week4_data']=unserialize($record['week4_data']);
		}else{
			$this->data['week4_data']=array(
				'K1'=>0,
				'K2'=>0,
				'K3'=>0,
				'K4'=>0,
				'K5'=>0,
				'K6'=>0
			);
		}
		
		if(isset($record['week5_data']) && $record['week5_data']){
			$this->data['week5_data']=unserialize($record['week5_data']);
		}else{
			$this->data['week5_data']=array(
				'E1'=>0,
				'E2'=>0,
				'E3'=>0,
				'E4'=>0,
				'E5'=>0,
				'E6'=>0
			);
		}

		if ($record['last_completed'] == 5) {
			$this->preventUpdate = true;
		}
		$this->data['last_completed'] = $record['last_completed'];
		$this->dateUpdated = $record['date_updated'];
		$this->data['date_updated'] = $record['date_updated'];
		$this->lastCompleted = $record['last_completed'];
	}

	private function getCorrectText($week, $page, $question) {
		$sql = "SELECT correctText FROM p_modules_exams " .
				"WHERE ITModuleName = 'ControlIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

	private function getIncorrectText($week, $page, $question) {
		$sql = "SELECT incorrectText FROM p_modules_exams " .
				"WHERE ITModuleName = 'ControlIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

}