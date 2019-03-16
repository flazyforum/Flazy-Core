<?php
/**
 * Скрипт обновления базы данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


define('UPDATE_TO', '0.7.2');
define('PRE_VERSION', '0.7.1');
define('UPDATE_TO_DB_REVISION', '13.2');

$version_history = array(
	UPDATE_TO 
);

// The number of items to process per pageview (lower this if the update script times out during UTF-8 conversion)
define('PER_PAGE', 300);

define('MIN_MYSQL_VERSION', '4.1.2');

header('Content-Type: text/html; charset=utf-8');

// Make sure we are running at least PHP 5
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	die('Your version of PHP '.PHP_VERSION.'. To work properly, flazy requires at least PHP '.MIN_PHP_VERSION.'. You need to upgrade PHP version, and only then you will be able to install flazy.');

define('FORUM_ROOT', '../');

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT.'include/config.php'))
	include FORUM_ROOT.'include/config.php';

// If FORUM isn't defined, config.php is missing or corrupt or we are outside the root directory
if (!defined('FORUM'))
	die("Can't find config.php, are you sure it exists?");

// Enable debug mode
if (!defined('FORUM_DEBUG'))
	define('FORUM_DEBUG', 1);

// Turn on full PHP error reporting
error_reporting(E_ALL);

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime())
	@ini_set('magic_quotes_runtime', false);

// Turn off PHP time limit
@set_time_limit(0);

// If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
	$cookie_name = 'flazy_cookie';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load the functions script
require FORUM_ROOT.'include/functions/common.php';

// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// If the request_uri is invalid try fix it
if (!defined('FORUM_IGNORE_REQUEST_URI'))
	forum_fix_request_uri();

// Instruct DB abstraction layer that we don't want it to "SET NAMES". If we need to, we'll do it ourselves below.
define('FORUM_NO_SET_NAMES', 1);

// Load DB abstraction layer and try to connect
if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb' || $db_type == 'pgsql' ||  $db_type == 'sqlite')
	require FORUM_ROOT.'include/dblayer/'.$db_type.'.php';
else
	error('\''.$db_type.'\' - wrong type database. Please check config.php.', __FILE__, __LINE__);

// Create the database adapter object (and open/connect to/select db)
$forum_db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);

// Check current version
$query = array(
	'SELECT'	=> 'conf_value',
	'FROM'		=> 'config',
	'WHERE'		=> 'conf_name = \'o_cur_version\''
);

$result = $forum_db->query_build($query);
$cur_version = $forum_db->result($result);

// Now we're definitely using UTF-8, so we convert the output properly
$forum_db->set_names('utf8');

// If MySQL, make sure it's at least 4.1.2
if ($db_type == 'mysql' || $db_type == 'mysqli')
{
	$mysql_info = $forum_db->get_version();
	if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<'))
		error('Your version of MySQL is '.$mysql_version.'. Flazy '.UPDATE_TO.' it requires at least MySQL '.MIN_MYSQL_VERSION.' to work corret. First you need to upgrade MySQL and then you will be able to continue.');
}

// Get the forum config
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'config'
);

$result = $forum_db->query_build($query);
while ($cur_config_item = $forum_db->fetch_row($result))
	$forum_config[$cur_config_item[0]] = $cur_config_item[1];

if (strpos($forum_config['o_cur_version'], 'dev') === false)
{
	if (isset($forum_config['o_database_revision']) && $forum_config['o_database_revision'] >= UPDATE_TO_DB_REVISION && version_compare($forum_config['o_cur_version'], UPDATE_TO, '>='))
		error('Ваша база данных не нуждается в обновлении.');

	if (!version_compare($forum_config['o_cur_version'], PRE_VERSION, '>='))
		error('Чтобы обновить Ваш форум до версии '.UPDATE_TO.' сначало его требуется обновить до предущей версии '.PRE_VERSION.' и только тогда вы сможете продолжить. Узнать какие версии вам нужны Вы можете <a href="http://flazy.mgknet.com/wiki/Скрипты_обновления">здесь</a>.');
}

// If $base_url isn't set, use o_base_url from config
if (!isset($base_url))
	$base_url = $forum_config['o_base_url'];

// There's no $forum_user, but we need the style element
if (file_exists(FORUM_ROOT.'style/'.$forum_config['o_default_style'].'/'.$forum_config['o_default_style'].'.php'))
	$forum_user['style'] = $forum_config['o_default_style'];
else
{
	$forum_user['style'] = 'default';

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value=\'default\'',
		'WHERE'		=> 'conf_name=\'o_default_style\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

$maintenance_message = $forum_config['o_maintenance_message'];

if(empty($style_url))
	$style_url = $base_url;

//
// Determines whether $str is UTF-8 encoded or not
//
function seems_utf8($str)
{
	$str_len = strlen($str);
	for ($i = 0; $i < $str_len; ++$i)
	{
		if (ord($str[$i]) < 0x80) continue; # 0bbbbbbb
		else if ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
		else if ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
		else if ((ord($str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
		else if ((ord($str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
		else if ((ord($str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
		else return false; # Does not match any model

		for ($j = 0; $j < $n; ++$j) # n bytes matching 10bbbbbb follow ?
		{
			if ((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
				return false;
		}
	}

	return true;
}


//
// Translates the number from an HTML numeric entity into an UTF-8 character
//
function dcr2utf8($src)
{
	$dest = '';
	if ($src < 0)
		return false;
	else if ($src <= 0x007f)
		$dest .= chr($src);
	else if ($src <= 0x07ff)
	{
		$dest .= chr(0xc0 | ($src >> 6));
		$dest .= chr(0x80 | ($src & 0x003f));
	}
	else if ($src == 0xFEFF)
	{
		// nop -- zap the BOM
	}
	else if ($src >= 0xD800 && $src <= 0xDFFF)
	{
		// found a surrogate
		return false;
	}
	else if ($src <= 0xffff)
	{
		$dest .= chr(0xe0 | ($src >> 12));
		$dest .= chr(0x80 | (($src >> 6) & 0x003f));
		$dest .= chr(0x80 | ($src & 0x003f));
	}
	else if ($src <= 0x10ffff)
	{
		$dest .= chr(0xf0 | ($src >> 18));
		$dest .= chr(0x80 | (($src >> 12) & 0x3f));
		$dest .= chr(0x80 | (($src >> 6) & 0x3f));
		$dest .= chr(0x80 | ($src & 0x3f));
	}
	else
	{
		// out of range
		return false;
	}

	return $dest;
}


//
// Attemts to convert $str from $old_charset to UTF-8. Also converts HTML entities (including numeric entities) to UTF-8 characters.
//
function convert_to_utf8(&$str, $old_charset)
{
	if ($str == '')
		return false;

	$save = $str;

	// Replace literal entities (for non-UTF-8 compliant html_entity_encode)
	if (version_compare(PHP_VERSION, '5.0.0', '<') && $old_charset == 'ISO-8859-1' || $old_charset == 'ISO-8859-15')
		$str = html_entity_decode($str, ENT_QUOTES, $old_charset);

	if (!seems_utf8($str))
	{
		if ($old_charset == 'ISO-8859-1')
			$str = utf8_encode($str);
		else if (function_exists('iconv'))
			$str = iconv($old_charset, 'UTF-8', $str);
		else if (function_exists('mb_convert_encoding'))
			$str = mb_convert_encoding($str, 'UTF-8', $old_charset);
	}

	// Replace literal entities (for UTF-8 compliant html_entity_encode)
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

	// Replace numeric entities
	$str = preg_replace_callback('/&#([0-9]+);/', 'utf8_callback_1', $str);
	$str = preg_replace_callback('/&#x([a-f0-9]+);/i', 'utf8_callback_2', $str);

	return ($save != $str);
}


function utf8_callback_1($matches)
{
	return dcr2utf8($matches[1]);
}


function utf8_callback_2($matches)
{
	return dcr2utf8(hexdec($matches[1]));
}


//
// Tries to determine whether post data in the database is UTF-8 encoded or not
//
function db_seems_utf8()
{
	global $db_type, $forum_db;

	$seems_utf8 = true;

	$query = array(
		'SELECT'	=> 'MIN(id), MAX(id), COUNT(id)',
		'FROM'		=> 'posts'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($min_id, $max_id, $count_id) = $forum_db->fetch_row($result);

	if ($count_id == 0)
		return false;

	// Get a random soup of data and check if it appears to be UTF-8
	for ($i = 0; $i < 100; ++$i)
	{
		$id = ($i == 0) ? $min_id : (($i == 1) ? $max_id : rand($min_id, $max_id));

		$query = array(
			'SELECT'	=> 'p.message, p.poster, t.subject, f.forum_name',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'topics AS t',
					'ON'		=> 't.id = p.topic_id'
				),
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'		=> 'f.id = t.forum_id'
				)
			),
			'WHERE'		=> 'p.id >= '.$id,
			'LIMIT'		=> '1'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$random_row = $forum_db->fetch_row($result);

		if (!seems_utf8($random_row[0].$random_row[1].$random_row[2].$random_row[3]))
		{
			$seems_utf8 = false;
			break;
		}
	}

	return $seems_utf8;
}


//
// Safely converts text type columns into utf8 (MySQL only)
// Function based on update_convert_table_utf8() from the Drupal project (http://drupal.org/)
//
function convert_table_utf8($table)
{
	global $forum_db;

	$types = array(
		'char'			=> 'binary',
		'varchar'		=> 'varbinary',
		'tinytext'		=> 'tinyblob',
		'mediumtext'	=> 'mediumblob',
		'text'			=> 'blob',
		'longtext'		=> 'longblob'
	);

	// Set table default charset to utf8
	$forum_db->query('ALTER TABLE `'.$table.'` CHARACTER SET utf8') or error(__FILE__, __LINE__);

	// Find out which columns need converting and build SQL statements
	$result = $forum_db->query('SHOW FULL COLUMNS FROM `'.$table.'`') or error(__FILE__, __LINE__);
	while ($cur_column = $forum_db->fetch_assoc($result))
	{
		list($type) = explode('(', $cur_column['Type']);
		if (isset($types[$type]) && strpos($cur_column['Collation'], 'utf8') === false)
		{
			$allow_null = ($cur_column['Null'] == 'YES');

			$forum_db->alter_field($table, $cur_column['Field'], preg_replace('/'.$type.'/i', $types[$type], $cur_column['Type']), $allow_null, $cur_column['Default']);
			$forum_db->alter_field($table, $cur_column['Field'], $cur_column['Type'].' CHARACTER SET utf8', $allow_null, $cur_column['Default']);
		}
	}
}


// Move avatars to DB
function convert_avatars()
{
	global $forum_config, $forum_db;

	$avatar_dir = FORUM_ROOT.'img/avatars/';
	if (!is_dir($avatar_dir))
	{
		return false;
	}

	if ($handle = opendir($avatar_dir))
	{
		while (false !== ($avatar = readdir($handle)))
		{
			$avatar_file = $avatar_dir.$avatar;
			if (!is_file($avatar_file))
			{
				continue;
			}

			//echo $avatar_file;

			$avatar = basename($avatar_file);
			if (preg_match('/^(\d+)\.(png|gif|jpg)/', $avatar, $matches))
			{

				$user_id = intval($matches[1], 10);
				$avatar_ext = $matches[2];

				$avatar_type = FORUM_AVATAR_NONE;
				if ($avatar_ext == 'png')
				{
					$avatar_type = FORUM_AVATAR_PNG;
				}
				else if ($avatar_ext == 'gif')
				{
					$avatar_type = FORUM_AVATAR_GIF;
				}
				else if ($avatar_ext == 'jpg')
				{
					$avatar_type = FORUM_AVATAR_JPG;
				}

				// Check user and avatar type
				if ($user_id < 2 || $avatar_type == FORUM_AVATAR_NONE)
				{
					continue;
				}

				// Now check the width/height
				list($width, $height, $type,) = @/**/getimagesize($avatar_file);
				if (empty($width) || empty($height) || $width > $forum_config['o_avatars_width'] || $height > $forum_config['o_avatars_height'])
				{
					@/**/unlink($avatar_file);
				}
				else
				{
					// Save to DB
					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'avatar=\''.$avatar_type.'\', avatar_height=\''.$height.'\', avatar_width=\''.$width.'\'',
						'WHERE'		=> 'id='.$user_id
					);
					$forum_db->query_build($query) or error(__FILE__, __LINE__);
				}
			}
		}
		closedir($handle);
	}
}


