<?php
require_once ($_SERVER['DOCUMENT_ROOT']."/classes/page/frontend/CronJob.php");
require_once ($_SERVER['DOCUMENT_ROOT']."/system/configuration.php");


$email = new CronJob();

$email->users();


?>