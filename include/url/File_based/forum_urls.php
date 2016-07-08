<?php
/**
 * SEF URL-адреса с местом расположения скриптов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// These are the simple file based SEF URLs
$forum_url = array(
'insertion_find'		=>	'.html',
'insertion_replace'		=>	'-$1.html',
'change_email'			=>	'change-email$1.html',
'change_email_key'		=>	'change-email$1-$2.html',
'change_password'		=>	'change-password$1.html',
'change_password_key'	=>	'change-password$1-$2.html',
'delete_user'			=>	'delete-user$1.html',
'delete'				=>	'delete$1.html',
'delete_avatar'			=>	'delete-avatar$1-$2.html',
'edit'					=>	'edit$1.html',
'email'					=>	'email$1.html',
'feed_forum_topics'		=>	'feed-$3-forum-topics$1-$2.xml',
'feed_forum_posts'		=>	'feed-$3-forum-posts$1-$2.xml',
'feed_index'			=>	'feed-$1.xml',
'feed_topic'			=>	'feed-$1-topic$2.xml',
'forum'					=>	'forum$1.html',
'help'					=>	'help-$1.html',
'index'					=>	'',
'category'				=>	'category$1.html',
'login'					=>	'login.html',
'logout'				=>	'logout$1-$2.html',
'online'				=>	'online.html',
'statistic'				=>	'statistic-$1.html',
'mark_read'				=>	'mark-read-$1.html',
'mark_forum_read'		=>	'mark-forum$1-read-$2.html',
'new_topic'				=>	'new-topic$1.html',
'new_reply'				=>	'new-reply$1.html',
'pm'					=>	'pm-$1.html',
'pm_edit' 				=>	'pm-edit-$1.html',
'pm_view'				=>	'pm-message-$1.html',
'pm_delete'				=>	'pm-delete-$1.html',
'pm_delete_section'		=>	'pm-delete.html',
'pm_post'				=>	'pm-write-$1.html',
'post'					=>	'post$1.html#p$1',
'profile'				=>	'user$1-$2.html',
'print'					=>	'print$1.html',
'quote'					=>	'new-reply$1quote$2.html',
'register'				=>	'register.html',
'report'				=>	'report$1-$2.html',
'request_password'		=>	'request-password.html',
'rules'					=>	'rules.html',
'smilies'				=>	'smilies.html',
'search'				=>	'search.html',
'search_resultft'		=>	'search-k$1-$4-a$3-$5-$6-$2-$7.html',
'search_results'		=>	'search$1.html',
'search_new'			=>	'search-new.html',
'search_new_results'	=>	'search-new-$1.html',
'search_recent'			=>	'search-recent.html',
'search_recent_results'	=>	'search-recent-$1.html',
'search_unanswered'		=>	'search-unanswered.html',
'search_subscriptions'	=>	'search-subscriptions$1.html',
'search_user_posts'		=>	'search-posts-user$1.html',
'search_user_topics'	=>	'search-topics-user$1.html',
'subscribe'				=>	'subscribe$1-$2.html',
'topic'					=>	'topic$1.html',
'poll'					=>	'poll$1.html',
'topic_new_posts'		=>	'topic$1new-posts.html',
'topic_last_post'		=>	'topic$1last-post.html',
'unsubscribe'			=>	'unsubscribe$1-$2.html',
'user'					=>	'user$1.html',
'users'					=>	'users.html',
'users_browse'			=>	'users/$4/$1$2-$3.html',
'page'					=>	'p$1',
'moderate_forum'		=>	'moderate$1.html',
'get_host'				=>	'get_host$1.html',
'move'					=>	'move_topics$1-$2.html',
'mod'					=>	'$1$2-$3-$4.html',
'moderate_topic'		=>	'moderate$1-$2.html',
'reputation'			=>	'user$1-$2.html',
'reputation_change'		=>	'user$1-post$2-$3.html',

);