header('Content-type: text/html; charset=utf-8');

// Empty all output buffers and stop buffering
while (@ob_end_clean());

$stage = isset($_GET['stage']) ? $_GET['stage'] : '';
$old_charset = isset($_GET['req_old_charset']) ? str_replace('ISO8859', 'ISO-8859', strtoupper($_GET['req_old_charset'])) : 'ISO-8859-1';
$start_at = isset($_GET['start_at']) ? intval($_GET['start_at']) : 0;
$query_str = '';

switch ($stage)
{
	// Show form
	case '':

	define ('FORUM_PAGE', 'dbupdate');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Database Update Flazy</title>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/bootstrap/css/bootstrap.min.css" />
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/dist/css/AdminLTE.min.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/dist/css/skins/_all-skins.min.css" />
<script  type="text/javascript" src="<?php echo $base_url ?>resources/admin/bootstrap/js/bootstrap.min.js"></script>
</head>
<body class="layout-boxed sidebar-mini skin-blue">
	<!-- Site wrapper -->
	<div class="wrapper">
		<header class="main-header">
			<!-- Logo -->
			<a href="../../" class="logo"> <!-- mini logo for sidebar mini 50x50 pixels --> <span class="logo-mini"><b>Flazy</b></span> <!-- logo for regular state and mobile devices --> <span class="logo-lg"><b>Fla</b>zy</span> </a>
			<!-- Header Navbar: style can be found in header.less -->
			<nav class="navbar navbar-static-top" role="navigation">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"> <span class="sr-only">Toggle navigation</span> </a>

			</nav>
		</header>

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1> Database Update <small>Updating db table</small></h1>
				<ol class="breadcrumb">
					<li>
						<a href="index.php"><i class="fa fa-dashboard"></i> Admin</a>
					</li>
					<li>
						<a href="settings.php">Settings</a>
					</li>
					<li class="active">
						Update
					</li>
				</ol>
			</section>

			<!-- Main content -->
			<section class="content">
				<div class="callout callout-info">
					<h4>Warning!</h4>
					<p>
						Update can take from few seconds to few minutes (or in some extreme cases even hours), it all depends on the speed of your server, the database size and the number of changes which are required.
					</p>
				</div>
				<!-- Default box -->
				<div class="box">
					<div class="box-header with-border">
						<h3 class="box-title">DB Update</h3>
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
						Before you make a update of your database, please make a copy if there is a crash or dublicated data.
					</div><!-- /.box-body -->
					<div class="box-footer">
<?php
	$current_url = get_current_url();
?>
		<form class="frm-form" method="get" accept-charset="utf-8" action="<?php echo $current_url ?>">
			<div class="hidden">
				<input type="hidden" name="stage" value="start" />
			</div>
			<div class="frm-buttons">
				<input type="submit" name="start" class="btn btn-primary" value="Update" />
			</div>
		</form>
					</div><!-- /.box-footer-->
				</div><!-- /.box -->
			</section><!-- /.content -->
		</div><!-- /.content-wrapper -->

		<footer class="main-footer">
			<div class="pull-right hidden-xs">
				<b>Version</b> 0.7.2
			</div>
			<strong>Copyright © 2013-2015 <a href="https://flazy.us">FLAZY</a>.</strong> All rights reserved.
		</footer>

	</div><!-- ./wrapper -->

	<!-- jQuery 2.1.4 -->
	<script src="<?php echo $base_url ?>/resources/admin/plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<!-- Bootstrap 3.3.5 -->
	<script src="<?php echo $base_url ?>/resiurces/admin/bootstrap/js/bootstrap.min.js"></script>


</body>
</html>
<?php

		break;

	// Start by updating the database structure
	case 'start':

		// Включение техобслуживания
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\'1\'',
			'WHERE'		=> 'conf_name=\'o_maintenance\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		require FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/admin_settings.php';

		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.$lang_admin_settings['Maintenance message default'].'\'',
			'WHERE'		=> 'conf_name=\'o_maintenance_message\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/cache.php';

		generate_config_cache();

		function query_update($version, $cur_version)
		{
			global $forum_db, $db_type, $forum_config;

			if ($version == '0.7.2' && version_compare($forum_config['o_cur_version'], $version, '<'))
			{
				$config = array(
					'o_about_us'			=> "'Flazy is forum cms '",
					'o_useful_links'		=> "'asd'",
					'o_social_links'		=> "'asd'",
					'o_board_keywords'		=> "'flazy, flazy cms, forum'",
				);

				foreach ($config as $conf_name => $conf_value)
				{
					if (!isset($forum_config[$conf_name]))
					{
						$query = array(
							'INSERT'	=> 'conf_name, conf_value',
							'INTO'		=> 'config',
							'VALUES'	=> '\''.$conf_name.'\', '.$conf_value.''
						);
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
				}
				
			}
		}

		foreach ($version_history as $key => $version)
			query_update($version, $forum_config['o_cur_version']);

		$query_str = '?stage=finish';

		break;

	case 'finish':

		// Delete hotfix
		$query = array(
			'DELETE'	=> 'extension_hooks',
			'WHERE'		=> 'extension_id LIKE \'hotfix%\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'extensions',
			'WHERE'		=> 'id LIKE \'hotfix%\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// We update the version number
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.UPDATE_TO.'\'',
			'WHERE'		=> 'conf_name=\'o_cur_version\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// And the database revision number
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.UPDATE_TO_DB_REVISION.'\'',
			'WHERE'		=> 'conf_name=\'o_database_revision\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Отключение техобслуживания
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\'0\'',
			'WHERE'		=> 'conf_name=\'o_maintenance\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.$maintenance_message.'\'',
			'WHERE'		=> 'conf_name=\'o_maintenance_message\''
		);
		
$forum_db->query_build($query) or error(__FILE__, __LINE__);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Empty the PHP cache
		forum_clear_cache();

		define ('FORUM_PAGE', 'dbupdate-finish');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Database Update Flazy</title>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/bootstrap/css/bootstrap.min.css" />
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/dist/css/AdminLTE.min.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $base_url ?>/resources/admin/dist/css/skins/_all-skins.min.css" />
<script  type="text/javascript" src="<?php echo $base_url ?>resources/admin/bootstrap/js/bootstrap.min.js"></script>
</head>
<body class="layout-boxed sidebar-mini skin-blue">
	<!-- Site wrapper -->
	<div class="wrapper">
		<header class="main-header">
			<!-- Logo -->
			<a href="../../" class="logo"> <!-- mini logo for sidebar mini 50x50 pixels --> <span class="logo-mini"><b>Flazy</b></span> <!-- logo for regular state and mobile devices --> <span class="logo-lg"><b>Fla</b>zy</span> </a>
			<!-- Header Navbar: style can be found in header.less -->
			<nav class="navbar navbar-static-top" role="navigation">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"> <span class="sr-only">Toggle navigation</span> </a>

			</nav>
		</header>

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1> Database Update <small>Updating db table</small></h1>
				<ol class="breadcrumb">
					<li>
						<a href="index.php"><i class="fa fa-dashboard"></i> Admin</a>
					</li>
					<li>
						<a href="settings.php">Settings</a>
					</li>
					<li class="active">
						Update
					</li>
				</ol>
			</section>

			<!-- Main content -->
			<section class="content">
				<div class="callout callout-success">
					<h4>Success!</h4>
					<p>
						Flazy database has been successfully updated.
					</p>
				</div>
				<!-- Default box -->
				<div class="box">
					<div class="box-header with-border">
						<h3 class="box-title">DB Update</h3>
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
						<p>The core of Flazy has been updated successfully and you can remove all the hotfixes now, because they are included in the release.</p>
						<p>Now you can go to your <a href="<?php echo $base_url ?>/index.php">forum home page</a>.</p>
					</div><!-- /.box-body -->
				</div><!-- /.box -->
			</section><!-- /.content -->
		</div><!-- /.content-wrapper -->

		<footer class="main-footer">
			<div class="pull-right hidden-xs">
				<b>Version</b> 0.0.2
			</div>
			<strong>Copyright © 2013-2015 <a href="https://flazy.us">FLAZY</a>.</strong> All rights reserved.
		</footer>

	</div><!-- ./wrapper -->

	<!-- jQuery 2.1.4 -->
	<script src="<?php echo $base_url ?>/resources/admin/plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<!-- Bootstrap 3.3.5 -->
	<script src="<?php echo $base_url ?>/resiurces/admin/bootstrap/js/bootstrap.min.js"></script>


</body>
</html>
<?php

		break;
}

$forum_db->end_transaction();
$forum_db->close();

if ($query_str != '')
	die('<script type="text/javascript">window.location="db_update.php'.$query_str.'"</script><br />JavaScript, кажется, отлючён. <a href="db_update.php'.$query_str.'">Нажмите для продолжения</a>.');
