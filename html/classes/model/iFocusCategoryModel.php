<?php
	require_once (LIB_ROOT."classes/common/Database.class.php");


// Experimental class 9/28/2010


class iFocusCategoryModel {
	private $dbOb = null;
	private $questions = array();
	private $nques;
	private $is_gina;
	private $is_wlq;
	private $gender;
	private $name;

	public function __construct ($category, $cid, $uid) {

		$this->dbOb = Database::create();

		$this->name = $this->dbOb->escape_string($category);

		$sql = "SELECT is_gina, is_wlq FROM p_company "
						"WHERE id = " . $this->dbOb->escape_string($cid) . " " .
						"AND is_active = 1";
		$company = $this->dbOb->getRow($sql);
		$this->is_gina = $company['is_gina'];
		$this->is_wlq = $company['is_wlq'];

		$sql = "SELECT gender FROM u_profile "
						"WHERE z_user_id = " . $this->dbOb->escape_string($uid);
		$this->gender = $this->dbOb->getOne($sql);

		// Retrieve questions for this category
		$sql = "SELECT id, question_text FROM p_ifocus_questions " .
						"WHERE category = '" . $this->name . "' " .
						"AND (gender = '" . $this->gender . "' " .
						"OR gender = 'B') " .
						"ORDER BY order";
		$ques = $this->dbOb->query($sql);

		$this->nques = 0;
		foreach($ques as $q) {
			$question = new iFocusQuestion();
			$question->setId = $q['id'];
			$question->setText = $q['question_text'];
			array_push($this->questions, $question);
			$this->nques += 1;
		}
		
	}

	public function getQuestions() {
		return $this->questions;
	}

	public function getNumberOfQuestions() {
		return $this->nques;
	}


}