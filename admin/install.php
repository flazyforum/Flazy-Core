<?php
/**
 * Установочный скрипт.
 *
 * Используется для установки Flazy
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2017 Flazy.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


define('FORUM_VERSION', '0.7.2');
define('FORUM_DB_REVISION', '13.2');
define('MIN_PHP_VERSION', '5.4.0');
define('MIN_MYSQL_VERSION', '4.1.2');

define('FORUM_ROOT', '../');
define('FORUM', 1);
define('FORUM_DEBUG', 1);

define('FORUM_SEARCH_MIN_WORD', 3);
define('FORUM_SEARCH_MAX_WORD', 20);

header('Content-Type: text/html; charset=utf-8');

if (file_exists(FORUM_ROOT.'include/config.php'))
<<<<<<< HEAD
	die('File \'config.php\' already exists. This means that Flazy already installed. Go to the <a href="'.FORUM_ROOT.'index.php">main page</a>.');
=======
	die('The \'config.php\' already exists. That means the Flazy is already installed.Go to the  <a href="'.FORUM_ROOT.'index.php">main page</a>.');
>>>>>>> origin/master

// Make sure we are running at least MIN_PHP_VERSION
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	die('Ваша версия PHP '.PHP_VERSION.'. Чтобы правильно работать, Flazy требуется  хотя бы PHP '.MIN_PHP_VERSION.'. Вам необходимо обновить PHP, и только тогда вы сможите прожолжить установку.');

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

// Turn off PHP time limit
@set_time_limit(0);

// We need some stuff from functions.php
require FORUM_ROOT.'include/functions/common.php';

// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// If the request_uri is invalid try fix it
if (!defined('FORUM_IGNORE_REQUEST_URI'))
	forum_fix_request_uri(); 


// Generate output to be used for config.php
function generate_config_file()
{
	global $db_type, $db_host, $db_name, $db_username, $db_password, $db_prefix, $base_url, $cookie_name;

	return '<?php'."\n\n".'$db_type = \''.$db_type."';\n".'$db_host = \''.$db_host."';\n".'$db_name = \''.addslashes($db_name)."';\n".'$db_username = \''.addslashes($db_username)."';\n".'$db_password = \''.addslashes($db_password)."';\n".'$db_prefix = \''.addslashes($db_prefix)."';\n".'$p_connect = false;'."\n\n".'$base_url = \''.$base_url.'\';'."\n\n".'$cookie_name = '."'".$cookie_name."';\n".'$cookie_domain = '."'';\n".'$cookie_path = '."'/';\n".'$cookie_secure = 0;'."\n\ndefine('FORUM', 1);";
}

$language = isset($_GET['lang']) ? $_GET['lang'] : (isset($_POST['req_language']) ? forum_trim($_POST['req_language']) : 'English');
$language = preg_replace('#[\.\\\/]#', '', $language);
if (!file_exists(FORUM_ROOT.'lang/'.$language.'/install.php'))
	die('The selected language pack does not exist or is damaged. Check and try again.');


// Load the language files
require FORUM_ROOT.'lang/'.$language.'/install.php';
$default_style = 'default';

if (isset($_POST['generate_config']))
{
	header('Content-Type: text/x-delimtext; name="config.php"');
	header('Content-disposition: attachment; filename=config.php');

	$db_type = $_POST['db_type'];
	$db_host = $_POST['db_host'];
	$db_name = $_POST['db_name'];
	$db_username = $_POST['db_username'];
	$db_password = $_POST['db_password'];
	$db_prefix = $_POST['db_prefix'];
	$base_url = $_POST['base_url'];
	$cookie_name = $_POST['cookie_name'];

	echo generate_config_file();
	die;
}

if (!isset($_POST['form_sent']))
{
	// Determine available database extensions
	$dual_mysql = false;
	$db_extensions = array();
	$mysql_innodb = false;
	if (function_exists('mysqli_connect'))
	{
		$db_extensions[] = array('mysqli', 'MySQL');
		$db_extensions[] = array('mysqli_innodb', 'MySQL Improved (InnoDB)');
		$mysql_innodb = true;
	}
	if (function_exists('mysql_connect'))
	{
		$db_extensions[] = array('mysql', 'MySQLi');
		$db_extensions[] = array('mysql_innodb', 'MySQL Standard (InnoDB)');
		$mysql_innodb = true;

		if (count($db_extensions) > 2)
			$dual_mysql = true;
	}
	if (function_exists('sqlite_open'))
		$db_extensions[] = array('sqlite', 'SQLite');
	if (function_exists('pg_connect'))
		$db_extensions[] = array('pgsql', 'PostgreSQL');

	if (empty($db_extensions))
		error($lang_install['No database support']);

function url_origin($s, $use_forwarded_host=false)
{
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}
function full_url($s, $use_forwarded_host=false)
{
    return url_origin($s, $use_forwarded_host) . dirname(dirname($_SERVER['REQUEST_URI']));
}
$absolute_url = full_url($_SERVER);


	// Check for available language packs
	$languages = get_language_packs();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Flazy Instalation</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<link rel="stylesheet" type="text/css" href="<?php echo FORUM_ROOT ?>resources/admin/bootstrap/css/bootstrap.min.css" />
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
 <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css" />
<?php

echo '<link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'resources/admin/dist/css/AdminLTE.min.css" />'."\n";
echo '<link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'resources/admin/dist/css/skins/_all-skins.min.css" />'."\n";
?>
<style type="text/css">
	.bwizard-steps {
	display: inline-block;
	margin: 0; padding: 0;
	background: #fff }
	.bwizard-steps .active {
		color: #fff;
		background: #007ACC }
	.bwizard-steps .active:after {
		border-left-color: #007ACC }
	.bwizard-steps .active a {
		color: #fff;
		cursor: default }
	.bwizard-steps .label {
		position: relative;
		top: -1px;
		margin: 0 5px 0 0; padding: 1px 5px 2px }
	.bwizard-steps .active .label {
		background-color: #333;}
	.bwizard-steps li {
		display: inline-block; position: relative;
		margin-right: 5px;
		padding: 12px 17px 10px 30px;
		*display: inline;
		*padding-left: 17px;
		background: #efefef;
		line-height: 18px;
		list-style: none;
		zoom: 1; }
	.bwizard-steps li:first-child {
		padding-left: 12px;
		-moz-border-radius: 4px 0 0 4px;
		-webkit-border-radius: 4px 0 0 4px;
		border-radius: 4px 0 0 4px; }
	.bwizard-steps li:first-child:before {
		border: none }
	.bwizard-steps li:last-child {
		margin-right: 0;
		-moz-border-radius: 0 4px 4px 0;
		-webkit-border-radius: 0 4px 4px 0;
		border-radius: 0 4px 4px 0; }
	.bwizard-steps li:last-child:after {
		border: none }
	.bwizard-steps li:before {
		position: absolute;
		left: 0; top: 0;
		height: 0; width: 0;
		border-bottom: 20px inset transparent;
		border-left: 20px solid #fff;
		border-top: 20px inset transparent;
		content: "" }
	.bwizard-steps li:after {
		position: absolute;
		right: -20px; top: 0;
		height: 0; width: 0;
		border-bottom: 20px inset transparent;
		border-left: 20px solid #efefef;
		border-top: 20px inset transparent;
		content: "";
		z-index: 2; }
	.bwizard-steps a {
		color: #333 }
	.bwizard-steps a:hover {
		text-decoration: none }
.bwizard-steps.clickable li:not(.active) {
	cursor: pointer }
.bwizard-steps.clickable li:hover:not(.active) {
	background: #ccc }
.bwizard-steps.clickable li:hover:not(.active):after {
	border-left-color: #ccc }
.bwizard-steps.clickable li:hover:not(.active) a {
	color: #08c }
@media (max-width: 480px) {
	/* badges only on small screens */
	.bwizard-steps li:after,
	.bwizard-steps li:before {
		border: none }
	.bwizard-steps li,
	.bwizard-steps li.active,
	.bwizard-steps li:first-child,
	.bwizard-steps li:last-child {
		margin-right: 0;
		padding: 0;
		background-color: transparent }
}
</style>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- jQuery 2.1.4 -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/plugins/jQuery/jQuery-2.1.4.min.js" type="text/javascript"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <!-- SlimScroll -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <!-- FastClick -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/plugins/fastclick/fastclick.min.js" type="text/javascript"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/dist/js/app.min.js" type="text/javascript"></script>
    <!-- AdminLTE Wizard -->
    <script src="<?php echo FORUM_ROOT ?>resources/admin/bootstrap/js/demo.js" type="text/javascript"></script>
