<?php
  require_once(LIB_ROOT."classes/base/PageBase.class.php");  
  require_once(LIB_ROOT."classes/common/Validator.class.php");
  require_once (ROOT_DIR."classes/model/UserProfileModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackWeightModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackCholesterolModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackBloodGlucoseModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackBPModel.php");
  require_once (ROOT_DIR."classes/model/UserTrackWorkoutModel.php");
  
/**
 * This is the default Admin class that handles the admin login.
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.model.admin
*/
  
  class ProgressReport extends PageBase{
  	public function Index($param){
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ProgressReport/index.tpt");
  			
  		$upm=new UserProfileModel($this->cred->getId());
  		$template->addVar("profile",$upm->getData());
  		$template->addVar("weight_change",$upm->getWeightChange());
  		$template->addvar("incentive_points",$upm->get("incentive_points_total"));
  		$gwc=$upm->getGoalWeightChange();
  		if($gwc==0) $gwc="at goal weight";
  		else if($gwc>0) $gwc=" Over by ".$gwc." lb";
  		else $gwc=" Under by ".abs($gwc)." lb";
  		$template->addVar("goal_weight_change",$gwc);
  			
  		$wm=new UserTrackWeightModel($this->cred->getId());
  		$le=$wm->getLastEntry();
  		$template->addVar("weight",$le);
  		$template->addVar("bmi",$upm->getBMI());
  			  		
  		$cm=new UserTrackCholesterolModel($this->cred->getId());
  		$le=$cm->getLastEntry();
  		$template->addVar("cholesterol",$le);
  			
  		$bpm=new UserTrackBPModel($this->cred->getId());
  		$le=$bpm->getLastEntry();
  		$template->addVar("bp",$le);

  		$bgm=new UserTrackBloodGlucoseModel($this->cred->getId());  			
  		$le=$bgm->getLastEntry();
  		$template->addVar("bg",$le);
  			
  		$wm=new UserTrackWorkoutModel($this->cred->getId());
  		$le=$wm->getLastEnteredExercises("all");  			
  		$template->addVar("ex",$le);
  		
  		return $template;
  	}
  	
  	public function TrackExercise(){
  		$wm=new UserTrackWorkoutModel($this->cred->getId());
  		$data=$wm->getEnteredExercises("cardio",1,"month");
  		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ProgressReport/track_exercises.tpt");  

  		$maxColWidth=400;
  		$unitRatio=$maxColWidth/$data->maxUnit;
  		$calRatio=$maxColWidth/$data->maxCal;
  		
  		foreach($data->exercises as &$ex){
  			$ex['unit_width']=floor($ex['units']*$unitRatio);
  			$ex['cal_width']=floor($ex['calories']*$calRatio);
  		}
  		$template->addVar("data",$data);  		
  	}
  	
  	public function WeeklySummary($param){
  		
  		$date=(isset($param[0]) && $param[0])?$param[0]:date("Y-m-d");
  		if(!preg_match("/^\d{4}-\d{2}-\d{2}\$/",$date)){
  			$date=date("Y-m-d");;
  		}
  		  		
  		$template=TemplateParser::enqueue(TEMPLATE_DIR."frontend/ProgressReport/weekly_summary.tpt");
  		$wm=new UserTrackWorkoutModel($this->cred->getId());
  		$ee=$wm->getEnteredExercisesForWeek($date);
  		$template->addVar("exercises",$ee);
  		
  		$days=array();
  		for($x=0;$x<7;$x++){
			$days[]=date("l F j, Y",strtotime("-".$x." days",strtotime($date)));
		}
  		
  		$template->addVar("days",$days);
  		return $template;
  	}
 }