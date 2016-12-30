<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  

  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

class Reports extends AdminPageBase{
	public function getBaseTableName(){return "p_site_pages";}	
	
	public function Index($param){

		$full=true;
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")){//if not a super admin ensure the user is the admin for the requested company
			$full=false;
  			$sql="SELECT id FROM p_company WHERE admin_user=".$this->cred->getId();
 			$companyId=$this->dbOb->getOne($sql);
		}

  		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/Reports/index.tpt");

  		if ($full) {
  			$sql="SELECT company_name as display, id as value FROM p_company WHERE is_active=1";
			$companies=$this->dbOb->query($sql);
			$template->addVar("companies",$companies);
  		}
  		else {
  			$sql="SELECT company_name FROM p_company ".
  					"WHERE id=".$this->dbOb->escape_string($companyId).
  					" AND is_active=1";
  			$company=$this->dbOb->getOne($sql);
  			$template->addVar("company", $company);
  			$template->addVar("company_id", $companyId);
  		}

		$template->addVar("full",$full);

	}
	
	public function Generate($param){
		$report=$_POST['report'];
		$cid = $_POST['company_id'];
		switch(strtolower($report)){
			case 'companyhealthassessment':
				return $this->CompanyHealthAssessment();
				break;
			case 'portalusage':
				return $this->PortalUsage($cid);
				break;
		}
	}
	
	private function CompanyHealthAssessment(){
		$questionTotals=array();
				
		$sql="SELECT * FROM u_mod_ifocus";
		$setup=$this->dbOb->getRow($sql);		

		$keys = array('q4','q5_0','q5_1','q5_2','q5_3','q5_4','q5_5','q5_6','q5_7','q5_8','q5_9','q5_10','q5_11','q5_12','q5_13','q5_14','q5_15',
		              'q7','q9','q10_1','q10_2','q10_3','q10_4','q10_5','q10_6','q8','q12','q13','q15','q16','q18','q19','q21','q22',
		              'q23_1','q23_2','q23_3','q23_4','q23_5','q23_6','q23_7','q23_8','qn24','qn25','q24','q25','q26','q27','q28','q29',
		              'q30','q31','q32','q33','q38_1','q38_2','q38_3','q38_4','q38_5','q38_6','q38_7','q38_8','q39','q40','q41','q42','q42a',
		              'q43','q44','q45','q46','q47','q48_1','q48_2','q48_3','q48_4','q48_5','q48_6','q48_7');

		$questions = array('q4' => 'Overall health status',
		                   'q5_0' => 'Anxiety',
		                   'q5_1' => 'Stroke',
		                   'q5_2' => 'Arthritis',
		                   'q5_3' => 'Back Pain',
		                   'q5_4' => 'Cancer',
		                   'q5_5' => 'Chronic Pain',
		                   'q5_6' => 'Congestive Heart Failure',
		                   'q5_7' => 'Depression',
		                   'q5_8' => 'High Cholesterol',
		                   'q5_9' => 'Diabetes',
		                   'q5_10' => 'Heartburn',
		                   'q5_11' => 'Heart disease',
		                   'q5_12' => 'High blood pressure',
		                   'q5_13' => 'Migraine Headaches',
		                   'q5_14' => 'Osteoporosis/Osteopeania',
		                   'q5_15' => 'Colon polyps',
		                   'q7' => 'Do you understand your health benefits?',
		                   'q9' => 'Do you know how to care for a minor injury using basci first aid?',
		                   'q10_1' => 'Breast Cancer',
		                   'q10_2' => 'Colorectal Cancer',
		                   'q10_3' => 'Diabetes',
		                   'q10_4' => 'Heart Disease',
		                   'q10_5' => 'High Blood Pressure',
		                   'q10_6' => 'High Cholesterol',
		                   'q8' => 'Do you obtain a yearly physical examination?',
		                   'q12' => 'Have you received a seasonal flu vaccine in the last 12 months?',
		                   'q13' => 'If you are between the age 50-75, do you obtain regular colorectal screenings?',
		                   'q15' => 'Have ever had an abnormal pap smear within the last 3 years?',
		                   'q16' => 'If you are over the age of 40, have you ever had a mammogram?',
		                   'q18' => 'Have you ever discussed the need for a prostate exam with your primary care physician?',
		                   'q19' => 'If yes, have you ever had a Prostate Specific Antigen (PSA) test?',
		                   'q21' => 'Do you eat breakfast daily?',
		                   'q22' => 'How often do you snack on foods such as chips, pastry, cookies, candy, etc?',
		                   'q23_1' => 'Fruit',
		                   'q23_2' => 'Vegetables',
		                   'q23_3' => 'Protein',
		                   'q23_4' => 'Dairy',
		                   'q23_5' => 'Processed foods',
		                   'q23_6' => 'Fried foods',
		                   'q23_7' => 'Sweets/Desserts',
		                   'q23_8' => 'Breads/Grains',
		                   'qn24' => 'How often do you add salt to your food, or eat salty foods?',
		                   'qn25' => 'How often do you eat high fat foods?',
		                   'q24' => 'How many days per week do you participate in a tleast 20-30 minutes of moderate physical activity',
		                   'q25' => 'How many days per week do you engage in strength training activities?',
		                   'q26' => 'How many days per week do you engage in stretching and flexibility exercises?',
		                   'q27' => 'How well do you feel you cope with day to day stressors?',
		                   'q28' => 'How much stress do you feel at your job?',
		                   'q29' => 'How much stress do you feel from your family realted activities and/or relationships?',
		                   'q30' => 'How often does stress from work and/or your family interfere with your daily job activities?',
		                   'q31' => 'How often do you get at least seven to eight hours of sleep per night?',
		                   'q32' => 'How often to you participate in relaxing activities?',
		                   'q33' => 'How often do you use medication to help you relax or sleep?',
		                   'q38_1' => 'Little interest or pleasure in activities that I normally enjoy',
		                   'q38_2' => 'Feeling down, depressed or hopeless',
		                   'q38_3' => 'Trouble falling or staying asleep, or sleeping too much',
		                   'q38_4' => 'Feeling tired or having too little energy for daily activities',
		                   'q38_5' => 'Poor appetite or overeating',
		                   'q38_6' => 'Feeling bad about yourself, or that you have lete yourself or your family down',
		                   'q38_7' => 'Trouble concentrating on activities, such as reading or watching TV',
		                   'q38_8' => 'Thinking that you would be better off dead or considering hurting yourself in some way',
		                   'q39' => 'How many alcholic drinks do you consume per week on averagae?',
		                   'q40' => 'Have you had more than 5 drinks in one sitting in the past four months?',
		                   'q41' => 'How often do you wear a seat belt while driving?',
		                   'q42' => 'Do you use a cell phone while driving?',
		                   'q42a' => 'Do you text message while driving?',
		                   'q43' => 'Do you drive while impaired after consuming alcohol or medications?',
		                   'q44' => 'Do you smoke cigarettes or use tobacco products?',
		                   'q45' => 'If you smoke cigarettes, how many do you smoke per day?',
		                   'q46' => 'If you smoke, how many cigars or pipes do you smoke per day?',
		                   'q47' => 'If you use smokeless tobacco, how many times per day do you use smokeless tobacco?',
		                   'q48_1' => 'Diet/Nutrition',
		                   'q48_2' => 'Physical activity',
		                   'q48_3' => 'Tobacco use',
		                   'q48_4' => 'Weight management',
		                   'q48_5' => 'Stress management',
		                   'q48_6' => 'Alcohol use',
		                   'q48_7' => 'Preventive care'
		);

		$answers = array ('q4' => array('Excellent', 'Very good', 'Good', 'Fair', 'Poor'),
		                  'q5_0' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_1' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_2' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_3' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_4' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_5' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_6' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_7' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_8' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_9' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_10' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_11' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_12' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_13' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_14' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q5_15' => array('Never', 'In the Past', 'Under Physician Care', 'Taking Prescribed Medication', 'Have condition but not in treatment'),
		                  'q7' => array('Yes', 'No'),
		                  'q9' => array('Yes', 'No'),
		                  'q10_1' => array('Yes', 'No', 'Not sure'),
		                  'q10_2' => array('Yes', 'No', 'Not sure'),
		                  'q10_3' => array('Yes', 'No', 'Not sure'),
		                  'q10_4' => array('Yes', 'No', 'Not sure'),
		                  'q10_5' => array('Yes', 'No', 'Not sure'),
		                  'q10_6' => array('Yes', 'No', 'Not sure'),
		                  'q8' => array('Yes', 'No'),
		                  'q12' => array('Yes', 'No'),
		                  'q13' => array('Yes', 'No', 'Does not apply to me'),
		                  'q15' => array('Yes', 'No'),
		                  'q16' => array('Yes', 'No', 'Does not apply to me'),
		                  'q18' => array('Yes', 'No', 'Does not apply to me'),
		                  'q19' => array('Yes', 'No'),
		                  'q21' => array('Yes', 'No', 'Sometimes'),
		                  'q22' => array('Often', 'Sometimes', 'Seldom'),
		                  'q23_1' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_2' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_3' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_4' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_5' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_6' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_7' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'q23_8' => array('None', '1-4 per week', '5-7 per week', '2 per day', '3+ per day'),
		                  'qn24' => array('Every meal', 'Most meals', 'Some meals', 'Seldom', 'Never'),
		                  'qn25' => array('Every meal', 'Most meals', 'Some meals', 'Seldom', 'Never'),
		                  'q24' => array('None', '1-2 days/week', '3-4 days/week', '5-6 days/week', '7 days/week'),
		                  'q25' => array('None', '1-2 days/week', '3-4 days/week', '5-6 days/week', '7 days/week'),
		                  'q26' => array('None', '1-2 days/week', '3-4 days/week', '5-6 days/week', '7 days/week'),
		                  'q27' => array('Unable to cope', 'Able to cope sometimes', 'Able to cope often', 'Cope very well'),
		                  'q28' => array('None', 'A little', 'Moderate', 'A lot'),
		                  'q29' => array('None', 'A little', 'Moderate', 'A lot'),
		                  'q30' => array('None', 'A little', 'A lot', 'Every day'),
		                  'q31' => array('None', 'Less than 3 nights', 'More than 3 nights', 'Most nights'),
		                  'q32' => array('Never', 'Seldom', 'Often', 'Always'),
		                  'q33' => array('Never', 'Seldom', 'Often', 'Always'),
		                  'q38_1' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_2' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_3' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_4' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_5' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_6' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_7' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q38_8' => array('Not at all', 'Several days', 'More than half the days', 'Nearly every day'),
		                  'q39' => array('None', '1-7 per week', '8-14 per week', '15-20 per week', '21 or more per week'),
		                  'q40' => array('Yes', 'No'),
		                  'q41' => array('Always', 'Often', 'Sometimes', 'Never'),
		                  'q42' => array('Yes, but it is hands-free', 'Yes but it is not hands-free', 'No'),
		                  'q42a' => array('Never', 'Seldom', 'Often', 'Always'),
		                  'q43' => array('Always', 'Often', 'Sometimes', 'Never'),
		                  'q44' => array('Yes', 'I quit 2 or more years ago', 'I quit 2 or less years ago', 'Never smoked'),
		                  'q45' => array('0 to 1', '2 to 3', '4 to 5', '6 to 7', '1 pk or more'),
		                  'q46' => array('0 to 1', '2 to 3', '4 to 5', '6 to 7'),
		                  'q47' => array('0 to 1', '2 to 3', '4 to 5', '6 to 7'),
		                  'q48_1' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_2' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_3' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_4' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_5' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_6' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		                  'q48_7' => array('I am not planning on making any changes', 'I plan to change in the next 6 months', 'I plan to change in the next 30 days', 'I have recently begun changes', 'I currently engage in healthy habits in this area'),
		);


		$numChoices=array(
			'q4' => 5,
			'q5_0' => 5, 'q5_1' => 5, 'q5_2' => 5, 'q5_3' => 5, 'q5_4' => 5, 'q5_5' => 5, 'q5_6' => 5, 'q5_7' => 5, 'q5_8' => 5,
			'q5_9' => 5, 'q5_10' => 5, 'q5_11' => 5, 'q5_12' => 5, 'q5_13' => 5, 'q5_14' => 5, 'q5_15' => 5,
			'q7' => 2, 'q9' => 2,
			'q10_1' => 3, 'q10_2' => 3, 'q10_3' => 3, 'q10_4' => 3, 'q10_5' => 3, 'q10_6' => 3,
			'q8' => 2, 'q12' => 2, 'q13' => 3, 'q15' => 2, 'q16' => 3, 'q18' => 3, 'q19' => 2,
			'q21' => 3,
			'q22' => 3,
			'q23_1' => 5, 'q23_2' => 5, 'q23_3' => 5, 'q23_4' => 5, 'q23_5' => 5, 'q23_6' => 5, 'q23_7' => 5, 'q23_8' => 5,
			'qn24' => 5, 'qn25' => 5,
			'q24' => 5,
			'q25' => 5,
			'q26' => 5,
			'q27' => 4,
			'q28' => 4,
			'q29' => 4,
			'q30' => 4,
			'q31' => 4,
			'q32' => 4,
			'q33' => 4,
			'q38_1' => 4, 'q38_2' => 4, 'q38_3' => 4, 'q38_4' => 4, 'q38_5' => 4, 'q38_6' => 4, 'q38_7' => 4, 'q38_8' => 4,
			'q39' => 5,
			'q40' => 2,
			'q41' => 4,
			'q42' => 3,
			'q42a' => 4,
			'q43' => 2,
			'q44' => 4,
			'q45' => 5,
			'q46' => 4,		
			'q47' => 5,
			'q48_1' => 5, 'q48_2' => 5, 'q48_3' => 5, 'q48_4' => 5, 'q48_5' => 5, 'q48_6' => 5, 'q48_7' => 5
		);

		foreach($setup as $field => $value){
			if ($field[0] != 'q') continue;
			$data = array();
			if (isset($numChoices[$field])) {
				for ($x = 1; $x <= $numChoices[$field]; $x++) $data[$x] = 0;
				$questionTotals[$field] = $data;
			}
		}
		
		
		$companyId = $_POST['company_id'];		
		$haUserCount = 0;

		$isGina = $this->dbOb->getOne("SELECT is_gina FROM p_company where id='".$this->dbOb->escape_string($companyId)."'");

		//get all the active users for a company
		$sql = "SELECT u.z_user_id FROM u_profile AS u " .
			  "JOIN z_users AS z ON u.z_user_id = z.id " .
			  "WHERE u.company_id = '" . $this->dbOb->escape_string($companyId) . "' " .
			  "AND u.status = 'active' " .
			  "AND z.is_active = 1";
		$totalUsers = $this->dbOb->query($sql);

		foreach ($totalUsers as $user) {
			$id = $this->dbOb->escape_string($user['z_user_id']);
			//get the latest ha from the users
			$sql = "SELECT * FROM u_mod_ifocus WHERE z_user_id = '" . $id . "' AND date_completed <> '0000-00-00' ORDER BY date_added DESC LIMIT 0,1";
			$ha = $this->dbOb->getRow($sql);
			if ($ha) {
				foreach ($ha as $field => $value) {
					if ($field[0] != 'q') continue;
					if (!isset($questionTotals[$field])) continue;
					$value = intval($value);
					if ($value >= $questionTotals[$field] || $value == 0) continue;
					$questionTotals[$field][$value]++;
				}
			}
		}

/*
		$totalUserPerTopic = array();
		$totalQAnswered = array();		
		foreach($questionTotals as $key => $value){
			$totalQAnswered[$key] = 0;
			foreach($value as $ans => $count){
				$totalQAnswered[$key] += $count;
			}
		}


		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q44','q45','q47'), $questionTotals, $totalQAnswered);		
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q41'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q4'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q10_2','q10_1','q10_3','q10_5','q10_6','q10_4'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q5_1','q5_3','q5_9','q5_2','q5_4','q5_8','q5_14','q5_5','q5_12','q5_7','q5_11','q5_15'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q39'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q43'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q33'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q24'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q25'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q26'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q27'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q31'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q48_2'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q48_1'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q48_3'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q48_4'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q48_5'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q21'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q22'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q23_8'), $questionTotals, $totalQAnswered);
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q23_1'), $questionTotals, $totalQAnswered);		
		$totalUserPerTopic[$count++] = $this->addSubtotals(array('q23_2'), $questionTotals, $totalQAnswered);

		$q45_p = $q45_t = 0;
		for ($x = 1; $x < 5; $x++) {
			$q45_t += $totalUserPerTopic[1]['questions']['q45'][$x][0];
			$q45_p += $totalUserPerTopic[1]['questions']['q45'][$x][1];
		}
		$totalUserPerTopic[1]['questions']['q45'] = array(array($q45_t,$q45_p),$totalUserPerTopic[1]['questions']['q45'][5]);

		$q47_p = $q47_t = 0;
		for ($x = 2; $x < 5; $x++) {
			$q47_t += $totalUserPerTopic[1]['questions']['q47'][$x][0];
			$q47_p += $totalUserPerTopic[1]['questions']['q47'][$x][1];
		}
		$totalUserPerTopic[1]['questions']['q47'] = array($totalUserPerTopic[1]['questions']['q47'][1],array($q47_t,$q47_p));

//&&& DEBUG &&&
//		print_r($totalUserPerTopic);
*/

		$template = TemplateParser::enqueue(TEMPLATE_DIR."admin/Reports/CompanyHealthAssessment.tpt");
		$template->addVar("keys", $keys);
		$template->addVar("data",$questionTotals);
		$template->addVar("questions", $questions);
		$template->addVar("answers", $answers);
		if ($isGina) {
			$template->addVar("gina", 1);
		}
		else {
			$template->addVar("gina", 0);
		}
		return $template;
	}
	
	private function addSubTotals($indexes,$answers,$totals){
	  $total=0;
	  $questions=array();
	  foreach($indexes as $idx){
	  	$total+=$totals[$idx];
	  }
	  foreach($indexes as $idx){
	  	
	  	foreach($answers[$idx] as $idx2=>$value){
	  		if($total>0)	  	
	    		$questions[$idx][$idx2]=array($value,round(($value/$total)*100));
	    	else 
	    		$questions[$idx][$idx2]=array($value,0);
	  	}
	  }
	  return array("total"=>$total,"questions"=>$questions);
	}




	private function PortalUsage($cid) {
		$sql = "SELECT company_name FROM p_company WHERE id = " . $cid;
		$company = $this->dbOb->getOne($sql);

		$monthly = date('Y-m-d', strtotime(date('Y-m-d')) - (30 * 24 * 60 * 60));
		$sql = "SELECT count(*) FROM z_users AS z " .
					"JOIN u_profile AS u ON z.id = u.z_user_id " .
					"WHERE u.company_id = " . $cid . " " .
					"AND z.last_login > '" . $monthly . "' " .
					"AND z.is_active = 1";
		$nlogins = $this->dbOb->getOne($sql);

		$sql = "SELECT count(*) FROM z_users AS z " .
					"JOIN u_profile AS u ON z.id = u.z_user_id " .
					"WHERE u.company_id = " . $cid . " " .
					"AND z.is_active = 1";
		$totalusers = $this->dbOb->getOne($sql);

		if ($totalusers > 0) {
			$sql = "SELECT count(*) FROM z_users AS z " .
						"JOIN u_profile AS u ON z.id = u.z_user_id " .
						"WHERE u.company_id = " . $cid . " " .
						"AND z.is_active = 1 " .
						"AND u.status = 'active'";
			$activeusers = $this->dbOb->getOne($sql);
			$pc_active = sprintf("%.1f", (100.0 * $activeusers/$totalusers));
		}
		else {
			$activeusers = 0;
			$pc_active = 0;
		}

		$sql = "SELECT count(DISTINCT z.id) FROM z_users AS z " .
					"JOIN u_profile AS u ON z.id = u.z_user_id " .
					"JOIN u_mod_ifocus AS m ON z.id = m.z_user_id " .
					"WHERE u.company_id = " . $cid . " " .
					"AND z.is_active = 1 " .
					"AND m.is_active = 1 " .
					"AND m.date_completed <> '0000-00-00'";
		$ifcomplete = $this->dbOb->getOne($sql);
		if ($activeusers > 0) {
			$ifpercent = 100.0 * $ifcomplete / $activeusers;
			$ifpercent = sprintf("%.1f", (100.0 * $ifcomplete / $activeusers));
		}
		else {
			$ifpercent = 0;
		}

		$sql = "SELECT class_name, name FROM p_modules AS pm " .
					"JOIN p_company_modules AS cm ON pm.id = cm.p_module_id " .
					"WHERE cm.p_company_id = " . $cid . " " .
					"AND pm.is_active = 1 " .
					"AND pm.type = 'IT'";
		$modList = $this->dbOb->query($sql);

		$myFocusActive = 0;
		$myFocusComplete = 0;
		if ($modList) {
			foreach ($modList as $mod) {
				switch ($mod['class_name']) {
					case "ModuleBreakIT":
						$table = "u_module_breakit";
						break;
					case "ModuleLoseIT":
						$table = "u_module_loseit";
						break;
					case "ModuleReduceIT":
						$table = "u_module_reduceit";
						break;
					case "ModuleMoveIT":
						$table = "u_module_moveit";
						break;
					case "ModuleBreathIT":
						$table = "u_module_breathit";
						break;
					case "ModuleLiftIT":
						$table = "u_module_liftit";
						break;
				}

				$sql = "SELECT count(DISTINCT z.id) FROM z_users AS z " .
						"JOIN u_profile AS u ON z.id = u.z_user_id " .
						"JOIN " . $table . " AS m ON z.id = m.z_user_id " .
						"WHERE u.company_id = " . $cid . " " .
						"AND m.last_completed < 5 ";
				$mFa = $this->dbOb->getOne($sql);
				$myFocusActive += $mFa;

				$sql = "SELECT count(DISTINCT z.id) FROM z_users AS z " .
						"JOIN u_profile AS u ON z.id = u.z_user_id " .
						"JOIN " . $table . " AS m ON z.id = m.z_user_id " .
						"WHERE u.company_id = " . $cid . " " .
						"AND m.last_completed = 5 ";
				$mFc = $this->dbOb->getOne($sql);
				$myFocusComplete += $mFc;
			}
		}
			
		$template = TemplateParser::enqueue(TEMPLATE_DIR."admin/Reports/PortalUsage.tpt");
		$template->addVar('company', $company);
		$template->addVar('totalUsers', $totalusers);
		$template->addVar('numLogins', $nlogins);
		$template->addVar('activeUsers', $activeusers);
		$template->addVar('percentActive', $pc_active);
		$template->addVar('ifComplete', $ifcomplete);
		$template->addVar('ifPercent', $ifpercent);
		$template->addVar('myFocusActive', $myFocusActive);
		$template->addVar('myFocusComplete', $myFocusComplete);
		return $template;
	}
}