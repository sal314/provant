<?php
	require_once (LIB_ROOT."classes/base/PageBase.class.php");
	require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
	require_once (ROOT_DIR."classes/model/UserTrackCholesterolModel.php");
	require_once (ROOT_DIR."classes/model/UserTrackBloodGlucoseModel.php");
	require_once (ROOT_DIR."classes/model/UserTrackBPModel.php");
	require_once (ROOT_DIR."classes/model/UserTrackWorkoutModel.php");
	require_once (ROOT_DIR."classes/model/UserProfileModel.php");
	require_once (ROOT_DIR."classes/model/FoodLogModel.php");
	require_once (ROOT_DIR.'classes/model/UserTrackerModel.php');



	/**
	*	@abstract - MyProgress reporting
	* @author - S.LePage
	*	@version - 1.1
	*/

class MyProgress extends PageBase {

	private $myId = 0;
	private $weight = null;
	private $chol = null;
	private $bp = null;
	private $bg = null;
	private $work = null;
	private $user = null;
	private $food = null;
	private $ifm = null;
	private $utm = null;


	public function __construct () {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit();
		}
		$this->myId = $this->cred->getId();
		$this->weight = new UserTrackWeightModel($this->myId);
		$this->chol = new UserTrackCholesterolModel($this->myId);
		$this->bp = new UserTrackBPModel($this->myId);
		$this->bg = new UserTrackBloodGlucoseModel($this->myId);
		$this->work = new UserTrackWorkoutModel($this->myId);
		$this->user = new UserProfileModel($this->myId);
		$this->food = new FoodLogModel($this->myId);
		$this->ifm = new IFocusModel($this->myId);
		$this->utm = new UserTrackerModel();
	}


	public function Index ($params) {
		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/Tracker/Progress/index.tpt");

		$iFocusComplete = $this->ifm->getCompleted();
		$if = 0;
		if (count($iFocusComplete) > 0) {
			$if = 1;
			$this->ifm = new IFocusModel($this->myId, false, $iFocusComplete[0]['value']);
			$scores = $this->ifm->score();
			$score = sprintf("%.1f", $scores['total']);
			$template->addVar("ifscore", $score);
		}
		$template->addVar("ifm", $if);
		
		$wt = $this->weight->getLastEntry();
		$template->addVar("weight", $wt['weight']);
		$template->addVar("wt_date", $wt['prog']);

		$bmi = $this->user->getBMI();
		$template->addVar("BMI", $bmi);

		$goal = $this->user->getGoalWeight();
		$template->addVar("goal", $goal);

		$dist = $this->user->getGoalWeightChange();
		$template->addVar("dist", $dist);

		$change = $this->user->getWeightChange();
		if ($change >= 0) {
			$template->addVar("direction", "lost");
		}
		else {
			$change = abs($change);
			$template->addVar("direction", "gained");
		}
		$template->addVar("change", $change);

		$incentive = $this->user->getIncentiveTotal();
		if ($incentive < 0) $incentive = 0;
		$template->addVar("incentive", $incentive);

		$cholesterol = $this->chol->getLastEntry();
		if ($cholesterol) {
			$cholesterol['hdl_ratio'] = sprintf("%.2f", ($cholesterol['total'] / $cholesterol['hdl']));
		}
		$template->addVar("cholesterol", $cholesterol);

		$bp = $this->bp->getLastEntry();
		$template->addVar("bp", $bp);
/*
		$bg = $this->bg->getLastEntry();
		$template->addVar("bg", $bg);
*/
		$activity = $this->work->getLastEntry();
		if (count($activity) > 0) {
			$activity['date_entered'] = date('M j, Y', strtotime($activity['date_entered']));
			$activity['calories'] = sprintf("%.1f", $activity['calories']);
			$template->addVar("activity", $activity);
		}
		else {
			$template->addVar("activity", "");
		}

		$food = $this->food->getLastEntry();
		if (count($food) > 0) {
			$food['date_entered'] = date('M j, Y', strtotime($food['date_entered']));
			$template->addVar("food", $food);
		}
		else {
			$template->addVar("food", "");
		}

		$diet = $this->food->getDailyRecommendedIntake();
		$diet['target'] = sprintf("%.1f", $diet['target']);
		$template->addVar("diet", $diet);

		$track = $this->utm->getLinks();
		$template->addVar('tracker', $track);
		return $template;
	}
}