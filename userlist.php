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

($hook = get_hook('ul_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'])
	message($lang_common['No view']);
else if (!$forum_user['g_view_users'])
	message($lang_common['No permission'], false, '403 Forbidden');

// Load the userlist.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/userlist.php';

// Miscellaneous setup
$forum_page['show_post_count'] = ($forum_config['o_show_post_count'] || $forum_user['is_admmod']) ? true : false;
$forum_page['username'] = (isset($_GET['username']) && $_GET['username'] != '-' && $forum_user['g_search_users']) ? $_GET['username'] : '';
$forum_page['show_group'] = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);
$forum_page['sort_by'] = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && ($_GET['sort_by'] != 'num_posts' || !$forum_page['show_post_count'])) ? 'username' : $_GET['sort_by'];
$forum_page['sort_dir'] = (!isset($_GET['sort_dir']) || strtoupper($_GET['sort_dir']) != 'ASC' && strtoupper($_GET['sort_dir']) != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);

$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1) ? 1 : intval($_GET['p']);

// Check for use of incorrect URLs
if (isset($_GET['username']) || isset($_GET['show_group']) || isset($_GET['sort_by']) || isset($_GET['sort_dir']) || $forum_page['page'] != 1)
{
	if ($forum_page['page'] != 1)
		confirm_current_url(forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page']), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')));
}

// Create any SQL for the WHERE clause
$where_sql = array();
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if ($forum_user['g_search_users'] && $forum_page['username'] != '')
	$where_sql[] = 'u.username '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $forum_page['username'])).'\'';
if ($forum_page['show_group'] > -1)
	$where_sql[] = 'u.group_id='.$forum_page['show_group'];

// Load cached
if (file_exists(FORUM_CACHE_DIR.'cache_stat_user.php'))
	include FORUM_CACHE_DIR.'cache_stat_user.php';
else
{
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();
	require FORUM_CACHE_DIR.'cache_stat_user.php';
}

