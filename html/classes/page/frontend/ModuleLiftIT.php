<?php
require_once (LIB_ROOT."classes/base/PageBase.class.php");
require_once (ROOT_DIR."classes/model/BreakIT.php");

class ModuleLiftIT  extends PageBase{
	public function Index($param){	
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit(0);
		}
	}
}