</head>
<body class="layout-boxed sidebar-mini skin-red">
	<!-- Site wrapper -->
	<div class="wrapper">

		<header class="main-header">
			<!-- Logo -->
			<a href="../../index2.html" class="logo"> <!-- mini logo for sidebar mini 50x50 pixels --> 
				<span class="logo-mini"><b>Flazy</b></span> <!-- logo for regular state and mobile devices --> 
				<span class="logo-lg"><b>Flazy</b></span> </a>
			<!-- Header Navbar: style can be found in header.less -->
			<nav class="navbar navbar-static-top" role="navigation">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"> <span class="sr-only">Toggle navigation</span> </a>

			</nav>
		</header>

		<!-- =============================================== -->

		<!-- Left side column. contains the sidebar -->
		<aside class="main-sidebar">
			<!-- sidebar: style can be found in sidebar.less -->
			<section class="sidebar" style="height: auto;">

				<ul class="sidebar-menu">
					<li class="header">
						MAIN NAVIGATION
					</li>

					<li class="active">
						<a href="#selectlanguage" disabled=""> <i class="fa fa-th"></i> <span>Select Language</span> </a>
					</li>
					<li>
						<a href="#dbsetups" disabled=""> <i class="fa fa-th"></i> <span>Database Setup</span> </a>
					</li>
					<li >
						<a href="#adminusersetup" disabled=""> <i class="fa fa-th"></i> <span>Admin user Setup</span> </a>
					</li>
					<li >
						<a href="#websitesetup" disabled=""> <i class="fa fa-th"></i> <span>Website Setup</span> </a>
					</li>
					
				</ul>
			</section>
			<!-- /.sidebar -->
		</aside>

		<!-- =============================================== -->

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper" style="min-height: 976px;">
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1> <?php printf($lang_install['Install Flazy'], FORUM_VERSION) ?><small> <?php echo $lang_install['Install Flazy decs'] ?></small></h1>
			</section>

			<!-- Main content -->
			<section class="content">
				

<?php
	
	if (count($languages) > 1)
	{

?>
				<!-- Default box -->
				<div class="box" id="selectlanguage">
					<div class="box-header with-border">
						<h3 class="box-title"><?php printf($lang_install['Install Flazy'], FORUM_VERSION) ?><small><?php echo $lang_install['Choose language'] ?></small></h3>
						<div class="box-tools pull-right">
							<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
								<i class="fa fa-minus"></i>
							</button>
							<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove">
								<i class="fa fa-times"></i>
							</button>
						</div>
					</div>
					<div class="box-body">

<form class="form-horizontal"  method="get" accept-charset="utf-8" action="install.php">
<fieldset>

<!-- Form Name -->
<legend><?php echo $lang_install['Choose language legend'] ?></legend>

<!-- Select Basic -->
<div class="form-group">
  <label class="col-md-4 control-label" for="selectbasic"><?php echo $lang_install['Installer language'] ?></label>
  <div class="col-md-4">
    <select id="fld0" name="lang" class="form-control">
<?php

foreach ($languages as $temp)
	echo "\t\t\t\t\t" . '<option value="' . $temp . '"' . ($language == $temp ? ' selected="selected"' : '') . '>' . $temp . '</option>' . "\n";
?>
    </select>
    <span class="help-block"><?php echo $lang_install['Choose language help'] ?></span> 
  </div>
</div>
		</div><!-- /.box-body -->
		<div class="box-footer">
					<div class="form-group">
  						<div class="col-md-4">
    						<button type="submit"  name="changelang" class="btn btn-primary"><?php echo $lang_install['Choose language'] ?></button>
  						</div>
					</div>
				</fieldset>
			</form>
		</div><!-- /.box-footer-->
	</div><!-- /.box -->
<?php
	}
?>
								<div class="box" id="dbsetups">
					<div class="box-header with-border">
						<h3 class="box-title"><?php echo $lang_install['Part1'] ?></h3>
						<div class="box-tools pull-right">
							<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
								<i class="fa fa-minus"></i>
							</button>
							<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove">
								<i class="fa fa-times"></i>
							</button>
						</div>
					</div>
					<div class="box-body" id="adminusersetup">
