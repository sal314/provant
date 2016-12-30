<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/IFocusModel.php");
require_once (ROOT_DIR."classes/model/UserProfileModel.php");

class IFocus  extends PageBase{

	private $iFocusID = 0;

	public function __construct(){
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
		
		$this->myId=$this->cred->getId();
		
		if($this->cred->has("LOGIN_HEALTH_COACH")){		
				
			if(isset($_SESSION["MASK_USER"])){
				$this->myId=$_SESSION["MASK_USER"];
			}else{
				throw new Exception("Illegal access: NO User ID Specified");
			}
		}
		
	}
	
	/**
	 * Initial entry - figure out what to show based on where
	 * the user is/left off.
	 */
	public function Index($param){	

		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/iFocus/landing.tpt");
		$if = new IFocusModel($this->myId,false);
		$latest = $if->isCompleted();
  	$status = $if->getProgress();

  	$c = $if->getCompleted();
   	$template->addVar("completed",$c);

		$ok2start = ($status == 0);
		if ($latest) {
			$upm = new UserProfileModel(0);
			$freq = $upm->getIFocusFreq();            //Get number of months between assessments
			$last_complete = $c[0]['display'];        //Get date of last completed assessment
			$t0 = strtotime($last_complete);          //Convert last to a timestamp
//			$t1 = strtotime("July 1, 1970");        //Six months after the epoch
//			$t1 = strtotime("January 2,1970");      //for testing - allow start every day!
			$t1 = $freq * 30.42 * 24 * 60 * 60;       //Calc number of seconds (30.42 days / month - avg)
			$t2 = $t0 + $t1;                          //Time when user can start a new one
			if ($t2 < time()) $ok2start = true;
		}

		$template->addVar("start", $ok2start);
		$template->addVar("continue", ($status == 1));

  	$topic = "";
  	if ($ok2start) {
  		$topic = "/IFocus/topic/demographics/start";
  	}
  	if (!$ok2start && !$latest) {
  		$topic=$if->getLastCompletedTopic();
  		$nl = $if->getNextAndLast($topic);
  		$topic = "/IFocus/topic/".$nl[1];
  	}
		$template->addVar("topic", $topic);

  	return $template;
	}

	/**
	 * Show the HA page for a specific topic
	 * @param unknown_type $param
	 */
	public function topic($param){
		$page=$param[0];
		$start = false;
		if (isset($param[1])) {
			if ($param[1] == "start") {
				$start = true;
			}
		}

		$hs=new IFocusModel($this->myId,$start);
		if($page){
			if(!$hs->isValidCat($page)) throw new Exception("Invalid Page Request.");
		}else{
			header("Location: /IFocus/Topic/demographics");
			exit();
		}

		$age = $hs->getUserAge();

		$answers = $hs->get($page);
		if ($page == 'preventative_health') {
			$PHdata = $this->getPreventativeData($answers['q11'], $answers['q14'], $answers['q17'], $answers['q20']);
			$yropt = $PHdata['yropt'];
			$mopt = $PHdata['monopt'];
			$answers['q11_year'] = $PHdata['q11_year'];
			$answers['q11_month'] = $PHdata['q11_month'];
			$answers['q14_year'] = $PHdata['q14_year'];
			$answers['q14_month'] = $PHdata['q14_month'];
			$answers['q17_year'] = $PHdata['q17_year'];
			$answers['q17_month'] = $PHdata['q17_month'];
			$answers['q20_year'] = $PHdata['q20_year'];
			$answers['q20_month'] = $PHdata['q20_month'];
		}

		if ($page == 'demographics') {
			$answers['dob_disp'] = date('M j, Y', strtotime($answers['dob']));
			$other = $hs->getOtherLanguages();
			array_unshift($other, array('value' => "0", 'display' => "- Specify -"));
		}

		if ($page == 'biometric_data') {
			$ratio = 0;
		 	if (isset($answers['hdl']) && $answers['hdl'] > 0) {
		 		$ratio = $answers['cholesterol'] / $answers['hdl'];
	 		}
	 		$answers['ratio'] = sprintf("%.1f", $ratio);
		}

		$first_question = $hs->getFirstQuestionNo($page);

	 	$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/iFocus/".$page.".tpt");
		$template->addVar("age", $age);
	 	$template->addVar("saved",isset($_GET['success']));
	 	$template->addVar("category",$page);
	 	$template->addVar("first_question", $first_question);
	 	$template->addVar("answers",$answers);
		if ($page == 'preventative_health') {
			$template->addVar("yropt", $yropt);
			$template->addVar("monopt", $mopt);
		}
		else if ($page == 'demographics') {
			$template->addVar("other_lang", $other);
		}
	 	$template->addVar("demographics",$hs->getDemographics());
	 	$template->addVar("next_last",$hs->getNextAndLast($page));
	 	$template->addVar("is_completed",$hs->isCompleted());
	 	
	 	$template->addVar("current_page",$hs->getCurrentPage($page));	 		 	
	 	$template->addVar("total_pages",$hs->getTotalPages());
	 	
	 	$bd=$hs->get("biometric_data");

	 	$template->addVar("height_in",$bd['height']%12);
	 	$template->addVar("height_ft",floor($bd['height']/12));
	 	return $template;	 	
	}
	
