<?php
//Is this an RSS feed request?
include_once($_SERVER["DOCUMENT_ROOT"]."/system/configuration.php");
if(defined("DB_SESSION")){
  	require_once (LIB_ROOT."classes/common/SessionManager.class.php");  	  
}
session_start();
if(!isset($_SERVER['REDIRECT_URL'])){
	error_log(var_export($_SERVER,true));
}

//$timeout = 30;				// in minutes
$timeout = 480;
$current = time();
if ((preg_match("/register\//i", $_SERVER['REDIRECT_URL'])) ||
		(preg_match("/landing\/logout/i", $_SERVER['REDIRECT_URL']))) {
	if (isset($_SESSION['expire'])) {
		unset($_SESSION['expire']);
	}
}
else {
	$timeout *= 60;		// in seconds
	if (isset($_SESSION['expire'])) {
		if ($_SESSION['expire'] < $current) {
			unset($_SESSION['expire']);
			header("Location: /Landing/logout");
			exit();
		}
		else {
			$_SESSION['expire'] = $current + $timeout;
		}
	}
	else {
		$_SESSION['expire'] = $current + $timeout;
	}
}

if(preg_match("/^\/admin\//",$_SERVER['REDIRECT_URL'])||preg_match("/^\/admin$/",$_SERVER['REDIRECT_URL'])){
  include(LIB_ROOT."classes/controller/AdminController.class.php");
  $page= new AdminController();
  $page->setDebugLevel(5);
  $page->processCommands();	
}else if(preg_match("/^\/ws\//",$_SERVER['REDIRECT_URL'])){
   include(LIB_ROOT."classes/controller/WSController.class.php");
   $page= new WSController();
   	$page->setDebugLevel(0);
	$page->processCommands();   
}
else if(preg_match("/rss\//",$_SERVER['REDIRECT_URL'])){	
	require_once(LIB_ROOT."classes/common/RssFeedGenerator.class.php");
	$RSSFeed=new RSSFeedGenerator();
	//remove the rss/ prefix and the .xml suffix to get the feed name
	$title=substr($_REQUEST['parameters'],4,sizeof($_REQUEST['parameters'])-5);
	$RSSFeed->getFeedForChannel($title);
}else if(preg_match("/register\//i",$_SERVER['REDIRECT_URL'])){
	/** 
	 * 	Ok the register url is is /register/{company name}
     *   We can't name functions to match all company names like 3m 3com
     *   And I don't want to dynamicaly add methods to the class
     *   We will need to play with the re-write mechanism to insert "Index" before the company name
     *   if it is register/coname => register/index/coname
     *   register/action/coname will work just fine       
	 */
	$pieces=preg_split("~/~",$_SERVER['REQUEST_URI']);	
	if(sizeof($pieces)==3){	
		$_REQUEST['parameters']=$pieces[1]."/Index/".$pieces[2];	
	}		
	include(LIB_ROOT."classes/controller/PageController.class.php");
	$page= new PageController();
	$page->setDebugLevel(0);
	$page->processCommands();
}else{	
	include(LIB_ROOT."classes/controller/PageController.class.php");
	$page= new PageController();
	$page->setDebugLevel(0);
	$page->processCommands();
}

exit();