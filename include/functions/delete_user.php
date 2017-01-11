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
 * Удалить участника и все данные связанные с ним.
 * @param int ID участника.
 * @param bool Удалять все сообщения участника.
 */
function delete_user($user_id, $delete_posts = false)
{
	global $forum_db, $db_type, $forum_config;

	$return = ($hook = get_hook('fn_delete_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// First we need to get some data on the user
	$query = array(
		'SELECT'	=> 'u.username, u.group_id, g.g_moderator',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'groups AS g',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'u.id='.$user_id
	);

	($hook = get_hook('fn_delete_user_qr_get_user_data')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$user = $forum_db->fetch_assoc($result);

	// Delete any subscriptions
	$query = array(
		'DELETE'	=> 'subscriptions',
		'WHERE'		=> 'user_id='.$user_id
	);

	($hook = get_hook('fn_delete_user_qr_delete_subscriptions')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Remove him/her from the online list (if they happen to be logged in)
	$query = array(
		'DELETE'	=> 'online',
		'WHERE'		=> 'user_id='.$user_id
	);

	($hook = get_hook('fn_delete_user_qr_delete_online')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Should we delete all posts made by this user?
	if ($delete_posts)
	{
		@set_time_limit(0);

		// Find all posts made by this user
		$query = array(
			'SELECT'	=> 'p.id, p.topic_id, t.forum_id, t.first_post_id, t.question',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'topics AS t',
					'ON'			=> 't.id=p.topic_id'
				)
			),
			'WHERE'		=> 'p.poster_id='.$user_id
		);

		($hook = get_hook('fn_delete_user_qr_get_user_posts')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!defined('FORUM_FUNCTIONS_DELETE_TOPIC'))
			require FORUM_ROOT.'include/functions/delete_post.php';
		if (!defined('FORUM_FUNCTIONS_DELETE_POST'))
			require FORUM_ROOT.'include/functions/delete_topic.php';

		while ($cur_post = $forum_db->fetch_assoc($result))
		{
			if ($cur_post['first_post_id'] == $cur_post['id'])
				delete_topic($cur_post['topic_id'], $cur_post['forum_id'], $cur_post['question']);
			else
				delete_post($cur_post['id'], $cur_post['topic_id'], $cur_post['forum_id']);
		}
	}
	else
	{
		// Set all his/her posts to guest
		$query = array(
			'UPDATE'	=> 'posts',
			'SET'		=> 'poster_id=1',
			'WHERE'		=> 'poster_id='.$user_id
		);

		($hook = get_hook('fn_delete_user_qr_reset_user_posts')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// Delete the user
	$query = array(
		'DELETE'	=> 'users',
		'WHERE'		=> 'id='.$user_id
	);

	($hook = get_hook('fn_delete_user_qr_delete_user')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Delete PM
	$query = array(
		'DELETE'    => 'pm',
		'WHERE'     => 'receiver_id='.$user_id.' AND deleted_by_sender=1'
	);

	($hook = get_hook('fn_fl_delete_user_qr_delete_pm')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
	    'UPDATE'    => 'pm',
		'SET'       => 'deleted_by_receiver=1',
	    'WHERE'     => 'receiver_id='.$user_id
	);

	($hook = get_hook('fn_fl_delete_user_qr_update_pm')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Delete user avatar
	if (!defined('FORUM_FUNCTIONS_DELETE_AVATAR'))
		require FORUM_ROOT.'include/functions/delete_avatar.php';

	delete_avatar($user_id);

	// If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums
	// and regenerate the bans cache (in case he/she created any bans)
	if ($user['group_id'] == FORUM_ADMIN || $user['g_moderator'])
	{
		if (!defined('FORUM_FUNCTIONS_CLEAN_FORUM_MODERATORS'))
			require FORUM_ROOT.'include/functions/clean_forum_moderators.php';

		clean_forum_moderators();

		// Regenerate the bans cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();
	}

	// Regenerate cache
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();

	($hook = get_hook('fn_delete_user_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_DELETE_USER', 1);
