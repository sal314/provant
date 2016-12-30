<?php
require_once (LIB_ROOT."classes/common/Database.class.php");
require_once (LIB_ROOT."classes/common/UserCredentials.class.php");

/**
 * @name: ColorsModel.php
 * @author: S.LePage
 * @abstract: The model for the color picker application
 * @version: 1.1
 */

class ColorsModel {
	
	private $dbOb = null;
	private $cred = null;
	private $colors = array();
	private $company_id = 0;

	public function __construct($cid) {

		$this->dbOb = Database::create();
		$this->cred = UserCredentials::load();
/*
		if (!$this->cred->isAdmin()) {
			throw new Exception("Only admin can use this class");
		}
*/
		$this->company_id = $this->dbOb->escape_string($cid);

		$sql = "SELECT color_background, color_color1, color_color2, color_articles, " .
						"color_tab1, color_tab2, color_tab3, color_tab4 " .
					"FROM p_company WHERE id = " . $this->company_id . " " .
					"AND is_active = 1";
		$row = $this->dbOb->getRow($sql);

		if ((count($row) == 0) || ($row['color_background'] == "")) {
			$this->colors['background'] = "#6CA726";
			$this->colors['color1'] = "#ACE756";
			$this->colors['color2'] = "#6CA726";
			$this->colors['articles'] = "#D7E7C4";
			$this->colors['tab1'] = "#6AA62A";
			$this->colors['tab2'] = "#D5BA1A";
			$this->colors['tab3'] = "#6DB4B8";
			$this->colors['tab4'] = "#6DB4B8";
//			$this->colors['tab1'] = "#ACE756";
//			$this->colors['tab2'] = "#D9B20D";
//			$this->colors['tab3'] = "#2FBED1";
//			$this->colors['tab4'] = "#1742EE";
		}
		else {
			$this->colors['background'] = $row['color_background'];
			$this->colors['color1'] = $row['color_color1'];
			$this->colors['color2'] = $row['color_color2'];
			$this->colors['articles'] = $row['color_articles'];
			$this->colors['tab1'] = $row['color_tab1'];
			$this->colors['tab2'] = $row['color_tab2'];
			$this->colors['tab3'] = $row['color_tab3'];
			$this->colors['tab4'] = $row['color_tab4'];
		}

		return;
	}


	public function getColors() {
		return $this->colors;
	}


	public function saveColors($colors) {
		foreach($colors as $k => $c) {
			$this->colors[$k] = $c;
		}

		$sql = "UPDATE p_company SET " .
					"color_background = '" . $this->dbOb->escape_string($colors['background']) . "', " .
					"color_color1 = '" . $this->dbOb->escape_string($colors['color1']) . "', " .
					"color_color2 = '" . $this->dbOb->escape_string($colors['color2']) . "', " .
					"color_articles = '" . $this->dbOb->escape_string($colors['articles']) . "', " .
					"color_tab1 = '" . $this->dbOb->escape_string($colors['tab1']) . "', " .
					"color_tab2 = '" . $this->dbOb->escape_string($colors['tab2']) . "', " .
					"color_tab3 = '" . $this->dbOb->escape_string($colors['tab3']) . "', " .
					"color_tab4 = '" . $this->dbOb->escape_string($colors['tab4']) . "' " .
					"WHERE id = " . $this->company_id . " " .
					"AND is_active = 1";
		$this->dbOb->update($sql);
		$this->generateCSS();
		return;
	}


	private function generateCSS() {
		$colorsDir = "/var/www/provant/html/assets/css/colors/";
		$template = $colorsDir . "template.css";
		$css_tmp = file_get_contents($template);

		$cssname = "color_scheme_" . $this->company_id . ".css";
		$fname = $colorsDir . $cssname;
		$fh = fopen ($fname, "w");
		fwrite($fh, "/*	file:       " . $cssname . "                                   */\n");
		fwrite($fh, "/*	created:    " . date('d-M-Y') . "                              */\n");
		fwrite($fh, "/*                                                                */\n");
		fwrite($fh, "/*################################################################*/\n");
		fwrite($fh, "/*##                                                            ##*/\n");
		fwrite($fh, "/*##  DO NOT EDIT THIS FILE.  THIS IS AUTOMATICALLY GENEREATED  ##*/\n");
		fwrite($fh, "/*##  BY THE ADMINISTRATORS OPTIONS.  ALL CHANGES MADE WILL BE  ##*/\n");
		fwrite($fh, "/*##  LOST.  YOU HAVE BEEN WARNED!!                             ##*/\n");
		fwrite($fh, "/*##                                                            ##*/\n");
		fwrite($fh, "/*################################################################*/\n");
		fwrite($fh, "/*                                                                */\n\n");

		$output = $css_tmp;
		foreach($this->colors as $key => $replace) {
			$token = "%" . $key . "%";
			while ($pos = stripos($output, $token)) {
				$output = substr_replace($output, $replace, $pos, strlen($token));
			}
		}
		fwrite($fh, $output);

		fwrite($fh, "/* Done. */\n");
		fclose($fh);
	}
}