	/**
	 * Update the responces for questions in a given health assessment
	 * @param unknown_type $param
	 */
	public function Update($param){	
		$page=$param[0];
		$hs=new IFocusModel($this->myId,false);
		if(!$page || !$hs->isValidCat($page)) throw new Exception("Invalid Page Request.");

		$next=$hs->getNextAndLast($page);

		$res=$hs->set($page,$_POST);
		if($res===false){
			if(isset($_POST['savestay'])){
				header("Location: /IFocus/topic/".$page."?success=1");
			}
			else if(!$next[1] || $next[1]==""){
				header("Location: /IFocus/Total");				
			}else{
				header("Location: /IFocus/topic/".$next[1]);
			}
			exit();
		}			
		if ($page == "preventative_health") {
			$PHdata = $this->getPreventativeData("1970-01-01", "1970-01-01", "1970-01-01", "1970-01-01");
			$yropt = $PHdata['yropt'];
			$mopt = $PHdata['monopt'];
		}

		if ($page == 'demographics') {
//			$_POST['dob_disp'] = date('M j, Y', strtotime($_POST['dob']));
			$other = $hs->getOtherLanguages();
			array_unshift($other, array('value' => "0", 'display' => "- Specify -"));
		}

		$first_question = $hs->getFirstQuestionNo($page);

	 	$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/iFocus/".$page.".tpt");
	 	$template->addVar("category",$page);
	 	$template->addVar("next_last",$hs->getNextAndLast($next));
	 	$template->addVar("answers",$_POST);
	 	if ($page == "preventative_health") {
	 		$template->addVar("yropt", $yropt);
	 		$template->addVar("monopt", $mopt);
	 	}
	 	else if ($page == "demographics") {
	 		$template->addVar("other_lang", $other);
	 	}
	 	$template->addVar("demographics",$hs->getDemographics());		
	 	$template->addVar("errors",$res);
		$template->addVar("first_question", $first_question);
	 	$template->addVar("next_last",$next);

	 	$template->addVar("current_page",$hs->getCurrentPage($page));	 		 	
	 	$template->addVar("total_pages",$hs->getTotalPages());
	 	
	 	$bd=$hs->get("biometric_data");	 	
	 	$template->addVar("height_in",$bd['height']%12);
	 	$template->addVar("height_ft",floor($bd['height']/12));
	 	
	 	return $template;
	}

	/**
	 * Create dropdown option lists for month and year used by
	 * preventative health.  Pulls out the month and year for all
	 * date questions and returns them
	 */
	private function getPreventativeData($q11, $q14, $q17, $q20) {
		$retArray = array();

		$months = array("", "January", "February", "March", "April", "May", "June",
		                    "July", "August", "September", "October", "November", "December");

		list($year, $month, $day) = explode("-", $q11);
		$retArray['q11_year'] = $year;
		$retArray['q11_month'] = $month;

		$yrstart = date('Y');
		$yrend = $yrstart - 11;
		$yropt = array();
		array_push($yropt, array('value' => 0, 'display' => "-Year-"));
		for ($i = $yrstart; $i > $yrend; $i--) {
			array_push($yropt, array('value' => $i, 'display' => $i));
		}
		$retArray['yropt'] = $yropt;

		$mopt = array();
		array_push($mopt, array('value' => 0, 'display' => "-Month-"));
		for ($i = 1; $i <= 12; $i++) {
			array_push($mopt, array('value' => $i, 'display' => $months[$i]));
		}
		$retArray['monopt'] = $mopt;

		if ($q14 != "") {
			list($year, $month, $day) = explode("-", $q14);
			$retArray['q14_year'] = $year;
			$retArray['q14_month'] = $month;
		}
		else {
			$retArray['q14_year'] = "0";
			$retArray['q14_month'] = "0";
		}

		if ($q17 != "") {
			list($year, $month, $day) = explode("-", $q17);
			$retArray['q17_year'] = $year;
			$retArray['q17_month'] = $month;
		}
		else {
			$retArray['q17_year'] = "0";
			$retArray['q17_month'] = "0";
		}

		if ($q20 != "") {
			list($year, $month, $day) = explode("-", $q20);
			$retArray['q20_year'] = $year;
			$retArray['q20_month'] = $month;
		}
		else {
			$retArray['q20_year'] = "0";
			$retArray['q20_month'] = "0";
		}

		return $retArray;
	}

