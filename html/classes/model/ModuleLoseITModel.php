<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");
	require_once (LIB_ROOT."classes/common/Validator.class.php");
	require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
	require_once (ROOT_DIR."classes/model/IncentivePointsModel.php");

class ModuleLoseITModel{
	private $preventUpdate=false;
	private $id=0;
	private $lastCompleted=0;
	private $data=array();
	private $dateUpdated=null;
	
	public function __construct($check=false){
		$this->dbOb=Database::create();
		$cred=UserCredentials::load();
		$this->id=$cred->getId();
		$this->lastCompleted=0;
		
		$this->preventUpdate=false;
		$sql="SELECT * FROM u_module_loseit WHERE z_user_id='".$this->dbOb->escape_string($this->id)."' AND is_active = 1";
		$data=$this->dbOb->getRow($sql);
		$this->restore($data);
		if(!$data){
			if (!$check) {
				$sql="INSERT INTO u_module_loseit(z_user_id) VALUES('".$this->dbOb->escape_string($this->id)."')";
				$this->dbOb->insert($sql);			
				$im=new IncentivePointsModel();
				$im->addIncentivePointMA("ModuleLoseIT","start");
			}
			else {
				$this->lastCompleted = 0;
			}
		}else{
			$this->lastCompleted=$data['last_completed'];
		}		
	}	
	
