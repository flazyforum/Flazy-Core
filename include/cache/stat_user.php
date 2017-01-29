<?php
/**
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2017 Flazy.org
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Создание кеша статистики колличества участиков.
 * @param int ID нового участника, по умолчанию null.
 * @param string Имя нового участника, по умолчанию null.
 */
function generate_stat_user_cache($id = null, $username = null)
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fl_fn_generate_stat_user_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'COUNT(u.id)-1',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.group_id!='.FORUM_UNVERIFIED
	);

	($hook = get_hook('ch_fl_fn_generate_stats_qr_get_user_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$stats['total_users'] = $forum_db->result($result);

	if (empty($id) && empty($username))
	{
		$query = array(
			'SELECT'	=> 'u.id, u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.group_id!='.FORUM_UNVERIFIED,
			'ORDER BY'	=> 'u.registered DESC',
			'LIMIT'		=> '1'
		);

		($hook = get_hook('ch_fl_fn_generate_stats_qr_get_newest_user')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		list($id, $username) = $forum_db->fetch_row($result);
	}

	$load_info = '$forum_stat_user = array('."\n".
		'\'total_users\'	=> \''.$stats['total_users'].'\','."\n".
		'\'id\'				=> \''.$id.'\','."\n".
		'\'username\'		=> \''.$username.'\','."\n".
		')';

	$fh = @fopen(FORUM_CACHE_DIR.'cache_stat_user.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл колличества участников в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_STAT_USER_LOADED\', 1);'."\n\n".$load_info.';'."\n\n".'?>');
	fclose($fh);
}

define('FORUM_CACHE_STAT_USER_LOADED', 1);
