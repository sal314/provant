<?php 
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/UserCredentials.class.php");
class UserNameModel {
	private $result=null;
	private $logo=null;
	public function __construct(){
		$this->dbOb=Database::create();
		$this->cred=UserCredentials::load();
		$id=$this->cred->getId();
		
		if(!$id){
		  return; 
		}
		$sql="SELECT * FROM z_users WHERE id='".$this->dbOb->escape_string($id)."'";
		$this->result=$this->dbOb->getRow($sql);
		if(!$this->result){
			return;
		}
	}
	public function getFirstName(){
		if($this->result) return $this->result["first_name"];
		return "";
	}

	public function getCompanyId() {
/*
		$cid = 0;
		$id = $this->cred->getId();
		if ($id) {
			$sql = "SELECT company_id FROM u_profile WHERE z_user_id = " . $id . " AND is_active = 1";
			$cid = $this->dbOb->getOne($sql);
		}
		return $cid;
*/
		$host = $_SERVER['HTTP_HOST'];
		$sql = "SELECT id FROM p_company WHERE host = '" . $host . "' AND is_active = 1";
		$cid = $this->dbOb->getOne($sql);
		return ($cid ? $cid : 0);
	}


	/**
	 * Get the user's state (pre-loaded, active, deleted)
	 *	@param - none
	 */
	public function getUserState() {
		if ($this->cred->isAdmin()) {
			return 'active';
		}

		$id = $this->cred_getId();
		$sql = "SELECT status FROM u_profile WHERE z_user_id = " . $id;
		return $this->dbOb->getOne($sql);
	}

	public function getCompanyRegUrl() {
		$host = $_SERVER['HTTP_HOST'];
		$sql = "SELECT url FROM p_company WHERE host = '" . $host . "' AND is_active = 1";
		return $this->dbOb->getOne($sql);
	}

	public function getColorScheme() {
		if ($this->cred->isAdmin()) {
			return;
		}

		if($this->cred->hasRead("ADMIN_LOGIN")){
			$sql = "SELECT id FROM p_company WHERE admin_user = " . $this->cred->getId() . " AND is_active = 1";
			$cid = $this->dbOb->getOne($sql);
		}
		else if ($this->cred->hasRead("LOGIN_USER")) {
			$sql = "SELECT company_id FROM u_profile WHERE z_user_id = " . $this->cred->getId() . " AND is_active = 1";
			$cid = $this->dbOb->getOne($sql);
		}
		else {
			//load in the company css file on the register/{company identifier} & login page
			$host = $_SERVER['HTTP_HOST'];
			$sql="SELECT id, file_logo " .
						"FROM p_company " .
						"WHERE host = '".$this->dbOb->escape_string($host) . "' " .
						"AND is_active=1";
			$companyStyling=$this->dbOb->getRow($sql);
			if($companyStyling){
				$this->logo=$companyStyling['file_logo'];
				$cid = $companyStyling['id'];
			}
		}

		if (isset($cid)) {
			if (!file_exists("/var/www/provant/html/assets/css/colors/color_scheme_" . $cid . ".css")) {
//				$this->generateCSS($cid, array());
				return "/assets/css/colors/blue-green.css";
			}
			else {
				return "/assets/css/colors/color_scheme_" .$cid . ".css";
			}
		}
		else {
			return "/assets/css/colors/blue-green.css";
		}
	}




	public function getLogo($cid) {

		$max_height = 75;
		$path = "/var/www/provant/html/uploads/image/";

		$ret = array('file'  => $path . "logo.jpg",
		             'height' => $max_height);
		if ($this->cred->isAdmin()) {
			return $ret;
		}

		if ($cid == 0) {
			return $ret;
		}

		$sql = "SELECT file_logo FROM p_company " .
						"WHERE id = " . $cid . " " .
						"AND is_active = 1";
		$logo = $this->dbOb->getOne($sql);
		if (!$logo) {
			return $ret;
		}
		else {
			$size = getimagesize($path . $logo);
			if ($size[1] > $max_height) {
				$ret['file'] = $logo;
				$ret['height'] = $max_height;
			}
			else {
				$ret['file'] = $logo;
				$ret['height'] = $size[1];
			}
		}
/*
		if ($this->cred->hasRead("ADMIN_LOGIN")) {
			$sql="SELECT file_logo FROM p_company " .
					"WHERE admin_user=".$this->cred->getId().
					" AND is_active=1";
			$logo = $this->dbOb->getOne($sql);
			if ($logo) {
				$ret = $logo;
			}
		}
		else if ($this->cred->hasRead("LOGIN_USER")) {
			$sql="SELECT comp.file_logo FROM p_company AS comp, u_profile AS user ".
					"WHERE user.z_user_id=".$this->cred->getId().
					" AND comp.id=user.company_id".
					" AND comp.is_active=1 AND user.is_active=1";
			$logo = $this->dbOb->getOne($sql);
			if ($logo) {
				$ret = $logo;
			}
		}else{
			//if we are at the register page the logo will have already have been found in the getColorScheme() call
			if($this->logo) return $this->logo;
		}
*/
		return $ret;
	}

