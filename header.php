<?php
/**
 *
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die ;

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');
// When yours truly first set eyes on this world! :)
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
// For HTTP/1.0 compability

// Send the Content-type header in case the web server is setup to send something else
// Для dev версии
/*if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
 header('Content-Type: application/xhtml+xml; charset=utf-8');
 else*/
header('Content-Type: text/html; charset=utf-8');

// Load the main template
if (substr(FORUM_PAGE, 0, 5) == 'admin')
	$tpl_path = check_tpl('admin');
else if (FORUM_PAGE == 'help')
	$tpl_path = check_tpl('help');
else if (FORUM_PAGE == 'smilies')
	$tpl_path = check_tpl('smilies');
else
	$tpl_path = check_tpl('main');

($hook = get_hook('hd_pre_template_loaded')) ? eval($hook) : null;

$tpl_main = file_get_contents($tpl_path);

($hook = get_hook('hd_template_loaded')) ? eval($hook) : null;

// START SUBST - <forum_include "*">
while (preg_match('#<forum_include "([^/\\\\]*?)">#', $tpl_main, $cur_include)) {
	if (!file_exists(FORUM_ROOT . 'include/user/' . $cur_include[1]))
		error('Unable to process user include &lt;!-- forum_include "' . forum_htmlencode($cur_include[1]) . '" --&gt; from template main.tpl. There is no such file in folder /include/user/', __FILE__, __LINE__);

	ob_start();
	include FORUM_ROOT . 'include/user/' . $cur_include[1];
	$tpl_temp = ob_get_contents();
	$tpl_main = str_replace($cur_include[0], $tpl_temp, $tpl_main);
	ob_end_clean();
}
// END SUBST - <forum_include "*">

// START SUBST - <forum_local>
$tpl_main = str_replace('<forum_local>', 'xml:lang="' . $lang_common['lang_identifier'] . '" lang="' . $lang_common['lang_identifier'] . '" dir="' . $lang_common['lang_direction'] . '"', $tpl_main);
// END SUBST - <forum_local>

// START SUBST - <forum_head>
// Is this a page that we want search index spiders to index?
	$forum_head['title'] = '<title>' . generate_crumbs(true) . '</title>';
if (!defined('FORUM_ALLOW_INDEX'))
	$forum_head['robots'] = '<meta name="robots" content="NOINDEX, FOLLOW" />';
else
	$forum_head['descriptions'] = '<meta name="description" content="'.forum_htmlencode($forum_config['o_board_desc']).'" />';
	$forum_head['keywords'] = '<meta name="keywords" content="'.forum_htmlencode($forum_config['o_board_keywords']).'"/>';
// Should we output a MicroID? http://microid.org/
if (strpos(FORUM_PAGE, 'profile') === 0)
	$forum_head['microid'] = '<meta name="microid" content="mailto+http:sha1:' . sha1(sha1('mailto:' . $user['email']) . sha1(forum_link($forum_url['user'], $id))) . '" />';
	$forum_head['compatible'] = '<meta name="viewport" content="width=device-width, initial-scale=1" />';
// Should we output feed links?
if (FORUM_PAGE == 'index') {
	$forum_head['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_index'], 'rss') . '" title="RSS" />';
	$forum_head['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_index'], 'atom') . '" title="ATOM" />';
	$forum_head['og:title'] = '<meta property="og:title" content="'. generate_crumbs(true) .'"/>';
	$forum_head['og:sitename'] = '<meta property="og:site_name" content="'. forum_htmlencode($forum_config['o_board_title']).'"/>';
	$forum_head['og:url'] = '<meta property="og:site_name" content="'.forum_link($forum_url['index']).'"/>';
} else if (FORUM_PAGE == 'viewforum') {
	$forum_head['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_forum_topics'], array($id, $cur_forum['sort_by'] == '1' ? 'posted' : 'last_post', 'rss')) . '" title="RSS" />';
	$forum_head['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_forum_topics'], array($id, $cur_forum['sort_by'] == '1' ? 'posted' : 'last_post', 'atom')) . '" title="ATOM" />';
	$forum_head['og:title'] = '<meta property="og:title" content="'. generate_crumbs(true) .'"/>';
	$forum_head['og:sitename'] = '<meta property="og:site_name" content="'. forum_htmlencode($forum_config['o_board_title']).'"/>';
	$forum_head['og:url'] = '<meta property="og:site_name" content="'.forum_link($forum_url['forum'], $id).'"/>';
} else if (FORUM_PAGE == 'viewtopic') {
	$forum_head['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_topic'], array('rss', $id)) . '" title="RSS" />';
	$forum_head['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_topic'], array('atom', $id)) . '" title="ATOM" />';
	$forum_head['og:title'] = '<meta property="og:title" content="'. generate_crumbs(true) .'"/>';
	$forum_head['og:sitename'] = '<meta property="og:site_name" content="'. forum_htmlencode($forum_config['o_board_title']).'"/>';
	$forum_head['og:url'] = '<meta property="og:site_name" content="'.forum_link($forum_url['topic'], $id).'"/>';
}
// If there are more than two breadcrumbs, add the "up" link (second last)
if (count($forum_page['crumbs']) > 2)
	$forum_head['up'] = '<link rel="start" href="' . $forum_page['crumbs'][count($forum_page['crumbs']) - 2][1] . '" title="' . forum_htmlencode($forum_page['crumbs'][count($forum_page['crumbs']) - 2][0]) . '" />';