	/**
	 * Display results of HA
	 * @param unknown_type $param
	 */
	public function Total($param){
		$specificReport=(isset($param[0]))?intval($param[0]):0;
		$hs=new IFocusModel($this->myId,false,$specificReport);
		$total=$hs->score();
		$risks=$hs->getRisks();
		$bio = $hs->get('biometric_data');
		if (isset($bio['cholesterol']) && $bio['cholesterol'] > 0) {
			$bio['hdl_ratio'] = $bio['cholesterol'] / $bio['hdl'];
		}
		if (isset($bio['bmi'])) {
			$bio['bmi'] = sprintf("%.1f", $bio['bmi']);
		}

		if ($specificReport == 0) {
			$specificReport = $hs->getID();
		}

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/iFocus/total.tpt");
		$template->addVar('rpt', $specificReport);

		// Total scores for each section are number from 0 to 5.  Calculate
		// the score based on 100 (percent).  Note that the total is already
		// based on 100.
		//
		// Also calculate an absolute location for the scale marker and associated text
		foreach ($total as $k => $v) {
			if ($k == "total") {
				$s = sprintf("%.1f", $v);
				$score[$k] = $s;
			}
			else if ($k == "biometric_data") {
				if ($v[0]->data['bp_systolic'][0] > 0) {
					$score['blood_pressure'] = 100.0 * (($v[0]->data['bp_systolic'][0] + $v[0]->data['bp_diastolic'][0]) / 2.0) / 5.0;
				}
				if ($v[0]->data['body_fat'][0] > 0) {
					$score['body_fat'] = 100.0 * ($v[0]->data['body_fat'][0] / 5.0);
				}
				if ($v[0]->data['bmi'][0] > 0) {
					$score['bmi'] = 100.0 * ($v[0]->data['bmi'][0] / 5.0);
					$score['bmi'] = sprintf("%.1f", $score['bmi']);
				}
				if ($v[0]->data['waist'][0] > 0) {
					$score['waist'] = 100.0 * ($v[0]->data['waist'][0] / 5.0);
				}
				if ($v[0]->data['blood_glucose'][0] > 0) {
					$score['blood_glucose'] = 100.0 * ($v[0]->data['blood_glucose'][0] / 5.0);
				}
				if ($v[0]->data['hemoglobin'][0] > 0) {
					$score['hemoglobin'] = 100.0 * ($v[0]->data['hemoglobin'][0] / 5.0);
				}
				if ($v[0]->data['cotinine'][0] > 0) {
					$score['cotinine'] = 100.0 * ($v[0]->data['cotinine'][0] / 5.0);
				}
				if ($v[0]->data['cholesterol'][0] > 0) {
					$score['total_cholesterol'] = 100.0 * ($v[0]->data['cholesterol'][0] / 5.0);
					$score['hdl'] = 100.0 * ($v[0]->data['hdl'][0] / 5.0);
					$score['ldl'] = 100.0 * ($v[0]->data['ldl'][0] / 5.0);
					$score['hdl_ratio'] = 100.0 * ($v[0]->data['tc_hdl'][0] / 5.0);
					$score['triglycerides'] = 100.0 * ($v[0]->data['triglycerides'][0] / 5.0);
				}
			}
			else {
				$s = 100.0 * ($v[0]->total / 5.0);
				if ($s < 100.0)
					$s = sprintf("%.1f", $s);
				$score[$k] = $s;
					
			}
		}

		foreach($score as $k => $v) {
			$left[$k] = $this->calcChipPosition($score[$k]);
			$text[$k] = $this->calcTextPosition($score[$k]);
			$stoplight[$k] = $this->calcStopLight($score[$k]);
		}

		$modules = $hs->getSuggestedModules();

		$template->addVar("biometric", $bio);
		$template->addVar("score", $score);
		$template->addVar("left", $left);
		$template->addVar("text", $text);
		$template->addVar("SLimage", $stoplight);
		$template->addVar("total",$total);
		$template->addVar("risks",$risks);
		$template->addVar("is_gina",$hs->isGina());
		$template->addVar("modules", $modules);

		$template->addVar("status","show");

		return $template;
	}


