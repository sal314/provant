<?php
	require_once (LIB_ROOT."classes/base/PageBase.class.php");
	
	
	
class PortionPlate extends PageBase {

	public function __construct() {
		parent::__construct();
		if (!$this->cred->getLoginStatus()) {
			header("Location: /Landing/Index");
			exit();
		}
	}

	public function Index ($params) {
		$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/portionplate/index.tpt");
		return $template;
	}
}