// If there are other page navigation links (first, next, prev and last)
if (!empty($forum_page['nav']))
	$forum_head['nav'] = implode("\n", $forum_page['nav']);

$forum_head['search'] = '<link rel="search" href="' . forum_link($forum_url['search']) . '" title="' . $lang_common['Search'] . '" />';
$forum_head['author'] = '<link rel="author" href="' . forum_link($forum_url['users']) . '" title="' . $lang_common['User list'] . '" />';

if (FORUM_PAGE == 'profile-about') {
	if ($user['url'] != '')
		$forum_head['rss'] = '<link rel="me" href="' . $user['url'] . '" />';
}

ob_start();

// Include stylesheets
if (defined('FORUM_PRINT'))
	$forum_head['style_print'] = '<link rel="stylesheet" type="text/css" media="print,screen" href="' . $base_url . '/style/' . $forum_user['style'] . '/css/print.css" />';
else {
	$forum_head['style_base'] = '<link rel="stylesheet" type="text/css" href="' . $base_url . '/style/' . $forum_user['style'] . '/css/base.css" />';
	require FORUM_ROOT . 'style/' . $forum_user['style'] . '/' . $forum_user['style'] . '.php';
}
$forum_head['favicon'] = '<link rel="shortcut icon" type="image/x-icon" href="' . $base_url . '/style/' . $forum_user['style'] . '/favicon.ico" />';
$head_temp = forum_trim(ob_get_contents());
$num_temp = 0;
$forum_head['jquery'] = '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
$forum_head['jquery_ui'] = '<script type="text/javascript" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>';
$forum_head['schosen'] = '<script type="text/javascript" src="' . $base_url . '/style/' . $forum_user['style'] . '/js/chosen.jquery.min.js"></script>';
$forum_head['mainjs'] = '<script type="text/javascript" src="' . $base_url . '/style/' . $forum_user['style'] . '/js/main.js"></script>';

foreach (explode("\n", $head_temp) as $style_temp)
	$forum_head['style' . $num_temp++] = $style_temp;

ob_end_clean();



($hook = get_hook('hd_head')) ? eval($hook) : null;

$tpl_main = str_replace('<forum_head>', implode("\n", $forum_head), $tpl_main);
unset($forum_head, $head_temp);
// END SUBST - <forum_head>

// START SUBST - <forum_head_admin>
// Is this a page that we want search index spiders to index?
if (!defined('FORUM_ALLOW_INDEX'))
	$forum_head_admin['robots'] = '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />';
else
	$forum_head_admin['descriptions'] = '<meta name="description" content="' . generate_crumbs(true) . '" />';

// Should we output a MicroID? http://microid.org/
if (strpos(FORUM_PAGE, 'profile') === 0)
	$forum_head_admin['microid'] = '<meta name="microid" content="mailto+http:sha1:' . sha1(sha1('mailto:' . $user['email']) . sha1(forum_link($forum_url['user'], $id))) . '" />';

$forum_head_admin['compatible'] = '<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">';

$forum_head_admin['title'] = '<title>' . generate_crumbs(true) . '</title>';
$forum_head_admin['favicon'] = '<link rel="shortcut icon" type="image/x-icon" href="' . $base_url . '/style/' . $forum_user['style'] . '/favicon.ico" />';

