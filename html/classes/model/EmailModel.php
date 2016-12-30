<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (ROOT_DIR."classes/model/MessageModel.php");

/**
 * @package EmailModel
 * Allows the admin to send email
 * @author S.LePage
 *	13-Aug-2010
 */


class EmailModel {

	private $from;
	private $id;
	private $dbOb;
	private $cred;
	private $filters = array(array ('value' => '1',  'display' => "All Users"),
						 array ('value' => '2',  'display' => "Users with hypertension"),
						 array ('value' => '3',  'display' => "Users with unhealthy cholesterol")
			);


	public function __construct($altid=0) {
		$this->dbOb = Database::create();
		$this->cred = UserCredentials::load();
		if (!$altid) {
			$this->id = $this->dbOb->escape_string($this->cred->getId());
		}
		else {
			$this->id = $this->dbOb->escape_string($altid);
		}		
	}


	//
	//Get a list of companies that are active
	//
	public function getCompanies() {
		$sql = "SELECT company_name as display, id as value FROM p_company WHERE is_active = 1";
		$companies = $this->dbOb->query($sql);
		return $companies;
	}

	//
	//Get a list of extra filters defined by the functions below
	//
	public function getFilters() {
		return $this->filters;
	}


	public function getCompanyName($cid) {
		if ($cid == 0) {
			$cname = "All Companies";
		}
		else {
			$sql = "SELECT company_name FROM p_company WHERE id = " . $this->dbOb->escape_string($cid);
			$cname = $this->dbOb->getOne($sql);
		}
		return $cname;
	}


	public function getFilterName($fid) {
		foreach ($this->filters as $f) {
			if ($fid == $f['value']) {
				$name =  $f['display'];
				break;
			}
		}
		return $name;
	}


	//
	//Build a distribution list based on company and filter
	//
	public function getUserList($company_id, $filter) {
		if ($filter == 1) {
			$list = $this->getCompanyUsers($company_id);
		}
		else if ($filter == 2) {
			$list = $this->getUsersWithHypertension($company_id);
		}
		else if ($filter == 3) {
			$list = $this->getUsersWithBadCholesterol($company_id);
		}
//&&& STUB
		$list = array(array ('id' => '5', 'email' => 'scott@shazamm.net'));
//&&&
		return $list;
	}


	private function getCompanyUsers($company_id) {
		if ($company_id == 0) {
			$and = "";
		}
		else {
			$and = " AND u.company_id = " . $this->dbOb->escape_string($company_id);
		}
		$sql = "SELECT DISTINCT z.id, z.email FROM z_users AS z " .
				"JOIN u_profile AS u ON z.id = u.z_user_id " .
				"WHERE z.is_active = 1" . $and;
		$list = $this->dbOb->query($sql);
		return $list;
	}

	
	//
	// Build a distribution list based on whether the latest Blood Pressure
	// entry is high.
	//
	private function getUsersWithHypertension($company_id=false) {
		if ($company_id) {
			$join = " JOIN u_profile AS u ON z.id = u.z_user_id ";
			$and = " AND u.company_id = " . $this->dbOb->escape_string($company_id);
		}
		else {
			$join = "";
			$and = "";
		}
		$sql = "SELECT DISTINCT bp.z_user_id,z.email FROM u_tracker_bp AS bp " .
				"JOIN z_users AS z ON z.id = bp.z_user_id " . $join .
				"WHERE z.is_active = 1" . $and;
		$users = $this->dbOb->query($sql);
		$list = array();
		if (count($users) > 0) {
			foreach ($users as $user) {
				$sql = "SELECT systolic, diastolic FROM u_tracker_bp WHERE z_user_id = " . $user['z_user_id'] .
						" AND is_active = 1 ORDER BY date_entered DESC";
				$row = $this->dbOb->getRow($sql);
				if (($row['systolic'] >= 120) ||
					($row['diastolic'] >= 80)) {
					array_push($list, array ('id' => $user['z_user_id'],
											'email' => $user['email']));
				}
			}
		}
		return $list;
	}


	//
	// Build a distribution list based on whether the lastest entry for
	// cholesterol is bad.
	//
	private function getUsersWithBadCholesterol($company_id=false) {
		if ($company_id) {
			$join = " JOIN u_profile AS u ON z.id = u.z_user_id ";
			$and = " AND u.company_id = " . $this->dbOb->escapce_string($company_id);
		}
		else {
			$join = "";
			$and = "";
		}
		$sql = "SELECT DISTINCT ch.z_user_id,z.email FROM u_tracker_cholesterol AS ch " .
				"JOIN z_users AS z ON z.id = ch.z_user_id " . $join .
				"WHERE z.is_active = 1" . $and;
		$users = $this->dbOb->query($sql);
		$list = array();
		if (count($users) > 0) {
			foreach ($users as $user) {
				$sql = "SELECT total, hdl, ldl, triglycerides FROM u_tracker_cholesterol " .
						"WHERE z_user_id = " . $user['z_user_id'] .
						" AND is_active = 1 ORDER BY date_entered DESC";
				$row = $this->dbOb->getRow($sql);
				if (($row['total'] >= 200) ||
					($row['hdl'] <= 45) ||
					($row['ldl'] >= 130) ||
					($row['triglycerides'] >= 150)) {
					array_push($list, array('id' => $user['z_user_id'],
											'email' => $user['email']));
				}
			}
		}
		return $list;
	}