<form class="form-horizontal" method="post" accept-charset="utf-8" action="install.php">
	<div class="hidden">
		<input type="hidden" name="form_sent" value="1" />
	</div>
<div class="alert alert-info alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h4><i class="icon fa fa-info"></i> Tips!</h4>
    <ol>
    					<li><span><strong><?php echo $lang_install['Database type'] ?></strong>: <?php echo $lang_install['Database type info']; if ($dual_mysql) echo ' '.$lang_install['Mysql type info']; if ($mysql_innodb) echo ' '.$lang_install['Mysql InnoDB info'] ?></span></li>
				<li><span><strong><?php echo $lang_install['Database server'] ?></strong>: <?php echo $lang_install['Database server info'] ?></span></li>
				<li><span><strong><?php echo $lang_install['Database name'] ?></strong>: <?php echo $lang_install['Database name info'] ?></span></li>
				<li><span><strong><?php echo $lang_install['Database user pass'] ?></strong>: <?php echo $lang_install['Database username info'] ?></span></li>
				<li><span><strong><?php echo $lang_install['Table prefix'] ?></strong>: <?php echo $lang_install['Table prefix info'] ?></span></li>
    </ol>
</div>
<div class="alert alert-warning alert-dismissable">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
	<h4><i class="icon fa fa-info">Warning!</i></h4>
	<p class="important"><?php printf($lang_install['Required warn'], '<em>'.$lang_install['Required'].'</em>') ?></p>
</div>
	<fieldset>
		<div class="form-group">
			<label class="col-md-4 control-label" for="selectbasic"><?php echo $lang_install['Database type'] ?> <em><?php echo $lang_install['Required'] ?></em></label>
			<div class="col-md-4">
				<select id="fld1" name="req_db_type" class="form-control">
<?php
	foreach ($db_extensions as $db_type)
		echo "\t\t\t\t\t".'<option value="'.$db_type[0].'">'.$db_type[1].'</option>'."\n";
?>
				</select>
				<span class="help-block"><?php echo $lang_install['Database type help'] ?></span>
			</div>
		</div>

		<div class="form-group">
			<label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Database server'] ?> <em><?php echo $lang_install['Required'] ?></em></label>
			<div class="col-md-4">
				<input id="fld2" type="text" name="req_db_host" value="localhost" size="35" maxlength="100" class="form-control input-md">
				<span class="help-block"><?php echo $lang_install['Database server help'] ?></span>
			</div>
		</div>

		<div class="form-group">
			<label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Database name'] ?> <em><?php echo $lang_install['Required'] ?></label>
			<div class="col-md-4">
				<input id="fld3" type="text" name="req_db_name" size="35" maxlength="50" class="form-control input-md">
				<span class="help-block"><?php echo $lang_install['Database name help'] ?></span>
			</div>
		</div>
		
		<div class="form-group">
			<label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Database username'] ?></label>
			<div class="col-md-4">
				<input id="fld4" type="text" name="db_username" size="35" maxlength="50" class="form-control input-md">
				<span class="help-block"><?php echo $lang_install['Database username help'] ?></span>
			</div>
		</div>
		
		<div class="form-group">
			<label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Database password'] ?></label>
			<div class="col-md-4">
				<input id="fld5" type="password" name="db_password" size="35" class="form-control input-md">
				<span class="help-block"><?php echo $lang_install['Database password help'] ?></span>
			</div>
		</div>
		
		<div class="form-group">
			<label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Table prefix'] ?></label>
			<div class="col-md-2">
				<input id="fld6" type="text" name="db_prefix" size="20" maxlength="30" class="form-control input-md">
				<span class="help-block"><?php echo $lang_install['Table prefix help'] ?></span>
			</div>
		</div>
	</div><!-- /.box-body -->
	<div class="box-footer">
		<div class="form-group">
			<div class="col-md-4">
				<button id="#websitesetup" name="singlebutton" class="btn btn-primary">
					Next
				</button>
			</div>
		</div>

</fieldset>
	</div><!-- /.box-footer-->
</div><!-- /.box -->
				
				<div class="box" id="adminusersetup">
					<div class="box-header with-border">
						<h3 class="box-title"><?php echo $lang_install['Part2'] ?></h3>
						<div class="box-tools pull-right">
							<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
								<i class="fa fa-minus"></i>
							</button>
							<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove">
								<i class="fa fa-times"></i>
							</button>
						</div>
					</div>
					<div class="box-body">
<fieldset class="form-horizontal">

<legend><?php echo $lang_install['Part2 legend'] ?></legend>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Admin username'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
  <input id="fld7" type="text" name="req_username" size="35" maxlength="25" class="form-control input-md">
  <span class="help-block"><?php echo $lang_install['Username help'] ?></span>  
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Admin password'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
  <input id="fld8" type="password" name="req_password1" size="35" class="form-control input-md">
  <span class="help-block"><?php echo $lang_install['Password help'] ?></span>  
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Admin confirm password'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
  <input id="fld9" type="password" name="req_password2" size="35" class="form-control input-md">
  <span class="help-block"><?php echo $lang_install['Confirm password help'] ?></span>  
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Admin e-mail'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
  <input id="fld10" type="text" name="req_email" size="35" maxlength="80" class="form-control input-md">
  <span class="help-block"><?php echo $lang_install['E-mail address help'] ?></span>  
  </div>
</div>

</fieldset>
					</div><!-- /.box-body -->
					<div class="box-footer">
		<div class="form-group">
			<div class="col-md-4">
				<button id="#websitesetup" name="singlebutton" class="btn btn-primary">
					Next
				</button>
			</div>
		</div>
					</div><!-- /.box-footer-->
				</div><!-- /.box -->
				<div class="box" id="websitesetup">
					<div class="box-header with-border">
						<h3 class="box-title"><?php echo $lang_install['Part3'] ?></h3>
						<div class="box-tools pull-right">
							<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
								<i class="fa fa-minus"></i>
							</button>
							<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove">
								<i class="fa fa-times"></i>
							</button>
						</div>
					</div>
					<div class="box-body">
<fieldset class="form-horizontal">

