<?php
  require_once (LIB_ROOT."classes/base/AdminPageBase.class.php");  
  
  /**
 * This is the default Action admin class
 * @author  Eric Joyce ejoyce@shazamm.net
 * @version 1.1
 * @package classes.page.admin
*/

  class UserOptionRace extends AdminPageBase{

	public function getBaseTableName(){return "p_user_option_race";}	
  }