if (!empty($where_sql))
{
	$query = array(
		'SELECT'	=> 'COUNT(u.id)',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id > 1 AND u.group_id != '.FORUM_UNVERIFIED.' AND '.implode(' AND ', $where_sql)
	);

	($hook = get_hook('ul_qr_get_user_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_stat_user['total_users'] = $forum_db->result($result);
}

// Determine the user offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil($forum_stat_user['total_users'] / 50);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = 50 * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + 50), ($forum_stat_user['total_users']));

$forum_page['users_searched'] = (($forum_user['g_search_users'] && $forum_page['username'] != '') || $forum_page['show_group'] > -1);

if ($forum_stat_user['total_users'] > 0)
	$forum_page['items_info'] = generate_items_info( (($forum_page['users_searched']) ? $lang_ul['Users found'] : $lang_ul['Users']), ($forum_page['start_from'] + 1), $forum_stat_user['total_users']);
else
	$forum_page['items_info'] = $lang_ul['Users'];

// Generate paging links
$forum_page['page_post']['paging'] = '<div class="pagination">' . $forum_page['items_info'] .' • '.$lang_common['Pages'] . paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['users_browse'], $lang_common['Paging separator'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')).'</div>';

// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages'])
{
	$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink($forum_url['users_browse'], $forum_url['page'], $forum_page['num_pages'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
	$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page'] + 1), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
}
if ($forum_page['page'] > 1)
{
	$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page'] - 1), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
	$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['users_browse'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')).'" title="'.$lang_common['Page'].' 1" />';
}

// Setup main heading
$forum_page['main_title'] = $lang_common['User list'];

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = $base_url.'/userlist.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_common['User list'], forum_link($forum_url['users']))
);

$forum_page['frm-info'] = array(
	'wildcard'	=> '<span>'.$lang_ul['Wildcard info'].'</span><br>',
	'group'		=> '<span>'.$lang_ul['Group info'].'</span><br>',
	'sort'		=> '<span>'.$lang_ul['Sort info'].'</span>'
);

// Setup main heading
if ($forum_page['num_pages'] > 1)
	$forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

($hook = get_hook('ul_pre_header_load')) ? eval($hook) : null;

define('FORUM_ALLOW_INDEX', 1);

define('FORUM_PAGE', 'userlist');
require FORUM_ROOT.'header.php';

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('ul_main_output_start')) ? eval($hook) : null;

?>

		<div class="stat-block online-list">
			<p><?php echo implode("\n\t\t\t\t", $forum_page['frm-info'])."\n" ?></p>
		</div>
		<div class="action-bar top">
			<div class="member-search panel">

		<div class="search-box">
		<form id="forum-search" method="get" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">

<?php ($hook = get_hook('ul_search_fieldset_start')) ? eval($hook) : null; ?>
			<fieldset>
<?php ($hook = get_hook('ul_pre_username')) ? eval($hook) : null; if ($forum_user['g_search_users']): ?>


	<input  class="inputbox search" type="search" id="search<?php echo $forum_page['fld_count'] ?>" name="username" value="<?php echo forum_htmlencode($forum_page['username']) ?>" size="35" maxlength="25" />
	<button class="button" type="submit" name="search" value="<?php echo $lang_ul['Submit user search'] ?>" >
		<i class="fa fa-search"></i>
	</button>


<?php endif; ?>

			</fieldset>
<?php ($hook = get_hook('ul_search_fieldset_end')) ? eval($hook) : null; ?>

		</form>
		</div></div>
	</div>
<?php

// Grab the users
$query = array(
	'SELECT'	=> 'u.id, u.username, u.title, u.num_posts, u.registered, u.last_visit, u.reputation_plus, u.reputation_minus, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id>1 AND u.group_id!='.FORUM_UNVERIFIED,
	'ORDER BY'	=> $forum_page['sort_by'].' '.$forum_page['sort_dir'].', u.id ASC',
	'LIMIT'		=> $forum_page['start_from'].', 50'
);

if (!empty($where_sql))
	$query['WHERE'] .= ' AND '.implode(' AND ', $where_sql);

($hook = get_hook('ul_qr_get_users')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$forum_page['item_count'] = 0;

if ($forum_db->num_rows($result))
{
	($hook = get_hook('ul_results_pre_header')) ? eval($hook) : null;

	$forum_page['table_header'] = array();
	$forum_page['table_header']['username'] = '<th class="name" scope="col">'.$lang_ul['Username'].'</th>';
	$forum_page['table_header']['title'] = '<th class="group" scope="col">'.$lang_ul['Title'].'</th>';
	if ($forum_config['o_rep_enabled'] && $forum_user['g_rep_enable'] && $forum_user['rep_enable'])
		$forum_page['table_header']['reputation'] = '<th class="reputation" scope="col">'.$lang_ul['Reputation'].'</th>';
	if ($forum_page['show_post_count'])
		$forum_page['table_header']['posts'] = '<th class="posts" scope="col">'.$lang_ul['Posts'].'</th>';
	$forum_page['table_header']['registered'] = '<th class="registered" scope="col">'.$lang_ul['Registered'].'</th>';
	$forum_page['table_header']['last visit'] = '<th  class="last_visit" scope="col">'.$lang_ul['Last visit'].'</th>';

	($hook = get_hook('ul_results_pre_header_output')) ? eval($hook) : null;

?><div class="forumbg forumbg-table">
	<div class="inner">
			<table class="table1 show-header responsive" id="memberlist" summary="<?php echo $lang_ul['Table summary'] ?>">
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
					</tr>
				</thead>
				<tbody>
<?php

	while ($user_data = $forum_db->fetch_assoc($result))
	{
		($hook = get_hook('ul_results_row_pre_data')) ? eval($hook) : null;

		$forum_page['reputation'] = ($user_data['reputation_plus'] == 0 && $user_data['reputation_minus'] == 0) ? 0 : '+'.forum_number_format($user_data['reputation_plus']).' \ -'.forum_number_format($user_data['reputation_minus']);

		$forum_page['table_row'] = array();
		$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></td>';
		$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'">'.get_title($user_data).'</td>';
		if ($forum_config['o_rep_enabled'] && $forum_user['g_rep_enable'] && $forum_user['rep_enable'])
			$forum_page['table_row']['reputation'] = '<td class="tc'.count($forum_page['table_row']).'">'.$forum_page['reputation'].'</td>';
		if ($forum_page['show_post_count'])
			$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';
		$forum_page['table_row']['registered'] = '<td class="tc'.count($forum_page['table_row']).'">'.format_time($user_data['registered'], 1).'</td>';
		$forum_page['table_row']['last visit'] = '<td class="tc'.count($forum_page['table_row']).'">'.format_time($user_data['last_visit'], 1).'</td>';

		++$forum_page['item_count'];

		($hook = get_hook('ul_results_row_pre_data_output')) ? eval($hook) : null;

?>
					<tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'bg1' : 'bg2'; ?>">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
					</tr>
<?php

	}

?>
				</tbody>
			</table>
<?php

}
else
{

?>

<div class="forumbg">
		<div class="inner">
			<ul class="topiclist">
				<li class="header">
					<dl class="icon">
						<dt>
							<div class="list-inner">
							</div>
						</dt>
					</dl>

				</li>
			</ul>

			<ul class="topiclist topics">
				<li class="row bg2">
					<dl>
						<dt>
							<div class="list-inner">
								<?php echo $lang_ul['No users found'] ?>
							</div>
						</dt>

					</dl>
				</li>

			</ul>
		</div>
	</div>
<?php
}
?>
	</div>
</div>

<?php

($hook = get_hook('ul_end')) ? eval($hook) : null;


$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'footer.php';
