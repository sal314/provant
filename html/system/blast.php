<?php
// FIRST GET ARGUMENTS FROM COMMAND LINE
  $id = $argv[1];
  set_time_limit(0);
  ignore_user_abort(1);

  $path=$_SERVER['PHP_SELF'];

  preg_match_all("/(.*)\/system/",$path,$dir);
  $site=$dir[1][0];

  require("configuration.php");
  require(LIB_ROOT."/classes/common/BlastEmailer.class.php");

  $blastEmailer=new BlastEmailer();
  $blastEmailer->setEmbedMedia(true);
  $blastEmailer->sendBlast($id);
  exit;
?>