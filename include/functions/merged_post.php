<?php
/**
 * Объединяет два сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Объядинить сообщения
function merged_post($post_info, &$new_pid)
{
	global $forum_db, $db_type;

	$return = ($hook = get_hook('fl_fn_add_merged_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Update the post
	$query = array(
		'UPDATE'	=> 'posts',
		'SET'		=> 'message=\''.$forum_db->escape($post_info['message']).'\', hide_smilies='.$post_info['hide_smilies'],
		'WHERE'		=> 'id='.$post_info['post_id']
	);

	($hook = get_hook('fl_fn_merged_qr_update_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Update topic
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'last_post='.$post_info['posted'],
		'WHERE'		=> 'id='.$post_info['topic_id']
	);

	($hook = get_hook('fl_fn_merged_qr_update_topics')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Update forums
	$query = array(
		'UPDATE'	=> 'forums',
		'SET'		=> 'last_post='.$post_info['posted'],
		'WHERE'		=> 'id='.$post_info['forum_id']
	);

	($hook = get_hook('fl_fn_merged_qr_update_forums')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Update user last post
	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'last_post='.$post_info['posted'],
		'WHERE'		=> 'id='.$post_info['poster_id']
	);

	($hook = get_hook('fl_fn_merged_qr_update_users')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$new_pid = $post_info['post_id'];

	($hook = get_hook('fl_fn_merged_post_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_MERGET_POST', 1);
