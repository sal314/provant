<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  

class UsersUpload extends AdminPageBase{
	public function getBaseTableName(){return "z_users";}	
	
	public function Index($params){
		$sql = "SELECT * FROM p_company WHERE is_active = 1";
		
		$result = $this->dbOb->query($sql);
		
		
		$total_result = count($result);
		
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/Users/upload.tpt");
		$template->addVar("total",$total_result);
		$template->addVar("company",$result);
		return $template;
	}
	
	public function Upload($param) {
		$NUM_COLUMNS = 22;
		$company_id = isset($_POST['company_list']) ? $_POST['company_list'] : '';
		
		$err="";
		
		if ($company_id == NULL || $company_id == "" || $company_id == "all") 
			
			throw new Exception404("please select company id.");
			
			
	
		
		
		if(!sizeof($_FILES)) throw new Exception("No file uploaded");
		if($_FILES["data"]["error"])throw new Exception("Error during file uploaded");
		
		$tmpFile=$_FILES["data"]["tmp_name"];
		$f=fopen($tmpFile,"r");
		if(!$f) throw new Exception ("Can not open csv file");				
		$columns=fgetcsv($f,0,',','"');
		/*Array
(
    [0] => Email (255 characters)
    [1] => Password ()
    [2] => Last_name (45 characters)
    [3] => First_name (45 characters)
    [4] => Gender (enum ('M', 'F'))
    [5] => Address1 (50 characters)
    [6] => Address2 (50 characters)
    [7] => City (50 characters)
    [8] => State (4 characters)
    [9] => Country (2 characters)
    [10] => Zipcode (10 characters)
    [11] => Date of Birth (YYYY-MM-DD)
    [12] => Race (enum (1,2,3,4,5,6,7,8) (African American, Asian/Pacific Islander, Caucasian, Hispanic, Middle Eastern, Native American, Other, Bi-racial))
    [13] => Marital Status (enum ('S', 'M', 'P', 'D', 'O') (single, married, separated, divorced, other))
    [14] => Education (enum ('I', 'H', 'S', 'C', 'G') (some high school, high school or GED, some college, college, post graduate))
    [15] => Height feet (number of feet)
    [16] => Height inches (number of inches)
    [17] => Initial weight (lbs)
    [18] => Goal weight (lbs)
    [19] => Goal (enum ('maintain', 'lose_1pw', 'lose_2pw', 'gain_1pw', 'gain_2pw'))
    [20] => Activity Level (enum (1,2,3,4,5) (Sedentary, Lightly Active, Moderately Active, Very Active, Extremely Active))
    [21] => Job Code (40 characters)
)

*/

		if(sizeof($columns)!=$NUM_COLUMNS)throw new Exception("Error csv column mismatch");
		$row=1;

		$loadCount = 0;
		while (($record=fgetcsv($f,0,',','"'))!== false){//while !eof
			$row++;
			if(sizeof($record)!=$NUM_COLUMNS){
				$err.="Error Skipping Row ".$row." does not contain the correct column count.<br/>   Count: " . sizeof($record) . "<br />";
				continue;	
			}

			$sql="SELECT * FROM z_users WHERE UCASE(email)=UCASE(TRIM('".$this->dbOb->escape_string($record[0])."'))";
			$uid=$this->dbOb->getOne($sql);
			if($uid){
				$err.="Error Skipping row Row ".$row." invalid user email.<br/>";
				continue;
			}
/*
			$sql="SELECT id FROM z_users WHERE login='".$this->dbOb->escape_string($record[1])."'";
			$id=$this->dbOb->getOne($sql);
			if($id){
				$err.="Error Skipping Row ".$row." invalid user login.<br/>";
				continue;
			}
*/
			//add main user data. 
			$sql="INSERT INTO z_users (`email`,`login`,`password`,`last_name`,`first_name`) VALUES (" .
				"'".$this->dbOb->escape_string($record[0])."'," .
				"'".$this->dbOb->escape_string($record[0])."'," .
				"'".$this->dbOb->escape_string($record[1])."'," .
				"'".$this->dbOb->escape_string($record[2])."'," .
				"'".$this->dbOb->escape_string($record[3])."')";
			$this->dbOb->insert($sql);
			
			$pk=$this->dbOb->getLastPKInserted();
			
			$sql="UPDATE z_users SET `password`=PASSWORD('".$this->dbOb->escape_string($record[2])."') WHERE id='".$this->dbOb->escape_string($pk)."'";
  		$this->dbOb->update($sql);
			
			//add user profile data
			$sql="INSERT INTO u_profile (`z_user_id`,`company_id`,`status`,`gender`, `address1`, `address2`, `city`, `state`, `country`, `zipcode`, `dob`, `race_id`, `marital_status`,`education`, `height_ft`, `height_in`, `initial_weight`, `goal_weight`, `goal`, `activity_level_id`,`job_code`) VALUES (" .
				"'".$this->dbOb->escape_string($pk)."'," .
				"'".$this->dbOb->escape_string($company_id)."'," .
				"'pre-loaded'," .
				"'".$this->dbOb->escape_string($record[4])."'," .
				"'".$this->dbOb->escape_string($record[5])."'," .
				"'".$this->dbOb->escape_string($record[6])."'," .
				"'".$this->dbOb->escape_string($record[7])."'," .
				"'".$this->dbOb->escape_string($record[8])."'," .
				"'".$this->dbOb->escape_string($record[9])."'," .
				"'".$this->dbOb->escape_string($record[10])."'," .
				"'".$this->dbOb->escape_string($record[11])."'," .
				"'".$this->dbOb->escape_string($record[12])."'," .
				"'".$this->dbOb->escape_string($record[13])."'," .
				"'".$this->dbOb->escape_string($record[14])."'," .
				"'".$this->dbOb->escape_string($record[15])."'," .
				"'".$this->dbOb->escape_string($record[16])."'," .
				"'".$this->dbOb->escape_string($record[17])."'," .
				"'".$this->dbOb->escape_string($record[18])."'," .
				"'".$this->dbOb->escape_string($record[19])."'," .
				"'".$this->dbOb->escape_string($record[20])."'," .
				"'".$this->dbOb->escape_string($record[21])."')";
			$this->dbOb->insert($sql);

			//add user role data
			//We only add COMPANY_USER types through this pre-load interface
			$sql = "SELECT id FROM z_roles WHERE name = 'COMPANY_USER'";
			$role = $this->dbOb->getOne($sql);

			$sql="INSERT INTO z_user_role (`user`,`role`) VALUES (
				'".$this->dbOb->escape_string($pk)."',
				'".$role."')";
			$this->dbOb->insert($sql);

			$loadCount += 1;			
		}
		fclose($f);

		print $err;
		//die("Done");
		if ($loadCount > 0) {
			print "<br /><br /><strong>Successfully loaded " . $loadCount . " users</strong><br /><br />";
		}
	}
}
