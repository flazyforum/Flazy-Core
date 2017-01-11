<?php
/**
 * Report management page
 *
 * Allows administrators and moderators to handle reported posts.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../');
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/functions/admin.php';

($hook = get_hook('arp_start')) ? eval($hook) : null;

if (!$forum_user['is_admmod'])
	message($lang_common['No permission']);

// Load the admin.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_reports.php';


// Mark reports as read
if (isset($_POST['mark_as_read']))
{
	if (empty($_POST['reports']))
		message($lang_admin_reports['No reports selected']);

	($hook = get_hook('arp_mark_as_read_form_submitted')) ? eval($hook) : null;

	$reports_to_mark = array_map('intval', array_keys($_POST['reports']));

	$query = array(
		'UPDATE'	=> 'reports',
		'SET'		=> 'zapped='.time().', zapped_by='.$forum_user['id'],
		'WHERE'		=> 'id IN('.implode(',', $reports_to_mark).') AND zapped IS NULL'
	);

	($hook = get_hook('arp_mark_as_read_qr_mark_reports_as_read')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'SELECT'	=> 'r.post_id',
		'FROM'		=> 'reports AS r',
		'WHERE'		=> 'r.id IN('.implode(',', $reports_to_mark).')'
	);

	($hook = get_hook('arp_mark_as_read_qr_mark_reports_post_id')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$posts_to_mark = array();
	while ($cur_report4 = $forum_db->fetch_assoc($result))
		$posts_to_mark[] = $cur_report4['post_id'];

	// Если r.id>1 не обновлять!
	$query = array(
		'UPDATE'	=> 'posts',
		'SET'		=> 'reported=0',
		'WHERE'		=> 'id IN('.implode(',', $posts_to_mark).')'
	);

	($hook = get_hook('arp_mark_as_read_qr_mark_reports_as_read_to_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache/report.php';
	generate_report_cache();

	($hook = get_hook('arp_mark_as_read_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/reports.php'), $lang_admin_reports['Reports marked read'].' '.$lang_admin_common['Redirect']);
}

// Mark reports as read
if (isset($_POST['delete']))
{
	if (empty($_POST['delete_reports']))
		message($lang_admin_reports['No reports selected']);

	($hook = get_hook('arp_delete_form_submitted')) ? eval($hook) : null;

	$delete = array_map('intval', array_keys($_POST['delete_reports']));

	$query = array(
		'DELETE'	=> 'reports',
		'WHERE'		=> 'id IN('.implode(',', $delete).')'
	);

	($hook = get_hook('arp_delete_reports_as_read')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('arp_delete_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/reports.php'), $lang_admin_reports['Reports delete'].' '.$lang_admin_common['Redirect']);
}

$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
);
if ($forum_user['g_id'] == FORUM_ADMIN)
	$forum_page['crumbs'][] = array($lang_admin_common['Management'], forum_link('admin/reports.php'));
$forum_page['crumbs'][] = array($lang_admin_common['Reports'], forum_link('admin/reports.php'));

if (!defined('FORUM_PARSER_LOADED'))
	require FORUM_ROOT.'include/parser.php';

($hook = get_hook('arp_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-reports');
require FORUM_ROOT.'header.php';

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('arp_main_output_start')) ? eval($hook) : null;

// Fetch any unread reports
$query = array(
	'SELECT'	=> 'r.id, r.topic_id, r.forum_id, r.pm_id, r.reported_by, r.created, r.poster_id, r.message, r.reason, p.id AS pid, f.forum_name, t.subject,  pm.id AS pmid, u0.username AS reporter, u1.username AS poster',
	'FROM'		=> 'reports AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'posts AS p',
			'ON'		=> 'r.post_id=p.id'
		),
		array(
			'LEFT JOIN'	=> 'topics AS t',
			'ON'		=> 'r.topic_id=t.id'
		),
		array(
			'LEFT JOIN'	=> 'forums AS f',
			'ON'		=> 'r.forum_id=f.id'
		),
		array(
			'LEFT JOIN'	=> 'pm AS pm',
			'ON'		=> 'r.pm_id=pm.id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u0',
			'ON'		=> 'r.reported_by=u0.id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u1',
			'ON'		=> 'r.poster_id=u1.id'
		)
	),
	'WHERE'		=> 'r.zapped IS NULL',
	'ORDER BY'	=> 'r.created DESC'
);

($hook = get_hook('arp_qr_get_new_reports')) ? eval($hook) : null;

$forum_page['new_reports'] = false;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

if ($forum_db->num_rows($result))
{
	$forum_page['new_reports'] = true;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_reports['New reports heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form id="arp-new-report-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/reports.php') ?>?action=zap">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/reports.php').'?action=zap') ?>" />
			</div>
<?php

	$forum_page['item_num'] = 0;

	while ($cur_report = $forum_db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="'.forum_link($forum_url['user'], $cur_report['reported_by']).'">'.forum_htmlencode($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];

		if ($cur_report['pm_id'] != '0')
		{
			$post_id = 'Сообщение №'.$cur_report['pmid'];
			$poster = ($cur_report['poster_id']) ? '<a href="'.forum_link($forum_url['user'], $cur_report['poster']).'">'.forum_htmlencode($cur_report['poster']).'</a>': $lang_admin_reports['Deleted user'];

			$forum_page['report_legend'] = 'Личное сообщение » '.$post_id.' » '.$poster;;
		}
		else
		{
			$forum = ($cur_report['forum_name'] != '') ? '<a href="'.forum_link($forum_url['forum'], array($cur_report['forum_id'], sef_friendly($cur_report['forum_name']))).'">'.forum_htmlencode($cur_report['forum_name']).'</a>' : $lang_admin_reports['Deleted forum'];
			$topic = ($cur_report['subject'] != '') ? '<a href="'.forum_link($forum_url['topic'], array($cur_report['topic_id'], sef_friendly($cur_report['subject']))).'">'.forum_htmlencode($cur_report['subject']).'</a>' : $lang_admin_reports['Deleted topic'];
			$post_id = ($cur_report['pid'] != '') ? '<a href="'.forum_link($forum_url['post'], $cur_report['pid']).'">Сообщение №'.$cur_report['pid'].'</a>' : $lang_admin_reports['Deleted post'];
			$poster = ($cur_report['poster_id']) ? '<a href="'.forum_link($forum_url['user'], $cur_report['poster_id']).'">'.forum_htmlencode($cur_report['poster']).'</a>': $lang_admin_reports['Deleted user'];

			$forum_page['report_legend'] = $forum.' » '.$topic.' » '.$post_id.' » '.$poster;
		}

		$pattern = array("\n", "\t", '  ', '  ');
		$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
		$text = str_replace($pattern, $replace, forum_htmlencode($cur_report['reason']));

		$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
		$text = str_replace('<p><br />', '<p>', $text);
		$message = str_replace('<p></p>', '', '<p>'.$text.'</p>');

		($hook = get_hook('arp_new_report_pre_display')) ? eval($hook) : null;

?>
			<div class="ct-set warn-set report set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box warn-box">
					<h3 class="ct-legend hn"><strong><?php echo ++$forum_page['item_num'] ?></strong> <cite class="username"><?php printf($lang_admin_reports['Reported by'], $reporter) ?></cite> <span><?php echo format_time($cur_report['created']) ?></span></h3>
					<h4 class="hn"><?php echo $forum_page['report_legend'] ?></h4>
					<h4 class="hn"><?php echo parse_message($cur_report['message'], 0) ?></h4>
					<p><?php echo $message ?></p>
					<p class="item-select"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="reports[<?php echo $cur_report['id'] ?>]" value="1" /> <label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_reports['Select report'] ?></label></p>
<?php ($hook = get_hook('arp_new_report_new_block')) ? eval($hook) : null; ?>
				</div>
			</div>
<?php

	}

?>
			<div class="frm-buttons">
				<span id="select-all"><a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById('arp-new-report-form'))"><?php echo $lang_admin_common['Select all'] ?></a></span>
				<span class="submit"><input type="submit" name="mark_as_read" value="<?php echo $lang_admin_reports['Mark read'] ?>" /></span>
			</div>
		</form>
<?php

}

// Fetch the last 10 reports marked as read
$query = array(
	'SELECT'	=> 'r.id, r.topic_id, r.forum_id, r.pm_id, r.reported_by, r.created, r.poster_id, r.message, r.reason, r.zapped, r.zapped_by AS zapped_by_id, p.id AS pid, f.forum_name, t.subject, pm.id AS pmid, u0.username AS reporter, u1.username AS zapped_by, u2.username AS poster',
	'FROM'		=> 'reports AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'posts AS p',
			'ON'		=> 'r.post_id=p.id'
		),
		array(
			'LEFT JOIN'	=> 'topics AS t',
			'ON'		=> 'r.topic_id=t.id'
		),
		array(
			'LEFT JOIN'	=> 'forums AS f',
			'ON'		=> 'r.forum_id=f.id'
		),
		array(
			'LEFT JOIN'	=> 'pm AS pm',
			'ON'		=> 'r.pm_id=pm.id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u0',
			'ON'		=> 'r.reported_by=u0.id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u1',
			'ON'		=> 'r.zapped_by=u1.id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u2',
			'ON'		=> 'r.poster_id=u2.id'
		)
	),
	'WHERE'		=> 'r.zapped IS NOT NULL',
	'ORDER BY'	=> 'r.zapped DESC',
	'LIMIT'		=> '10'
);

($hook = get_hook('arp_qr_get_last_zapped_reports')) ? eval($hook) : null;


$forum_page['old_reports'] = false;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
if ($forum_db->num_rows($result))
{
	$i = 1;
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['item_num'] = 0;
	$forum_page['old_reports'] = true;

?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_admin_reports['Read reports heading'] ?><?php echo ($forum_db->num_rows($result)) ? '' : ' '.$lang_admin_reports['No new reports'] ?></span></h2>
		</div>
			<form id="arp-delete-report-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/reports.php') ?>?action=delete">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/reports.php').'?action=delete') ?>" />
			</div>
<?php

	while ($cur_report = $forum_db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="'.forum_link($forum_url['user'], $cur_report['reported_by']).'">'.forum_htmlencode($cur_report['reporter']).'</a>' : $lang_admin_reports['Deleted user'];

		if ($cur_report['pm_id'] != '0')
		{
			$post_id = 'Сообщение №'.$cur_report['pmid'];
			$poster = ($cur_report['poster'] != '') ? '<a href="'.forum_link($forum_url['user'], $cur_report['poster_id']).'">'.forum_htmlencode($cur_report['poster']).'</a>': $lang_admin_reports['Deleted user'];

			$forum_page['report_legend'] = 'Личное сообщение » '.$post_id.' » '.$poster;;
		}
		else
		{
			$forum = ($cur_report['forum_name'] != '') ? '<a href="'.forum_link($forum_url['forum'], array($cur_report['forum_id'], sef_friendly($cur_report['forum_name']))).'">'.forum_htmlencode($cur_report['forum_name']).'</a>' : $lang_admin_reports['Deleted forum'];
			$topic = ($cur_report['subject'] != '') ? '<a href="'.forum_link($forum_url['topic'], array($cur_report['topic_id'], sef_friendly($cur_report['subject']))).'">'.forum_htmlencode($cur_report['subject']).'</a>' : $lang_admin_reports['Deleted topic'];
			$post_id = ($cur_report['pid'] != '') ? '<a href="'.forum_link($forum_url['post'], $cur_report['pid']).'">Сообщение №'.$cur_report['pid'].'</a>' : $lang_admin_reports['Deleted post'];
			$poster = ($cur_report['poster_id']) ? '<a href="'.forum_link($forum_url['user'], $cur_report['poster_id']).'">'.forum_htmlencode($cur_report['poster']).'</a>': $lang_admin_reports['Deleted user'];

			$forum_page['report_legend'] = $forum.' » '.$topic.' » '.$post_id.' » '.$poster;
		}

		$pattern = array("\n", "\t", '  ', '  ');
		$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
		$text = str_replace($pattern, $replace, forum_htmlencode($cur_report['reason']));

		$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
		$text = str_replace('<p><br />', '<p>', $text);
		$message = str_replace('<p></p>', '', '<p>'.$text.'</p>');

		$zapped_by = ($cur_report['zapped_by'] != '') ? '<a href="'.forum_link($forum_url['user'], $cur_report['zapped_by_id']).'">'.forum_htmlencode($cur_report['zapped_by']).'</a>' : $lang_admin_reports['Deleted user'];

		($hook = get_hook('arp_report_pre_display')) ? eval($hook) : null;

?>
			<div class="ct-set report data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><strong><?php echo ++$forum_page['item_num'] ?></strong> <cite class="username"><?php printf($lang_admin_reports['Reported by'], $reporter) ?></cite> <span><?php echo format_time($cur_report['created']) ?></span></h3>
					<h4 class="hn"><?php echo $forum_page['report_legend'] ?></h4>
					<h4 class="hn"><?php echo parse_message($cur_report['message'], 0) ?></h4>
					<p><?php echo $message ?> <strong><?php printf($lang_admin_reports['Marked read by'], format_time($cur_report['zapped']), $zapped_by) ?></strong></p>
					<p class="item-select"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="delete_reports[<?php echo $cur_report['id'] ?>]" value="1" /> <label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_reports['Select report'] ?></label></p>
<?php ($hook = get_hook('arp_report_new_block')) ? eval($hook) : null; ?>
				</div>
			</div>
<?php

	}

?>
			<div class="frm-buttons">
				<span id="select-all"><a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById('arp-delete-report-form'))"><?php echo $lang_admin_common['Select all'] ?></a></span>
				<span class="submit"><input type="submit" name="delete" value="<?php echo $lang_admin_reports['Delete'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}

if (!$forum_page['new_reports'] && !$forum_page['old_reports'])
{

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_reports['Empty reports heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box">
			<p><?php echo $lang_admin_reports['No reports'] ?></p>
		</div>
	</div>
<?php

}

($hook = get_hook('arp_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'footer.php';
