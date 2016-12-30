<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  S.LePage
 * @version 1.1
 * @package classes.page.admin
*/

class UserOptionOtherLanguages extends AdminPageBase{

	public function getBaseTableName(){return "p_other_languages";}	
}
