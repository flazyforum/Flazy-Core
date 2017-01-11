<?php
/**
 * Функции кеша.
 *
 * Этот скрипт содержит все функции используемые для создания кеш-файлов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Создать кеш config
function generate_config_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_generate_config_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get the forum config from the DB
	$query = array(
		'SELECT'	=> 'c.conf_name, c.conf_value',
		'FROM'		=> 'config AS c'
	);

	($hook = get_hook('ch_fn_generate_config_cache_qr_get_config')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	while ($cur_config_item = $forum_db->fetch_row($result))
		$output[$cur_config_item[0]] = $cur_config_item[1];

	// Output config as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_config.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл конфигурации в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_CONFIG_LOADED\', 1);'."\n\n".'$forum_config = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

// Создать кеш bans
function generate_bans_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_generate_bans_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get the ban list from the DB
	$query = array(
		'SELECT'	=> 'b.id, b.username, b.ip, b.email, b.message, b.expire, b.ban_creator, u.username AS ban_creator_username',
		'FROM'		=> 'bans AS b',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=b.ban_creator'
			)
		),
		'ORDER BY'	=> 'b.id'
	);

	($hook = get_hook('ch_fn_generate_bans_cache_qr_get_bans')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	while ($cur_ban = $forum_db->fetch_assoc($result))
		$output[] = $cur_ban;

	// Output ban list as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_bans.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл заблокированых в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_BANS_LOADED\', 1);'."\n\n".'$forum_bans = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

// Создать кеш ranks
function generate_ranks_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_generate_ranks_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get the rank list from the DB
	$query = array(
		'SELECT'	=> 'r.id, r.rank, r.min_posts',
		'FROM'		=> 'ranks AS r',
		'ORDER BY'	=> 'r.min_posts'
	);

	($hook = get_hook('ch_fn_generate_ranks_cache_qr_get_ranks')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	while ($cur_rank = $forum_db->fetch_assoc($result))
		$output[] = $cur_rank;

	// Output ranks list as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_ranks.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл рангов в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_RANKS_LOADED\', 1);'."\n\n".'$forum_ranks = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

// Создать кеш слов цензуры
function generate_censors_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_generate_censors_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get the censor list from the DB
	$query = array(
		'SELECT'	=> 'c.id, c.search_for, c.replace_with',
		'FROM'		=> 'censoring AS c',
		'ORDER BY'	=> 'c.search_for'
	);

	($hook = get_hook('ch_fn_generate_censors_cache_qr_get_censored_words')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	while ($cur_censor = $forum_db->fetch_assoc($result))
		$output[] = $cur_censor;

	// Output censors list as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_censors.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл цензуры в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_CENSORS_LOADED\', 1);'."\n\n".'$forum_censors = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

// Создать кеш hooks
function generate_hooks_cache()
{
	global $forum_db, $forum_config, $base_url;

	$return = ($hook = get_hook('ch_fn_generate_hooks_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get the hooks from the DB
	$query = array(
		'SELECT'	=> 'eh.id, eh.code, eh.extension_id, e.dependencies',
		'FROM'		=> 'extension_hooks AS eh',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'extensions AS e',
				'ON'			=> 'e.id=eh.extension_id'
			)
		),
		'WHERE'		=> 'e.disabled=0',
		'ORDER BY'	=> 'eh.priority, eh.installed'
	);

	($hook = get_hook('ch_fn_generate_hooks_cache_qr_get_hooks')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$output = array();
	while ($cur_hook = $forum_db->fetch_assoc($result))
	{
		$load_ext_info = '$GLOBALS[\'ext_info_stack\'][] = array('."\n".
			'\'id\'				=> \''.$cur_hook['extension_id'].'\','."\n".
			'\'path\'			=> FORUM_ROOT.\'extensions/'.$cur_hook['extension_id'].'\','."\n".
			'\'url\'			=> $GLOBALS[\'base_url\'].\'/extensions/'.$cur_hook['extension_id'].'\','."\n".
			'\'dependencies\'	=> array ('."\n";

		$dependencies = explode('|', substr($cur_hook['dependencies'], 1, -1));
		foreach ($dependencies as $cur_dependency)
		{
			// This happens if there are no dependencies because explode ends up returning an array with one empty element
			if (empty($cur_dependency))
				continue;

			$load_ext_info .= '\''.$cur_dependency.'\'	=> array('."\n".
				'\'id\'				=> \''.$cur_dependency.'\','."\n".
				'\'path\'			=> FORUM_ROOT.\'extensions/'.$cur_dependency.'\','."\n".
				'\'url\'			=> $GLOBALS[\'base_url\'].\'/extensions/'.$cur_dependency.'\'),'."\n";
		}

		$load_ext_info .= ')'."\n".');'."\n".'$ext_info = $GLOBALS[\'ext_info_stack\'][count($GLOBALS[\'ext_info_stack\']) - 1];';
		$unload_ext_info = 'array_pop($GLOBALS[\'ext_info_stack\']);'."\n".'$ext_info = empty($GLOBALS[\'ext_info_stack\']) ? array() : $GLOBALS[\'ext_info_stack\'][count($GLOBALS[\'ext_info_stack\']) - 1];';

		$output[$cur_hook['id']][] = $load_ext_info."\n\n".$cur_hook['code']."\n\n".$unload_ext_info."\n";
	}

	// Output hooks as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_hooks.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл расширений в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_HOOKS_LOADED\', 1);'."\n\n".'$hooks = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

// Создать кеш обновлений
function generate_updates_cache()
{
	global $forum_db, $forum_config;

	$return = ($hook = get_hook('ch_fn_generate_updates_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get a list of installed hotfix extensions
	$query = array(
		'SELECT'	=> 'e.id',
		'FROM'		=> 'extensions AS e',
		'WHERE'		=> 'e.id LIKE \'hotfix_%\''
	);

	($hook = get_hook('ch_fn_generate_updates_cache_qr_get_hotfixes')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_hotfixes = $forum_db->num_rows($result);

	$hotfixes = array();
	for ($i = 0; $i < $num_hotfixes; ++$i)
		$hotfixes[] = urlencode($forum_db->result($result, $i));

	// Contact the flazy.ru updates service
	if (!defined('FORUM_FUNCTIONS_GET_REMOTE_FILE'))
		require FORUM_ROOT.'include/functions/get_remote_file.php';
	$result = get_remote_file('http://flazy.ru/update/index.php?version='.str_replace(' ', '', $forum_config['o_cur_version']).'&hotfixes='.implode(',', $hotfixes), 8);

	// Make sure we got everything we need
	if ($result != null && strpos($result['content'], '</updates>') !== false)
	{
		if (!defined('FORUM_XML_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/xml.php';

		$output = xml_to_array($result['content']);
		$output = current($output);
		$output['cached'] = time();
		$output['fail'] = false;
	}
	else	// If the update check failed, set the fail flag
		$output = array('cached' => time(), 'fail' => true);

	// This hook could potentially (and responsibly) be used by an extension to do its own little update check
	($hook = get_hook('ch_fn_generate_updates_cache_write')) ? eval($hook) : null;

	// Output update status as PHP code
	$fh = @fopen(FORUM_CACHE_DIR.'cache_updates.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл обновлений в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'if (!defined(\'FORUM_UPDATES_LOADED\')) define(\'FORUM_UPDATES_LOADED\', 1);'."\n\n".'$forum_updates = '.var_export($output, true).';'."\n\n".'?>');
	fclose($fh);
}

define('FORUM_CACHE_FUNCTIONS_LOADED', 1);
