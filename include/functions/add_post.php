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
 * Отправка писем по подписке.
 * @param array Массив, содержащий ключи - и соответствующие значения использумы в функции add_post()
 * @param int ID сообщения.
 * @see add_post()
 */
function send_subscriptions($post_info, $new_pid)
{
	global $forum_config, $forum_db, $forum_url, $lang_common;

	$return = ($hook = get_hook('fn_send_subscriptions_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!$forum_config['o_subscriptions'])
		return;

	// Get the post time for the previous post in this topic
	$query = array(
		'SELECT'	=> 'p.posted',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$post_info['topic_id'],
		'ORDER BY'	=> 'p.id DESC',
		'LIMIT'		=> '1, 1'
	);

	($hook = get_hook('fn_send_subscriptions_qr_get_previous_post_time')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$previous_post_time = $forum_db->result($result);

	// Get any subscribed users that should be notified (banned users are excluded)
	$query = array(
		'SELECT'	=> 'u.id, u.email, u.notify_with_post, u.language',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'subscriptions AS s',
				'ON'			=> 'u.id=s.user_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id='.$post_info['forum_id'].' AND fp.group_id=u.group_id)'
			),
			array(
				'LEFT JOIN'		=> 'online AS o',
				'ON'			=> 'u.id=o.user_id'
			),
			array(
				'LEFT JOIN'		=> 'bans AS b',
				'ON'			=> 'u.username=b.username'
			),
		),
		//'WHERE'		=> 'b.username IS NULL AND COALESCE(o.logged, u.last_visit)>'.$previous_post_time.' AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.topic_id='.$post_info['topic_id'].' AND u.id!='.$post_info['poster_id']
//Flazy fix
		'WHERE'		=> 'b.username IS NULL AND COALESCE(o.logged, u.last_visit)<'.$previous_post_time.' AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.topic_id='.$post_info['topic_id'].' AND u.id!='.$post_info['poster_id']
//
	);

	($hook = get_hook('fn_send_subscriptions_qr_get_users_to_notify')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result))
	{
		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/functions/email.php';

		$notification_emails = array();

		// Loop through subscribed users and send e-mails
		while ($cur_subscriber = $forum_db->fetch_assoc($result))
		{
			// Is the subscription e-mail for $cur_subscriber['language'] cached or not?
			if (!isset($notification_emails[$cur_subscriber['language']]) && file_exists(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl'))
			{
				// Load the "new reply" template
				$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply.tpl'));
				// Load the "new reply full" template (with post included)
				$mail_tpl_full = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_reply_full.tpl'));

				// The first row contains the subject (it also starts with "Subject:")
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

				$first_crlf = strpos($mail_tpl_full, "\n");
				$mail_subject_full = forum_trim(substr($mail_tpl_full, 8, $first_crlf-8));
				$mail_message_full = forum_trim(substr($mail_tpl_full, $first_crlf));

				$mail_subject = str_replace('<topic_subject>', '\''.$post_info['subject'].'\'', $mail_subject);
				$mail_message = str_replace('<topic_subject>', '\''.$post_info['subject'].'\'', $mail_message);
				$mail_message = str_replace('<replier>', $post_info['poster'], $mail_message);
				$mail_message = str_replace('<post_url>', forum_link($forum_url['post'], $new_pid), $mail_message);
				$mail_message = str_replace('<unsubscribe_url>', forum_link($forum_url['unsubscribe'], array($post_info['topic_id'], generate_form_token('unsubscribe'.$post_info['topic_id'].$cur_subscriber['id']))), $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);


				$mail_subject_full = str_replace('<topic_subject>', '\''.$post_info['subject'].'\'', $mail_subject_full);
				$mail_message_full = str_replace('<topic_subject>', '\''.$post_info['subject'].'\'', $mail_message_full);
				$mail_message_full = str_replace('<replier>', $post_info['poster'], $mail_message_full);
				$mail_message_full = str_replace('<message>', $post_info['message'], $mail_message_full);
				$mail_message_full = str_replace('<post_url>', forum_link($forum_url['post'], $new_pid), $mail_message_full);
				$mail_message_full = str_replace('<unsubscribe_url>', forum_link($forum_url['unsubscribe'], array($post_info['topic_id'], generate_form_token('unsubscribe'.$post_info['topic_id'].$cur_subscriber['id']))), $mail_message_full);
				$mail_message_full = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message_full);

				$notification_emails[$cur_subscriber['language']][0] = $mail_subject;
				$notification_emails[$cur_subscriber['language']][1] = $mail_message;
				$notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
				$notification_emails[$cur_subscriber['language']][3] = $mail_message_full;

				$mail_subject = $mail_message = $mail_subject_full = $mail_message_full = null;
			}

			// Make sure the e-mail address format is valid before sending
			if (isset($notification_emails[$cur_subscriber['language']]) && is_valid_email($cur_subscriber['email']))
			{
				if ($cur_subscriber['notify_with_post'] == '0')
					forum_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
				else
					forum_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
			}
		}
	}

	($hook = get_hook('fn_send_subscriptions_end')) ? eval($hook) : null;
}


/**
 * Создать новое сообщение.
 * @param array Массив, содержащий следующие ключи - и соответствующие значения:
 *  - Обязательные:
 *    -# is_guest: Глобальная переменная $forum_user['is_guest'].
 *    -# poster: Имя автора сообщения.
 *    -# poster_id: ID автора сообщения, всегда 1 для сообщений от гостей.
 *    -# poster_email: Email автора сообщения, всегда 0 для зарегистрированый пользователей.
 *    -# subject: Название темы (заголовок).
 *    -# message: Тело сообщения.
 *    -# hide_smilies: Состояние отображение смайлов в сообщений: 1 - заменять на изображения, 0 - не заменять.
 *    -# posted: Время создания темы.
 *    -# subscr_action: Подписаться или не подписываться на тему.
 *    -# topic_id: ID темы к которой принадлежит сообщение.
 *    -# forum_id: ID форума к которому принадлежит сообщение.
 *    -# update_user: Увеличить число сообщений и изненить время последнего сообщения автора.
 *    -# counter: Увеличить число сообщений автора, настройка счетчика форума.
 *    -# update_unread: Обновить индикатор непрочитаных сообщений.
 * @param int ID нового сообщения.
 */
function add_post($post_info, &$new_pid)
{
	global $forum_db, $db_type, $forum_config, $lang_common;

	$return = ($hook = get_hook('fn_add_post_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Add the post
	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
		'INTO'		=> 'posts',
		'VALUES'	=> '\''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$forum_db->escape(get_remote_address()).'\', \''.$forum_db->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$post_info['topic_id']
	);

	// If it's a guest post, there might be an e-mail address we need to include
	if ($post_info['is_guest'] && $post_info['poster_email'] != null)
	{
		$query['INSERT'] .= ', poster_email';
		$query['VALUES'] .= ', \''.$forum_db->escape($post_info['poster_email']).'\'';
	}

	($hook = get_hook('fn_add_post_qr_add_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $forum_db->insert_id();

	if (!$post_info['is_guest'])
	{
		// Subscribe or unsubscribe?
		if ($post_info['subscr_action'] == 1)
		{
			$query = array(
				'INSERT'	=> 'user_id, topic_id',
				'INTO'		=> 'subscriptions',
				'VALUES'	=> $post_info['poster_id'].' ,'.$post_info['topic_id']
			);

			($hook = get_hook('fn_add_post_qr_add_subscription')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else if ($post_info['subscr_action'] == 2)
		{
			$query = array(
				'DELETE'	=> 'subscriptions',
				'WHERE'		=> 'topic_id='.$post_info['topic_id'].' AND user_id='.$post_info['poster_id']
			);

			($hook = get_hook('fn_add_post_qr_delete_subscription')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Count number of replies in the topic
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$post_info['topic_id']
	);

	($hook = get_hook('fn_add_post_qr_get_topic_reply_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_replies = $forum_db->result($result, 0) - 1;

	// Update topic
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'num_replies='.$num_replies.', last_post='.$post_info['posted'].', last_post_id='.$new_pid.', last_poster=\''.$forum_db->escape($post_info['poster']).'\', last_poster_id='.$post_info['poster_id'],
		'WHERE'		=> 'id='.$post_info['topic_id']
	);

	($hook = get_hook('fn_add_post_qr_update_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_FUNCTIONS_SYNS_FORUM'))
		require FORUM_ROOT.'include/functions/synchronize.php';

	sync_forum($post_info['forum_id']);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	update_search_index('post', $new_pid, $post_info['message']);

	send_subscriptions($post_info, $new_pid);

	// Increment his/her post count & last post time
	if (isset($post_info['update_user']))
	{
		if ($post_info['is_guest'])
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
			);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'id='.$post_info['poster_id']
			);

			if ($post_info['counter'])
				$query['SET'] .= ', num_posts=num_posts+1';
		}

		($hook = get_hook('fn_add_post_qr_update_last_post')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// If the posting user is logged in update his/her unread indicator
	if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
	{
		$tracked_topics = get_tracked_topics();
		$tracked_topics['topics'][$post_info['topic_id']] = time();
		set_tracked_topics($tracked_topics);
	}

	($hook = get_hook('fn_add_post_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_ADD_POST', 1);
