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
 * Обновление полей replies, last_post, last_post_id, last_poster и last_poster_id темы.
 * @param int ID темы.
 */
function sync_topic($topic_id)
{
	global $forum_db;

	$return = ($hook = get_hook('fn_sync_topic_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Count number of replies in the topic
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$topic_id
	);

	($hook = get_hook('fn_sync_topic_qr_get_topic_reply_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_replies = $forum_db->result($result, 0) - 1;

	// Get last_post, last_post_id and last_poster
	$query = array(
		'SELECT'	=> 'p.posted, p.id, p.poster, p.poster_id',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$topic_id,
		'ORDER BY'	=> 'p.id DESC',
		'LIMIT'		=> '1'
	);

	($hook = get_hook('fn_sync_topic_qr_get_topic_last_post_data')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($last_post, $last_post_id, $last_poster, $last_poster_id) = $forum_db->fetch_row($result);

	// Now update the topic
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'num_replies='.$num_replies.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$forum_db->escape($last_poster).'\', last_poster_id=\''.$forum_db->escape($last_poster_id).'\'',
		'WHERE'		=> 'id='.$topic_id
	);

	($hook = get_hook('fn_sync_topic_qr_update_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('fn_sync_topic_end')) ? eval($hook) : null;
}


/**
 * Обновление полей posts, topics, last_post, last_post_id и last_poster форума.
 * @param int ID форума.
 */
function sync_forum($forum_id)
{
	global $forum_db;

	$return = ($hook = get_hook('fn_sync_forum_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Get topic and post count for forum
	$query = array(
		'SELECT'	=> 'COUNT(t.id), SUM(t.num_replies)',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.forum_id='.$forum_id
	);

	($hook = get_hook('fn_sync_forum_qr_get_forum_stats')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($num_topics, $num_posts) = $forum_db->fetch_row($result);

	$num_posts = $num_posts + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)

	// Get last_post, last_post_id and last_poster for forum (if any)
	$query = array(
		'SELECT'	=> 't.last_post, t.last_post_id, t.last_poster',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.forum_id='.$forum_id.' AND t.moved_to is NULL',
		'ORDER BY'	=> 't.last_post DESC',
		'LIMIT'		=> '1'
	);

	($hook = get_hook('fn_sync_forum_qr_get_forum_last_post_data')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result))
	{
		list($last_post, $last_post_id, $last_poster) = $forum_db->fetch_row($result);
		$last_poster = '\''.$forum_db->escape($last_poster).'\'';
	}
	else
		$last_post = $last_post_id = $last_poster = 'NULL';

	// Now update the forum
	$query = array(
		'UPDATE'	=> 'forums',
		'SET'		=> 'num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster='.$last_poster,
		'WHERE'		=> 'id='.$forum_id
	);

	($hook = get_hook('fn_sync_forum_qr_update_forum')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('fn_sync_forum_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_SYNS', 1);
