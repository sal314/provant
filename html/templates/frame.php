<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="title" content="Site" />
    <meta name="robots" content="index, follow" />
    <meta name="language" content="en" />
<?php
  require_once (ROOT_DIR."classes/model/TitleModel.php");
  $tm = new TitleModel();
	$title = $tm->getTitleString($_SERVER['REDIRECT_URL']);
?>
    <title><?php echo $title; ?></title>
   	<link rel="stylesheet" type="text/css" media="screen" href="/assets/css/main.css" /> 
   	<link rel="stylesheet" type="text/css" media="screen" href="/assets/css/other.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="/assets/css/forms.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="/assets/css/colorbox.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="/assets/css/colortip-1.0-jquery.css" />
   	<!--[if lte IE 7]>
	<link rel="stylesheet" type="text/css" media="screen" href="/css/ie6_style.css" /> 
	<![endif]-->
	<!--[if IE 7]>
	<style type"text/css" media="screen">
		.datagrid td.question {width:auto}
	</style>
	<![endif]-->
	<!--[if IE 8]>
	<script src="/common/js/IE7/IE9.js" type="text/javascript"></script>
	<![endif]-->
	
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.colorbox-min.js"></script> 
	<script type="text/javascript" src="/assets/js/colortip-1.0-jquery.js"></script>
   	<script type="text/javascript" src="/common/js/zmedia/zmedia.js"></script>
    <script type="text/javascript" src="/common/js/utils/IEEmu.js"></script>
	<script type="text/javascript" src="/assets/js/actions.js"></script>
    <script type="text/javascript" src="/assets/js/common.js"></script>
	

	
	

   	<?php
  		global $_SYSTEM; 
  		require_once (ROOT_DIR."classes/model/MessageModel.php");
  		require_once (ROOT_DIR."classes/model/UserNameModel.php");
  		 
  		$cred=UserCredentials::load(); 
  		$logged_in=$cred->getLoginStatus();  
 
   		$mm=new MessageModel();
  		$um=new UserNameModel();
			$cid = $um->getCompanyId();

  		$messages=false;
  		$user=false;
  		$coach = false;
  		$admin = false;
  		$homeLink="#";
  		if(!$cred->isAdmin()){
				if($cred->hasRead("LOGIN_USER")){
//					$user_state = $um->getUserState();
//					if ($user_state == "pre-loaded") {
//						$user = false;
//						$messages = false;
//						$homeLink = "Register/" . $company/optin;
//					}
//					else if ($user_state == "active") {
						$messages=true;
						$user=true;
						$homeLink="User/Index";
//					}
				}
				if($cred->hasRead("LOGIN_HEALTH_COACH")){
					$coach = true;
					$messages=true;
					$homeLink="HealthCoach/Index";
				}
			}

  	
  		$classes=$um->getNavStatus();
  		$enabled=$um->getNavEnabledList();

     	$queue=HeaderInclude::getQueue();
     	foreach($queue as $item){
     		print $item."\n";
     	}
     	$admin=false;
     	if($_SYSTEM['showAdmin']){
     		$admin=true;
     	?>  
	 <link rel="stylesheet" type="text/css" media="screen" href="/assets/css/style_admin.css" />
	  <?php if(isset($_SYSTEM['include_tinymce']) && $_SYSTEM['include_tinymce']==true){
			include(COMMON_TEMPLATE_ROOT."interface/tinymce.tpt");		
			}		
		}
		if ($color = $um->getColorScheme()) {
			require_once (ROOT_DIR."classes/model/ColorsModel.php");
			$cid = $um->getCompanyId();
			$clr = new ColorsModel($cid);
			$baseColors = $clr->getColors();
		?>
	 <link rel="stylesheet" type="text/css" media="screen" href="<?php echo $color; ?>" />
	 <?php }
		else {
		 	$baseColors['background'] = "#6CA726"; }?>
  </head>
