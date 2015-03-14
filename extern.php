<?php
/**
 * Скрипт для внешнего обьединения данных с форума.
 *
 * Позволяет экспортировать содержание форума в формата (пр: RSS, Atom, XML, HTML).
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

/***********************************************************************

  ИНСТРУКЦИЯ

  This script is used to include information about your board from
  pages outside the forums and to syndicate news about recent
  discussions via RSS/Atom/XML. The script can display a list of
  recent discussions, a list of active users or a collection of
  general board statistics. The script can be called directly via
  an URL, from a PHP include command or through the use of Server
  Side Includes (SSI).

  The scripts behaviour is controlled via variables supplied in the
  URL to the script. The different variables are: action (what to
  do), show (how many items to display), fid (the ID or ID's of
  the forum(s) to poll for topics), nfid (the ID or ID's of forums
  that should be excluded), tid (the ID of the topic from which to
  display posts) and type (output as HTML or RSS). The only
  mandatory variable is action. Possible/default values are:

    action: feed - показать последние темы/сообщения (HTML или RSS/Atom)
            online - показать участников присутствующих на форуме (HTML)
            online_full - тоже что и выше, но включает в себя полный
            список участников (HTML)
            stats - показать статистику форума (HTML)

    type:   rss - результат в RSS 2.0
            atom - результат в Atom 1.0
            xml - результат в XML
            html - результат в HTML (<li>'s)

    content: topics - показывать последние темы в указанных форумах
             posts - показать последние сообщения в указанных темах или форумам

    fid:    Один или несколько ID форумов (через запятую) которые
            следует показать.

    nfid:   Один или несколько ID форумов (через запятую), которые должны
            быть исключены. Например ID тестового форума.

    tid:    ID темы из которой следует показывать сообщения. Если
            поставлен tid, fit и nfid игнорируются.

    lengt:  Значение определяющие длину после которой название тема будет
            урезана (для вывода HTML, по умолчанию равно 30)

    show:   Любое целое число от 1 до 50. По умолчанию равно 15.

/***********************************************************************/


define('FORUM_QUIET_VISIT', 1);

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('ex_start')) ? eval($hook) : null;

// If we're a guest and we've sent a username/pass, we can try to authenticate using those details
if ($forum_user['is_guest'] && isset($_SERVER['PHP_AUTH_USER']))
	authenticate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

if (!$forum_user['g_read_board'])
{
	http_authenticate_user();
	die($lang_common['No view']);
}

$action = isset($_GET['action']) ? $_GET['action'] : 'feed';

// Sends the proper headers for Basic HTTP Authentication
function http_authenticate_user()
{
	global $forum_config, $forum_user;

	if (!$forum_user['is_guest'])
		return;

	header('WWW-Authenticate: Basic realm="'.$forum_config['o_board_title'].' External Syndication"');
	header('HTTP/1.0 401 Unauthorized');
}