<legend><?php echo $lang_install['Part3 legend'] ?></legend>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Board title'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
	<input id="fld11" type="text" name="board_title" size="35" maxlength="255" class="form-control input-md"> 
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Board description'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
	<input id="fld12" type="text" name="board_description" size="35" maxlength="255" class="form-control input-md">
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Board keywords'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
	<input id="fld13" type="text" name="board_keywords" size="35" maxlength="255" class="form-control input-md">
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="textinput"><?php echo $lang_install['Base URL'] ?> <em><?php echo $lang_install['Required'] ?></em></label>  
  <div class="col-md-4">
  <input id="fld14" type="text" name="req_base_url" value="<?php echo $absolute_url ?>" size="35" maxlength="100" class="form-control input-md">
  <span class="help-block"><?php echo $lang_install['Base URL help'] ?></span>  
  </div>
</div>
<?php
	
	if (count($languages) > 1)
	{
?>

<div class="form-group">
  <label class="col-md-4 control-label" for="selectbasic"><?php echo $lang_install['Default language'] ?></label>
  <div class="col-md-4">
    <select id="fld15" name="req_language" class="form-control">
<?php
		foreach ($languages as $temp)
			echo "\t\t\t\t\t".'<option value="'.$temp.'"'.($language == $temp ? ' selected="selected"' : '').'>'.$temp.'</option>'."\n";
?>
	</select>
    <span class="help-block"><?php echo $lang_install['Default language help'] ?></span>
  </div>
</div>
<?php

	}
	else
	{
?>
			<div class="hidden">
				<input type="hidden" name="req_language" value="<?php echo $languages[0]; ?>" />
			</div>
<?php

	}

?>
</fieldset>
					</div><!-- /.box-body -->
	<div class="box-footer">
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="start" class="btn btn-primary" value="<?php echo $lang_install['Start install'] ?>" /></span>
		</div>
	</div><!-- /.box-footer-->
				</div><!-- /.box -->
	</form>
			</section><!-- /.content -->
		</div><!-- /.content-wrapper -->

		<footer class="main-footer">
			<div class="pull-right hidden-xs">
				<b>Version</b> <?php echo FORUM_VERSION ?>
			</div>
			<strong>Copyright © 2014-2015 <a href="https://flazy.us">flazy</a>.</strong> All rights reserved.
		</footer>

	</div><!-- ./wrapper -->
