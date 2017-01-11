<?php
/**
 * Скрипт обновления базы данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


define('UPDATE_TO', '0.7');
define('PRE_VERSION', '0.6.2');
define('UPDATE_TO_DB_REVISION', '13');

$version_history = array(
	UPDATE_TO
);

// The number of items to process per pageview (lower this if the update script times out during UTF-8 conversion)
define('PER_PAGE', 300);

define('MIN_MYSQL_VERSION', '4.1.2');

header('Content-Type: text/html; charset=utf-8');

// Make sure we are running at least PHP 4.3.0
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	die('Ваша версия PHP '.PHP_VERSION.'. Чтобы правильно работать, Flazy требуется  хотя бы PHP '.MIN_PHP_VERSION.'. Вам необходимо обновить PHP, и только тогда вы сможите прожолжить установку.');

define('FORUM_ROOT', '../');

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT.'include/config.php'))
	include FORUM_ROOT.'include/config.php';

// If FORUM isn't defined, config.php is missing or corrupt or we are outside the root directory
if (!defined('FORUM'))
	die('Не могу найти config.php, вы уверены, что он существует?');

// Enable debug mode
if (!defined('FORUM_DEBUG'))
	define('FORUM_DEBUG', 1);

// Turn on full PHP error reporting
error_reporting(E_ALL);

// Turn off magic_quotes_runtime
if (get_magic_quotes_runtime())
	set_magic_quotes_runtime(0);

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
	error('\''.$db_type.'\' - не правильный тип базы данных. Пожалуйста, проверьте настройки в config.php.', __FILE__, __LINE__);

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
		error('Вы используете MySQL '.$mysql_version.'. Flazy '.UPDATE_TO.' требует, по минимум MySQL '.MIN_MYSQL_VERSION.' для правильной работы. Сначало вы должны обновить MySQL и только тогда вы сможете продолжить.');
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
		error('Чтобы обновить Ваш форум до версии '.UPDATE_TO.' сначало его требуется обновить до предущей версии '.PRE_VERSION.' и только тогда вы сможете продолжить. Узнать какие версии вам нужны Вы можете <a href="http://flazy.ru/wiki/Скрипты_обновления">здесь</a>.');
}

// If $base_url isn't set, use o_base_url from config
if (!isset($base_url))
	$base_url = $forum_config['o_base_url'];

// There's no $forum_user, but we need the style element
// We default to Flazy_Cold if the default style is invalid.
if (file_exists(FORUM_ROOT.'style/'.$forum_config['o_default_style'].'/'.$forum_config['o_default_style'].'.php'))
	$forum_user['style'] = $forum_config['o_default_style'];
else
{
	$forum_user['style'] = 'Flazy_Cold';

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value=\'Flazy_Cold\'',
		'WHERE'		=> 'conf_name=\'o_default_style\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

$maintenance_message = $forum_config['o_maintenance_message'];

if(empty($style_url))
	$style_url = $base_url;

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
<title>Обновление Базы Данных Flazy</title>
<?php

// Include the stylesheets
echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'/style/base.css" />';
require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

?>
<script type="text/javascript" src="<?php echo $base_url ?>/js/common.js"></script>
</head>
<body>

<div id="brd-update" class="brd-page">
<div id="brd-wrap" class="brd">

<div id="brd-head" class="gen-content">
	<p id="brd-title"><strong>Обновление Базы Данных Flazy</strong></p>
	<p id="brd-desc">Обновление таблиц БД</p>
</div>

<div id="brd-main" class="main basic">
	<div class="main-head">
		<h1 class="hn"><span>Обновление Базы Данных Flazy: Выполните обновление.</span></h1>
	</div>
	<div class="main-content frm">
		<div class="ct-box info-box">
			<ul class="spaced">
				<li class="warn"><span><strong>Внимание!</strong> Процедура обновления может занять от нескольких секунд до нескольких минут (или, в крайнем случае, часов) в зависимости от скорости сервера, размера базы данных форума, и числа требуемых изменений.</span></li>
				<li><span>Не забудьте сделать резервную копию данных перед тем, как продолжить.</span></li>
				<li><span>Прочитали ли вы <a href="http://flazy.ru/flazy/wiki/obnovlenie#obnovlenie_do_novoj_versii"><span>инструкциию по обновлению</span></a>? Если нет, обязательно прочитайте.</span></li>
			</ul>
		</div>
<?php

	$current_url = get_current_url();

?>
		<form class="frm-form" method="get" accept-charset="utf-8" action="<?php echo $current_url ?>">
			<div class="hidden">
				<input type="hidden" name="stage" value="start" />
			</div>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="start" value="Начать обновление" /></span>
			</div>
		</form>
	</div>

</div>

</div>
</div>
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

			if ($version == UPDATE_TO && (version_compare($cur_version, UPDATE_TO, '<') || strpos($cur_version, 'dev') !== false))
			{
				if (!$forum_db->index_exists('posts', 'posted_idx'))
					$forum_db->add_index('posts', 'posted_idx', array('posted'));

				if (!$forum_db->field_exists('reports', 'reason'))
					$forum_db->rename_field('reports', 'message', 'reason', 'TEXT', true);
				$forum_db->rename_field('reputation', 'rep_plus', 'plus', 'TINYINT(1)', false, 0);
				$forum_db->rename_field('reputation', 'rep_minus', 'minus', 'TINYINT(1)', false, 0);
				$forum_db->rename_field('users', 'rep_plus', 'reputation_plus', 'INT(10) UNSIGNED', false, 0);
				$forum_db->rename_field('users', 'rep_minus', 'reputation_minus', 'INT(10) UNSIGNED', false, 0);
				$forum_db->rename_field('users', 'pos_plus', 'positive_plus', 'INT(10) UNSIGNED', false, 0);
				$forum_db->rename_field('users', 'pos_minus', 'positive_minus', 'INT(10) UNSIGNED', false, 0);

				$forum_db->add_field('reports', 'pm_id', 'INT(10) UNSIGNED', false, '0');
				$forum_db->add_field('reports', 'poster_id', 'INT(10) UNSIGNED', false, '1');
				$forum_db->add_field('reports', 'message', 'TEXT', true);
				$forum_db->add_field('users', 'fasety_auth', 'TINYINT(1)', false, '0');
				$forum_db->add_field('users', 'fasety_last_auth', 'INT(10) UNSIGNED', false, '0');
				$forum_db->add_field('users', 'fasety_auth_mail', 'TINYINT(1)', false, '0');

				$config = array(
					'o_spam_username'		=> "'".$forum_config['o_spam_name']."'",
					'o_gravatar'			=> "'G'",
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

				// Изменения полей
				$forum_db->alter_field('topics', 'description', 'VARCHAR(255)', true, '\'\'', 'subject');
				$forum_db->alter_field('topics', 'question', 'VARCHAR(255)', true, '\'\'', 'description');
				$forum_db->alter_field('users', 'avatar', 'CHAR(3)', true, null, 'title');
				$forum_db->alter_field('users', 'time_format', 'TINYINT(1)', false, '0', 'dst');
				$forum_db->alter_field('users', 'date_format', 'TINYINT(1)', false, '0', 'time_format');

				// DELETE
				// Удалить историю поиска
				$query = array(
					'DELETE'	=> 'search_cache',
				);
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$config_names = array('o_avatars_dir, o_forum_branch, o_spam_name, o_show_ua_info');
				$query = array(
					'DELETE'	=> 'config',
					'WHERE'		=> 'conf_name IN (\''.implode('\', \'', $config_names).'\')'
				);
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
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

		// Empty the PHP cache
		forum_clear_cache();

		define ('FORUM_PAGE', 'dbupdate-finish');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Обновление Базы Данных Flazy</title>
<?php

// Include the stylesheets
echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'/style/base.css" />';
require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

?>
<script type="text/javascript" src="<?php echo $base_url ?>/js/common.js"></script>
</head>
<body>

<div id="brd-update" class="brd-page">
<div id="brd-wrap" class="brd">

<div id="brd-head" class="gen-content">
	<p id="brd-title"><strong>Обновление Базы Данных Flazy</strong></p>
	<p id="brd-desc">Обновление таблиц БД</p>
</div>

<div id="brd-main" class="main basic">

	<div class="main-head">
		<h1 class="hn"><span>Обновление Базы Данных Flazy завершено!</span></h1>
	</div>

	<div class="main-content frm">
		<div class="ct-box info-box">
			<p>База вашего форума обнавлена успешно и вы можете удалить все исправления форума, так как они включены в этот релиз.</p>
			<p>Теперь вы можете перейти на <a href="<?php echo $base_url ?>/index.php">главную страница форума</a>.</p>
		</div>
	</div>

</div>

</div>
</div>
</body>
</html>
<?php

		break;
}

$forum_db->end_transaction();
$forum_db->close();

if ($query_str != '')
	die('<script type="text/javascript">window.location="db_update.php'.$query_str.'"</script><br />JavaScript, кажется, отлючён. <a href="db_update.php'.$query_str.'">Нажмите для продолжения</a>.');