	/**
	 * Calculate where the 'poker' chip goes on the scale.
	 *
	 */
	private function calcChipPosition($score) {
		// geometry
		$div_width = 155;
		$chip_width = 30;
		$margin = 7;
		$loc_green = 245;
		$loc_yellow = $loc_green + $div_width + $margin;
		$loc_red = $loc_yellow + $div_width + $margin;
		// absolute location of the right edge of the <div>  (minus the 'chip' image width) in px
		$end_green = $loc_green + $div_width - $chip_width;
		$end_yellow = $loc_yellow + $div_width - $chip_width;
		$end_red = $loc_red + $div_width - $chip_width;
		// size of the <div> (minus the 'chip' image width) in px
		$box_size = $div_width - $chip_width;
		// low score of each range
		$low_green = 80;
		$low_yellow = 50;
		$low_red = 0;
		// range size
		$range_green = 20;
		$range_yellow = 30;
		$range_red = 50;
		
		if ($score >= $low_green) {
			$left = $end_green - ($box_size * (round($score) - $low_green) / $range_green);
		}
		else if ($score >= $low_yellow) {
			$left = $end_yellow - ($box_size * (round($score) - $low_yellow) / $range_yellow);
		}
		else {
			$left = $end_red - ($box_size * (round($score) - $low_red) / $range_red);
		}

		return $left;
	}


	/**
	 * Calculate the location of the text score (xxx/100) based
	 * on which group (green, yellow, red) it falls in
	 *
	 */
	private function calcTextPosition($score) {
		$low_green = 80;
		$low_yellow = 50;

		if ($score >= $low_green) {
			$text = 300;
		}
		else if ($score >= $low_yellow) {
			$text = 470;
		}
		else {
			$text = 630;
		}

		return $text;
	}


	/**
	 * Select which stop light image is to be displayed based on risk
	 * Green for low risk (high score), Yellow for moderate, and Red for high
	 * risk (low score)
	 *
	 */
	private function calcStopLight($score) {
		$low_green = 80;
		$low_yellow = 50;

		if ($score >= $low_green) {
			$image = "green_chips.png";
		}
		else if ($score >= $low_yellow) {
			$image = "yellow_chips.png";
		}
		else {
			$image = "red_chips.png";
		}
		return $image;
	}

	/**
	 * Create a custom PDF certificate for the user.  Include the user's name
	 * and the date of completion.
	 *
	 */
	public function Certificate($param) {
		$id = isset($param[0]) ? $param[0] : 0;
		if ($id > 0) {
			$ifm = new IFocusModel($this->cred->getId(), false, $id);
			$dc = $ifm->getDateCompleted();
			$name = $ifm->getUserName();

			$dateCompleted=date("F j, Y", strtotime($dc));

			require_once(ROOT_DIR.'tcpdf/config/lang/eng.php');
			require_once(ROOT_DIR.'tcpdf/tcpdf.php');

			//create new PDF document
			$pdf = new TCPDF("L", PDF_UNIT, 'USLETTER', true, 'UTF-8', false);

			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('Provant');
			$pdf->SetTitle('iFOCUS Completion');
			$pdf->SetSubject('Completion Certificate');
			$pdf->SetKeywords('Provant iFOCUS Completion Certificate');
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			//set margins
			$pdf->SetMargins(0, 0, 0);
			$pdf->SetHeaderMargin(0);
			$pdf->SetFooterMargin(0);

			//set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

			//set some language-dependent strings
			$pdf->setLanguageArray($l);

			$pdf->SetFont('helvetica', 'B',"10");

			// ---------------------------------------------------------

			// set JPEG quality
			$pdf->setJPEGQuality(75);

			// Image example
//			error_log(ROOT_DIR.'uploads/file_upload/iFOCUS/cert.jpg');
			$pdf->Image(ROOT_DIR.'assets/media/images/ifocus/certificate.jpg', 0, 0, 280,0, 
								'JPEG', '', '', true, 100, '', false, false, 0, false, false, false);

			$loc = 248 - ((strlen($dateCompleted)-1) * 2);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->Text($loc, 70, $dateCompleted);

			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFontSize(24);
			$pdf->Text(85, 128, $name);
			// ---------------------------------------------------------

			//Close and output PDF document
			$pdf->Output('iFOCUS_Completion.pdf', 'I');
		}
		else {
			header('Location: /IFocus/Index');
			exit();
		}
	}

	/**
	 * Entry for displaying a pop up with the lightbox stuff
	 *
	 */
	public function info($param) {
		$page = $param[0];
		$template = TemplateParser::create(TEMPLATE_DIR."frontend/iFocus/".$page."_pop.tpt");
		$template->parse();
		exit();
	}

	public function details($param) {
		$id = $param[0];
		$section = $param[1];

		$ifm = new IFocusModel($this->myId, false, $id);
		$info = $ifm->getSectionDetails($section);

		$template = TemplateParser::create(TEMPLATE_DIR."frontend/iFocus/details_pop.tpt");
		$template->addVar('title', $info['title']);
		$template->addVar('text', $info['text']);
		$template->addVar('image', $info['image']);
		$template->addVar('score', $info['score']);
		$template->addVar('range', $info['range']);
		$template->parse();
		exit();
	}
}
