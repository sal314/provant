<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class LabVoucherKit extends AdminPageBase{
	public function getBaseTableName(){return "u_lab_voucher_kit";}	
	
	public function Index($params){
		$template=TemplateParser::enqueue(TEMPLATE_DIR."admin/LVK/index.tpt");
		return $template;
	}

	public function download($param){
		$sql="SELECT * FROM u_lab_voucher_kit_order AS LVK WHERE LVK.is_downloaded=0";
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
			$sql="UPDATE u_lab_voucher_kit_order SET is_downloaded=1 WHERE is_downloaded=0 AND date_added<='".$this->dbOb->escape_string($last)."'";
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
		header("Content-Disposition: attachment; filename=lab_voucher_requests.csv;");
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
		/*AArray
(
    [0] => User Email
    [1] => Health Coach Login
    [2] => Date
    [3] => TOTALCHOL
    [4] => HDL
    [5] => CHOLRATIO
    [6] => LDL
    [7] => VLDL
    [8] => TRIGLY
    [9] => GLUCOSE
    [10] => SYSTOLIC
    [11] => DIASTOLIC
    [12] => BODYFAT
    [13] => BMISCORE
    [14] => Ht Ft
    [15] => Ht In
    [16] => Weight
    [17] => Nicotine
    [18] => Waist In
    [19] => Hip
    [20] => Fasting
    [21] => tobacco
    [22] => activity
    [23] => Match Y/N
)

*/
		$healthCoach=array();
		if(sizeof($columns)!=24)throw new Exception("Error csv column mismatch");
		$row=1;
		$err="";
		while (($record=fgetcsv($f,0,',','"'))!== false){//while !eof
			$row++;
			if(sizeof($record)!=24){
				$err.="Error Skipping Row ".$row." does not contain the correct column count.<br/>";
				continue;	
			}
			
			$sql="SELECT * FROM z_users WHERE UCASE(email)=UCASE(TRIM('".$this->dbOb->escape_string($record[0])."'))";
			$uid=$this->dbOb->getOne($sql);
			if(!$uid){
				$err.="Error Skipping row Row ".$row." invalid user login id/email.<br/>";
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
			$sql="INSERT INTO u_lab_voucher_kit_results (`z_user_id`,`entered_by`,`date_entered`,
				`total`,`hdl`,`ratio`,`ldl`,`vldl`,`triglycerides`,`glucose`,`systolic`,`diastolic`,`body_fat`,
				`bmi`,`height_ft`,`height_in`,`weight`,`nicotine`,`waist_in`,`hip`,`fasting`,`tobacco`,
				`activity`,`match_yn`) VALUES (
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
				'".$this->dbOb->escape_string($record[20])."',
				'".$this->dbOb->escape_string($record[21])."',
				'".$this->dbOb->escape_string($record[22])."',
				'".$this->dbOb->escape_string($record[23])."')";
			$this->dbOb->insert($sql);
			//add the tracker to the appropriate trackers

			$sql="UPDATE u_lab_voucher_kit_order set is_received=1 WHERE z_user_id='".$this->dbOb->escape_string($uid)."'";
			$this->dbOb->update($sql);
			
			//weight
			if($record[4]>0){
				$sql="SELECT * FROM u_tracker_weight WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
				if(!$this->dbOb->getOne($sql)){
					$sql="INSERT INTO u_tracker_weight(z_user_id,entered_by,date_entered,weight) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[4])."')";
					$this->dbOb->insert($sql);
				}//else weight was already entered for this date should we acept the entered one as is or update it with the new value?
			}
			
			//cholesterol
			if($record[3] && $record[4] && $record[6] && $record[8]){
				$sql="SELECT * FROM u_tracker_cholesterol WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
				if(!$this->dbOb->getOne($sql)){
					$sql="INSERT INTO u_tracker_cholesterol(z_user_id,entered_by,date_entered,total,hdl,ldl,triglycerides ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[3])."',
					'".$this->dbOb->escape_string($record[4])."',
					'".$this->dbOb->escape_string($record[6])."',
					'".$this->dbOb->escape_string($record[8])."')";
					$this->dbOb->insert($sql);
				}
				//else cholesterol was already entered for this date should we acept the entered one as is or update it with the new values?
			}					
			/* Not sure what the glucose / fasting columns is so we are not going to add the  blood glucose just yet
			 * Assuming fasting is the fasting column and glucose is the random.  
			 * The fasting may be whether or not the glucose was fasting or random 
			//blood glucose
			$sql="SELECT * FROM u_tracker_blood_glucose WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
			if(!$this->dbOb->query($sql)){
				if($record[20]){
					$sql="INSERT INTO u_tracker_blood_glucose(z_user_id,entered_by,date_entered,time_entered,blood_glucose,method ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'12:00',
					'".$this->dbOb->escape_string($record[20])."',
					'fasting')";
					$this->dbOb->insert($sql);
				}
				
				if($record[9]){
					$sql="INSERT INTO u_tracker_blood_glucose(z_user_id,entered_by,date_entered,time_entered,blood_glucose,method ) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'12:00',
					'".$this->dbOb->escape_string($record[9])."',
					'random')";					
					$this->dbOb->insert($sql);
				}
				//else blood glucose was already entered for this date should we acept the entered one as is or update it with the new values?
			}
			*/
			//blood pressure
			if($record[10] && $record[11]){
				$sql="SELECT * FROM u_tracker_bp WHERE date_entered='".$this->dbOb->escape_string($de)."' AND z_user_id='".$this->dbOb->escape_string($uid)."'";
				if(!$this->dbOb->getOne($sql)){
					$sql="INSERT INTO u_tracker_bp(z_user_id,entered_by,date_entered,systolic,diastolic) VALUES(
					'".$this->dbOb->escape_string($uid)."',
					'".$this->dbOb->escape_string($hcId)."',
					'".$this->dbOb->escape_string($de)."',
					'".$this->dbOb->escape_string($record[10])."',
					'".$this->dbOb->escape_string($record[11])."'
					)";
					$this->dbOb->insert($sql);
				}
			}
			//else blood pressure was already entered for this date should we acept the entered one as is or update it with the new values?
		}
		
		$ft=intval($record[14]);
		$in=intval($record[15]);
			if($ft>=3 && $ft<9 && $in<12 && $in>=0){
				$sql="UPDATE u_profile set height_ft='".$ft."', height_in='".$in."' WHERE z_user_id='".$this->dbOb->escape_string($uid)."'";
				$this->dbOb->update($sql);
		}
		
		print $err;
		die("Done");
	}
	
	private function validateData(&$record){
		/*
	[3] => TOTALCHOL
    [4] => HDL
    [5] => CHOLRATIO
    [6] => LDL
    [7] => VLDL
    [8] => TRIGLY
    [9] => GLUCOSE
    [10] => SYSTOLIC
    [11] => DIASTOLIC
    [12] => BODYFAT
    [13] => BMISCORE
    [14] => Ht Ft
    [15] => Ht In
    [16] => Weight
    [17] => Nicotine
    [18] => Waist In
    [19] => Hip
    [20] => Fasting
    [21] => tobacco
    [22] => activity
    [23] => Match Y/N
    */
		$record[3]=intval($record[3]);
		$record[4]=intval($record[4]);
		$record[5]=floatval($record[5]);
		$record[6]=intval($record[6]);
		//$record[7]=intval($record[7]);
		$record[8]=intval($record[8]);
		$record[9]=intval($record[9]);
		$record[10]=intval($record[10]);
		$record[11]=intval($record[11]);
		$record[12]=floatval($record[12]);
		$record[13]=floatval($record[13]);
		$record[14]=intval($record[14]);
		$record[15]=intval($record[15]);
		$record[16]=floatval($record[16]);
		//$record[17]=intval($record[17]);
		$record[18]=intval($record[18]);
		$record[19]=intval($record[19]);
		$record[20]=floatval($record[20]);
		//$record[21]=floatval($record[21]);
		//$record[22]=floatval($record[21]);
		//$record[23]=floatval($record[21]);
	}
  }