<?php
/**
 *
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('st_fl_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'] || !$forum_config['o_statistic'])
	message($lang_common['No view']);

// Load the statistic.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/statistic.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;

if (!$section || $section == 'about')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'], forum_link($forum_url['statistic'], 'about'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'about'));

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	($hook = get_hook('st_fl_pre_about_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

?>
<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_stat['About stat'] ?></span></h2>
</div>
	<div class="main-content main-frm">
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
<?php ($hook = get_hook('st_fl_about_visit')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Online today'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'visit') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc visit'] ?></span></ul>
				</div>
			</div>
<?php ($hook = get_hook('st_fl_about_author')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top author little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'author') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc author'] ?></span></ul>
				</div>
			</div>
<?php ($hook = get_hook('st_fl_about_replies')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top replies little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'replies') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc replies'] ?></span></ul>
				</div>
			</div>
<?php ($hook = get_hook('st_fl_about_topviews')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top views little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'views') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc views'] ?></span></ul>
				</div>
			</div>
<?php ($hook = get_hook('st_fl_about_bans')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Bans'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'bans') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc bans'] ?></span></ul>
				</div>
			</div>
<?php ($hook = get_hook('st_fl_about_stat_pre_fieldset_end')) ? eval($hook) : null; ?>
		</fieldset>
<?php ($hook = get_hook('st_fl_about_stat_fieldset_end')) ? eval($hook) : null; ?>
	</div>
<?php

	($hook = get_hook('st_fl_about_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

$forum_page['main_menu'] = array(
	'visit'		=> '<li'.(($section == 'visit')  ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'visit').'"><span>'.$lang_stat['Online today'].'</span></a></li>',
	'author'	=> '<li'.(($section == 'author')  ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'author').'"><span>'.$lang_stat['Top author little'].'</span></a></li>',
	'treplies'	=> '<li'.(($section == 'replies') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'replies').'"><span>'.$lang_stat['Top replies little'].'</span></a></li>',
	'views'		=> '<li'.(($section == 'views') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'views').'"><span>'.$lang_stat['Top views little'].'</span></a></li>',
	'bans'		=> '<li'.(($section == 'bans') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'bans').'"><span>'.$lang_stat['Bans'].'</span></a></li>'
);

($hook = get_hook('st_fl_main_menu_end')) ? eval($hook) : null;

// Страница "Сегодня были"
if ($section == 'visit')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'], forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Online today'], forum_link($forum_url['statistic'], 'visit'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'visit'));

	($hook = get_hook('st_fl_pre_visit_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-visit');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

?>

	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc visit'] ?></span></h2>
		</div>
<?php

	$query = array(
		'SELECT'	=> 'u.id, u.last_visit, u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.last_visit>'.strtotime(gmdate('M d y')).' AND u.id>1 AND group_id!='.FORUM_UNVERIFIED,
		'ORDER BY'	=> 'u.last_visit DESC'
	);


	($hook = get_hook('st_fl_online_today_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
	{

?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['No one was'] ?></strong></span></h2>
		</div>
<?php

	}
	else
	{
		$forum_page['table_header'] = array();
		$forum_page['table_header']['username'] = '<th class="group '.count($forum_page['table_header']).'" scope="col">'.$lang_common['Username'].'</th>';
		$forum_page['table_header']['last_visit'] = '<th class="group '.count($forum_page['table_header']).'" scope="col">'.$lang_stat['Last visit'].'</th>';

		($hook = get_hook('st_fl_visit_results_pre_header_output')) ? eval($hook) : null;
?>
<div class="forumbg forumbg-table">
	<div class="inner">
		<table class="table1 show-header responsive" id="memberlist" summary="List of users filtered and sorted according to the criteria (if any) you have chosen.">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

		$forum_page['item_count'] = 0;
		while ($cur_stats = $forum_db->fetch_assoc($result))
		{
			$forum_page['table_row'] = array();
			$forum_page['table_row']['username'] = '<dd class="posts '.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $cur_stats['id']).'">'.forum_htmlencode($cur_stats['username']).'</a></dd>';
			$forum_page['table_row']['last_visit'] = '<dd class="views '.count($forum_page['table_row']).'">'.format_time($cur_stats['last_visit']).$lang_common['Title separator'].flazy_format_time($cur_stats['last_visit']).'</dd>';

			++$forum_page['item_count'];

			($hook = get_hook('st_fl_visit_results_row_pre_data_output')) ? eval($hook) : null;

?>
				<tr class="bg<?php echo ($forum_page['item_count'] % 2 != 0) ? '1' : '2' ?><?php echo ($forum_page['item_count'] == 1) ? '1' : '2' ?>">
					<td class="tc0">
<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php
		}

?>
			</tbody>
		</table>
	</div>
</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('st_fl_online_today_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

// Страница "Самые активные пользователи"
else if ($section == 'author')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top author'],forum_link($forum_url['statistic'], 'author'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'author'));

	($hook = get_hook('st_fl_pre_author_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-author');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	$forum_page['table_header'] = array();
	$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:35%" scope="col">'.$lang_common['Username'].'</th>';
	$forum_page['table_header']['registered'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:20%" scope="col">'.$lang_common['Registered'].'</th>';
	$forum_page['table_header']['posts'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:15%" scope="col">'.$lang_common['Posts'].'</th>';
	$forum_page['table_header']['com_forum'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:15%" scope="col">'.$lang_stat['Com forum'].'</th>';
	$forum_page['table_header']['in_day'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:15%" scope="col">'.$lang_stat['In day'].'</th>';

	($hook = get_hook('st_fl_author_results_pre_header_output')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc author'] ?></span></h2>
		</div>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

	$query = array(
		'SELECT'	=> 'SUM(f.num_posts)',
		'FROM'		=> 'forums AS f'
	);

	($hook = get_hook('st_fl_stats_qr_get_post_stats')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$total_posts = $forum_db->result($result);

	$query = array(
		'SELECT'	=> 'u.id, u.username, u.registered, u.num_posts',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id>1 AND u.num_posts>0',
		'ORDER BY'	=> 'u.num_posts DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_fl_author_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_page['item_count'] = 0;
	while ($cur_starts = $forum_db->fetch_assoc($result))
	{
		$pr_post = ($total_posts) ? substr($cur_starts['num_posts' ] / ($total_posts * 0.01), 0, 5) : '0';

		$num_posts_day = $cur_starts['num_posts'] > 0 ? substr($cur_starts['num_posts'] / (floor((time() - $cur_starts['registered']) / 84600) + (((time() - $cur_starts['registered']) % 84600) ? 1 : 0)), 0, 5) : 0;

		$forum_page['table_row'] = array();
		$forum_page['table_row']['user'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $cur_starts['id']).'">'.forum_htmlencode($cur_starts['username']).'</a></td>';
		$forum_page['table_row']['registered'] = '<td class="tc'.count($forum_page['table_row']).'">'.format_time($cur_starts['registered']).'</td>';
		$forum_page['table_row']['num_posts'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($cur_starts['num_posts']).'</td>';
		$forum_page['table_row']['pr_post'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($pr_post).' %</td>';
		$forum_page['table_row']['posts_day'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($num_posts_day).'</td>';

		++$forum_page['item_count'];

		($hook = get_hook('st_fl_author_results_row_pre_data_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

	}

?>
			</tbody>
			</table>
		</div>
	</div>
<?php

	($hook = get_hook('st_fl_author_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

// Страница "Самые комментируемые темы"
else if ($section == 'replies')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top replies'],forum_link($forum_url['statistic'], 'replies'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'replies'));

	$query = array(
		'SELECT'	=> 't.id, t.poster, t.poster_id, t.subject, t.num_replies, t.num_views',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'forum_perms AS fp',
				'ON'		=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> 't.moved_to IS NULL AND fp.read_forum IS NULL OR fp.read_forum=1',
		'ORDER BY'	=> 't.num_replies DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_fl_replies_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('st_fl_pre_replies_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-replies');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc replies'] ?></span></h2>
		</div>
		<div class="ct-group">
<?php

	if ($forum_db->num_rows($result))
	{
		$forum_page['table_header'] = array();
		$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:100%" scope="col">'.$lang_common['Username'].'</th>';
		$forum_page['table_header']['author'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:20em" scope="col">'.$lang_stat['Author'].'</th>';
		$forum_page['table_header']['replies'] = '<th class="info-replies" scope="col">'.$lang_stat['Replies'].'</th>';
		$forum_page['table_header']['views'] = '<th class="info-views" scope="col">'.$lang_stat['Views'].'</th>';

		($hook = get_hook('st_fl_replies_results_pre_header_output')) ? eval($hook) : null;

?>
			<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

		$forum_page['item_count'] = 0;
		while ($cur_stats = $forum_db->fetch_assoc($result))
		{
			if ($forum_config['o_censoring'])
				$cur_stats['subject'] = censor_words($cur_stats['subject']);

			$forum_page['table_row'] = array();
			$forum_page['table_row']['topic'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['topic'], $cur_stats['id']).'">'.forum_htmlencode($cur_stats['subject']).'</a></td>';
			$forum_page['table_row']['user'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $cur_stats['poster_id']).'">'.forum_htmlencode($cur_stats['poster']).'</a></td>';
			$forum_page['table_row']['num_replies'] = '<td class="info-replies"><span class="'.item_size($cur_stats['num_replies']).'">'.forum_number_format($cur_stats['num_replies']).'</span></td>';
			$forum_page['table_row']['num_views'] = '<td class="info-views"><span class="'.item_size($cur_stats['num_views']).'">'.forum_number_format($cur_stats['num_views']).'</span></td>';

			++$forum_page['item_count'];

			($hook = get_hook('st_fl_replies_results_row_pre_data_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}

?>
			</tbody>
			</table>
		</div>
<?php

	}
	else
	{

?>
		<div class="ct-box">
			<h2 class="hn"><strong><?php echo $lang_stat['No topics'] ?></strong></h2>
		</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('st_fl_replies_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

// Страница "Самые просматриваемые темы"
else if ($section == 'views')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top views'],forum_link($forum_url['statistic'], 'views'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'views'));

	$query = array(
		'SELECT'	=> 't.id, t.poster, t.poster_id, t.subject, t.num_replies, t.num_views',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'forum_perms AS fp',
				'ON'		=> '(fp.forum_id=t.forum_id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> 't.moved_to IS NULL AND fp.read_forum IS NULL OR fp.read_forum=1',
		'ORDER BY'	=> 't.num_views DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_fl_views_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('st_fl_pre_views_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-views');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();
?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc views'] ?></span></h2>
		</div>
<?php

	if ($forum_db->num_rows($result))
	{
		$forum_page['table_header'] = array();
		$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:100%" scope="col">'.$lang_common['Username'].'</th>';
		$forum_page['table_header']['author'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:20em" scope="col">'.$lang_stat['Author'].'</th>';
		$forum_page['table_header']['replies'] = '<th class="info-replies" scope="col">'.$lang_stat['Replies'].'</th>';
		$forum_page['table_header']['views'] = '<th class="info-views" scope="col">'.$lang_stat['Views'].'</th>';

		($hook = get_hook('st_fl_views_results_pre_header_output')) ? eval($hook) : null;

?>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

		$forum_page['item_count'] = 0;
		while ($cur_stats = $forum_db->fetch_assoc($result))
		{
			if ($forum_config['o_censoring'])
				$cur_stats['subject'] = censor_words($cur_stats['subject']);

			$forum_page['table_row'] = array();
			$forum_page['table_row']['topic'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['topic'], $cur_stats['id']).'">'.forum_htmlencode($cur_stats['subject']).'</a></td>';
			$forum_page['table_row']['user'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $cur_stats['poster_id']).'">'.forum_htmlencode($cur_stats['poster']).'</a></td>';
			$forum_page['table_row']['num_replies'] = '<td class="info-views"><span class="'.item_size($cur_stats['num_replies']).'">'.forum_number_format($cur_stats['num_replies']).'</span></td>';
			$forum_page['table_row']['num_views'] = '<td class="info-replies"><span class="'.item_size($cur_stats['num_views']).'">'.forum_number_format($cur_stats['num_views']).'</span></td>';

			++$forum_page['item_count'];

			($hook = get_hook('st_fl_views_results_row_pre_data_output')) ? eval($hook) : null;
?>
				<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}

?>
			</tbody>
			</table>
		</div>
<?php

	}
	else
	{

?>
		<div class="ct-box">
			<h2 class="hn"><strong><?php echo $lang_stat['No topics'] ?></strong></h2>
		</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('st_fl_views_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

// Страница "Баны" => bans
else if ($section == 'bans')
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'bans'));

	$query = array(
		'SELECT'	=> 'COUNT(*)',
		'FROM'		=> 'bans AS b'
	);

	($hook = get_hook('st_fl_bans_qr_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$ban = $forum_db->result($result);

	if ($ban)
	{
		$forum_page['num_pages'] = ceil(($ban + 1) / $forum_user['disp_posts']);
		$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
		$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);

		if ($forum_page['page'] < $forum_page['num_pages'])
		{
			$forum_page['nav']['last'] = '<link rel="last" href="'.forum_link($forum_url['statistic'], $forum_url['page'], $forum_page['num_pages'], 'bans').'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
			$forum_page['nav']['next'] = '<link rel="next" href="'.forum_link($forum_url['statistic'], $forum_url['page'], $forum_page['num_pages'] + 1, 'bans').'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
		}

		if ($forum_page['page'] > 1)
		{
			$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_link($forum_url['statistic'], $forum_url['page'] -1 , $forum_page['num_pages'], 'bans').'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
			$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['statistic'], 'bans').'" title="'.$lang_common['Page'].' 1" />';
		}

		$page_post_ban = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['statistic'], $lang_common['Paging separator'], 'bans').'</p>';

		$query = array(
			'SELECT'	=> 'b.id, b.username, b.ip, b.email, b.message, b.expire, b.ban_creator, u0.id, u1.username AS username_creator',
			'FROM'		=> 'bans AS b',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u0',
					'ON'			=> 'b.username=u0.username'
				),
				array(
					'INNER JOIN'	=> 'users AS u1',
					'ON'			=> 'b.ban_creator=u1.id'
				)
			),
			'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
		);

		($hook = get_hook('st_fl_bans_qr')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Bans'],forum_link($forum_url['statistic'], 'bans'))
	);

	($hook = get_hook('st_fl_pre_bans_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-bans');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc bans'] ?></span></h2>
		</div>
<?php

		//If there are any bans in the ban list, put them in
	if ($ban)
	{
		$forum_page['table_header'] = array();
		$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:20%" scope="col">'.$lang_common['Username'].'</th>';
		$forum_page['table_header']['message'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:45%" scope="col">'.$lang_stat['Message'].'</th>';
		$forum_page['table_header']['expires'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:20%" scope="col">'.$lang_stat['Expires'].'</th>';
		$forum_page['table_header']['creator'] = '<th class="tc'.count($forum_page['table_header']).'" style="width:15%" scope="col">'.$lang_stat['Ban creator'].'</th>';

		($hook = get_hook('on_fl_bans_pre_header_output')) ? eval($hook) : null;

?>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<?php echo implode("\n\t\t\t\t", $forum_page['table_header'])."\n" ?>
			</thead>
			<tbody>
<?php

		$forum_page['item_count'] = 0;
		while ($cur_ban = $forum_db->fetch_assoc($result))
		{
			if ($forum_config['o_censoring'])
				$cur_ban['message'] = censor_words($cur_ban['message']);

			$forum_page['table_row'] = array();
			$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'">'.($cur_ban['username'] != '' ? '<a href="'.forum_link($forum_url['user'], $cur_ban['id']).'">'.forum_htmlencode($cur_ban['username']).'</a>' : $lang_stat['No IP']).'</td>';
			$forum_page['table_row']['message'] = '<td class="tc'.count($forum_page['table_row']).'">'.($cur_ban['message'] != '' ? $cur_ban['message'] : $lang_stat['No']).'</td>';
			$forum_page['table_row']['expire'] = '<td class="tc'.count($forum_page['table_row']).'">'.format_time($cur_ban['expire'], true).'</td>';
			$forum_page['table_row']['creator'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $cur_ban['ban_creator']).'">'.forum_htmlencode($cur_ban['username_creator']).'</a></td>';

			++$forum_page['item_count'];

			($hook = get_hook('st_fl_bans_results_row_pre_data_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
					<?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}

?>
			</tbody>
			</table>
		</div>
<?

	}
	else
	{

?>
		<div class="ct-box">
			<h2 class="hn"><strong><?php echo $lang_stat['No bans']; if ($forum_config['o_rules']) printf($lang_stat['No bans rules'], forum_link($forum_url['rules'])); ?></strong></h2>
		</div>
<?php

	}
	
?>
	</div>
<?php

	if (!empty($page_post_ban))
	{

?>
<div id="brd-pagepost-end" class="main-pagepost gen-content">
	<?php echo $page_post_ban ?>
</div>
<?php

	}

	($hook = get_hook('st_fl_bans_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

message($lang_common['Bad request'], false, '404 Not Found');