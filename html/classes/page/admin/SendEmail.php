<?php
require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");
require_once (ROOT_DIR."classes/model/EmailModel.php");

class SendEmail extends AdminPageBase {

	public function getBaseTableName() { return ""; }


	public function Index ($params) {

		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")) throw new Exception("Only Administrators Can perform this function!");
		$email = new EmailModel();

		if (isset($_POST['company_id'])) {
			$template = TemplateParser::enqueue(TEMPLATE_DIR."admin/Email/compose.tpt");
			$template->addVar("company_id", $_POST['company_id']);
			$template->addVar("filter", $_POST['filter']);
			$template->addVar("company_name", $email->getCompanyName($_POST['company_id']));
			$template->addVar("filter_name", $email->getFilterName($_POST['filter']));
		}
		else {
			$companies = $email->getCompanies();
			$filters = $email->getFilters();

			$template = TemplateParser::enqueue(TEMPLATE_DIR."admin/Email/index.tpt");
			$template->addVar("companies", $companies);
			$template->addVar("filters", $filters);
			if (isset($_GET['sent'])) {
				$template->addVar("sent", $_GET['sent']);
			}
		}
		return $template;
	}


	public function SendMessage($params) {
		if(!$this->cred->isAdmin() && !$this->cred->has("SITE_ADMIN")) throw new Exception("Only Administrators Can perform this function!");
		$email = new EmailModel();

		$list = $email->getUserList($_POST['company_id'], $_POST['filter']);
		$msg = str_replace("\n", "<br>", $_POST['message']);
		$msg = "<body>" . $msg . "</body>";
		$email->sendBulkEmail($list, $_POST['subject'], $msg);

		header("Location: /admin/SendEmail/Index&sent=success");
		exit();
	}
}