	public function getNavEnabledList() {
		// Right now all links are enabled except the 'kits' link
		// is enabled only if the company has subscribed to either
		// the Home Screening or the Lab Screening kits

		$navlist = array();
		$navlist[0] = true;
		$navlist[1] = true;
		$navlist[2] = true;
		$navlist[3] = true;
		$navlist[4] = true;
		$navlist[5] = true;

		if ($this->cred->hasRead("LOGIN_USER")) {
			$sql="SELECT cm.p_module_id FROM p_company_modules AS cm, ".
					"p_company AS comp, u_profile AS user ".
					"WHERE user.z_user_id=".$this->cred->getId().
					" AND user.is_active=1 ".
					"AND user.company_id=comp.id ".
					"AND cm.p_company_id=comp.id ".
					"AND comp.is_active=1";
			$modules = $this->dbOb->query($sql);
			if (!$modules) {
				$navlist[6] = false;
			}
			else {
				$navlist[6] = false;
				foreach($modules as $mod) {
					if (($mod['p_module_id'] == 3) ||
				    	($mod['p_module_id'] == 9)) {
					    $navlist[6] = true;
					    break;
					}
				}
			}
		}
		else {
			$navlist[6] = true;
		}

		return $navlist;
	}


	public function getNavStatus(){
		$classes=array(0=>array('','','','','','','','','','',''),1=>array('','','','','','','','','','',''));
		$url=preg_split("/\//",$_SERVER['REQUEST_URI']);
		$ca="class='nav_active'";
		$cs="class='nav_active_span'";						
		if(sizeof($url)<2) return $classes;							
		switch (strtolower($url[1])){
			case "page":									
				switch(strtolower($url[2])){
					case "trackers": 
						$classes[0][0]=$ca;
						$classes[1][0]=$cs;
					break;
					case "itmodules": 
						$classes[0][2]=$ca;
						$classes[1][2]=$cs;
					break;
					case "kits": 
						$classes[0][6]=$ca;
						$classes[1][6]=$cs;
					break;
			}
			break;
			case "ifocus":
				$classes[0][1]=$ca;
				$classes[1][1]=$cs;
			break;
			case "healtharticles":
				$classes[0][3]=$ca;
				$classes[1][3]=$cs;
			break;
			case "healthlibrary":
				$classes[0][4]=$ca;
				$classes[1][4]=$cs;
			break;
			
			case "healthyachievements":
				$classes[0][5]=$ca;
				$classes[1][5]=$cs;
				break;
			break;
			case "homehealthscreeningkit":
				$classes[0][6]=$ca;
				$classes[1][6]=$cs;
				break;
			break;
			
			case "healthcoach":
				switch(strtolower($url[2])){
					case "index": 
						$classes[0][7]=$ca;
						$classes[1][7]=$cs;
					break;
					case "calllog": 
						$classes[0][8]=$ca;
						$classes[1][8]=$cs;
					break;
					case "getusers": 
						$classes[0][9]=$ca;
						$classes[1][9]=$cs;
					break;
				}
			break;

			case "messages":
				$classes[0][10]=$ca;
				$classes[1][10]=$cs;
			break;

			case "workoutplan":
				$classes[0][0]=$ca;
				$classes[1][0]=$cs;				
			break;

			case "mealplan":
				$classes[0][0] = $ca;
				$classes[1][0] = $cs;
			break;

			case "foodlog":
				$classes[0][0] = $ca;
				$classes[1][0] = $cs;
			break;

			case "portionplate":
				$classes[0][0] = $ca;
				$classes[1][0] = $cs;
			break;

			case "myprogress":
				$classes[0][0] = $ca;
				$classes[1][0] = $cs;
			break;

			default:
				//is it a tracker module
				$p=stripos($url[1],"tracker");
				if($p==0 && !($p===false)){
					$classes[0][0]=$ca;
					$classes[1][0]=$cs;
					break;
				}
				//is it a it module?
				$p=stripos($url[1],"module");
				if($p==0 && !($p===false)){
					$classes[0][2]=$ca;
					$classes[1][2]=$cs;
					break;
				}
				
		}	
		return $classes;
	}
}
