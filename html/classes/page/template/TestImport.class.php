<?php
require_once (LIB_ROOT."classes/base/TemplatePopulator.class.php");

class TestImport extends TemplatePopulator{
	public function run(&$template){
		$results=$this->dbOb->query("SELECT * FROM test_data ORDER BY name");		
		$template->addVar("subresults",$results);	
	}	
}