	// Function to send a message thru the internal messaging center as well
	// as sending an email to the input distribution list.
	public function sendBulkEmail ($list, $subj, $msg) {
		$mm = new MessageModel();

		$sql = "SELECT email, concat(first_name, ' ', last_name) as name FROM z_users WHERE id = " . $this->id;
		$admin = $this->dbOb->getRow($sql);

		// Set reply email address to 'NoReply'
		$pos = strpos($admin['email'], '@');
		if ($pos !== false) {
			$retAddr = "NoReply" . substr($admin['email'], $pos);
		}
		else {
			$retAddr = "NoReply";
		}

		foreach ($list as $user) {
			$arr = array ('to' => $user['id'],
						'subject' => $subj,
						'message' => $msg);
			$err = $mm->validateInfo($arr);
			if (!$err) {
				$mm->sendMessage(false);
			}

			$this->sendMail($user['email'], $msg, $subj, $retAddr, $admin['name'], false);
		}
		return;
	}


	// Send a single email to an input email address
	public function sendMail ($to, $body, $subject, $fromaddress, $fromname, $attachments=false)	{
		#
		# Setup common variables
		#
		$eol = "\r\n";
		$eoln = "\n";
		$mime_boundary = md5(time());

		#
		# Common Mail Headers
		#
		$headers  = "";
		$headers .= "From: " . $fromname . "<" . $fromaddress . ">" . $eoln;
		$headers .= "Reply-To: " . $fromname . "<" . $fromaddress . ">" . $eoln;
		$headers .= "Return-Path: " . $fromname . "<" . $fromaddress . ">" . $eoln;
		$headers .= "Message-ID: <" . time() . "-" . $fromaddress . ">" . $eoln;
		$headers .= "X-Mailer: PHP v" . phpversion() . $eoln;

		#
		# Boundry for marking the split & Multitype Headers
		#
		$headers .= 'MIME-Version: 1.0' . $eoln;
		$headers .= "Content-Type: multipart/mixed; boundary=\"" . $mime_boundary . "\"" . $eol . $eol;

		#
		# Open the first part of the mail
		#
		$msg = "--" . $mime_boundary . $eoln;
		$htmlalt_mime_boundary = $mime_boundary . "_htmlalt";

		#
		# Setup for text OR html -
		#
		$msg .= "Content-Type: multipart/alternative; boundary=\"" . $htmlalt_mime_boundary . "\"" . $eol . $eol;

		#
		# Text Version
		#
		$msg .= "This is a multi-part message in MIME format." . $eoln;
		$msg .= "--" . $htmlalt_mime_boundary . $eoln;
		$msg .= "Content-Type: text/plain; charset=iso-8859-1" . $eoln;
		$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
		$tmp = str_replace("<p>", "\n", $body);
		$tmp = str_replace("</p>", "\n", $tmp);
		$tmp = str_replace("<br />", "\n", $tmp);
		$msg .= strip_tags (str_replace ("<br>", "\n", substr ($tmp, (strpos ($tmp, "<body>") + 6)))) . $eol . $eol;

		#
		# HTML Version
		#
		$msg .= "--" . $htmlalt_mime_boundary . $eoln;
		$msg .= "Content-Type: text/html; charset=iso-8859-1" . $eoln;
		$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
		$msg .= $body . $eol . $eol;

		#
		# close the html/plain text alternate portion
		#
		$msg .= "--" . $htmlalt_mime_boundary . "--" . $eol . $eol;

		#
		# Process any attachments if requested
		#
		if ($attachments !== false) {
		    for($i = 0; $i < count ($attachments); $i++) {
				if (is_file ($attachments[$i]["file"])) {
				    #
				    # File for Attachment
				    #
				    $file_name = substr ($attachments[$i]["file"], (strrpos ($attachments[$i]["file"], "/") + 1));
				    $handle = fopen ($attachments[$i]["file"], 'rb');
				    $f_contents = fread ($handle, filesize ($attachments[$i]["file"]));
				    $f_contents = chunk_split (base64_encode ($f_contents));
				    $f_type = filetype ($attachments[$i]["file"]);
				    fclose ($handle);

				    #
				    # Attachment
				    #
				    $msg .= "--" . $mime_boundary . $eol;
			        $msg .= "Content-Type: " . $attachments[$i]["content_type"] . "; name=\"" . $file_name . "\"" . $eol;
				    $msg .= "Content-Transfer-Encoding: base64" . $eol;
				    $msg .= "Content-Description: " . $file_name . $eol;
				    $msg .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"" . $eol . $eol;
				    $msg .= $f_contents . $eol . $eol;
			    }
			}
		}

		#
		# Finish the mime
		#
		$msg .= "--" . $mime_boundary . "--" . $eol . $eol;

		#
		# Set the from address in the INI
		#
		ini_set ("sendmail_from", $fromaddress);

		#
		# Send the mail message
		#
		$mail_sent = mail ($to, $subject, $msg, $headers);

		#
		# Restore the from address in the INI
		#
		ini_restore ("sendmail_from");

		#
		# Return the mail send status
		#
		return $mail_sent;
	}
	
}