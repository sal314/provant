<?php
require_once(LIB_ROOT."classes/common/Database.class.php");

/**
 * This is the health articles model to do the database access
 *
 * @author  Scott LePage slepage@shazamm.net
 * @version 1.2
 * @package classes.model
*/

	class HealthArticlesModel {

		private $dbOb=null;

		public function __construct () {
			$this->dbOb = Database::create();
		}

		public function getFeaturedArticles($lim = 0) {
			$today = date('Y-m-d') . " 00:00:00";
			if ($lim == 0) {
				$limit = "";
			}
			else {
				$limit = " LIMIT 0," . $lim;
			}
			$sql = "SELECT * FROM p_health_articles " .
				"WHERE is_active = 1 " .
				"AND ((start_view <= '" . $today . "' AND end_view >= '" . $today . "') OR start_view = '0000-00-00 00:00:00') " .
				"ORDER BY display_order" . $limit;
			$art = $this->dbOb->query($sql);
			return $art;
		}
	}