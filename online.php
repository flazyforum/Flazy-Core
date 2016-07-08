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

($hook = get_hook('on_fl_start')) ? eval($hook) : null;

// Check for use of incorrect URLs
confirm_current_url(forum_link($forum_url['online']));

if (!$forum_user['g_read_board'] || !$forum_config['o_users_online'])
	message($lang_common['No view']);

// Load the online.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/online.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_online['Online List'],forum_link($forum_url['online']))
);

($hook = get_hook('on_fl_pre_header_load')) ? eval($hook) : null;

define('FORUM_ALLOW_INDEX', 1);
define('FORUM_PAGE', 'online');
require FORUM_ROOT.'header.php';

// START SUBST - <forum_main>
ob_start();

$forum_page['table_header'] = array();
$forum_page['table_header']['username'] = '<th class="name'.count($forum_page['table_header']).'"  scope="col">'.$lang_common['Username'].'</th>';
$forum_page['table_header']['action'] = '<th class="group'.count($forum_page['table_header']).'"  scope="col">'.$lang_online['Last action'].'</th>';
$forum_page['table_header']['time'] = '<th class="last_visit'.count($forum_page['table_header']).'"  scope="col">'.$lang_online['Time'].'</th>';

($hook = get_hook('on_fl_results_pre_header_output')) ? eval($hook) : null;

?>
<div id="wrap-body">
	<div class="chunk">	
		<div class="forumbg forumbg-table">
			<div class="inner">
				<table class="table1 show-header responsive" id="memberlist" summary="List of users filtered and sorted according to the criteria (if any) you have chosen.">
					<thead>
						<tr>
							<?php echo implode("\n\t\t\t\t", $forum_page['table_header'])."\n" ?>
						</tr>
					</thead>
					<tbody>
<?php

// Получим список участников
$query = array(
	'SELECT'	=> 'o.user_id, o.ident, o.logged, o.idle, o.current_page, o.current_page_id, o.current_ip, t.subject, u.username, f.forum_name,  t1.subject AS subject_edit',
	'FROM'		=> 'online AS o',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'topics AS t',
			'ON'			=> 't.id=o.current_page_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=o.current_page_id'
		),
		array(
			'LEFT JOIN'		=> 'forums AS f',
			'ON'			=> 'f.id=o.current_page_id'
		),
		array(
			'LEFT JOIN'		=> 'posts AS p',
			'ON'			=> 'p.id=o.current_page_id'
		),
		array(
			'LEFT JOIN'		=> 'topics AS t1',
			'ON'			=> 't1.id=p.topic_id'
		),
	),
	'WHERE'		=> 'o.idle=0',
	'ORDER BY'	=> 'o.ident'
);

($hook = get_hook('on_fl_online_list_qr_get')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

if ($forum_db->num_rows($result))
{
	$forum_page['item_count'] = 0;
	while ($cur_online = $forum_db->fetch_assoc($result))
	{
		$list = array();

		if ($cur_online['user_id'] > 1)
		{
			$ip = ($forum_user['is_admmod']) ? '<sup><a href="'.forum_link($forum_url['get_host'], $cur_online['current_ip']).'">'.forum_htmlencode($cur_online['current_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext='.forum_htmlencode($cur_online['current_ip']).'&amp;do_search=Search" onclick="window.open(this.href); return false">Whois</a></sup>' : '';
			$page_user = '<a href="'.forum_link($forum_url['user'], $cur_online['user_id']).'">'.forum_htmlencode($cur_online['ident']).'</a> '.$ip.'';
		}
		else
		{
			$ip = (preg_match('/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/', $cur_online['ident'], $matches) || preg_match('/^([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4})$/', $cur_online['ident'], $matches)) ? $matches[1].'.'.$matches[2].'.*.*' : intval($cur_online['ident']);
			$page_user = $lang_online['Guest'].''.(($forum_user['is_admmod']) ? ' <sup><a href="'.forum_link($forum_url['get_host'], forum_htmlencode($cur_online['current_ip'])).'">'.forum_htmlencode($cur_online['current_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&full_query_string=&searchtext='.forum_htmlencode($cur_online['current_ip']).'&do_search=Search" onclick="window.open(this.href); return false">Whois</a></sup>' : '<sup> IP: '.$ip.'</sup>');
		}

		($hook = get_hook('on_fl_pre_current_page')) ? eval($hook) : null;

		$cur_page = $cur_online['current_page'];

		if (substr($cur_page, 0, 5) == 'admin')
			$cur_page = 'admin';
		else if ((@$lang_online[$cur_page]) == '')
 			$cur_page = 'Hiding Somewhere';

		if ($cur_page == 'viewtopic' || $cur_page == 'post')
			$page_name = ': <strong><a href="'.forum_link($forum_url['topic'], array($cur_online['current_page_id'], sef_friendly($cur_online['subject']))).'">'.forum_htmlencode($cur_online['subject']).'</a></strong>';
		else if ($cur_page == 'postedit')
			$page_name = ': <strong><a href="'.forum_link($forum_url['post'], array($cur_online['current_page_id'], sef_friendly($cur_online['subject']))).'">'.forum_htmlencode($cur_online['subject_edit']).'</a></strong>';
		else if ($cur_page == 'profile-about' || $cur_page == 'profile' || $cur_page == 'reputation' || $cur_page == 'positive')
			$page_name = ': <strong><a href="'.forum_link($forum_url['user'], array($cur_online['current_page_id'])).'">'.forum_htmlencode($cur_online['username']).'</a></strong>';
		else if ($cur_page == 'viewforum')
			$page_name = ': <strong><a href="'.forum_link($forum_url['forum'], $cur_online['current_page_id']).'">'.forum_htmlencode($cur_online['forum_name']).'</a></strong>';
		else
			$page_name = '';

		$forum_page['table_row'] = array();
		$forum_page['table_row']['user'] = '<td class="tc'.count($forum_page['table_row']).'"><dfn style="display: none;">Username</dfn>'.$page_user.'</td>';
		$forum_page['table_row']['page'] = '<td class="tc'.count($forum_page['table_row']).'"><dfn style="display: none;">Page</dfn>'.$lang_online[$cur_page].$page_name.'</td>';
		$forum_page['table_row']['time'] = '<td class="tc'.count($forum_page['table_row']).'"><dfn style="display: none;">Last login</dfn>'.format_time($cur_online['logged']).'</td>';

		($hook = get_hook('on_fl_results_row_pre_data_output')) ? eval($hook) : null;

		++$forum_page['item_count'];

?>

			<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'bg1' : 'bg1' ?><?php echo ($forum_page['item_count'] == 1) ? ' bg2' : '' ?>">
				<?php echo implode("\n\t\t\t\t", $forum_page['table_row'])."\n" ?>
			</tr>
<?php

	}
}
else
{

?>
			<tr>
				<td colspan="4"><? echo $lang_online['Nobody'] ?></td>
			</tr>
<?php

	($hook = get_hook('on_fl_after_nobody')) ? eval($hook) : null;
}

?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php

($hook = get_hook('on_fl_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'footer.php';