// Should we output feed links?
if (FORUM_PAGE == 'index') {
	$forum_head_admin['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_index'], 'rss') . '" title="RSS" />';
	$forum_head_admin['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_index'], 'atom') . '" title="ATOM" />';
} else if (FORUM_PAGE == 'viewforum') {
	$forum_head_admin['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_forum_topics'], array($id, $cur_forum['sort_by'] == '1' ? 'posted' : 'last_post', 'rss')) . '" title="RSS" />';
	$forum_head_admin['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_forum_topics'], array($id, $cur_forum['sort_by'] == '1' ? 'posted' : 'last_post', 'atom')) . '" title="ATOM" />';
} else if (FORUM_PAGE == 'viewtopic') {
	$forum_head_admin['rss'] = '<link rel="alternate" type="application/rss+xml" href="' . forum_link($forum_url['feed_topic'], array('rss', $id)) . '" title="RSS" />';
	$forum_head_admin['atom'] = '<link rel="alternate" type="application/atom+xml" href="' . forum_link($forum_url['feed_topic'], array('atom', $id)) . '" title="ATOM" />';
}

// If there are more than two breadcrumbs, add the "up" link (second last)
if (count($forum_page['crumbs']) > 2)
	$forum_head_admin['up'] = '<link rel="start" href="' . $forum_page['crumbs'][count($forum_page['crumbs']) - 2][1] . '" title="' . forum_htmlencode($forum_page['crumbs'][count($forum_page['crumbs']) - 2][0]) . '" />';

// If there are other page navigation links (first, next, prev and last)
if (!empty($forum_page['nav']))
	$forum_head_admin['nav'] = implode("\n", $forum_page['nav']);

$forum_head_admin['search'] = '<link rel="search" href="' . forum_link($forum_url['search']) . '" title="' . $lang_common['Search'] . '" />';
$forum_head_admin['author'] = '<link rel="author" href="' . forum_link($forum_url['users']) . '" title="' . $lang_common['User list'] . '" />';

if (FORUM_PAGE == 'profile-about') {
	if ($user['url'] != '')
		$forum_head_admin['rss'] = '<link rel="me" href="' . $user['url'] . '" />';
}

ob_start();

// Include stylesheets
if (defined('FORUM_PRINT'))
	$forum_head_admin['style_print'] = '<link rel="stylesheet" type="text/css" media="print,screen" href="' . $base_url . '/style/' . $forum_user['style'] . '/css/print.css" />';
else {
	$forum_head_admin['style_base'] = '<link rel="stylesheet" type="text/css" href="' . $base_url . '/resources/admin/bootstrap/css/bootstrap.min.css"/>';
	require FORUM_ROOT . 'admin/style.php';
}

$head_temp = forum_trim(ob_get_contents());
$num_temp = 0;

foreach (explode("\n", $head_temp) as $style_temp)
	$forum_head_admin['style' . $num_temp++] = $style_temp;

ob_end_clean();

$forum_head_admin['commonjs'] = '<script type="text/javascript" src="' . $base_url . '/style/' . $forum_user['style'] . '/js/main.js"></script>';

($hook = get_hook('hd_head')) ? eval($hook) : null;

$tpl_main = str_replace('<forum_head_admin>', implode("\n", $forum_head_admin), $tpl_main);
unset($forum_head_admin, $head_temp);
// END SUBST - <forum_head_admin>

// START SUBST OF COMMON ELEMENTS
// Setup array of general elements
$gen_elements = array();

// <forum_html_top>
$gen_elements['<forum_html_top>'] = ($forum_config['o_html_top'] && !defined('FORUM_DISABLE_HTML')) ? $forum_config['o_html_top_message'] : '';

// Forum page id and classes
if (!defined('FORUM_PAGE_TYPE')) {
	if (substr(FORUM_PAGE, 0, 5) == 'admin')
		define('FORUM_PAGE_TYPE', 'admin-page');
	else {
		if (!empty($forum_page['page_post']))
			define('FORUM_PAGE_TYPE', 'paged-page');
		else if (!empty($forum_page['main_menu']))
			define('FORUM_PAGE_TYPE', 'menu-page');
		else
			define('FORUM_PAGE_TYPE', 'basic-page');
	}
}

// Forum Title
$gen_elements['<forum_title>'] = '<h2 class="solo">'.forum_htmlencode($forum_config['o_board_title']).'</h2>';
$gen_elements['<forum_title_admin>'] = forum_htmlencode($forum_config['o_board_title']);

// Forum Logo
$gen_elements['<forum_logo>'] = '<a class="site-logo" href="' . forum_link($forum_url['index']) . '"></a>';

// Forum Description
$gen_elements['<forum_desc>'] = ($forum_config['o_board_desc'] != '') ? '<p id="brd-desc">' . forum_htmlencode($forum_config['o_board_desc']) . '</p>' : '';

// Main Top Navigation
$gen_elements['<forum_topnavlinks>'] = generate_topnavlinks();

