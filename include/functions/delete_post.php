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
 * Удаление одного сообщения.
 * @param array Массив, содержащий следующие ключи - и соответствующие значения:
 *  - Обязательные:
 *    -# post_id: int ID сообщения которое надо удалить.
 *    -# poster_id: int ID автора сообщения, для синхронизации колличества сообщений.
 *    -# topic_id: int ID темы к которому принадлежит сообщение, для синхронизации колличества сообщений.
 *    -# forum_id: int ID форума к которому принадлежит сообщение, для синхронизации колличества сообщений.
 * @see sync_topic()
 * @see sync_forum()
 */
function delete_post($post_info)
{
	global $forum_db, $db_type;

	$return = ($hook = get_hook('fn_delete_post_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	//Время последнего сообщения
	if ($post_info['poster_id'] > 1)
	{
		$query = array(
			'SELECT'	=> 'p.posted',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.poster_id='.$post_info['poster_id'],
			'ORDER BY'	=> 'p.id DESC',
			'LIMIT'		=> '1'
		);

		($hook = get_hook('fn_fl_posted_qr_delete_post')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$last_post = $forum_db->result($result);

		// Обновим данные
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'num_posts=num_posts-1, last_post='.$last_post,
			'WHERE'		=> 'id='.$post_info['poster_id']
		);

		($hook = get_hook('fn_fl_update_num_posts_qr_delete_post')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// Delete the post
	$query = array(
		'DELETE'	=> 'posts',
		'WHERE'		=> 'id='.$post_info['post_id']
	);

	($hook = get_hook('fn_delete_post_qr_delete_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	strip_search_index($post_info['post_id']);

	if (!defined('FORUM_FUNCTIONS_SYNS'))
		require FORUM_ROOT.'include/functions/synchronize.php';

	sync_topic($post_info['topic_id']);
	sync_forum($post_info['forum_id']);

	($hook = get_hook('fn_delete_post_end')) ? eval($hook) : null;

}

define('FORUM_FUNCTIONS_DELETE_POST', 1);