<?php if ($user) {?>
  <body>
<?php } else if (($coach) || ($admin)) {?>
  <body style="background-color:#dfeff9;">
 <?php } else if ($_SERVER['REQUEST_URI'] == "/Page/Privacy/") {?>
 <body style="background:none;">
<?php } else if (($_SERVER['REQUEST_URI'] == "/") ||
                 (strncmp($_SERVER['REQUEST_URI'], "/Landing",8) == 0) ||
                 (strncmp($_SERVER['REQUEST_URI'], "/Page", 5) == 0) ||
                 (strncmp($_SERVER['REQUEST_URI'], "/Register", 9) == 0)) { ?>
	<body style="background:#ffffff">
<?php }else { ?>
	<body>
<?php
//  <body style="background:#dfeff9 url(/assets/media/images/login/bg.png) 50% 0 no-repeat;">
?>
 <?php }
	 if ($user) {?>
 <input type="hidden" id="hilightColor" value="<?php echo $baseColors['background'];?>" />
 <div id="page">
	<div id="header">
<?php $logo = $um->getLogo($cid); ?>
		<div id="logo" class="left"> <a href="/<?php print $homeLink;?>"><img src="/zImageCache/Scale/500/<?php echo $logo['height']; ?>/<?php echo $logo['file']; ?>" alt="Logo"/></a></div>
	
		<div id="login-holder">
			<div id="login">
				<div id="login-info" class="right">
					<div class="cap-left left">&nbsp;</div>
					<ul class="left">
                    	<li><a href="/<?php print$homeLink;?>">Home</a></li>
						<li><?php if($user){?><a href="/User/info/">Profile</a>
							<?php }else{?><a href="#">Profile</a><?php }?></li>
						<li class="last"><a href="/Landing/logout/">Logout</a></li>
					</ul>
					<div class="cap-right left">&nbsp;</div>
				</div>
				<div id="welcome" class="right">WELCOME <?php echo $um->getFirstName(); ?>!</div>
			</div>
			<div class="clear"></div>
			<div id="message-info"> <?php if($messages){?><a href="/Messages/Index/">&gt;&gt; Message Center (<?php $mm->getNewMesageCount(); ?>)</a><?php }?> </div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="navcontainer">
		<div id="nav">
			<ul>
			<?php if($user || !$logged_in){
				?>
				<li><a href="/IFocus/Index" <?php echo $classes[0][1]?>><span <?php echo $classes[1][1]?>>iFOCUS Assessment</span></a></li>
				<li><a href="/Page/ITModules/" <?php echo $classes[0][2]?>><span <?php echo$classes[1][2]?>>myFOCUS</span></a></li>
				<li><a href="/HealthyAchievements/Index" <?php echo $classes[0][5]?>><span <?php echo $classes[1][5]?>>Healthy Achievements</span></a></li>
				<li><a href="/Page/Trackers/" <?php echo $classes[0][0]?>><span <?php echo $classes[1][0]?>>Resources and Tools</span></a></li>
				<li><a href="/HealthArticles/Index" <?php echo $classes[0][3]?>><span <?php echo $classes[1][3]?>>Health Articles</span></a></li>
				<li><a href="/HealthLibrary/Index" <?php echo $classes[0][4]?>><span <?php echo $classes[1][4]?>>Health Library</span></a></li>
			 	<?php if ($enabled[6]) {?>
				<li><a href="/page/kits/" <?php echo $classes[0][6]?>><span <?php echo $classes[1][6]?>>Screening Kits</span></a></li>
				<?php }?>
			 <?php }else if(!$admin){?>
			 	<li><a href="/HealthCoach/Index/" <?php echo $classes[0][7]?>><span <?php echo $classes[1][7]?>>Home</span></a></li>
			 	<li><a href="/HealthCoach/CallLog/" <?php echo $classes[0][8]?>><span <?php echo $classes[1][8]?>>Call Log</span></a></li>
			 	<li><a href="/HealthCoach/GetUsers/" <?php echo $classes[0][9]?>><span <?php echo $classes[1][9]?>>View user</span></a></li>
			 <?php }else{?>
			 	<li><a href="#"><span>Admin</span></a></li>
			 <?php }?>
			</ul>
			<div class="clear"> </div>
		</div>
	</div>
	<div id="container">
		<div id="content">
		<?php } else if ($coach) { ?>
        <div id="coach-bg">
        <div id="coach-container">
        <div id="healthcoach-nav">
      	  	<ul class="right logout">
          	    <li><a href="/HealthCoach/ChangePassword">CHANGE PASSWORD</a></li>
                <li><a href="/Landing/logout/">LOGOUT</a></li>
            </ul>
            <ul id="navlist">
<!--                  <li><a href="/HealthCoach/Index" <?php echo $classes[0][7]?>><span <?php echo $classes[1][7]?>>HOME</span></a></li>	-->
                <li><a href="/Messages/Index" <?php echo $classes[0][10]?>><span <?php echo $classes[1][10]?>>CHECK MESSAGES</span></a></li>
                <li><a href="/HealthCoach/CallLog" <?php echo $classes[0][8]?>><span <?php echo $classes[1][8]?>>CALL LOG</span></a></li>
                <li><a href="/HealthCoach/GetUsers" <?php echo $classes[0][9]?>><span <?php echo $classes[1][9]?>>VIEW USER INFO</span></a></li>
            </ul>
            <div class="clear"> </div>
        </div>
		<?php }

			if($_SYSTEM['showAdmin'] && $logged_in){
				include_once(LIB_ROOT."classes/common/AdminNavComponent.class.php");
				$setPage=isset($_SYSTEM['admin_page_select'])?$_SYSTEM['admin_page_select']:"";
				$AdminNavComponent=new AdminNavComponent();	 			
				$AdminNavComponent->parse($setPage);
			}
	
			$queue=TemplateQueue::getQueue('body');
			foreach($queue as $item){
				try{
					$item->parse();
				}catch(Exception $e){
					print "Error: ".$e->getMessage()."<br>";
				}
			}
		if ($user) {?>
        </div>
		
	
		<!--Footer-->
		<div id="footer">
			<div id="footer-content">
				<div id="footer-links">
					<ul>
					<?php if($user || !$logged_in){?>
						<li class="color1"><a href="/Page/Trackers/">Resources and Tools</a></li>
						<li class="color2"><a href="/HealthyAchievements/Index">Healthy Achievements</a></li>
						<?php if ($enabled[6]) {?>
						<li class="color1"><a href="/page/kits/">Screening Kits</a></li>
						<?php } else {?>
						<li class="color1"><a href="/User/Info">Edit Profile</a></li>
						<?php }?>
						<li class="color2"><a href="/HealthLibrary/Index">Health Library</a></li>
						<li class="color1"><a href="/IFocus/Index">iFOCUS Health Assessment</a></li>
						<li class="color2"><a href="/Page/ITModules/">myFOCUS</a></li>
						<li class="color1"><a href="/HealthArticles/Index">Featured Health Articles</a></li>
						<li class="color2"><a href="/Messages/Compose">Connect with a Health Coach</a></li>
					<?php }else if(!$admin){?>
					<li class="color1"><a href="/HealthCoach/Index/">Home</a></li>
					<li class="color2"><a href="/HealthCoach/CallLog/">Call Log</a></li>
					<li class="color1"><a href="/HealthCoach/GetUsers/">View User</a></li>
				 <?php }?>
					</ul>
					
					<div class="clear"> </div>
				</div>
				<div class="divider"></div>
				<!--Divide-->
				<div id="footer-bottom">
					<div id="poweredby" class="right"> <img src="/assets/media/images/header-footer/provant-logo.png" alt="" /> </div>
					<div id="footer-logo"> <br/><!-- <img src="/assets/media/images/header-footer/logo-small.jpg" alt=""/> --> </div>
					<div id="copyright">&copy; 2010 Provant all rights reserved. &nbsp;<a href="/Page/Privacy/">Privacy Policy</a> &nbsp;| <a href="/Page/Terms/"> &nbsp;Terms of Use </a> &nbsp; |&nbsp; <a href="/Page/Legal/">Legal Notice </a> &nbsp;| &nbsp;<a href="">About Us</a> &nbsp;|  &nbsp;<a href="">Contact Us</a></div>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	</div>
</div>
		</div>
</div>
		<?php }?>
		
			<script type="text/javascript" src="/assets/js/jquery/jquery.corner.js"></script>
  
  	<script type="text/javascript">
	$(function() {
		$('#header').corner("10px");
		$('#container').corner("10px");
		$('#nav').corner("10px");
	});
	</script>

  </body>
</html>