// Admin Navigation
$gen_elements['<forum_navlinks_admins>'] =  generate_navlinks_admins();


// Main Navigation
$gen_elements['<forum_navlinks>'] = '<ul id="site-menu">' . "\n\t\t" . generate_navlinks() . "\n\t" . '</ul>';

// <forum_adbox>
$gen_elements['<forum_adbox>'] = ($forum_config['o_adbox'] && !defined('FORUM_DISABLE_HTML')) ? '<div class="stat-block online-list">' . "\n\t" . $forum_config['o_adbox_message'] . "\n" . '</div>' . "\n" : '';

// <forum_guestbox -->
$gen_elements['<forum_guestbox>'] = ($forum_config['o_guestbox'] && $forum_user['is_guest'] && !defined('FORUM_DISABLE_HTML')) ? '<div id="forum_features" class="flazy_alert">
			<a href="#" class="alert_close" id="close_features"></a><h3 class="alert_title">Information&nbsp;</h3><p class="alert_text">' . "\n\t" . $forum_config['o_guestbox_message'] . "\n" . '</p></div>' . "\n" : '';

// Announcement
$gen_elements['<forum_announcement>'] = ($forum_config['o_announcement'] && $forum_user['g_read_board'] && !defined('FORUM_DISABLE_HTML')) ? '<div id="brd-announcement" class="gen-content">' . ($forum_config['o_announcement_heading'] != '' ? "\n\t" . '<h1 class="hn"><span>' . $forum_config['o_announcement_heading'] . '</span></h1>' : '') . "\n\t" . '<div class="content">' . $forum_config['o_announcement_message'] . '</div>' . "\n" . '</div>' . "\n" : '';

// Maintenance Warning
$gen_elements['<forum_maint>'] = ($forum_user['g_id'] == FORUM_ADMIN && $forum_config['o_maintenance']) ? '<p id="maint-alert" class="warn">' . sprintf($lang_common['Maintenance warning'], '<a href="' . forum_link('admin/settings.php?section=maintenance') . '">' . $lang_common['Maintenance mode'] . '</a>') . '</p>' : '';

($hook = get_hook('hd_gen_elements')) ? eval($hook) : null;

$tpl_main = str_replace(array_keys($gen_elements), array_values($gen_elements), $tpl_main);
unset($gen_elements);
// END SUBST OF COMMON ELEMENTS

// START SUBST VISIT ELEMENTS
$visit_elements = array();
if ($forum_user['g_read_board'] && $forum_user['g_search']) {
	$visit_links = array();

	if (!$forum_user['is_guest'])
		$visit_links['newposts'] = '<li class="font-icon icon-search-self"><a href="' . forum_link($forum_url['search_new']) . '" title="' . $lang_common['New posts title'] . '"><i class="fa fa-file-text"></i>' . $lang_common['New posts'] . '</a></li>';

	$visit_links['recent'] = '<li class="font-icon icon-search-self"><a href="' . forum_link($forum_url['search_recent']) . '" title="' . $lang_common['Active topics title'] . '"><i class="fa fa-fire"></i>' . $lang_common['Active topics'] . '</a></li>';
	$visit_links['unanswered'] = '<li class="font-icon icon-search-self"><a href="' . forum_link($forum_url['search_unanswered']) . '" title="' . $lang_common['Unanswered topics title'] . '"><i class="fa fa-file-o"></i>' . $lang_common['Unanswered topics'] . '</a></li>';
	if (!$forum_user['is_guest'])
		$visit_links['posts'] = '<li class="font-icon icon-search-self"><a href="' . forum_link($forum_url['search_user_posts'], $forum_user['id']) . '" title="' . $lang_common['My posts title'] . '"><i class="fa fa-history"></i>' . $lang_common['My posts'] . '</a></li>';
}


$visit_elements['<forum_visit>'] = (!empty($visit_links)) ? '' . implode(' ', $visit_links) . '</p>' : '';

($hook = get_hook('hd_visit_elements')) ? eval($hook) : null;

$tpl_main = str_replace(array_keys($visit_elements), array_values($visit_elements), $tpl_main);
unset($visit_elements, $visit_links);
// END SUBST VISIT ELEMENTS

// START SUBST - <forum_admod>
$admod_links = array();