</body>
</html>
<?php

}
else
{
	// Strip slashes only if magic_quotes_gpc is on.
	function unescape($str)
	{
		return (get_magic_quotes_gpc() == 1) ? stripslashes($str) : $str;
	}

	$db_type = $_POST['req_db_type'];
	$db_host = forum_trim($_POST['req_db_host']);
	$db_name = forum_trim($_POST['req_db_name']);
	$db_username = unescape(forum_trim($_POST['db_username']));
	$db_password = unescape(forum_trim($_POST['db_password']));
	$db_prefix = forum_trim($_POST['db_prefix']);
	$username = unescape(forum_trim($_POST['req_username']));
	$email = unescape(strtolower(forum_trim($_POST['req_email'])));
	$password1 = unescape(forum_trim($_POST['req_password1']));
	$password2 = unescape(forum_trim($_POST['req_password2']));
	$board_title = unescape(forum_trim($_POST['board_title']));
	$board_description = unescape(forum_trim($_POST['board_description']));
	$board_keywords = unescape(forum_trim($_POST['board_keywords']));
	$default_lang = preg_replace('#[\.\\\/]#', '', unescape(forum_trim($_POST['req_language'])));

	// Make sure base_url doesn't end with a slash
	if (substr($_POST['req_base_url'], -1) == '/')
		$base_url = substr($_POST['req_base_url'], 0, -1);
	else
		$base_url = $_POST['req_base_url'];

	// Validate form
	if (utf8_strlen($db_name) == 0)
		error($lang_install['Missing database name']);
	if (utf8_strlen($username) < 2)
		error($lang_install['Username too short']);
	if (utf8_strlen($username) > 25)
		error($lang_install['Username too long']);
	if (utf8_strlen($password1) < 4)
		error($lang_install['Pass too short']);
	if ($password1 != $password2)
		error($lang_install['Pass not match']);
	if (strtolower($username) == 'guest')
		error($lang_install['Username guest']);
	if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username) || preg_match('/((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))/', $username))
		error($lang_install['Username IP']);
	if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		error($lang_install['Username reserved chars']);
	if (preg_match('/(?:\[\/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)/i', $username))
		error($lang_install['Username BBCode']);

	// Validate email
	if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/functions/email.php';

	if (!is_valid_email($email))
		error($lang_install['Invalid email']);

	// Make sure board title, description and keywords aren't left blank
	if ($board_title == '')
		$board_title = 'Flazy installation complete';
	if ($board_description == '')
		$board_description = 'Flazys.';
	if ($board_keywords == '')
		$board_keywords = 'Flazy, keywords, test';

	if (utf8_strlen($base_url) == 0)
		error($lang_install['Missing base url']);
	
	if (!file_exists(FORUM_ROOT.'lang/'.$default_lang.'/common.php'))
		error($lang_install['Invalid language']);

	// Load DB abstraction layer and try to connect
	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb' || $db_type == 'pgsql' ||  $db_type == 'sqlite')
		require FORUM_ROOT.'include/dblayer/'.$db_type.'.php';
	else
		error(sprintf($lang_install['No such database type'], forum_htmlencode($db_type)));

	// Create the database adapter object (and open/connect to/select db)
	$forum_db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);

	// If MySQL, make sure it's at least 4.1.2
	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
	{
		$mysql_info = $forum_db->get_version();
		if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<'))
			error(sprintf($lang_install['Invalid MySQL version'], $mysql_version, MIN_MYSQL_VERSION));
	}

	// Validate prefix
	if (strlen($db_prefix) > 0 && (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $db_prefix) || strlen($db_prefix) > 40))
		error(sprintf($lang_install['Invalid table prefix'], $db_prefix));

	// Check SQLite prefix collision
	if ($db_type == 'sqlite' && strtolower($db_prefix) == 'sqlite_')
		error($lang_install['SQLite prefix collision']);


	// Make sure Flazy isn't already installed
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'users',
		'WHERE'		=> 'id=1'
	);

	$result = $forum_db->query_build($query);
	if ($forum_db->num_rows($result))
		error(sprintf($lang_install['Flazy already installed'], $db_prefix, $db_name));

	// Check if InnoDB is available
	if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
	{
		$result = $forum_db->query('SHOW VARIABLES LIKE \'have_innodb\'');
		list (, $result) = $forum_db->fetch_row($result);
		if ((strtoupper($result) != 'YES'))
			error($lang_install['InnoDB not enabled']);
	}

	// Start a transaction
	$forum_db->start_transaction();


	// Create all tables
	$schema = array(
		'FIELDS'	=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'answer'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=>	array('id, topic_id')
	);

	$forum_db->create_table('answers', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'username'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'ip'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'email'			=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'message'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'expire'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'ban_creator'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('bans', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'cat_name'		=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\''.$lang_install['Default category name'].'\''
			),
			'disp_position'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('categories', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'search_for'	=> array(
				'datatype'		=> 'VARCHAR(60)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'replace_with'	=> array(
				'datatype'		=> 'VARCHAR(60)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('censoring', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'conf_name'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'conf_value'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('conf_name')
	);

	$forum_db->create_table('config', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'title'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'version'		=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'description'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'keywords'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			
			'about_us'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'useful_links'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'social_links'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'author'		=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'uninstall'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'uninstall_note'=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'disabled'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'dependencies'	=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('extensions', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'VARCHAR(150)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'extension_id'	=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'code'			=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'installed'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'priority'		=> array(
				'datatype'		=> 'TINYINT(1) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '5'
			)
		),
		'PRIMARY KEY'	=> array('id', 'extension_id')
	);

	$forum_db->create_table('extension_hooks', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'group_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'read_forum'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'post_replies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'post_topics'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			)
		),
		'PRIMARY KEY'	=> array('group_id', 'forum_id')
	);

	$forum_db->create_table('forum_perms', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'forum_name'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\'New forum\''
			),
			'forum_desc'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'redirect_url'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'counter'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'moderators'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'num_topics'	=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_posts'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_poster'	=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'sort_by'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'disp_position'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=>	'0'
			),
			'cat_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=>	'0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('forums', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'g_id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'g_title'		=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'g_user_title'	=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'g_moderator'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_edit_users'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_rename_users'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_change_passwords'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_mod_ban_users'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_read_board'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_view_users'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_replies'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_topics'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_edit_posts'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_delete_posts'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_delete_topics'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_poll_add'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_set_title'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_search'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_search_users'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_send_email'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_post_flood'	=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '30'
			),
			'g_search_flood'=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '30'
			),
			'g_email_flood'	=> array(
				'datatype'		=> 'SMALLINT(6)',
				'allow_null'	=> false,
				'default'		=> '60'
			),
			'g_rep_enable'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'g_rep_plus_min'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'g_rep_minus_min'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('g_id')
	);

	$forum_db->create_table('groups', $schema);



	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'ident'			=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'logged'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'idle'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'csrf_token'	=> array(
				'datatype'		=> 'VARCHAR(40)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'prev_url'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'current_page'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'current_page_id'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'current_ip'	=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> true
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
		),
		'UNIQUE KEYS'	=> array(
			'user_id_ident_idx'	=> array('user_id', 'ident')
		),
		'INDEXES'		=> array(
			'ident_idx'		=> array('ident'),
			'logged_idx'	=> array('logged')
		),
		'ENGINE'		=> 'HEAP'
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
	{
		$schema['UNIQUE KEYS']['user_id_ident_idx'] = array('user_id', 'ident(25)');
		$schema['INDEXES']['ident_idx'] = array('ident(25)');
	}

	if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['ENGINE'] = 'InnoDB';

	$forum_db->create_table('online', $schema);


	$schema = array(
		'FIELDS'			=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'sender_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'receiver_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'edited'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'readed'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'subject'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'message'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> false
			),
			'status'		=> array(
				'datatype'		=> 'VARCHAR(9)',
				'allow_null'	=> false,
				'default'		=> '\'draft\'',
			),
			'deleted_by_sender'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'deleted_by_receiver'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'sender_id_idx'		=> array('sender_id'),
			'receiver_id_idx'	=> array('receiver_id'),
		)
	);

	$forum_db->create_table('pm', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'poster'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'poster_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'poster_ip'		=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> true
			),
			'poster_email'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'message'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'hide_smilies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'edited'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'edited_by'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'reported'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'topic_id_idx'	=> array('topic_id'),
			'multi_idx'		=> array('poster_id', 'topic_id'),
			'posted_idx'    => array('posted')
		)
	);

	$forum_db->create_table('posts', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'rank'			=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'min_posts'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id')
	);

	$forum_db->create_table('ranks', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'pm_id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'reported_by'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'created'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'poster_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'message'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'reason'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'zapped'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'zapped_by'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'zapped_idx'	=> array('zapped')
		)
	);

	$forum_db->create_table('reports', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'from_user_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'time'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'reason'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'plus'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'minus'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topics_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'rep_post_id_idx'	=> array('post_id'),
			'rep_multi_user_id_idx'	=> array('topics_id, from_user_id'),
		)
	);

	$forum_db->create_table('reputation', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'ident'			=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'search_data'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'ident_idx'	=> array('ident')
		)
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['INDEXES']['ident_idx'] = array('ident(8)');

	$forum_db->create_table('search_cache', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'post_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'word_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'subject_match'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'INDEXES'		=> array(
			'word_id_idx'	=> array('word_id'),
			'post_id_idx'	=> array('post_id')
		)
	);

	$forum_db->create_table('search_matches', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'word'			=> array(
				'datatype'		=> 'VARCHAR(20)',
				'allow_null'	=> false,
				'default'		=> '\'\'',
				'collation'		=> 'bin'
			)
		),
		'PRIMARY KEY'	=> array('word'),
		'INDEXES'		=> array(
			'id_idx'	=> array('id')
		)
	);

	if ($db_type == 'sqlite')
	{
		$schema['PRIMARY KEY'] = array('id');
		$schema['UNIQUE KEYS'] = array('word_idx'	=> array('word'));
	}

	$forum_db->create_table('search_words', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('user_id', 'topic_id')
	);

	$forum_db->create_table('subscriptions', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'poster'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'poster_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'subject'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'question'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true,
				'default'		=> '\'\''
			),
			'posted'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'first_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'last_post_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_poster'	=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> true
			),
			'last_poster_id'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_views'		=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_replies'	=> array(
				'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'closed'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'sticky'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'moved_to'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'read_unvote'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'revote'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'poll_created'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'days_count'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'votes_count'	=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'forum_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'forum_id_idx'		=> array('forum_id'),
			'moved_to_idx'		=> array('moved_to'),
			'last_post_idx'		=> array('last_post'),
			'first_post_id_idx'	=> array('first_post_id')
		)
	);

	$forum_db->create_table('topics', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'				=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'group_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '3'
			),
			'username'		=> array(
				'datatype'		=> 'VARCHAR(200)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'password'		=> array(
				'datatype'		=> 'VARCHAR(40)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'salt'			=> array(
				'datatype'		=> 'VARCHAR(12)',
				'allow_null'	=> true
			),
			'email'			=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'title'			=> array(
				'datatype'		=> 'VARCHAR(50)',
				'allow_null'	=> true
			),
			'avatar'		=> array(
				'datatype'		=> 'CHAR(3)',
				'allow_null'	=> true
			),
			'realname'		=> array(
				'datatype'		=> 'VARCHAR(40)',
				'allow_null'	=> true
			),
			'sex'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'url'			=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'jabber'		=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'icq'			=> array(
				'datatype'		=> 'VARCHAR(12)',
				'allow_null'	=> true
			),
			'msn'			=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'aim'			=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'yahoo'			=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'skype'			=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'magent'		=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'vkontakte'		=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'classmates'	=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'mirtesen'		=> array(
				'datatype'		=> 'VARCHAR(12)',
				'allow_null'	=> true
			),
			'moikrug'		=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'facebook'		=> array(
				'datatype'		=> 'VARCHAR(15)',
				'allow_null'	=> true
			),
			'twitter'			=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'lastfm'		=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'location'		=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'country'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> true
			),
			'signature'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> true
			),
			'disp_topics'	=> array(
				'datatype'		=> 'TINYINT(3) UNSIGNED',
				'allow_null'	=> true
			),
			'disp_posts'	=> array(
				'datatype'		=> 'TINYINT(3) UNSIGNED',
				'allow_null'	=> true
			),
			'email_setting'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'notify_with_post'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'auto_notify'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'show_smilies'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_img'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_img_sig'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_avatars'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_sig'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'show_bb_panel'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'access_keys'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'timezone'		=> array(
				'datatype'		=> 'FLOAT',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'dst'			=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'time_format'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'date_format'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'language'		=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'Russian\''
			),
			'style'			=> array(
				'datatype'		=> 'VARCHAR(25)',
				'allow_null'	=> false,
				'default'		=> '\'default\''
			),
			'num_posts'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_post'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_search'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'last_email_sent'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'registered'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'registration_ip'=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> false,
				'default'		=> '\'0.0.0.0\''
			),
			'security_ip'	=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'user_agent'	=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'last_visit'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'admin_note'	=> array(
				'datatype'		=> 'VARCHAR(30)',
				'allow_null'	=> true
			),
			'activate_string'=> array(
				'datatype'		=> 'VARCHAR(80)',
				'allow_null'	=> true
			),
			'activate_key'	=> array(
				'datatype'		=> 'VARCHAR(8)',
				'allow_null'	=> true
			),
			'pm_inbox'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'pm_outbox'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'pm_new'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'pm_long_subject'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'pm_get_mail'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'rep_enable'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'rep_enable_adm'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '1'
			),
			'reputation_plus'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'reputation_minus'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'positive_plus'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'positive_minus'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'fasety_auth'	=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'fasety_last_auth'=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'fasety_auth_mail'=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			)
		),
		'PRIMARY KEY'	=> array('id'),
		'INDEXES'		=> array(
			'registered_idx'	=> array('registered'),
			'username_idx'		=> array('username')
		)
	);

	if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$schema['INDEXES']['username_idx'] = array('username(8)');

	$forum_db->create_table('users', $schema);


	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'topic_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false
			),
			'user_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false
			),
			'answer_id'		=> array(
				'datatype'		=> 'INT(10)',
				'allow_null'	=> false
			),
			'voted'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=>	array('id'),
		'INDEXES'		=> array(
			'topic_id_idx'		=> array('topic_id'),
			'user_id_idx'		=> array('user_id'),
			'answer_id_idx'		=> array('answer_id')
		)
	);

	$forum_db->create_table('voting', $schema);


	$now = time();

	// Insert the four preset groups
	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Administrators\', \'Admin\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 1';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Guest\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 2';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Member\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 3';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
		'INTO'		=> 'groups',
		'VALUES'	=> '\'Moderators\', \'Moderator\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', g_id';
		$query['VALUES'] .= ', 4';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Insert guest and first admin user
	$query = array(
		'INSERT'	=> 'group_id, username, password, email',
		'INTO'		=> 'users',
		'VALUES'	=> '2, \'Guest\', \'Guest\', \'Guest\''
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', id';
		$query['VALUES'] .= ', 1';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$salt = random_key(12);

	$query = array(
		'INSERT'	=> 'group_id, username, password, email, language, num_posts, last_post, registered, registration_ip, last_visit, salt',
		'INTO'		=> 'users',
		'VALUES'	=> '1, \''.$forum_db->escape($username).'\', \''.forum_hash($password1, $salt).'\', \''.$forum_db->escape($email).'\', \''.$forum_db->escape($default_lang).'\', 1, '.$now.', '.$now.', \''.$forum_db->escape(get_remote_address()).'\', '.$now.', \''.$forum_db->escape($salt).'\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_uid = $forum_db->insert_id();

	// Enable/disable avatars depending on file_uploads setting in PHP configuration
	$avatars = in_array(strtolower(@ini_get('file_uploads')), array('on', 'true', '1')) ? 1 : 0;

	// Enable/disable automatic check for updates depending on PHP environment (require cURL, fsockopen or allow_url_fopen)
	$check_for_updates = (function_exists('curl_init') || function_exists('fsockopen') || in_array(strtolower(@ini_get('allow_url_fopen')), array('on', 'true', '1'))) ? 1 : 0;

	// Insert config data
	$config = array(
		'o_cur_version'			=> "'".FORUM_VERSION."'",
		'o_database_revision'	=> "'".FORUM_DB_REVISION."'",
		'o_board_title'			=> "'".$forum_db->escape($board_title)."'",
		'o_board_desc'			=> "'".$forum_db->escape($board_description)."'",
		'o_board_keywords'		=> "'".$forum_db->escape($board_keywords)."'",
		'o_about_us'			=> "''",
		'o_useful_links'		=> "''",
		'o_social_links'		=> "''",
		'o_default_timezone'	=> "'0'",
		'o_time_format'			=> "'H:i:s'",
		'o_date_format'			=> "'Y-m-d'",
		'o_check_for_updates'	=> "'$check_for_updates'",
		'o_timeout_visit'		=> "'5400'",
		'o_timeout_online'		=> "'300'",
		'o_register_timeout'	=> "'3600'",
		'o_redirect_delay'		=> "'1'",
		'o_show_version'		=> "'0'",
		'o_show_user_info'		=> "'1'",
		'o_show_post_count'		=> "'1'",
		'o_merge_timeout'		=> "'300'",
		'o_signatures'			=> "'1'",
		'o_smilies'				=> "'1'",
		'o_smilies_sig'			=> "'1'",
		'o_make_links'			=> "'1'",
		'o_post_edit'			=> "'0'",
		'o_default_lang'		=> "'".$forum_db->escape($default_lang)."'",
		'o_default_style'		=> "'default'",
		'o_user_style'			=> "'1'",
		'o_default_user_group'	=> "'3'",
		'o_topic_review'		=> "'15'",
		'o_disp_topics_default'	=> "'30'",
		'o_disp_posts_default'	=> "'25'",
		'o_indent_num_spaces'	=> "'4'",
		'o_quote_depth'			=> "'3'",
		'o_quickpost'			=> "'1'",
		'o_rep_enabled'			=> "'1'",
		'o_rep_timeout'			=> "'300'",
		'o_users_online'		=> "'1'",
		'o_online_today'		=> "'1'",
		'o_online_ft'			=> "'1'",
		'o_statistic'			=> "'1'",
		'o_record'				=> "'1'",
		'o_censoring'			=> "'0'",
		'o_ranks'				=> "'1'",
		'o_show_dot'			=> "'0'",
		'o_topic_views'			=> "'1'",
		'o_quickjump'			=> "'1'",
		'o_gzip'				=> "'0'",
		'o_additional_navlinks'	=> "''",
		'o_report_enabled'		=> "'1'",
		'o_report_method'		=> "'0'",
		'o_regs_report'			=> "'0'",
		'o_default_email_setting'=> "'1'",
		'o_mailing_list'		=> "'".$forum_db->escape($email)."'",
		'o_avatars'				=> "'$avatars'",
		'o_avatars_width'		=> "'120'",
		'o_avatars_height'		=> "'120'",
		'o_avatars_size'		=> "'20480'",
		'o_gravatar'			=> "'G'",
		'o_pm_inbox_size'		=> "'100'",
		'o_pm_outbox_size'		=> "'100'",
		'o_pm_show_new_count'	=> "'1'",
		'o_pm_show_global_link'	=> "'0'",
		'o_pm_get_mail'			=> "'1'",
		'o_google_analytics'	=> "''",
		'o_search_all_forums'	=> "'1'",
		'o_sef'					=> "'Default'",
		'o_admin_email'			=> "'".$forum_db->escape($email)."'",
		'o_webmaster_email'		=> "'".$forum_db->escape($email)."'",
		'o_subscriptions'		=> "'1'",
		'o_smtp_host'			=> "NULL",
		'o_smtp_user'			=> "NULL",
		'o_smtp_pass'			=> "NULL",
		'o_smtp_ssl'			=> "'0'",
		'o_regs_allow'			=> "'1'",
		'o_regs_verify'			=> "'0'",
		'o_spam_ip'				=> "'1'",
		'o_spam_email'			=> "'1'",
		'o_spam_username'		=> "'1'",
		'o_announcement'		=> "'0'",
		'o_announcement_heading'=> "'".$lang_install['Default announce heading']."'",
		'o_announcement_message'=> "'".$lang_install['Default announce message']."'",
		'o_html_top'			=> "'0'",
		'o_html_top_message'	=> "'".$lang_install['Default HTML message']."'",
		'o_html_bottom'			=> "'0'",
		'o_html_bottom_message'	=> "'".$lang_install['Default HTML message']."'",
		'o_adbox'				=> "'0'",
		'o_adbox_message'		=> "'".$lang_install['Default Adbox message']."'",
		'o_guestbox'			=> "'0'",
		'o_guestbox_message'	=> "'".$lang_install['Default Guestbox message']."'",
		'o_topicbox'			=> "'0'",
		'o_topicbox_message'	=> "'".$lang_install['Default HTML message']."'",
		'o_externbox'			=> "'0'",
		'o_externbox_message'	=> "'".$lang_install['Default HTML message']."'",
		'o_rules'				=> "'0'",
		'o_rules_message'		=> "'".$lang_install['Default rules']."'",
		'o_maintenance'			=> "'0'",
		'o_maintenance_message'	=> "'".$lang_install['Default maint message']."'",
		'o_default_dst'			=> "'0'",
		'p_message_bbcode'		=> "'1'",
		'p_message_img_tag'		=> "'1'",
		'p_message_all_caps'	=> "'1'",
		'p_subject_all_caps'	=> "'1'",
		'p_sig_all_caps'		=> "'1'",
		'p_sig_bbcode'			=> "'1'",
		'p_sig_img_tag'			=> "'0'",
		'p_sig_length'			=> "'400'",
		'p_sig_lines'			=> "'4'",
		'p_allow_banned_email'	=> "'1'",
		'p_allow_dupe_email'	=> "'0'",
		'p_force_guest_email'	=> "'1'",
		'p_enable_bb_panel'		=> "'1'",
		'p_bb_panel_smilies'	=> "'16'",
		'p_poll_enable_read'	=> "'0'",
		'p_poll_enable_revote'	=> "'0'",
		'p_poll_max_answers'	=> "'7'",
		'p_poll_min_posts'		=> "'0'",
		'c_max_users'			=> "'0'",
		'c_max_guests'			=> "'0'",
		'c_max_total_users'		=> "'0'"
	);

	if ($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
		$config['o_database_engine'] = "'InnoDB'";
	else if ($db_type == 'mysql' || $db_type == 'mysqli')
		$config['o_database_engine'] = "'MyISAM'";

	foreach ($config as $conf_name => $conf_value)
	{
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\''.$conf_name.'\', '.$conf_value.''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// Insert some other default data
	$query = array(
		'INSERT'	=> 'cat_name, disp_position',
		'INTO'		=> 'categories',
		'VALUES'	=> '\''.$lang_install['Default category name'].'\', 1'
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id',
		'INTO'		=> 'forums',
		'VALUES'	=> '\''.$lang_install['Default forum name'].'\', \''.$lang_install['Default forum descrip'].'\', 1, 1, '.$now.', 1, \''.$forum_db->escape($username).'\', 1, '.$forum_db->insert_id().''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'poster, poster_id, subject, posted, first_post_id, last_post, last_post_id, last_poster, last_poster_id, forum_id',
		'INTO'		=> 'topics',
		'VALUES'	=> '\''.$forum_db->escape($username).'\', 2, \''.$lang_install['Default topic subject'].'\', '.$now.', 1, '.$now.', 1, \''.$forum_db->escape($username).'\', 2, '.$forum_db->insert_id().''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, posted, topic_id',
		'INTO'		=> 'posts',
		'VALUES'	=> '\''.$forum_db->escape($username).'\', '.$new_uid.', \''.$forum_db->escape(get_remote_address()).'\', \''.$lang_install['Default post contents'].'\', '.$now.', '.$forum_db->insert_id().''
	);

	if ($db_type != 'pgsql')
	{
		$query['INSERT'] .= ', id';
		$query['VALUES'] .= ', 1';
	}

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Add new post to search table
	require FORUM_ROOT.'include/search_idx.php';
	update_search_index('post', $forum_db->insert_id(), $lang_install['Default post contents'], $lang_install['Default topic subject']);

	// Insert the default ranks
	$query = array(
		'INSERT'	=> 'rank, min_posts',
		'INTO'		=> 'ranks',
		'VALUES'	=> '\''.$lang_install['Default rank 1'].'\', 0'
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'INSERT'	=> 'rank, min_posts',
		'INTO'		=> 'ranks',
		'VALUES'	=> '\''.$lang_install['Default rank 2'].'\', 10'
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_db->end_transaction();


	$alerts = array();
	// Check if the cache directory is writable
	if (!@is_writable('./cache/'))
		$alerts[] = '<li><span>'.$lang_install['No cache write'].'</span></li>';

	// Check if default avatar directory is writable
	if (!@is_writable('./resources/avatars/'))
		$alerts[] = '<li><span>'.$lang_install['No avatar write'].'</span></li>';

	// Check if we disabled uploading avatars because file_uploads was disabled
	if ($avatars == '0')
		$alerts[] = '<li><span>'.$lang_install['File upload alert'].'</span></li>';

	// Add some random bytes at the end of the cookie name to prevent collisions
	$cookie_name = 'flazy_cookie_'.random_key(6, false, true);

	// Generate the config.php file data
	$config = generate_config_file();

	// Attempt to write config.php and serve it up for download if writing fails
	$written = false;
	if (is_writable(FORUM_ROOT.'include/'))
	{
		$fh = @fopen(FORUM_ROOT.'include/config.php', 'wb');
		if ($fh)
		{
			fwrite($fh, $config);
			fclose($fh);

			$written = true;
		}
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Инсталация на Flazy</title>
<?php

echo '<link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'style/base.css" />'."\n";
echo '<link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'style/'.$default_style.'/'.$default_style.'.css" />'."\n";
echo '<link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'style/'.$default_style.'/'.$default_style.'_cs.css" />'."\n";
echo '<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'style/'.$default_style.'/'.$default_style.'_ie6.css" /><![endif]-->'."\n";
echo '<!--[if IE 7]]><link rel="stylesheet" type="text/css" href="'.FORUM_ROOT.'style/'.$default_style.'/'.$default_style.'_ie7.css" /><![endif]-->'."\n";

?>
</head>
<body>

<div id="brd-install" class="brd-page">
<div id="brd-wrap" class="brd">

<div id="brd-head" class="gen-content">
	<p id="brd-title"><strong><?php printf($lang_install['Install Flazy'], FORUM_VERSION) ?></strong></p>
	<p id="brd-desc"><?php printf($lang_install['Success description'], FORUM_VERSION) ?></p>
</div>

<div id="brd-visit" class="gen-content">
	<p><?php echo $lang_install['Success welcome'] ?></p>
</div>

<div id="brd-main" class="main basic">
	<div class="main-head">
		<h1 class="hn"><span><?php echo $lang_install['Final instructions'] ?></span></h1>
	</div>
	<div class="main-content main-frm">
<?php

if (!$written)
{

?>
		<div class="ct-box info-box">
			<p class="warn"><?php echo $lang_install['No write info 1'] ?></p>
			<p class="warn"><?php printf($lang_install['No write info 2'], '<a href="'.FORUM_ROOT.'index.php">'.$lang_install['Go to index'].'</a>') ?></p>
		</div>
<?php if (!empty($alerts)): ?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><span><?php echo $lang_install['Warning'] ?></p></span></h2> 
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $alerts)."\n" ?>
			</ul>
		</div>
<?php endif; ?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="install.php">
			<div class="hidden">
				<input type="hidden" name="generate_config" value="1" />
				<input type="hidden" name="db_type" value="<?php echo $db_type; ?>" />
				<input type="hidden" name="db_host" value="<?php echo $db_host; ?>" />
				<input type="hidden" name="db_name" value="<?php echo forum_htmlencode($db_name); ?>" />
				<input type="hidden" name="db_username" value="<?php echo forum_htmlencode($db_username); ?>" />
				<input type="hidden" name="db_password" value="<?php echo forum_htmlencode($db_password); ?>" />
				<input type="hidden" name="db_prefix" value="<?php echo forum_htmlencode($db_prefix); ?>" />
				<input type="hidden" name="base_url" value="<?php echo forum_htmlencode($base_url); ?>" />
				<input type="hidden" name="cookie_name" value="<?php echo forum_htmlencode($cookie_name); ?>" />
			</div>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" value="<?php echo $lang_install['Download config'] ?>" /></span>
			</div>
		</form>
<?php

}
else
{

?>
		<div class="ct-box info-box">
			<p class="warn"><?php printf($lang_install['Write info'], '<a href="'.FORUM_ROOT.'index.php">'.$lang_install['Go to index'].'</a>') ?></p>
		</div>
<?php

}

?>
	</div>
</div>
</div>
</div>
</body>
</html>

<?php

}