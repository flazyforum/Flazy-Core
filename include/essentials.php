<?php
/**
 * Loads the minimum amount of data (eg: functions, database connection, config data, etc) necessary to integrate the site.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
{
	header('Content-type: text/html; charset=utf-8');
	die('Константа FORUM_ROOT должны быть определена и ссылаться на действующий корневой каталог Flazy.');
}

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '0.7.2');
define('FORUM_DB_REVISION', '13.2');

// Загрузить скрипт с функциями
require FORUM_ROOT.'include/functions/common.php';

// Загрузить функции UTF-8
require FORUM_ROOT.'include/utf8/utf8.php';

// Обратный эффект register_globals
forum_unregister_globals();

// Ignore any user abort requests
ignore_user_abort(true);

// Attempt to load the configuration file config.php
$config = FORUM_ROOT.'include/config.php';
if (file_exists($config))
{
	$perms = @fileperms($config);
        if (!($perms === false) && ($perms & 2))
		error('Не правильные права доступа на файл \'config.php\', право на запись должен иметь только \'Владелец\'. Отключите возможность записи для групп и других пользователей.');
	else
		require $config;
}

if (!defined('FORUM'))
	error('The file \'config.php\' doesn\'t exist or is corrupt.<br />Please run <a href="'.FORUM_ROOT.'admin/install.php">install.php</a> to install Flazy first.');

// Загрузить скрипт с классами
require FORUM_ROOT.'include/class/common.php';

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
	header('HTTP/1.1 403 Prefetching Forbidden');

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compability
	die;
}

// Record the start time (will be used to calculate the generation time for the page)
$forum_start = get_microtime();

// Make sure PHP reports all errors except E_NOTICE. Flazy supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('FORUM_DEBUG'))
	error_reporting(E_ALL);
else
	error_reporting(E_ALL ^ E_NOTICE);

// Detect UTF-8 support in PCRE
if ((version_compare(PHP_VERSION, '5.1.0', '>=') || (version_compare(PHP_VERSION, '5.0.0-dev', '<=') && version_compare(PHP_VERSION, '4.4.0', '>='))) && @/**/preg_match('/\p{L}/u', 'a') !== FALSE)
	{
		define('FORUM_SUPPORT_PCRE_UNICODE', 1);
	}
// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

if(function_exists('date_default_timezone_set'))
	date_default_timezone_set('UTC');

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load DB abstraction layer and connect
if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb' || $db_type == 'pgsql' ||  $db_type == 'sqlite')
	require FORUM_ROOT.'include/dblayer/'.$db_type.'.php';
else
	error('\''.$db_type.'\' - не правильный тип базы данных. Пожалуйста, проверьте настройки в config.php.', __FILE__, __LINE__);

// Create the database adapter object (and open/connect to/select db)
$forum_db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);

// Пароль больше не нужен, уберёв в целях безопастности.
unset($db_password);

// Start a transaction
$forum_db->start_transaction();

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

if (!defined('FORUM_CONFIG_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_config_cache();
	require FORUM_CACHE_DIR.'cache_config.php';
}

// Load hooks
if (file_exists(FORUM_CACHE_DIR.'cache_hooks.php'))
	include FORUM_CACHE_DIR.'cache_hooks.php';

if (!defined('FORUM_HOOKS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_hooks_cache();
	require FORUM_CACHE_DIR.'cache_hooks.php';
}

if (!defined('FORUM_AVATAR_DIR'))
	define('FORUM_AVATAR_DIR', 'img/avatars/');

// If the request_uri is invalid try fix it
if (!defined('FORUM_IGNORE_REQUEST_URI'))
	forum_fix_request_uri();

if (!isset($base_url))
{
	// Make an educated guess regarding base_url
	$base_url_guess = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);

	$base_url = $base_url_guess;
}

// Verify that we are running the proper database schema revision
if (defined('PUN') || !isset($forum_config['o_database_revision']) || $forum_config['o_database_revision'] < FORUM_DB_REVISION || version_compare($forum_config['o_cur_version'], FORUM_VERSION, '<'))
	error('Your Flazy database is out-of-date and must be upgraded in order to continue.<br />Please run <a href="'.$base_url.'/admin/db_update.php">db_update.php</a> in order to complete the upgrade process.');


// Load hooks
if (file_exists(FORUM_CACHE_DIR.'cache_hooks.php'))
	include FORUM_CACHE_DIR.'cache_hooks.php';

if (!defined('FORUM_HOOKS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_hooks_cache();
	require FORUM_CACHE_DIR.'cache_hooks.php';
}
// Define a few commonly used constants
define('FORUM_UNVERIFIED', 0);
define('FORUM_ADMIN', 1);
define('FORUM_GUEST', 2);

// A good place to add common functions for your extension
($hook = get_hook('es_essentials')) ? eval($hook) : null;

if (!defined('FORUM_MAX_POSTSIZE'))
	define('FORUM_MAX_POSTSIZE', 65535);

if (!defined('FORUM_SEARCH_MIN_WORD'))
	define('FORUM_SEARCH_MIN_WORD', 3);
if (!defined('FORUM_SEARCH_MAX_WORD'))
	define('FORUM_SEARCH_MAX_WORD', 20);

define('FORUM_ESSENTIALS_LOADED', 1);
