<?php
define("LIB_ROOT","/var/www/default/zmedia_3.0/lib/");
//define("COMMON_TEMPLATE_ROOT",LIB_ROOT."templates/");

/* Correct the domain info here
 * This is if we need to run an extrenal process (sh or php) from outside the current process
 *  Set Document Root if we are kicking off a ssh script for this site (like the blast or index).
 *  Set the domain also as an ssh script will not have the correct domain info.
*/
if(!isset($_SERVER['DOCUMENT_ROOT'])||$_SERVER['DOCUMENT_ROOT']==""){
  	$_SERVER['DOCUMENT_ROOT']="/var/www/provant/html";
  	$_SERVER["HTTP_HOST"]="provant.shazamm.net";
}


define("DOCUMENT_ROOT",$_SERVER['DOCUMENT_ROOT']);
define("ROOT_DIR",DOCUMENT_ROOT."/");
define("TEMPLATE_DIR",DOCUMENT_ROOT."/templates/");
define("COMMON_TEMPLATE_ROOT", DOCUMENT_ROOT."/templates/");
define("DOMAIN_NAME", "dev.provant.internal");

define("ITEMS_PER_PAGE",25);

define("DEFAULT_PAGE", "Landing");
define("DEFAULT_ACTION", "Index");

define("DEBUG","1");
define("REQUIRE_SESSION_COOKIES",0);
/*Correct the db config here*/

define("DATABASE_HOST",'localhost');
define("DATABASE_USER",'provant');
define("DATABASE_PASSWORD",'ZuZaqYADLrKRH5Qv');
define("DATABASE_NAME",'provant');

define("DB_SESSION",1);

define("ADMIN_EMAIL","webmaster@shazamm.net");
//define("FILE_BROWSER_SHOW_STATS",1);
//define("APC_CACHE_KEY", "DOMAINNAME_"); //uncomment this when the table structure is defined to enable caching

define("IT_STUB",true);		//stub out the week contraint on the IT Modules
