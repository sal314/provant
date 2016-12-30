<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");

/**
 * This is the HealthyAchievment class to show details of the company's incentive program.
 * @author  Scott LePage scott@shazamm.net
 * @version 1.1
 * @package classes.page.frontend
*/

	class HealthyAchievements extends PageBase {

		public function Index($param) {
			if (!$this->cred->getLoginStatus()) {
				header("Location: /Landing/Index");
				exit(0);
			}

//&&& Stub
//$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/HealthyAchievements/stub.tpt");
//return $template;

			$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/HealthyAchievements/activities.tpt");

			$up = new UserProfileModel($this->cred->getId());
			$profile_data = $up->getData();
			$company_id = $profile_data['company_id'];

			$sql="SELECT * FROM p_incentive_program WHERE ".
					"company_id=".$this->dbOb->escape_string($company_id).
					" AND is_active=1".
					" AND start_date <= NOW()".
					" AND (end_date = '0000-00-00' OR end_date >= NOW())";
			$pgm=$this->dbOb->getRow($sql);
			if (!$pgm) {
				throw new Exception("No active incentive program found for your company");
			}

			$sql="SELECT * FROM p_incentive_triggers AS pit".
					" JOIN p_incentive_activity AS pia ON pit.incentive_activity_id=pia.id".
					" WHERE pit.incentive_program_id=".$this->dbOb->escape_string($pgm['id']).
					" AND pia.is_active=1".
					" AND pia.module <> 'Self-reporting'";
			$pgm_activities=$this->dbOb->query($sql);
			if (!$pgm_activities) {
				throw new Exception("No activities for this incentive program");
			}

			$sql="SELECT pit.id as tid,pit.frequency as freq,pia.id as aid,pia.description as description,pit.points as points".
					" FROM p_incentive_triggers AS pit".
					" JOIN p_incentive_activity AS pia ON pit.incentive_activity_id=pia.id".
					" WHERE pit.incentive_program_id=".$this->dbOb->escape_string($pgm['id']).
					" AND pia.is_active=1".
					" AND pia.module = 'Self-reporting'";
			$pgm_self=$this->dbOb->query($sql);

			$sql="SELECT * FROM p_incentive_triggers_log".
					" WHERE z_user_id=".$profile_data['z_user_id'];
			$logs=$this->dbOb->query($sql);
			if ($pgm_self) {
				foreach($pgm_self as $key=>$val) {
					if ($pgm_self[$key]['freq'] == "onetime") {
						$pgm_self[$key]['showcheck'] = 1;
						foreach($logs as $log) {
							if ($log['p_incentive_triggers_id'] == $pgm_self[$key]['tid']) {
								$pgm_self[$key]['showcheck'] = 0;
							}
						}
					}
					else if ($pgm_self[$key]['freq'] == "monthly") {
						$pgm_self[$key]['showcheck'] = 1;
						foreach($logs as $log) {
							if ($log['p_incentive_triggers_id'] == $pgm_self[$key]['tid']) {
								$limit = date('Y-m-d', (time() - (30 * 24 * 60 * 60)));
								if (substr($log['date_added'], 0, 10) > $limit) {
									$pgm_self[$key]['showcheck'] = 0;
								}
							}
						}
					}
					else if ($pgm_self[$key]['freq'] == 'weekly') {
						$pgm_self[$key]['showcheck'] = 1;
						foreach($logs as $log) {
							if ($log['p_incentive_triggers_id'] == $pgm_self[$key]['tid']) {
								$limit = date('Y-m-d', (time() - (7 * 24 * 60 * 60)));
								if (substr($log['date_added'], 0, 10) > $limit) {
									$pgm_self[$key]['showcheck'] = 0;
								}
							}
						}
					}
					else if ($pgm_self[$key]['freq'] == 'daily') {
						$pgm_self[$key]['showcheck'] = 1;
						foreach($logs as $log) {
							if ($log['p_incentive_triggers_id'] == $pgm_self[$key]['tid']) {
								$limit = date('Y-m-d', (time() - (1 * 24 * 60 * 60)));
								if (substr($log['date_added'], 0, 10) > $limit) {
									$pgm_self[$key]['showcheck'] = 0;
								}
							}
						}
					}
				}
			}

			$template->addVar("title", $pgm['page_text_healthy_habits']);
			$template->addVar("acts", $pgm_activities);
			if ($pgm_self) {
				$template->addVar("z_user_id", $profile_data['z_user_id']);
				$template->addVar("sacts", $pgm_self);
			}

			return $template;
		}

		public function AddSelfReporting ($param) {
			$action = false;

			$uid = isset($_POST['z_user_id'])?intval($_POST['z_user_id']):0;
			$sql = "SELECT company_id FROM u_profile".
					" WHERE z_user_id=".$this->dbOb->escape_string($uid).
					" AND is_active=1";
			$cid = $this->dbOb->getRow($sql);
			if (!$cid) {
				throw new Exception("User id ".$uid." has no company assigned");
			}

			$sql = "SELECT id FROM p_incentive_program".
					" WHERE company_id=".$cid['company_id'].
					" AND is_active=1".
					" AND start_date <= NOW()".
					" AND (end_date = '0000-00-00' OR end_date >= NOW())";
			$pgm_id = $this->dbOb->getRow($sql);
			if (!$pgm_id) {
				throw new Exception("Company (".$cid['company_id'].") does not have an active incentive program");
			}

			for($i=0;;$i++) {

				if (isset($_POST['self_'.$i])) {
					if ($_POST['self_'.$i] == "on") {
						$aid = $_POST['act_'.$i];

						$sql = "SELECT id,points FROM p_incentive_triggers".
								" WHERE incentive_program_id=".$pgm_id['id'].
								" AND incentive_activity_id=".$this->dbOb->escape_string($aid);
						$pit = $this->dbOb->getRow($sql);
						if (!$pit) {
							throw new Exception("No trigger found for PgmId=".$pgm_id['id']." and ActivityId=".$aid);
						}

						$sql = "INSERT INTO p_incentive_triggers_log (z_user_id, p_incentive_triggers_id, points)".
								"VALUES (".$this->dbOb->escape_string($uid).",".
										$pit['id'].",".$pit['points'].")";
						$this->dbOb->insert($sql);

						$sql = "UPDATE u_profile SET incentive_points_total=incentive_points_total+".$pit['points'].
								" WHERE z_user_id=".$this->dbOb->escape_string($uid);
						$this->dbOb->update($sql);

						$action = true;
					}
				}
				else {
					break;
				}
			}

			if ($action)
				$params="mode=success";
			else
				$params="mode=no_action_taken";

			header("Location: /HealthyAchievements/Index?".$params);
			exit(0);
		}

		
		/**
		 * 
		 * Method to make a request to the administrator to redeem acumulated points.
		 * @param $param
		 * 
		 * 		&&& TBD &&&
		 */
		public function RequestPointsRedemtion($param) {
			
		}
	}

