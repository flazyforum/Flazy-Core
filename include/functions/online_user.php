<?php
/**
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Показывает список участников которые просматривают форум\тему в данный момент.
 * @param int ID страницы.
 * @param string Название страницы.
 * @return string Список участников через запятую, включая число гостей.
 */
function online_user($id, $type)
{
	global $forum_db, $forum_url, $forum_user, $lang_common;

	$return = ($hook = get_hook('fn_fl_online_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'o.user_id, o.ident',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.idle=0 AND o.current_page_id='.$id.' AND o.current_page=\''.$type.'\'',
		'ORDER BY'	=> 'o.ident'
	);

	($hook = get_hook('fn_fl_qr_get_users_on')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$num_users = $num_guests = 0;

	$users_on = array();
	$guests_on = array();

	if ($forum_user['is_guest'])
		$num_guests = 1;
	else
	{
		$users_on[] = '<a href="'.forum_link($forum_url['user'], $forum_user['id']).'">'.forum_htmlencode($forum_user['username']).'</a>';
		$num_users = 1;
	}

	while ($cur_user = $forum_db->fetch_assoc($result))
	{
		if ($cur_user['user_id'] != $forum_user['id'])
		{
			if ($cur_user['user_id'] > 1)
			{
				$users_on[] = '<a href="'.forum_link($forum_url['user'], $cur_user['user_id']).'">'.forum_htmlencode($cur_user['ident']).'</a>';
				++$num_users;
			}
			if ($cur_user['user_id'] == 1)
			{
				$guests_on[] = $cur_user['user_id'];
				++$num_guests;
			}
		}
	}

	$online_guests = ($num_guests > 0) ? ($num_users && $num_guests ? $lang_common['and'] : '').'<strong>'.forum_number_format($num_guests).'</strong> '.declination($num_guests, array($lang_common['Guests none'], $lang_common['Guests single'], $lang_common['Guests plural'])) : '';

	($hook = get_hook('fn_fl_online_user_end')) ? eval($hook) : null;

	return implode(', ', $users_on).$online_guests;
}

define('FORUM_FUNCTIONS_ONLINE_FT', 1);
