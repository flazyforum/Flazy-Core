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

// These are the "fancy" folder based SEF URLs
$forum_url = array(
'change_email'			=>	'change/email/$1/',
'change_email_key'		=>	'change/email/$1/$2/',
'change_password'		=>	'change/password/$1/',
'change_password_key'	=>	'change/password/$1/$2/',
'delete'				=>	'delete/$1/',
'delete_avatar'			=>	'delete/avatar/$1/$2/',
'delete_user'			=>	'delete/user/$1/',
'edit'					=>	'edit/$1/',
'email'					=>	'email/$1/',
'feed_forum_topics'		=>  'feed/$3/forum/topics/$1/$2/',
'feed_forum_posts'		=>  'feed/$3/forum/posts/$1/$2/',
'feed_index'			=>	'feed/$1/',
'feed_topic'			=>	'feed/$1/topic/$2/',
'forum'					=>	'forum/$1/$2/',
'help'					=>	'help/$1/',
'index'					=>	'',
'category'				=>	'category/$1/',
'login'					=>	'login/',
'logout'				=>	'logout/$1/$2/',
'online'				=>	'online/',
'statistic'				=>	'statistic/$1/',
'mark_read'				=>	'mark/read/$1/',
'mark_forum_read'		=>	'mark/forum/$1/read/$2/',
'new_topic'				=>	'new/topic/$1/',
'new_reply'				=>	'new/reply/$1/',
'pm'					=>	'pm/$1/',
'pm_edit' 				=>	'pm/edit/$1/',
'pm_view'				=>	'pm/message/$1/',
'pm_delete'				=>	'pm/delete/$1/',
'pm_delete_section'		=>	'pm/delete/',
'pm_post'				=>	'pm/write/$1',
'post'					=>	'post/$1/#p$1',
'profile'				=>	'user/$1/$2/',
'print'					=>	'print/$1/$2/',
'quote'					=>	'new/reply/$1/quote/$2/',
'register'				=>	'register/',
'report'				=>	'report/$1/$2/',
'request_password'		=>	'request/password/',
'rules'					=>	'rules/',
'smilies'				=>	'smilies/',
'search'				=>	'search/',
'search_resultft'		=>	'search/k$1/$2/a$3/$4/$5/$6/$7/',
'search_results'		=>	'search/$1/',
'search_new'			=>	'search/new/',
'search_new_results'	=>	'search/new/$1/',
'search_recent'			=>	'search/recent/',
'search_recent_results'	=>	'search/recent/$1/',
'search_unanswered'		=>	'search/unanswered/',
'search_subscriptions'	=>	'search/subscriptions/$1/',
'search_user_posts'		=>	'search/posts/user/$1/',
'search_user_topics'	=>	'search/topics/user/$1/',
'subscribe'				=>	'subscribe/$1/$2/',
'topic'					=>	'topic/$1/$2/',
'poll'					=>	'poll/$1/$2/',
'topic_new_posts'		=>	'topic/$1/$2/new/posts/',
'topic_last_post'		=>	'topic/$1/last/post/',
'unsubscribe'			=>	'unsubscribe/$1/$2/',
'user'					=>	'user/$1/',
'users'					=>	'users/',
'users_browse'			=>	'users/$4/$1/$2/$3/',
'page'					=>	'page/$1/',
'moderate_forum'		=>	'moderate/$1/',
'get_host'				=>	'get_host/$1/',
'move'					=>	'move_topics/$1/$2/',
'mod'					=>	'$1/$2/$3/$4/',
'moderate_topic'		=>	'moderate/$1/$2/',
'reputation'			=>	'user/$1/$2/',
'reputation_change'		=>	'user/$1/post/$2/$3/',
);

?>