// We only need to run this query for mods/admins if there will actually be reports to look at
if ($forum_user['is_admmod'] && $forum_config['o_report_method'] != 1) {
	// Load cached
	if (file_exists(FORUM_CACHE_DIR . 'cache_report.php'))
		include FORUM_CACHE_DIR . 'cache_report.php';
	else {
		if (!defined('FORUM_CACHE_REPORT_LOADED'))
			require FORUM_ROOT . 'include/cache/report.php';
		generate_report_cache();
		require FORUM_CACHE_DIR . 'cache_report.php';
	}

	if ($forum_report['report'])
		$admod_links['reports'] = '<span id="reports"><a href="' . forum_link('admin/reports.php') . '"><strong>' . $lang_common['New reports'] . '</strong></a></span>';
}
if ($forum_user['g_id'] == FORUM_ADMIN)
{
	// Warn the admin that maintenance mode is enabled
	if ($forum_config['o_maintenance'])
		$alert_items = true;
	if ($forum_config['o_check_for_updates'])
	{
		if ($forum_updates['fail'] ||
			isset($forum_updates['version']) && isset($forum_updates['hotfix']) ||
			isset($forum_updates['version']) ||
			isset($forum_updates['hotfix']))
			$alert_items = true;
	}
	if ($forum_config['o_database_revision'] > FORUM_DB_REVISION ||
		($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') && $forum_config['o_database_engine'] != 'InnoDB' ||
		($db_type == 'mysql' || $db_type == 'mysqli') && $forum_config['o_database_engine'] != 'MyISAM')
		$alert_items = true;
	if (!empty($alert_items))
		$admod_links['alert'] = '<span id="alert"><a href="'.forum_link('admin/index.php').'"><strong>'.$lang_common['New alerts'].'</strong></a></span>';
	($hook = get_hook('hd_alert')) ? eval($hook) : null;
}
($hook = get_hook('hd_main_elements')) ? eval($hook) : null;

$tpl_main = str_replace('<forum_admod>', (!empty($admod_links)) ? '<p id="brd-admod">' . implode(' ', $admod_links) . '</p>' : '', $tpl_main);
unset($admod_links);
// END SUBST - <forum_admod>

// MAIN SECTION INTERFACE ELEMENT SUBSTITUTION
$main_elements = array();

// Top breadcrumbs
$main_elements['<forum_crumbs_top>'] = (FORUM_PAGE != 'index') ? generate_crumbs(false) : '';

$main_elements['<forum_crumbs_top_admin>'] = (FORUM_PAGE != 'index') ? '<ol class="breadcrumb">'.generate_crumbs_admin(false) : '</ol>';

// Bottom breadcrumbs
$main_elements['<forum_crumbs_end>'] = (FORUM_PAGE != 'index') ? generate_crumbs(false) : '';

// Main section heading
$main_elements['<forum_main_title>'] = isset($forum_page['main_title']) ? '' : "\n\t" . '<h2 class="solo">' . forum_htmlencode(is_array($last_crumb = end($forum_page['crumbs'])) ? reset($last_crumb) : $last_crumb) . (isset($forum_page['main_head_pages']) ? ' <small>' . $forum_page['main_head_pages'] . '</small>' : '') . '</h2>' . "\n";

// Top pagination and post links
$main_elements['<forum_main_pagepost_top>'] = (!empty($forum_page['page_post'])) ? "\n\t" . implode("\n\t", $forum_page['page_post']) . "\n" : '';

// Bottom pagination and postlink
$main_elements['<forum_main_pagepost_end>'] = (!empty($forum_page['page_post'])) ? "\n\t" . implode("\n\t", $forum_page['page_post']) . "\n" : '';

// Main section menu e.g. profile menu
$main_elements['<forum_main_menu>'] = (!empty($forum_page['main_menu'])) ?  implode("\n\t\t", $forum_page['main_menu']) . "\n\t"  : '';

// Main section menu e.g. profile menu
if (substr(FORUM_PAGE, 0, 5) == 'admin' && FORUM_PAGE_TYPE != 'paged') {
	$main_elements['<forum_admin_menu>'] = '<ul class="sidebar-menu">' . "\n\t\t" . generate_admin_menu(false) . "\n\t" . '</ul>';
}

// Section users online in forum\topic
$main_elements['<forum_main_extra>'] = (!empty($forum_page['main_extra'])) ? '<div class="stat-block online-list">' . "\n\t" . implode("\n\t", $forum_page['main_extra']) . "\n" . '</div>' : '';


$tpl_main = str_replace(array_keys($main_elements), array_values($main_elements), $tpl_main);
unset($main_elements);

// Узнаем местонахождение пользователя
if ($forum_config['o_users_online'])
	forum_online();

// END MAIN SECTION INTERFACE ELEMENT SUBSTITUTION
($hook = get_hook('hd_end')) ? eval($hook) : null;

if (!defined('FORUM_HEADER'))
	define('FORUM_HEADER', 1);