// Output $feed as RSS 2.0
function output_rss($feed)
{
	global $lang_common, $forum_config;

	// Send XML/no cache headers
	header('Content-Type: text/xml; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
	echo '<rss version="2.0">'."\n";
	echo "\t".'<channel>'."\n";
	echo "\t\t".'<title><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
	echo "\t\t".'<link>'.$feed['link'].'</link>'."\n";
	echo "\t\t".'<description><![CDATA['.escape_cdata($feed['description']).']]></description>'."\n";
	echo "\t\t".'<lastBuildDate>'.gmdate('r', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</lastBuildDate>'."\n";

	if ($forum_config['o_show_version'])
		echo "\t\t".'<generator>Flazy '.$forum_config['o_cur_version'].'</generator>'."\n";
	else
		echo "\t\t".'<generator>Flazy</generator>'."\n";

	($hook = get_hook('ex_add_new_rss_info')) ? eval($hook) : null;

	foreach ($feed['items'] as $item)
	{
		echo "\t\t".'<item>'."\n";
		echo "\t\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
		echo "\t\t\t".'<link>'.$item['link'].'</link>'."\n";
		echo "\t\t\t".'<description><![CDATA['.escape_cdata($item['description'].($forum_config['o_externbox'] && !defined('FORUM_DISABLE_HTML') ? $forum_config['o_externbox_message'] : '')).']]></description>'."\n";
		echo "\t\t\t".'<author><![CDATA['.(isset($item['author']['email']) ? escape_cdata($item['author']['email']) : 'example@example.com').' ('.escape_cdata($item['author']['name']).')]]></author>'."\n";
		echo "\t\t\t".'<pubDate>'.gmdate('r', $item['pubdate']).'</pubDate>'."\n";
		echo "\t\t\t".'<guid>'.$item['link'].'</guid>'."\n";

		($hook = get_hook('ex_add_new_rss_item_info')) ? eval($hook) : null;

		echo "\t\t".'</item>'."\n";
	}

	echo "\t".'</channel>'."\n";
	echo '</rss>'."\n";
}


// Output $feed as Atom 1.0
function output_atom($feed)
{
	global $lang_common, $forum_config;

	// Send XML/no cache headers
	header('Content-Type: text/xml; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
	echo '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";

	echo "\t".'<title type="html"><![CDATA['.escape_cdata($feed['title']).']]></title>'."\n";
	echo "\t".'<link rel="self" href="'.forum_htmlencode(get_current_url()).'"/>'."\n";
	echo "\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', count($feed['items']) ? $feed['items'][0]['pubdate'] : time()).'</updated>'."\n";

	if ($forum_config['o_show_version'])
		echo "\t".'<generator version="'.$forum_config['o_cur_version'].'">Flazy</generator>'."\n";
	else
		echo "\t".'<generator>Flazy</generator>'."\n";

	($hook = get_hook('ex_add_new_atom_info')) ? eval($hook) : null;

	echo "\t".'<id>'.$feed['link'].'</id>'."\n";

	$content_tag = ($feed['type'] == 'posts') ? 'content' : 'summary';

	foreach ($feed['items'] as $item)
	{
		echo "\t\t".'<entry>'."\n";
		echo "\t\t\t".'<title type="html"><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
		echo "\t\t\t".'<link rel="alternate" href="'.$item['link'].'"/>'."\n";
		echo "\t\t\t".'<'.$content_tag.' type="html"><![CDATA['.escape_cdata($item['description'].($forum_config['o_externbox'] && !defined('FORUM_DISABLE_HTML') ? $forum_config['o_externbox_message'] : '')).']]></'.$content_tag.'>'."\n";
		echo "\t\t\t".'<author>'."\n";
		echo "\t\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

		if (isset($item['author']['email']))
			echo "\t\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";

		if (isset($item['author']['uri']))
			echo "\t\t\t\t".'<uri>'.$item['author']['uri'].'</uri>'."\n";

		echo "\t\t\t".'</author>'."\n";
		echo "\t\t\t".'<updated>'.gmdate('Y-m-d\TH:i:s\Z', $item['pubdate']).'</updated>'."\n";

		($hook = get_hook('ex_add_new_atom_item_info')) ? eval($hook) : null;

		echo "\t\t\t".'<id>'.$item['link'].'</id>'."\n";
		echo "\t\t".'</entry>'."\n";
	}

	echo '</feed>'."\n";
}


// Output $feed as XML
function output_xml($feed)
{
	global $lang_common, $forum_config;

	// Send XML/no cache headers
	header('Content-Type: application/xml; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
	echo '<source>'."\n";
	echo "\t".'<url>'.$feed['link'].'</url>'."\n";

	($hook = get_hook('ex_add_new_xml_info')) ? eval($hook) : null;

	$forum_tag = ($feed['type'] == 'posts') ? 'post' : 'topic';

	foreach ($feed['items'] as $item)
	{
		echo "\t".'<'.$forum_tag.' id="'.$item['id'].'">'."\n";

		echo "\t\t".'<title><![CDATA['.escape_cdata($item['title']).']]></title>'."\n";
		echo "\t\t".'<link>'.$item['link'].'</link>'."\n";
		echo "\t\t".'<content><![CDATA['.escape_cdata($item['description']).']]></content>'."\n";
		echo "\t\t".'<author>'."\n";
		echo "\t\t\t".'<name><![CDATA['.escape_cdata($item['author']['name']).']]></name>'."\n";

		if (isset($item['author']['email']))
			echo "\t\t\t".'<email><![CDATA['.escape_cdata($item['author']['email']).']]></email>'."\n";

		if (isset($item['author']['uri']))
			echo "\t\t\t".'<uri>'.$item['author']['uri'].'</uri>'."\n";

		echo "\t\t".'</author>'."\n";
		echo "\t\t".'<posted>'.gmdate('r', $item['pubdate']).'</posted>'."\n";

		($hook = get_hook('ex_add_new_xml_item_info')) ? eval($hook) : null;

		echo "\t".'</'.$forum_tag.'>'."\n";
	}

	echo '</source>'."\n";
}


// Output $feed as HTML (using <li> tags)
function output_html($feed)
{
	$lengt = isset($_GET['lengt']) ? intval($_GET['lengt']) : 30;
	if ($lengt < 15)
		$lengt = 30;

	// Send the Content-type header in case the web server is setup to send something else
	header('Content-type: text/html; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	foreach ($feed['items'] as $item)
	{
		if (utf8_strlen($item['title']) > $lengt)
			$subject_truncated = forum_htmlencode(forum_trim(utf8_substr($item['title'], 0, ($lengt - 5)))).' …';
		else
			$subject_truncated = forum_htmlencode($item['title']);

		echo '<li><a href="'.$item['link'].'" title="'.forum_htmlencode($item['title']).'">'.$subject_truncated.'</a></li>'."\n";
	}
}

// Show recent discussions
if ($action == 'feed')
{
	// Determine what type of feed to output
	$type = isset($_GET['type']) && in_array($_GET['type'], array('html', 'rss', 'atom', 'xml')) ? $_GET['type'] : 'html';

	$show = isset($_GET['show']) ? intval($_GET['show']) : 15;
	if ($show < 1 || $show > 50)
		$show = 15;

	($hook = get_hook('ex_set_syndication_type')) ? eval($hook) : null;

	// Was a topic ID supplied?
	if (isset($_GET['tid']))
	{
		$tid = intval($_GET['tid']);

		// Fetch topic subject
		$query = array(
			'SELECT'	=> 't.subject, t.description, t.first_post_id',
			'FROM'		=> 'topics AS t',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
				)
			),
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL AND t.id='.$tid
		);

		($hook = get_hook('ex_qr_get_topic_data')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if (!$forum_db->num_rows($result))
		{
			http_authenticate_user();
			header('Content-type: text/html; charset=utf-8');
			die($lang_common['Bad request']);
		}

		$cur_topic = $forum_db->fetch_assoc($result);

		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		if ($forum_config['o_censoring'])
		{
			$cur_topic['subject'] = censor_words($cur_topic['subject']);
			$cur_topic['description'] = censor_words($cur_topic['description']);
		}

		if (empty($cur_topic['description']))
			$subj = $cur_topic['subject'];
		else
			$subj = $cur_topic['subject'].'; '.$cur_topic['description'];

		// Setup the feed
		$feed = array(
			'title' 		=>	$forum_config['o_board_title'].$lang_common['Title separator'].$subj,
			'link'			=>	forum_link($forum_url['topic'], array($tid, sef_friendly($cur_topic['subject']))),
			'description'	=>	sprintf($lang_common['RSS description topic'], $cur_topic['subject']),
			'items'			=>	array(),
			'type'			=>	'posts'
		);

		// Fetch $show posts
		$query = array(
			'SELECT'	=> 'p.id',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.topic_id='.$tid,
			'ORDER BY'	=> 'p.posted DESC',
			'LIMIT'		=> $show
		);

		($hook = get_hook('ex_qr_get_id_posts')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$posts_id = array();
		while ($row = $forum_db->fetch_row($result))
			$posts_id[] = $row[0];

		$query = array(
			'SELECT'	=> 'p.id, p.poster, p.message, p.hide_smilies, p.posted, p.poster_id, u.email_setting, u.email, p.poster_email',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=p.poster_id'
				)
			),
			'WHERE'		=> 'p.id IN ('.implode(',', $posts_id).')',
		);

		($hook = get_hook('ex_qr_get_posts')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$posts_info = array();
		while ($cur_post = $forum_db->fetch_assoc($result))
		{
			$tmp_index = array_search($cur_post['id'], $posts_id);
			$posts_info[$tmp_index] = $cur_post;
		}
		krsort($posts_info);
		unset($posts_id);

		foreach ($posts_info as $cur_post) 
		{
			if ($forum_config['o_censoring'])
				$cur_post['message'] = censor_words($cur_post['message']);

			$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

			$item = array(
				'id'		=>	$cur_post['id'],
				'title'		=>	$cur_topic['first_post_id'] == $cur_post['id'] ? $cur_topic['subject'] : $lang_common['RSS reply'].$cur_topic['subject'],
				'link'		=>	forum_link($forum_url['post'], $cur_post['id']),
				'description'	=>	$cur_post['message'],
				'author'	=>	array(
								'name'		=> $cur_post['poster'],
							),
				'pubdate'	=>	$cur_post['posted']
			);

			if ($cur_post['poster_id'] > 1)
			{
				if ($cur_post['email_setting'] == '0' && !$forum_user['is_guest'])
					$item['author']['email'] = $cur_post['email'];

				$item['author']['uri'] = forum_link($forum_url['user'], $cur_post['poster_id']);
			}
			else if ($cur_post['poster_email'] != '' && !$forum_user['is_guest'])
				$item['author']['email'] = $cur_post['poster_email'];

			$feed['items'][] = $item;

			($hook = get_hook('ex_modify_cur_post_item')) ? eval($hook) : null;
		}

		($hook = get_hook('ex_pre_topic_output')) ? eval($hook) : null;

		$output_func = 'output_'.$type;
		$output_func($feed);
	}
	else
	{
		$order_posted = isset($_GET['order']) && $_GET['order'] == 'posted';
		$forum_name = '';

		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
		
		// Were any forum ID's supplied?
		if (isset($_GET['fid']) && is_scalar($_GET['fid']) && $_GET['fid'] != '')
		{
			$fids = explode(',', forum_trim($_GET['fid']));
			$fids = array_map('intval', $fids);

			if (!empty($fids))
				$forum_sql = ' AND t.forum_id IN('.implode(',', $fids).')';

			if (count($fids) == 1)
			{
				// Fetch forum name
				$query = array(
					'SELECT'	=> 'f.forum_name',
					'FROM'		=> 'forums AS f',
					'JOINS'		=> array(
						array(
							'LEFT JOIN'		=> 'forum_perms AS fp',
							'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
						)
					),
					'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fids[0]
				);

				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				if ($forum_db->num_rows($result))
					$forum_name = $lang_common['Title separator'].$forum_db->result($result);
			}
		}

		// Any forum ID's to exclude?
		if (isset($_GET['nfid']) && is_scalar($_GET['nfid']) && $_GET['nfid'] != '')
		{
			$nfids = explode(',', forum_trim($_GET['nfid']));
			$nfids = array_map('intval', $nfids);

			if (!empty($nfids))
				$forum_sql = ' AND t.forum_id NOT IN('.implode(',', $nfids).')';
		}

		if (isset($_GET['content']) && $_GET['content'] == 'posts')
		{
			// Fetching last posts from the forums specified

			// Setup the feed
			$feed = array(
				'title' 		=>	$forum_config['o_board_title'].$forum_name,
				'link'			=>	forum_link($forum_url['index']),
				'description'	=>	sprintf($lang_common['RSS description topic'], $forum_config['o_board_title']),
				'items'			=>	array(),
				'type'			=>	'posts'
			);

			// Fetch $show posts
			$query = array(
				'SELECT'	=> 'p.id, p.poster, p.posted, p.poster_id, p.poster_email, t.subject, t.first_post_id, p.message, p.hide_smilies, u.email_setting, u.email',
				'FROM'		=> 'topics AS t',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'posts AS p',
						'ON'			=> 'p.topic_id=t.id'
					),
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id=p.poster_id'
					),
					array(
						'LEFT JOIN'		=> 'forum_perms AS fp',
						'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
					)
				),
				'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL',
				'ORDER BY'	=> 'p.posted DESC',
				'LIMIT'		=> $show
			);

			if (isset($forum_sql))
				$query['WHERE'] .= $forum_sql;

			($hook = get_hook('ex_qr_get_topics')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			while ($cur_post = $forum_db->fetch_assoc($result))
			{
				if ($forum_config['o_censoring'] == '1')
					$cur_post['message'] = censor_words($cur_post['message']);

				$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

				$item = array(
					'id'			=>	$cur_post['id'],
					'title'			=>	$cur_post['first_post_id'] == $cur_post['id'] ? $cur_post['subject'] : $lang_common['RSS reply'].$cur_post['subject'],
					'link'			=>	forum_link($forum_url['post'], $cur_post['id']),
					'description'	=>	$cur_post['message'],
					'author'		=>	array(
						'name'			=> $cur_post['poster']
					),
					'pubdate'		=>	$cur_post['posted']
				);

				if ($cur_post['poster_id'] > 1)
				{
					if ($cur_post['email_setting'] == '0' && !$forum_user['is_guest'])
						$item['author']['email'] = $cur_post['email'];

					$item['author']['uri'] = forum_link($forum_url['user'], $cur_post['poster_id']);
				}
				else if ($cur_post['poster_email'] != '' && !$forum_user['is_guest'])
					$item['author']['email'] = $cur_post['poster_email'];

				$feed['items'][] = $item;

				($hook = get_hook('ex_modify_forum_cur_post_item')) ? eval($hook) : null;
			}
		}
		else
		{
			// Fetching last topics from the forums specified

			// Setup the feed
			$feed = array(
				'title' 		=>	$forum_config['o_board_title'].$forum_name,
				'link'			=>	forum_link($forum_url['index']),
				'description'	=>	sprintf($lang_common['RSS description'], $forum_config['o_board_title']),
				'items'			=>	array(),
				'type'			=>	'topics'
			);

			// Fetch $show topics
			$query = array(
				'SELECT'	=> 't.id, t.poster, t.subject, t.description, t.posted, t.last_post, t.last_poster, p.message, p.hide_smilies, u.email_setting, u.email, p.poster_id, p.poster_email',
				'FROM'		=> 'topics AS t',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'posts AS p',
						'ON'			=> 'p.id=t.first_post_id'
					),
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id = p.poster_id'
					),
					array(
						'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
					)
				),
				'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL',
				'ORDER BY'	=> ($order_posted ? 't.posted' : 't.last_post').' DESC',
				'LIMIT'		=> $show
			);

			if (isset($forum_sql))
				$query['WHERE'] .= $forum_sql;

			($hook = get_hook('ex_qr_get_topics')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($cur_topic = $forum_db->fetch_assoc($result))
			{
				if ($forum_config['o_censoring'])
				{
					$cur_topic['subject'] = censor_words($cur_topic['subject']);
					$cur_topic['description'] = censor_words($cur_topic['description']);
					$cur_topic['message'] = censor_words($cur_topic['message']);
				}

				if (empty($cur_topic['description']))
					$subj = $cur_topic['subject'];
				else
					$subj = $cur_topic['subject'].'; '.$cur_topic['description'];

				$cur_topic['message'] = parse_message($cur_topic['message'], $cur_topic['hide_smilies']);

				$item = array(
					'id'		=>	$cur_topic['id'],
					'title'		=>	$subj,
					'link'		=>	$order_posted ? forum_link($forum_url['topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))) : forum_link($forum_url['topic_new_posts'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))),
					'description'	=>	$cur_topic['message'],
					'author'	=>	array(
								'name'		=> $order_posted ? $cur_topic['poster'] : $cur_topic['last_poster']
							),
					'pubdate'	=>	$order_posted ? $cur_topic['posted'] : $cur_topic['last_post']
				);

				if ($cur_topic['poster_id'] > 1)
				{
					if ($cur_topic['email_setting'] == '0' && !$forum_user['is_guest'])
						$item['author']['email'] = $cur_topic['email'];

					$item['author']['uri'] = forum_link($forum_url['user'], $cur_topic['poster_id']);
				}
				else if ($cur_topic['poster_email'] != '' && !$forum_user['is_guest'])
					$item['author']['email'] = $cur_topic['poster_email'];

				$feed['items'][] = $item;

				($hook = get_hook('ex_modify_cur_topic_item')) ? eval($hook) : null;
			}
		}

		($hook = get_hook('ex_pre_forum_output')) ? eval($hook) : null;

		$output_func = 'output_'.$type;
		$output_func($feed);
	}

	die;
}

// Show users online
else if ($action == 'online' || $action == 'online_full')
{
	// Load the index.php language file
	require FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/index.php';

	// Fetch users online info and generate strings for output
	$num_guests = $num_users = 0;
	$users = array();

	$query = array(
		'SELECT'	=> 'o.user_id, o.ident',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.idle=0',
		'ORDER BY'	=> 'o.ident'
	);

	($hook = get_hook('ex_qr_get_users_online')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($forum_user_online = $forum_db->fetch_assoc($result))
	{
		if ($forum_user_online['user_id'] > 1)
		{
			$users[] = ($forum_user['g_view_users']) ? '<a href="'.forum_link($forum_url['user'], $forum_user_online['user_id']).'">'.forum_htmlencode($forum_user_online['ident']).'</a>' : forum_htmlencode($forum_user_online['ident']);
			++$num_users;
		}
		else
			++$num_guests;
	}

	($hook = get_hook('ex_pre_online_output')) ? eval($hook) : null;

	header('Content-type: text/html; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo $lang_index['Guests online'].': '.forum_number_format($num_guests).'<br />'."\n";

	if ($action == 'online_full' && !empty($users))
	{
		$users = ((count($users) != 0) ? implode($lang_index['Online list separator'], $users) : $lang_index['No users']);
		echo $lang_index['Users online'].': '.$users.'<br />'."\n";
	}
	else
		echo $lang_index['Users online'].': '.forum_number_format($num_users).'<br />'."\n";

	die;
}

// Show board statistics
else if ($action == 'stats')
{
	// Load the index.php language file
	require FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/index.php';

	header('Content-type: text/html; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	($hook = get_hook('ex_pre_stats_output')) ? eval($hook) : null;

	// Load cached
	if (file_exists(FORUM_CACHE_DIR.'cache_stat_user.php'))
		require FORUM_CACHE_DIR.'cache_stat_user.php';
	else
	{
		if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
			require FORUM_ROOT.'include/cache/stat_user.php';

		generate_stat_user_cache();
		require FORUM_CACHE_DIR.'cache_stat_user.php';
	}

	echo sprintf($lang_index['No of users'], forum_number_format($forum_stat_user['total_users'])).'<br />'."\n";
	echo sprintf($lang_index['Newest user'], ($forum_user['g_view_users']) ? '<a href="'.forum_link($forum_url['user'], $forum_stat_user['id']).'">'.forum_htmlencode($forum_stat_user['username']).'</a>' : forum_htmlencode($forum_stat_user['username'])).'<br />'."\n";

	$query = array(
		'SELECT'	=> 'SUM(f.num_topics), SUM(f.num_posts)',
		'FROM'		=> 'forums AS f'
	);

	($hook = get_hook('in_stats_qr_get_post_stats')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($stats['total_topics'], $stats['total_posts']) = $forum_db->fetch_row($result);

	$query = array(
		'SELECT'	=> 'p.posted',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.posted>='.mktime(0, 0, 0, date('m')-1, date('d'), date('y'))
	);

	($hook = get_hook('ex_stats_qr_get_time_post_stats')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$posts_week = $posts_day = 0;
	$all_posts = array();

	while ($posts = $forum_db->fetch_assoc($result))
	{
		if ($posts['posted'] >= (time()-86400))
		{
			$all_posts[] = $posts['posted'];
			++$posts_day;
		}
			++$posts_week;
	}

	echo sprintf($lang_index['No of topics'], forum_number_format($stats['total_topics'])).'<br />'."\n";
	echo sprintf($lang_index['No of posts'],  forum_number_format($stats['total_posts']), forum_number_format($posts_week), $lang_index['Online list separator'], forum_number_format($posts_day)).'<br />'."\n";
	
	die;
}


($hook = get_hook('ex_new_action')) ? eval($hook) : null;

// If we end up here, the script was called with some wacky parameters
header('Content-type: text/html; charset=utf-8');
die($lang_common['Bad request']);
