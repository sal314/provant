<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class Exercises extends AdminPageBase{

	public function getBaseTableName(){return "p_workout_exercises";}	
    
	public function Insert($params){		
		return parent::Insert($params,TEMPLATE_DIR."admin/Exercises/insert.tpt");
	}
  
  	public function Edit($params){
		return parent::Edit($params,TEMPLATE_DIR."admin/Exercises/edit.tpt");
	}
	
	public function FindExercise($params){
		require_once(LIB_ROOT."classes/common/Ajax.class.php");
  		$ajax=new Ajax();
		try{
			$name=isset($_POST['value'])?$_POST['value']:null;
			$src=isset($_POST['src'])?$_POST['src']:null;
			$category=isset($_POST['cat'])?$_POST['cat']:null;
			$equipment=isset($_POST['equip'])?$_POST['equip']:null;
			
			if(!$src || !$name ||!$category || !$equipment){
  		 	 	$ajax->addResponseMessage("Error",Ajax::ERROR,"Missing required parameters.");
  		 	 	$ajax->writeResponseXML();
  		 	 	exit;				
  		 	}
			
			$sql="SELECT concat(id,'|',category,'|',equipment) as value, 
						name as selected, 
						concat(name,' ',category,' ',equipment) as display 
				  FROM p_workout_exercises WHERE ";  
			if($equipment!="ANY") $sql.="equipment='".$this->dbOb->escape_string($equipment)."' AND ";
			if($category!="ANY")  $sql.="category='".$this->dbOb->escape_string($category)."' AND ";
			$sql.=" name like '%".$this->dbOb->escape_string($name)."%' 
					AND is_active=1 ORDER BY name LIMIT 0,10
			 ";
			
			error_log($sql);
			
			$data=$this->dbOb->query($sql);
			$ajax->addResponseMessage("Success",Ajax::SUCCESS,"");
  		 	$ajax->addResponseData("values",$data);
  		 	$ajax->addResponseData("src",$src);
			
		}catch(Exception $e){
			$ajax->addResponseMessage("Error",Ajax::ERROR,$e->getMessage());
		}
		$ajax->writeResponseXML();
  		exit;				  		 		  		 	
		
	}
  }