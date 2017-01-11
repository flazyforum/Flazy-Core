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

// These are the regular, "non-SEF" URLs (you probably don't want to edit these)
$forum_url = array(
'change_email'			=>	'profile.php?action=change_email&amp;id=$1',
'change_email_key'		=>	'profile.php?action=change_email&amp;id=$1&amp;key=$2',
'change_password'		=>	'profile.php?action=change_pass&amp;id=$1',
'change_password_key'	=>	'profile.php?action=change_pass&amp;id=$1&amp;key=$2',
'delete_user'			=>	'profile.php?action=delete_user&amp;id=$1',
'delete'				=>	'delete.php?id=$1',
'delete_avatar'			=>	'profile.php?action=delete_avatar&amp;id=$1&amp;csrf_token=$2',
'edit'					=>	'edit.php?id=$1',
'email'					=>	'misc.php?email=$1',
'feed_forum_topics'		=>  'extern.php?action=feed&amp;fid=$1&amp;order=$2&amp;content=topics&amp;type=$3',
'feed_forum_posts'		=>  'extern.php?action=feed&amp;fid=$1&amp;order=$2&amp;content=posts&amp;type=$3',
'feed_index'			=>	'extern.php?action=feed&amp;type=$1',
'feed_topic'			=>	'extern.php?action=feed&amp;tid=$2&amp;type=$1',
'forum'					=>	'viewforum.php?id=$1',
'help'					=>	'help.php?section=$1',
'index'					=>	'index.php',
'category'				=>	'index.php?cid=$1',
'login'					=>	'login.php',
'logout'				=>	'login.php?action=out&amp;id=$1&amp;csrf_token=$2',
'online'				=>	'online.php',
'statistic'				=>	'statistic.php?section=$1',
'mark_read'				=>	'misc.php?action=markread&amp;csrf_token=$1',
'mark_forum_read'		=>	'misc.php?action=markforumread&amp;fid=$1&amp;csrf_token=$2',
'new_topic'				=>	'post.php?fid=$1',
'new_reply'				=>	'post.php?tid=$1',
'pm'					=>	'pm.php?section=$1',
'pm_edit' 				=>	'pm.php?section=edit&amp;id=$1',
'pm_view'				=>	'pm.php?section=message&amp;id=$1',
'pm_delete'				=>	'pm.php?section=delete&amp;id=$1',
'pm_delete_section'		=>	'pm.php?section=delete',
'pm_post'				=>	'pm.php?section=write&amp;id=$1',
'post'					=>	'viewtopic.php?pid=$1#p$1',
'profile'				=>	'profile.php?section=$2&amp;id=$1',
'profile_admin'			=>	'profile.php?section=admin&amp;id=$1',
'print'					=>	'viewtopic.php?id=$1&amp;action=print',
'quote'					=>	'post.php?tid=$1&amp;qid=$2',
'register'				=>	'register.php',
'report'				=>	'misc.php?report=$1&amp;action=$2',
'request_password'		=>	'login.php?action=forget',
'rules'					=>	'misc.php?action=rules',
'smilies'				=>	'misc.php?action=smilies',
'search'				=>	'search.php',
'search_resultft'		=>	'search.php?action=search&amp;keywords=$1&amp;author=$3&amp;forum=$2&amp;search_in=$4&amp;sort_by=$5&amp;sort_dir=$6&amp;show_as=$7',
'search_results'		=>	'search.php?search_id=$1',
'search_new'			=>	'search.php?action=show_new',
'search_new_results'	=>	'search.php?action=show_new&amp;forum=$1',
'search_recent'			=>	'search.php?action=show_recent',
'search_recent_results'	=>	'search.php?action=show_recent&amp;value=$1',
'search_unanswered'		=>	'search.php?action=show_unanswered',
'search_subscriptions'	=>	'search.php?action=show_subscriptions&amp;user_id=$1',
'search_user_posts'		=>	'search.php?action=show_user_posts&amp;user_id=$1',
'search_user_topics'	=>	'search.php?action=show_user_topics&amp;user_id=$1',
'subscribe'				=>	'misc.php?subscribe=$1&amp;csrf_token=$2',
'topic'					=>	'viewtopic.php?id=$1',
'topic_new_posts'		=>	'viewtopic.php?id=$1&amp;action=new',
'topic_last_post'		=>	'viewtopic.php?id=$1&amp;action=last',
'poll'					=>	'viewpoll.php?id=$1',
'unsubscribe'			=>	'misc.php?unsubscribe=$1&amp;csrf_token=$2',
'user'					=>	'profile.php?id=$1',
'users'					=>	'userlist.php',
'users_browse'			=>	'userlist.php?show_group=$1&amp;sort_by=$2&amp;sort_dir=$3&amp;username=$4',
'page'					=>	'&amp;p=$1',
'moderate_forum'		=>	'moderate.php?fid=$1',
'get_host'				=>	'moderate.php?get_host=$1',
'move'					=>	'moderate.php?fid=$1&amp;move_topics=$2',
'mod'					=>	'moderate.php?fid=$2&amp;$1=$3&amp;csrf_token=$4',
'moderate_topic'		=>	'moderate.php?fid=$1&amp;tid=$2',
'reputation'			=>	'reputation.php?id=$1&amp;section=$2',
'reputation_change'		=>	'reputation.php?id=$1&amp;pid=$2&amp;method=$3',
);

?>