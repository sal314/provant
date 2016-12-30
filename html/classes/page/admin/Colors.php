<?php
require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");
require_once (ROOT_DIR."classes/model/ColorsModel.php");

/**
 * 
 * @author: S.LePage
 * @name: Colors.php
 * @abstract: Allows the admin to select color schemes for a company
 * @version 1.1
 * 
 */

class Colors extends AdminPageBase {

	private $mod = null;


	public function getBaseTableName() { return ""; }


	public function Index($params) {
		if (isset($params[0])) {
			$cid = $params[0];
		}
		else {
			throw new Exception ("Company identifier required");
		}

		$this->mod = new ColorsModel($cid);

		if (count($_POST) > 0) {

			$colors['background'] = sprintf("#%02X%02X%02X", $_POST['background_r'], $_POST['background_g'], $_POST['background_b']);
			$colors['tab1'] = sprintf("#%02X%02X%02X", $_POST['tab1_r'], $_POST['tab1_g'], $_POST['tab1_b']);
			$colors['tab2'] = sprintf("#%02X%02X%02X", $_POST['tab2_r'], $_POST['tab2_g'], $_POST['tab2_b']);
			$colors['tab3'] = sprintf("#%02X%02X%02X", $_POST['tab3_r'], $_POST['tab3_g'], $_POST['tab3_b']);
			$colors['tab4'] = sprintf("#%02X%02X%02X", $_POST['tab4_r'], $_POST['tab4_g'], $_POST['tab4_b']);
			$colors['articles'] = sprintf("#%02X%02X%02X", $_POST['articles_r'], $_POST['articles_g'], $_POST['articles_b']);
			$colors['color1'] = sprintf("#%02X%02X%02X", $_POST['color1_r'], $_POST['color1_g'], $_POST['color1_b']);
			$colors['color2'] = sprintf("#%02X%02X%02X", $_POST['color2_r'], $_POST['color2_g'], $_POST['color2_b']);
			
			$this->mod->saveColors($colors);
			$msg = "Colors Saved";
		}
		else {
			$colors = $this->mod->getColors();
			$msg = "Edit Colors";
		}

		$template = TemplateParser::enqueue(TEMPLATE_DIR."admin/Colors/index.tpt");
		$template->addVar("message", $msg);
		$template->addVar("cid", $cid);
		$template->addVar("colors", $colors);
		foreach($colors as $k => $color) {
			$i = 0;
			foreach (array('r', 'g', 'b') as $c) {
				$var = $k . "_" . $c;
				$off = ($i * 2) + 1;
				$val = sscanf(substr($color, $off, 2), '%X');
				$template->addVar($var, $val[0]);
				$i++;
			}
		}

		return $template;
	}
}