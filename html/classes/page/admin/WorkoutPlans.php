<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class WorkoutPlans extends AdminPageBase{

	public function getBaseTableName(){return "p_workout_plans";}	
    
	public function Insert($params){		
		return parent::Insert($params,TEMPLATE_DIR."admin/WorkoutPlans/insert.tpt");		
	}
  
   public function InsertRec($params){
   	
		$id=parent::InsertRec($params,false);
		foreach($_POST['addedExercises'] as $key=>$value){
			$values=preg_split("/\|/",$value);
			$sql="INSERT INTO p_workout_plan_exercises(p_workout_plan_id,p_workout_exercises,day,reps,sets) VALUES (
				'".$this->dbOb->escape_string($id)."',
				'".$this->dbOb->escape_string($values[1])."',
				'".$this->dbOb->escape_string($values[0])."',
				'".$this->dbOb->escape_string($values[2])."',
				'".$this->dbOb->escape_string($values[3])."')";
			$this->dbOb->insert($sql);
		}
		$this->forceRedirect(true);
	}
  	public function Edit($param){  		
		$template=parent::Edit($param,TEMPLATE_DIR."admin/WorkoutPlans/insert.tpt");
		$sql="SELECT ex.id,ex.name,ex.category,ex.equipment,pex.sets,pex.reps,pex.day 
			  FROM p_workout_plan_exercises as pex
			  JOIN p_workout_exercises as ex ON ex.id=pex.p_workout_exercises
			  WHERE p_workout_plan_id='".$this->dbOb->escape_string($param[0])."'
			  ORDER BY pex.day,ex.name
			  ";
		$template->addVar("exercises",$this->dbOb->query($sql));
		return $template;
	}
	
	public function Change($params){
		parent::Change($params,false);
		$id=$_POST['p_workout_plans']['id'];
		$sql="DELETE FROM p_workout_plan_exercises WHERE  p_workout_plan_id='".$this->dbOb->escape_string($id)."'";
		$this->dbOb->update($sql);
				
		foreach($_POST['addedExercises'] as $key=>$value){
			$values=preg_split("/\|/",$value);
			$sql="INSERT INTO p_workout_plan_exercises(p_workout_plan_id,p_workout_exercises,day,reps,sets) VALUES (
				'".$this->dbOb->escape_string($id)."',
				'".$this->dbOb->escape_string($values[1])."',
				'".$this->dbOb->escape_string($values[0])."',
				'".$this->dbOb->escape_string($values[2])."',
				'".$this->dbOb->escape_string($values[3])."')";
			$this->dbOb->insert($sql);
		}
		$this->forceRedirect(true);
	}
	
  }