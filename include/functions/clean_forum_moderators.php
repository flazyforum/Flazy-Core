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
 * Последовательно проходит по списку модератор форума и удаляет любые ошибочных записей.
 */
function clean_forum_moderators()
{
	global $forum_db;

	$return = ($hook = get_hook('fn_clean_forum_moderators_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get a list of forums and their respective lists of moderators
	$query = array(
		'SELECT'	=> 'f.id, f.moderators',
		'FROM'		=> 'forums AS f',
		'WHERE'		=> 'f.moderators IS NOT NULL'
	);

	($hook = get_hook('fn_clean_forum_moderators_qr_get_forum_moderators')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$removed_moderators = array();
	while ($cur_forum = $forum_db->fetch_assoc($result))
	{
		$cur_moderators = unserialize($cur_forum['moderators']);
		$new_moderators = $cur_moderators;

		// Iterate through each user in the list and check if he/she is in a moderator or admin group
		foreach ($cur_moderators as $username => $user_id)
		{
			if (in_array($user_id, $removed_moderators))
			{
				unset($new_moderators[$username]);
				continue;
			}

			$query = array(
				'SELECT'	=> '1',
				'FROM'		=> 'users AS u',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'groups AS g',
						'ON'			=> 'g.g_id=u.group_id'
					)
				),
				'WHERE'		=> '(g.g_moderator=1 OR u.group_id=1) AND u.id='.$user_id
			);

			($hook = get_hook('fn_clean_forum_moderators_qr_check_user_in_moderator_group')) ? eval($hook) : null;
			$result2 = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			if (!$forum_db->num_rows($result2)) // If the user isn't in a moderator or admin group, remove him/her from the list
			{
				unset($new_moderators[$username]);
				$removed_moderators[] = $user_id;
			}
		}

		// If we changed anything, update the forum
		if ($cur_moderators != $new_moderators)
		{
			$new_moderators = (!empty($new_moderators)) ? '\''.$forum_db->escape(serialize($new_moderators)).'\'' : 'NULL';

			$query = array(
				'UPDATE'	=> 'forums',
				'SET'		=> 'moderators='.$new_moderators,
				'WHERE'		=> 'id='.$cur_forum['id']
			);

			($hook = get_hook('fn_qr_clean_forum_moderators_set_forum_moderators')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	($hook = get_hook('fn_clean_forum_moderators_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_CLEAN_FORUM_MODERATORS', 1);
