<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class HomeHealthScreeningKit extends AdminPageBase{
	public function getBaseTableName(){return "u_home_health_screening_kit";}	
	
	public function Index($params){
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/HHSK/index.tpt");
		return $template;
	}

	public function download($param){
		$sql="SELECT * FROM u_home_health_screening_kit_order AS hhsk WHERE hhsk.is_downloaded=0";
		$csv="USER ID,Company Name,First Name,Last Name,Address1,Address2,City,State,Contry,Zip,Phone,Email Address,Gender,DOB\n";
		$users=$this->dbOb->query($sql);
		if($users){
			foreach($users as $user){
			  $csv.=$user["z_user_id"].",".$user['company_name'].",".
			  $user['first_name'].","	.$user['last_name'].",".
			  $user['address1'].",".$user['address2'].",".$user['city'].",".$user['state'].",".$user['country'].",".$user['zipcode'].",".
			  $user['phone'].",".$user['email'].",".
			  $user['gender'].",".$user['dob']."\n";
			}
			$last=$users[sizeof($users)-1]['date_added'];
			$sql="UPDATE u_home_health_screening_kit_order SET is_downloaded=1 WHERE is_downloaded=0 AND date_added<='".$this->dbOb->escape_string($last)."'";
			$this->dbOb->update($sql);
		}
				header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");

		//Use the switch-generated Content-Type
		header("Content-Type: text/csv");

		//Force the download
		header("Content-Disposition: attachment; filename=home_health_screening_kit_requests.csv;");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".sizeof($csv));
		print $csv;
		exit(); 
	} 
	
	public function upload($param){
		if(!sizeof($_FILES)) throw new Exception("No file uploaded");
		if($_FILES["data"]["error"])throw new Exception("Error during file uploaded");
		
		$tmpFile=$_FILES["data"]["tmp_name"];
		$f=fopen($tmpFile,"r");
		if(!$f) throw new Exception ("Can not open csv file");				
		$columns=fgetcsv($f,0,',','"');
		/*Array
(
    [0] => User Email
    [1] => Health Coach Login
    [2] => Screening Date
    [3] => Height(in)
    [4] => Weight(lbs)
    [5] => Blood Pressure (Systolic)
    [6] => Blood Pressure (Diastolic)
    [7] => Heart Rate
    [8] => Cholesterol (Total)
    [9] => Cholesterol (LDL)
    [10] => Cholesterol (HDL)
    [11] => Cholesterol (Triglycerides)
    [12] => Cholesterol (Ratio)
    [13] => C-Reactive Protein
    [14] => Glucose (Fasting) 
    [15] => Glucose (Random)
    [16] => Hemoglobin (HgA1c)
    [17] => Spirometry (FVC)
    [18] => Spirometry (FEV1)
    [19] => Spirometry (Lung Age)
    [20] => Prostate (PSA)
    [21] => Osteoporosis (Bone Density)
)
*/
		$healthCoach=array();
		if(count($columns)!=22)throw new Exception("Error csv column mismatch");
		$row=1;
		$err="";
		while (($record=fgetcsv($f,0,',','"')) != false){//while !eof
			$row++;
			if(count($record)!=22){
				$err.="Error Skipping Row ".$row." does not contain the correct column count.<br/>";
				continue;	
			}
			
			$sql="SELECT * FROM z_users WHERE UCASE(email)=UCASE(TRIM('".$this->dbOb->escape_string($record[0])."'))";
			$uid=$this->dbOb->getOne($sql);
			if(!$uid){
				$err.="Error Skipping Row ".$row." invalid user login id/email.<br/>";
				continue;
			}
			$hc=$record[1];
			if(!isset($healthCoach[$hc])){
			 $sql="SELECT id FROM z_users WHERE login='".$this->dbOb->escape_string($hc)."'";
			 $id=$this->dbOb->getOne($sql);
			 if(!$id){
			 	$err.="Error Skipping Row ".$row." invalid health coach.<br/>";
			 	continue;
			 } 
			 $healthCoach[$hc]=$id;
			}
			$hcId=$healthCoach[$hc];
			$de=$record[2];
			
			
			preg_match("/(\d{4}\-\d{2}-\d{2})/",$de,$matches);
			if(sizeof($matches)){//Y-M-D format
				$de=$matches[0];
			}else{
				preg_match("/(\d{1,2})\/(\d{1,2})\/(\d{4})/",$de,$matches);
				if($matches){//m/d/y format
					$de=$matches[3]."-".(strlen($matches[1])==1?"0":"").$matches[1]."-".(strlen($matches[2])==1?"0":"").$matches[2];
					
				}else{
					$err.="Error Skipping Row ".$row." invalid Screening Date.<br/>";
				}
			}
			
			$this->validateData($record);
			
			//add the home health kit data. 
			$sql="INSERT INTO u_home_health_screening_kit_results (`z_user_id`,`entered_by`,`date_entered`,
				`height`,`weight`,`systolic`,`diastolic`,`heart_rate`,`total_cholesterol`,`ldl`,`triglycerides`,
				`cholesterol_ratio`,`c_reactive_protein`,`glocose_fasting`,`glucose_random`,`HgA1c`,`fvc`,
				`fev1`,`lung_age`,`psa`,`bone_density`) VALUES (
				'".$this->dbOb->escape_string($uid)."',
				'".$this->dbOb->escape_string($hcId)."',
				'".$this->dbOb->escape_string($de)."',
				'".$this->dbOb->escape_string($record[3])."',
				'".$this->dbOb->escape_string($record[4])."',
				'".$this->dbOb->escape_string($record[5])."',
				'".$this->dbOb->escape_string($record[6])."',
				'".$this->dbOb->escape_string($record[7])."',
				'".$this->dbOb->escape_string($record[8])."',
				'".$this->dbOb->escape_string($record[9])."',
				'".$this->dbOb->escape_string($record[10])."',
				'".$this->dbOb->escape_string($record[11])."',
				'".$this->dbOb->escape_string($record[12])."',
				'".$this->dbOb->escape_string($record[13])."',
				'".$this->dbOb->escape_string($record[14])."',
				'".$this->dbOb->escape_string($record[15])."',
				'".$this->dbOb->escape_string($record[16])."',
				'".$this->dbOb->escape_string($record[17])."',
				'".$this->dbOb->escape_string($record[18])."',
				'".$this->dbOb->escape_string($record[19])."',
				'".$this->dbOb->escape_string($record[20])."')";
			$this->dbOb->insert($sql);
			//add the tracker to the appropriate trackers

			$sql="UPDATE u_home_health_screening_kit_order set is_received=1 WHERE z_user_id='".$this->dbOb->escape_string($uid)."'";
			$this->dbOb->update($sql);
			
			if($record[3]){
				$ft=floor($record[3]/12);
				$in=$record[3]%12;
				if($ft>=3 && $ft<9 && $in<12 && $in>=0){
					$sql="UPDATE u_profile set height_ft='".$ft."', height_in='".$in."' WHERE z_user_id='".$this->dbOb->escape_string($uid)."'";
					$this->dbOb->update($sql);
				}
			}
			
			//weight
			if($record[4]){
				$sql="SELECT * FROM u_tracker_weight WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
				if(!$this->dbOb->getOne($sql)){
					$sql="INSERT INTO u_tracker_weight(z_user_id,entered_by,date_entered,weight) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[4])."')";
					$this->dbOb->insert($sql);
				}
				//else weight was already entered for this date should we acept the entered one as is or update it with the new value?
			}
			
			//cholesterol
			if($record[8]&&$record[9]&&$record[10]&&$record[11]){
				$sql="SELECT * FROM u_tracker_cholesterol WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
				if(!$this->dbOb->getOne($sql)){
					$sql="INSERT INTO u_tracker_cholesterol(z_user_id,entered_by,date_entered,total,hdl,ldl,triglycerides ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[8])."',
					'".$this->dbOb->escape_string($record[9])."',
					'".$this->dbOb->escape_string($record[10])."',
					'".$this->dbOb->escape_string($record[11])."')";
					$this->dbOb->insert($sql);
				}
				//else cholesterol was already entered for this date should we acept the entered one as is or update it with the new values?
			}					
			
			//blood glucose
			$sql="SELECT * FROM u_tracker_blood_glucose WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
			if(!$this->dbOb->query($sql)){
				if($record[14]){
					$sql="INSERT INTO u_tracker_blood_glucose(z_user_id,entered_by,date_entered,time_entered,blood_glucose,method ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'12:00',
					'".$this->dbOb->escape_string($record[14])."',
					'fasting')";
					$this->dbOb->insert($sql);
				}
				
				if($record[15]){
					$sql="INSERT INTO u_tracker_blood_glucose(z_user_id,entered_by,date_entered,time_entered,blood_glucose,method ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'12:00',
					'".$this->dbOb->escape_string($record[15])."',
					'random')";
					$this->dbOb->insert($sql);
				}
			}//else blood glucose was already entered for this date should we acept the entered one as is or update it with the new values?
			
			//blood pressure
			$sql="SELECT * FROM u_tracker_bp WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
			if(!$this->dbOb->getOne($sql)){
				if($record[5] && $record[6]){
					$sql="INSERT INTO u_tracker_bp(z_user_id,entered_by,date_entered,systolic,diastolic) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[5])."',
					'".$this->dbOb->escape_string($record[6])."'
					)";
				}
				$this->dbOb->insert($sql);
			}//else blood pressure was already entered for this date should we acept the entered one as is or update it with the new values?
		}
		echo "<br /><a href=\"/admin/zAdmin\">Back to the Home Page</a><br /><br />\n";
		print $err;
		die("Done");
	}
	private function validateData(&$record){
		/*
    [3] => Height(in)
    [4] => Weight(lbs)
    [5] => Blood Pressure (Systolic)
    [6] => Blood Pressure (Diastolic)
    [7] => Heart Rate
    [8] => Cholesterol (Total)
    [9] => Cholesterol (LDL)
    [10] => Cholesterol (HDL)
    [11] => Cholesterol (Triglycerides)
    [12] => Cholesterol (Ratio)
    [13] => C-Reactive Protein
    [14] => Glucose (Fasting) 
    [15] => Glucose (Random)
    [16] => Hemoglobin (HgA1c)
    [17] => Spirometry (FVC)
    [18] => Spirometry (FEV1)
    [19] => Spirometry (Lung Age)
    [20] => Prostate (PSA)
    [21] => Osteoporosis (Bone Density)
		*/
		$record[3]=intval($record[3]);
		$record[4]=floatval($record[4]);
		$record[5]=intval($record[5]);
		$record[6]=intval($record[6]);
		$record[7]=intval($record[7]);
		$record[8]=intval($record[8]);
		$record[9]=intval($record[9]);
		$record[10]=intval($record[10]);
		$record[11]=intval($record[11]);
		$record[12]=floatval($record[12]);
		$record[13]=floatval($record[13]);
		$record[14]=intval($record[14]);
		$record[15]=intval($record[15]);
		$record[16]=floatval($record[16]);
		$record[17]=floatval($record[17]);
		$record[18]=floatval($record[18]);
		$record[19]=floatval($record[19]);
		$record[20]=floatval($record[20]);
		$record[21]=floatval($record[21]);
	}
	
  }