<?php
  require_once (LIB_ROOT."classes/base/PageBase.class.php");
  require_once (ROOT_DIR."classes/model/HealthArticlesModel.php");

/**
 * This is the Health Articles class that posts a listing of articles
 * as links for the user to read
 * 
 * @author  Scott LePage slepage@shazamm.net
 * @version 1.1
 * @package classes.model
*/
  

	class HealthArticles extends PageBase {

		public function Index ($param) {
			$ha = new HealthArticlesModel();
			$articles = $ha->getFeaturedArticles();
			$template = TemplateParser::enqueue(TEMPLATE_DIR."frontend/HealthArticles/listing.tpt");
			$template->addVar("articles", $articles);

			return $template;
		}
	}
