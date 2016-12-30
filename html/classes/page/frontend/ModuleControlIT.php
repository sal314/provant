<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/ModuleControlITModel.php");

/**
 * ModuleControlIT class definition
 * @author slepage
 * @version 1.1
 */
class ModuleControlIT  extends PageBase{

	private $mod = null;		//Control IT model

	public function __construct() {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}

		if ($this->cred->hasRead("LOGIN_HEALTH_COACH")) {
			$this->mod = new ModuleControlITModel(true);
		}
		else {
			$id=$this->cred->getID();
	  		$sql="SELECT company_id FROM u_profile WHERE z_user_id='".$this->dbOb->escape_string($id)."'";
	  		$cid=$this->dbOb->getOne($sql);

	  		//hard coded ID to save a look up here!
	  		$sql="SELECT * FROM p_company_modules WHERE p_company_id='".$this->dbOb->escape_string($cid)."' AND p_module_id='5'";
	  		if(!$this->dbOb->getOne($sql))throw new Exception("This module is not available");

	  		$this->mod=new ModuleControlItModel();
		}
	}

	public function Index($param){
		if($this->mod->introComplete()){
			$t="landing.tpt";
		}else{
			$t="intro_1.tpt";
		}
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ITModules/ControlIt/".$t);

		$template->addVar("last_completed",$this->mod->getLastCompleted());

		return $template;
	}

	public function Intro($param){
		$page=isset($param[0])&&$param[0]?$param[0]:1;
		$result['err'] = false;
		if(count($_POST) && $page==3){
			$result = $this->mod->validate($_POST,"intro",$page);
			if (!$result['err']) {
				$this->mod->update();
				header("Location: /ModuleControlIT/Index/");
				exit();
			}
		}
		if(!count($_POST) && $page==3){
			$_POST=$this->mod->get("intro_data");
		}
		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ITModules/ControlIt/intro_".$page.".tpt");
		$template->addVar("error",$result['err']);
		$template->addVar("POST",$_POST);
		$template->addVar("current_page",$page);
		$template->addVar("total_pages",3);

		return $template;
	}


	public function Week($param){
		$week=isset($param[0])&&$param[0]?$param[0]:1;
		$page=isset($param[1])&&$param[1]?$param[1]:1;
		$err=null;
		$validateData=false;
		$attempt = 1;

		if($week>$this->mod->getLastCompleted()+1){
			throw new Exception("You must complete the previous week before you can start this week.");
		}

		// If we're on the first page, save the start date of this week
		if ($page == 1) {
			$this->mod->recordStart($week);
		}

		$pages=1;
		$numPages=array(0,8,7,10,12,6);

		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ITModules/ControlIt/week".$week."_".$page.".tpt");	

		if(count($_POST)){
			$ftt = 1;	
			$validateData=false;
			switch($week){
				case 1:
					if ($page==1) {
						$validateData = true;
					}
					else if ($page==7) {
						$validateData=true;
						$attempt = $_POST['attempt'] + 1;
					}											
					break;

				case 2:
					if ($page==6) {
						$validateData=true;
						$attempt = $_POST['attempt'] + 1;
					}
					break;

				case 3:
					if ($page==9) {
						$validateData=true;
						$attempt = $_POST['attempt'] + 1;
					}
					break;

				case 4:
					if ($page==11) {
						$validateData=true;
						$attempt = $_POST['attempt'] + 1;
					}
					break;

				case 5:
					if ($page==5) {
						$validateData=true;
						$attempt = $_POST['attempt'] + 1;
					}
					break;

				default:
					$validateData=false;
			}

			if($validateData){
				$result = $this->mod->validate($_POST,"week".$week,$page);
				$template->addVar("result", $result);
				if (isset($result['class'])) {
					$template->addVar("resClass", $result['class']);
				}
				
				if(!$result['err']){
					$this->mod->update();
					if ($attempt == 1) {	//If it's just an activity (not an exam), go to next page
						header("Location: /ModuleControlIT/Week/".$week."/".(++$page));
						exit();
					}
					$template->addVar("submit", 1);
				}
				else {
					if ($attempt > 3) {
						$template->addVar("submit", 2);
					}
					else {
						$template->addVar("submit", 3);
					}
				}
			}
			else {
				$result = array('err' => false);
			}
		}
		else {
			$result = array('err' => false);
			$ftt = 0;
		}

		$pages=$numPages[$week];

		$template->addVar("ftt", $ftt);
		$template->addVar("attempt", $attempt);
		$template->addVar("current_page",$page);
		$template->addVar("total_pages",$pages);

		$template->addVar("errors",$result['err']);
		$_POST=$this->mod->get("week".$week."_data");
		$template->addVar("POST",$_POST);
		return $template;
	}

	public function info($param){
		$page=$param[0];
		$template=TemplateParser::create(TEMPLATE_DIR."frontend/ITModules/ControlIt/".$page."_pop.tpt");
		$template->parse();
		exit();
	}


	public function Record($param){
		$date=$_POST['date'];
		$vc= new Validator();
		
		$a= new Ajax();
		try{		
			$v=$vc->exists("date",$_POST,"date",array("datestamp"=>1),false,true);
			$this->mod->addDate($v);			
			$a->addResponseMessage("response",AJAX::SUCCESS,"success");
			$a->addResponseData("date",$v);
		}catch(Exception $e){
			$a->addResponseMessage("response",AJAX::FATAL,"Invalid date");
		}
		$a->writeResponseXML();
		exit();	
	}


	public function populate($param){
		$a= new Ajax();
		try{		
			$month=isset($_POST['month'])?intval($_POST['month']):-1;
						
			$year=isset($_POST['year'])?intval($_POST['year']):0;
			if($month>12 || $month<1) throw new Exception("Invalid month");			
			if($year>2100 || $year<0) throw new Exception("Invalid year");
			if($month<10)$month="0".$month;
			
			$ld=$this->mod->get("listen_data");			
			$keys=array_keys($ld);
			$regex="/".$year."-".$month."-(\d{2})/";
			$data=array();
			foreach($keys as $date){
				error_log($date." ".$regex);
				if (preg_match($regex,$date,$matches)){
					error_log(var_export($matches,true));
					$data[]=$matches[1];
				}
			}
			$a->addResponseData("days",$data);
			$a->addResponseMessage("response",AJAX::SUCCESS,"success");
		}catch(Exception $e){
			$a->addResponseMessage("response",AJAX::FATAL,$e->getMessage());
		}
		$a->writeResponseXML();
		exit();	
		
	}
	
	public function Download($param){
		if($this->mod->getLastCompleted()!="5") throw new Exception("You must complete the exam!");
		$dateCompleted=date("F j, Y",strtotime($this->mod->get('date_updated')));
		
		$sql="SELECT Concat(first_name,' ',last_name) FROM z_users WHERE id='".$this->dbOb->escape_string($this->cred->getID())."'";
		$name=$this->dbOb->getOne($sql);
		
		require_once(ROOT_DIR.'tcpdf/config/lang/eng.php');
		require_once(ROOT_DIR.'tcpdf/tcpdf.php');

		//create new PDF document
		$pdf = new TCPDF("L", PDF_UNIT, 'USLETTER', true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Provant');
		$pdf->SetTitle('ControlIT Completion');
		$pdf->SetSubject('Completion Certificate');
		$pdf->SetKeywords('Provant ControlIT Completion Certificate');

		// set default header data
		//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 009', PDF_HEADER_STRING);

		// set header and footer fonts
		//$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$pdf->SetMargins(0, 0, 0);
		$pdf->SetHeaderMargin(0);
		$pdf->SetFooterMargin(0);

		//set auto page breaks
		//$pdf->SetAutoPageBreak(TRUE, 0);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		$pdf->setLanguageArray($l);

		$pdf->SetFont('helvetica', 'B',"10");
		
		// ---------------------------------------------------------

		// add a page
		//$pdf->AddPage();

		// set JPEG quality
		$pdf->setJPEGQuality(75);

		// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

		// Image example
//		error_log(ROOT_DIR.'uploads/file_upload/ITModules/ControlIT/cert.jpg');
		$pdf->Image(ROOT_DIR.'assets/media/images/myfocus/controlit/certificate.jpg', 0, 0, 280,0, 
			'JPEG', '', '', true, 100, '', false, false, 0, false, false, false);

		
		$pdf->SetTextColor(255, 255, 255);
		$pdf->Text(155, 134.5, $dateCompleted);
		
		$pdf->SetFontSize(32);
		$pdf->Text(150, 150, $name);

		//&&&
		// Add text for health coach's name
		//

		// ---------------------------------------------------------

		//Close and output PDF document
		$pdf->Output('ControlIT_Completion.pdf', 'I');		
	}	

	//
	// Health Coach request
	//
	public function Start ($params) {
		if (!$this->cred->hasRead("LOGIN_HEALTH_COACH")) {
			header("Location: /User/Index");
		}

		$err = array();
		$id = isset($params[0]) ? $params[0] : 0;
		if ($id == 0) {
			$error = new stdClass();
			$error->display_name = "";
			$error->name = "";
			$error->type = 5;
			$error->message = "FAILED: A user identifier was not supplied";
			$err[] = $error;
		}

		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/healthcoach/controlit.tpt");

		if (count($err) > 0) {
			$template->addVar("errors", $err);
			return $template;
		}

		$sql = "SELECT * FROM z_users AS z " .
				"JOIN u_profile AS u ON z.id = u.z_user_id " .
				"WHERE z.id = " . $this->dbOb->escape_string($id) . " " .
				"AND z.is_active = 1 " .
				"AND u.is_active = 1";
		$user = $this->dbOb->getRow($sql);
		if (count($user) == 0) {
			$nouser = new stdClass();
			$nouser->display_name = "";
			$nouser->name = "";
			$nouser->type = 5;
			$nouser->message = "The user with identifier = '" . $id . "' was not found in the system.";
			$err[] = $nouser;
		}
		else {
			$template->addVar("user", $user);

			$sql = "SELECT * FROM u_module_controlit " .
					"WHERE z_user_id = " . $this->dbOb->escape_string($id) . " " .
					"AND is_active = 1"; 
			$data = $this->dbOb->getRow($sql);
			if (count($data) == 0) {
				$nodata = new stdClass();
				$nodata->display_name = "";
				$nodata->name = "";
				$nodata->type = 5;
				$nodata->message = $user['first_name'] . " " . $user['last_name'] . " has not taken the Control IT module";
				$err[] = $nodata;				
			}
			else {
				$this->mod->restore($data);
				$lastCompleted = $this->mod->getLastCompleted(true);
				$template->addVar("last_completed", $lastCompleted);
				$date_upd = $this->mod->get('date_updated');
				$template->addVar("date_updated", $date_upd);
				$lc = 0;
				$week = array();
				while ($lc <= $lastCompleted) {
					if ($lc == 0) {
						$week[$lc] = $this->mod->get('intro_data');
					}
					else {
						$week[$lc] = $this->mod->get('week'.$lc.'_data');
					}
					$lc += 1;
				}
				$template->addVar("week", $week);
			}
		}
		$template->addVar("errors", $err);
		
		return $template;
	}
}