	public function getLastCompleted($mode=false){
		if (($this->lastCompleted == 5) || ($this->lastCompleted <= 0)){
			return $this->lastCompleted;
		}

		if (($mode) || (IT_STUB)){									//override to get actual last completed (health coach)
			return $this->lastCompleted;
		}

		$sql = "SELECT week" . $this->lastCompleted . "_start FROM u_module_loseit WHERE z_user_id = " . $this->id;
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


	public function introComplete(){
		return  $this->lastCompleted>=0;
	}
	

	/**
	 * recordStart()
	 *	@param - week number
	 *	save the date of when this week was started
	 */
	public function recordStart($week) {
		$sql = "SELECT week" . $week . "_start FROM	u_module_loseit WHERE z_user_id = " . $this->id;
		$sd = $this->dbOb->getOne($sql);
		if ($sd == "0000-00-00") {
			$sql = "UPDATE u_module_loseit SET week" . $week . "_start = '" . date("Y-m-d") . "' WHERE z_user_id = " . $this->id;
			$this->dbOb->update($sql);
		}
	}



	public function get($index){
		//if index is set return the fields for that index.
		if(isset($this->data[$index]))
			return $this->data[$index];
		return null;//ignore non-existant fields.
	}
	
	public function validate($arr,$topic,$page){
		$err=array();
		$vc= new Validator();
		$result = array();
		$class = array();

		switch($topic."_".$page){
			case 'intro_3':
				
				foreach($arr as $key=>$value){
					if($key=='C4' && $value!=1) $arr['C5']='';
					if($key=='C9' && $value!=1) $arr['C10']='';
					if($key=='C5' || $key=='C10') continue;					
					if(isset($this->data['intro_data'][$key])){
						$this->data['intro_data'][$key]=$value==1?1:0;
					} 					
				}

				$this->data['intro_data']['C5']=$arr['C5'];
				$this->data['intro_data']['C10']=$arr['C10'];
				
				if($this->lastCompleted<0) $this->lastCompleted=0;
				$result['err'] = false;
				return $result;
				break;
			case 'week1_1':
				for($x=1;$x<7;$x++){
					try{
						$this->data['week1_data']['A'.$x]=$vc->exists('A'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				try{
					$this->data['week1_data']['A7']=$vc->exists('A7',$arr,"text",null,false,false);
				}catch(ValidationException $e){
    				$err[]=$e->createErrorObject();
    			}
    			$result['err'] = false;
    			return $result;
			  break;
			  case 'week1_2':
				for($x=1;$x<9;$x++){
					try{
						$this->data['week1_data']['B'.$x]=$vc->exists('B'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week1_5':
			  	$retAnsAll = false;
				for($x=1;$x<6;$x++){					
					try{
						$this->data['week1_data']['E'.$x]=$vc->exists('E'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,true);
					}catch(ValidationException $e){
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

				if(!$err){
					$correct=array(1,4,2,4,1);
					$wrong=0;
					for($x=0;$x<5;$x++){
						if($this->data['week1_data']['E'.($x+1)]!=$correct[$x]){
							$wrong++;
							$result['E'.($x+1)] = $this->getIncorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "incorrect";
						}
						else {
							$result['E'.($x+1)] = $this->getCorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "correct";
						}
					}

					$result['class'] = $class;
					if($wrong>0){
						$errOb=new stdClass();
						$errOb->display_name="";
    					$errOb->name="";
    					$errOb->type=5;
    					$errOb->message="You have an incorrect answer please try again";
    					$err[]=$errOb;
					}
				}
				if(!$err){
					if($this->lastCompleted<1) $this->lastCompleted=1;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}
				return $result;
			  break;
			  
			  case 'week2_2':
				for($x=1;$x<6;$x++){				
					try{
						$this->data['week2_data']['B'.$x]=$vc->exists('B'.$x,$arr,"text",null,false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week2_4':
				for($x=1;$x<7;$x++){				
					try{
						$this->data['week2_data']['D'.$x]=$vc->exists('D'.$x,$arr,"text",null,false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week2_5':		 	
			  	$retAnsAll = false;
			  	for($x=1;$x<6;$x++){
					try{
						$this->data['week2_data']['E'.$x]=$vc->exists('E'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,true);
					}catch(ValidationException $e){
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

				if(!$err){
					$correct=array(2,4,1,3,1);
					$wrong=0;
					for($x=0;$x<5;$x++){
						if($this->data['week2_data']['E'.($x+1)]!=$correct[$x]){
							$wrong++;
							$result['E'.($x+1)] = $this->getIncorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "incorrect";
						}
						else {
							$result['E'.($x+1)] = $this->getCorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "correct";
						}
					}

					$result['class'] = $class;
					if($wrong>0){
						$errOb=new stdClass();		
						$errOb->display_name="";
    					$errOb->name="";    	
    					$errOb->type=5;
    					$errOb->message="You have  incorrect answer(s) please try again";
    					$err[]=$errOb;
					}
				}
				if(!$err){					
					if($this->lastCompleted<2) $this->lastCompleted=2;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}
				return $result;
			  break;
			  case 'week3_3':
				for($x=1;$x<10;$x++){				
					try{
						$this->data['week3_data']['C'.$x]=$vc->exists('C'.$x,$arr,"text",null,false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week3_4':
			  	$retAnsAll = false;
			  	for($x=1;$x<6;$x++){
					try{
						$this->data['week3_data']['D'.$x]=$vc->exists('D'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,true);
					}catch(ValidationException $e){
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

				if(!$err){
					$correct=array(3,4,2,2,3);
					$wrong=0;
					for($x=0;$x<5;$x++){
						if($this->data['week3_data']['D'.($x+1)]!=$correct[$x]){
							$wrong++;
							$result['D'.($x+1)] = $this->getIncorrectText($topic, $page, 'D'.($x+1));
							$class['D'.($x+1)] = "incorrect"; 	
						}
						else {
							$result['D'.($x+1)] = $this->getCorrectText($topic, $page, 'D'.($x+1));
							$class['D'.($x+1)] = "correct";
						}
					}

					$result['class'] = $class;
					if($wrong>0){
						$errOb=new stdClass();		
						$errOb->display_name="";
    					$errOb->name="";    	
    					$errOb->type=5;
    					$errOb->message="You have incorrect answer(s) please try again";
    					$err[]=$errOb;
					}
				}
				if(!$err){
					if($this->lastCompleted<3) $this->lastCompleted=3;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}
				return $result;
			  break;
			  case 'week4_1':
				for($x=1;$x<4;$x++){				
					try{
						$this->data['week4_data']['A'.$x]=$vc->exists('A'.$x,$arr,"text",null,false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week4_3':
			  	$retAnsAll = false;
			  	for($x=1;$x<6;$x++){
					try{
						$this->data['week4_data']['C'.$x]=$vc->exists('C'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,true);
					}catch(ValidationException $e){
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

				if(!$err){
					$correct=array(2,1,1,3,4);
					$wrong=0;
					for($x=0;$x<5;$x++){
						if($this->data['week4_data']['C'.($x+1)]!=$correct[$x]){
							$wrong++;
							$result['C'.($x+1)] = $this->getIncorrectText($topic, $page, 'C'.($x+1));
							$class['C'.($x+1)] = "incorrect";
						}
						else {
							$result['C'.($x+1)] = $this->getCorrectText($topic, $page, 'C'.($x+1));
							$class['C'.($x+1)] = "correct";
						}
					}

					$result['class'] = $class;
					if($wrong>0){
						$errOb=new stdClass();		
						$errOb->display_name="";
    					$errOb->name="";    	
    					$errOb->type=5;
    					$errOb->message="You have incorrect answer(s) please try again";
    					$err[]=$errOb;
					}
				}
				if(!$err){					
					if($this->lastCompleted<4) $this->lastCompleted=4;
					$result['err'] = false;
				}
				else {
					$result['err'] = $err;
				}
				return $result;
			  break;
			  case 'week5_2':
				for($x=1;$x<11;$x++){
					if(isset($arr['B'.$x])){
						$this->data['week5_data']['B'.$x]=($arr['B'.$x]==1)?1:0;
					}				
				}
				for($x=11;$x<14;$x++){				
					try{
						$this->data['week5_data']['B'.$x]=$vc->exists('B'.$x,$arr,"text",null,false,false);
					}catch(ValidationException $e){
    					$err[]=$e->createErrorObject();
    				}
				}
				$result['err'] = false;
				return $result;
			  break;
			  case 'week5_5':
			  	$retAnsAll = false;
			  	for($x=1;$x<6;$x++){
					try{
						$this->data['week5_data']['E'.$x]=$vc->exists('E'.$x,$arr,"enum",array("values"=>array(1,2,3,4)),false,true);
					}catch(ValidationException $e){
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

				if(!$err){
					$correct=array(3,2,1,4,1);
					$wrong=0;
					for($x=0;$x<5;$x++){
						if($this->data['week5_data']['E'.($x+1)]!=$correct[$x]){
							$wrong++;
							$result['E'.($x+1)] = $this->getIncorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "incorrect";
						}
						else {
							$result['E'.($x+1)] = $this->getCorrectText($topic, $page, 'E'.($x+1));
							$class['E'.($x+1)] = "correct";
						}
					}

					$result['class'] = $class;
					if($wrong>0){
						$errOb=new stdClass();		
						$errOb->display_name="";
    					$errOb->name="";    	
    					$errOb->type=5;
    					$errOb->message="You have incorrect answer(s) please try again";
    					$err[]=$errOb;
					}
				}
				if(!$err){
					if($this->lastCompleted < 5) {
						$this->lastCompleted = 5;
						$this->data['date_entered'] = date('Y-m-d');
						$im=new IncentivePointsModel();
						$im->addIncentivePointMA("ModuleLoseIT","complete",$this->data['date_entered']);
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
		$sql="UPDATE u_module_loseit SET last_completed='".$this->dbOb->escape_string($this->lastCompleted)."',
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
			$im->addIncentivePointMA("ModuleLoseIT","start");
		}
		if($this->lastCompleted==5){
			$im->addIncentivePointMA("ModuleLoseIT","complete");
		}
	}
	
	public function restore($record){
		$this->data=array();
		if(isset($record['intro_data']) && $record['intro_data']){
			$this->data['intro_data']=unserialize($record['intro_data']);
		}else{
			$this->data['intro_data']=array(
				'C1'=>0,
				'C2'=>0,
				'C3'=>0,
				'C4'=>0,
				'C5'=>'',
				'C6'=>0,
				'C7'=>0,
				'C8'=>0,
				'C9'=>0,
				'C10'=>''
			);
		}
		if(isset($record['week1_data']) && $record['week1_data']){
			$this->data['week1_data']=unserialize($record['week1_data']);
		}else{
			$this->data['week1_data']=array(
				'A1'=>0,
				'A2'=>0,
				'A3'=>0,
				'A4'=>0,
				'A5'=>0,
				'A6'=>0,
				'A7'=>'',
				'B1'=>0,
				'B2'=>0,
				'B3'=>0,
				'B4'=>0,
				'B5'=>0,
				'B6'=>0,
				'B7'=>0,
				'B8'=>0,
				'E1'=>0,
				'E2'=>0,
				'E3'=>0,
				'E4'=>0,
				'E5'=>0
			);
		}
		
		if(isset($record['week2_data']) && $record['week2_data']){
			$this->data['week2_data']=unserialize($record['week2_data']);
		}else{
			$this->data['week2_data']=array(
				'B1'=>0,
				'B2'=>0,
				'B3'=>0,
				'B4'=>0,
				'B5'=>0,
				'D1'=>'',
				'D2'=>'',
				'D3'=>'',
				'D4'=>'',
				'D5'=>'',
				'D6'=>'',
				'E1'=>0,
				'E2'=>0,
				'E3'=>0,
				'E4'=>0,
				'E5'=>0			
			);
		}
		
		if(isset($record['week3_data']) && $record['week3_data']){
			$this->data['week3_data']=unserialize($record['week3_data']);
		}else{
			$this->data['week3_data']=array(
				'C1'=>'',
				'C2'=>'',
				'C3'=>'',
				'C4'=>'',
				'C5'=>'',
				'C6'=>'',
				'C7'=>'',
				'C8'=>'',
				'C9'=>'',
				'D1'=>'',
				'D2'=>'',
				'D3'=>'',
				'D4'=>'',
				'D5'=>'',
			);
		}
		
		if(isset($record['week4_data']) && $record['week4_data']){
			$this->data['week4_data']=unserialize($record['week4_data']);
		}else{
			$this->data['week4_data']=array(
				'A1'=>'',
				'A2'=>'',
				'A3'=>'',
				'C1'=>0,
				'C2'=>0,
				'C3'=>0,
				'C4'=>0,
				'C5'=>0,
			);
		}
		
		if(isset($record['week5_data']) && $record['week5_data']){
			$this->data['week5_data']=unserialize($record['week5_data']);
		}else{
			$this->data['week5_data']=array(
				'B1'=>0,
				'B2'=>0,
				'B3'=>0,
				'B4'=>0,
				'B5'=>0,
				'B6'=>0,
				'B7'=>0,
				'B8'=>0,
				'B9'=>0,
				'B10'=>0,
				'B11'=>'',
				'B12'=>'',
				'B13'=>'',
				'E1'=>0,
				'E2'=>0,
				'E3'=>0,
				'E4'=>0,
				'E5'=>0,
			);
		}
		if (isset($record['listen_data']) && $record['week5_data']) {
			$this->data['listen_data'] = unserialize($record['listen_data']);
			if ($record['last_completed'] == 5)
				$this->preventUpdate = true;
			$this->dateUpdated = $record['date_updated'];
		}
		else {
			$this->data['listen_data']=array();
		}
		$this->data['last_completed'] = $record['last_completed'];
		$this->dateUpdated = $record['date_updated'];
		$this->data['date_updated'] = $record['date_updated'];
		$this->lastCompleted = $record['last_completed'];
	}


	function addDate($dateStamp){
		$this->data['listen_data'][$dateStamp]=1;
		$sql="UPDATE u_module_loseit 
			SET listen_data='".$this->dbOb->escape_string(serialize($this->data['listen_data']))."'";
		if(!$this->preventUpdate)
			$sql.=", date_updated=NOW()"; //dont reset the date once it has been completed.
		$sql.=" WHERE z_user_id='".$this->dbOb->escape_string($this->id)."'";
		$this->dbOb->update($sql);
	}

	function getDateCompleted(){
		return $this->dateUpdated;
	}

	private function getCorrectText($week, $page, $question) {
		$sql = "SELECT correctText FROM p_modules_exams " .
				"WHERE ITModuleName = 'LoseIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

	private function getIncorrectText($week, $page, $question) {
		$sql = "SELECT incorrectText FROM p_modules_exams " .
				"WHERE ITModuleName = 'LoseIT' " .
				"AND week = '" . $week . "' " .
				"AND page = '" . $page . "' " .
				"AND question = '" . $question . "'";
		return $this->dbOb->getOne($sql);
	}

}