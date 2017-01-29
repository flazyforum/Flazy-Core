<?php
/**
<<<<<<< HEAD
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
=======
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2017 Flazy.org
>>>>>>> origin/master
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Показывает навигационное меню панели администратора.
 * @param bool TRUE - подменю, FALSE - главное навигационное меню.
 * @return string HTML код навигационого меню.
 */
function generate_admin_menu()
{
	global $forum_config, $forum_user, $lang_admin_common;//, $db_type;

	$return = ($hook = get_hook('ca_fn_generate_admin_menu_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;



		if ($forum_user['g_id'] != FORUM_ADMIN)
			echo "koko";
		else
		{
			$forum_page['admin_menu']['index'] = '
			<li class="'.((FORUM_PAGE_SECTION == 'start') ? 'active' : 'unactive').((empty($forum_page['admin_menu'])) ? ' treeview' : '').'">
				<a href="'.forum_link('admin/index.php').'"><i class="fa fa-tachometer"></i><span>'.$lang_admin_common['Start'].'</span><i class="fa fa-angle-left pull-right"></i></a>
					<ul class="treeview-menu">
						<li class="'.((FORUM_PAGE == 'admin-information') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/index.php').'"><i class="fa fa-info-circle"></i> '.$lang_admin_common['Information'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-categories') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/categories.php').'"><i class="fa fa-sitemap"></i> '.$lang_admin_common['Categories'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-forums') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/forums.php').'"><i class="fa fa-comments-o"></i> '.$lang_admin_common['Forums'].'</a></li>
					</ul>
			</li>';
			$forum_page['admin_menu']['settings_setup'] = '
			<li class="'.((FORUM_PAGE_SECTION == 'settings') ? 'active' : 'unactive').((empty($forum_page['admin_menu'])) ? ' treeview' : '').'">
				<a href="'.forum_link('admin/settings.php?section=setup').'"><i class="fa fa-cog"></i><span>'.$lang_admin_common['Settings'].'</span><i class="fa fa-angle-left pull-right"></i></a>
					<ul class="treeview-menu">
						<li class="'.((FORUM_PAGE == 'admin-settings-setup') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=setup').'"><i class="fa fa-cogs"></i> '.$lang_admin_common['Setup'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-settings-features') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=features').'"><i class="fa fa-list"></i> '.$lang_admin_common['Features'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-settings-announcements') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=announcements').'"><i class="fa fa-bullhorn"></i> '.$lang_admin_common['Announcements'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-settings-email') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=email').'"><i class="fa fa-envelope-o"></i> '.$lang_admin_common['E-mail'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-settings-registration') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=registration').'"><i class="fa fa-registered"></i> '.$lang_admin_common['Registration'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-censoring') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/censoring.php').'"><i class="fa fa-hand-paper-o"></i> '.$lang_admin_common['Censoring'].'</a></li>
					</ul>
			</li>';
			$forum_page['admin_menu']['users'] = '
			<li class="'.((FORUM_PAGE_SECTION == 'users') ? 'active' : 'unactive').((empty($forum_page['admin_menu'])) ? ' treeview' : '').'">
				<a href="'.forum_link('admin/users.php').'"><i class="fa fa-user"></i><span>'.$lang_admin_common['Users'].'</span><i class="fa fa-angle-left pull-right"></i></a>
					<ul class="treeview-menu">
						<li class="'.((FORUM_PAGE == 'admin-users' || FORUM_PAGE == 'admin-uresults' || FORUM_PAGE == 'admin-iresults') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/users.php').'"><i class="fa fa-search"></i> '.$lang_admin_common['Searches'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-groups') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/groups.php').'"><i class="fa fa-users"></i> '.$lang_admin_common['Groups'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-ranks') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/ranks.php').'"><i class="fa fa-certificate"></i> '.$lang_admin_common['Ranks'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-bans') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/bans.php').'"><i class="fa fa-ban"></i> '.$lang_admin_common['Bans'].'</a></li>
					</ul>
			</li>';
			$forum_page['admin_menu']['reports'] = '
			<li class="'.((FORUM_PAGE_SECTION == 'management') ? 'active' : 'unactive').((empty($forum_page['admin_menu'])) ? ' treeview' : '').'">
				<a href="'.forum_link('admin/reports.php').'"><i class="fa fa-flag"></i><span>'.$lang_admin_common['Management'].'</span><i class="fa fa-angle-left pull-right"></i></a>
					<ul class="treeview-menu">
						<li class="'.((FORUM_PAGE == 'admin-reports') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/reports.php').'"><i class="fa fa-flag-o"></i> '.$lang_admin_common['Reports'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-prune') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/prune.php').'"><i class="fa fa-trash-o"></i> '.$lang_admin_common['Prune topics'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-reindex') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/reindex.php').'"><i class="fa fa-repeat"></i> '.$lang_admin_common['Rebuild index'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-maintenance') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/settings.php?section=maintenance').'"><i class="fa fa-wrench"></i> '.$lang_admin_common['Maintenance mode'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-cache') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/cache.php').'"><i class="fa fa-random"></i> '.$lang_admin_common['Cache'].'</a></li>
					</ul>
			</li>';
			$forum_page['admin_menu']['extensions_manage'] = '
			<li class="'.((FORUM_PAGE_SECTION == 'extensions') ? 'active' : 'unactive').((empty($forum_page['admin_menu'])) ? 'treeview' : '').'">
				<a href="'.forum_link('admin/extensions.php?section=manage').'"><i class="fa fa-plug"></i><span>'.$lang_admin_common['Extensions'].'</span><i class="fa fa-angle-left pull-right"></i></a>
					<ul class="treeview-menu">
						<li class="'.((FORUM_PAGE == 'admin-extensions-manage') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/extensions.php?section=manage').'"><i class="fa fa-plus"></i> '.$lang_admin_common['Manage extensions'].'</a></li>
						<li class="'.((FORUM_PAGE == 'admin-extensions-hotfixes') ? 'active' : 'unactive').((empty($forum_page['admin_submenu'])) ? ' ' : '').'"><a href="'.forum_link('admin/extensions.php?section=hotfixes').'"><i class="fa fa-wrench"></i> '.$lang_admin_common['Manage hotfixes'].'</a></li>	
					</ul>
			</li>';
		}

		($hook = get_hook('ca_fn_generate_admin_menu_new_link')) ? eval($hook) : null;

		return implode("\n\t\t", $forum_page['admin_menu']);
	}



/**
 * Delete topics from $forum_id that are "older than" $prune_date (if $prune_sticky is 1, sticky topics will also be deleted).
 */
function prune($forum_id, $prune_sticky, $prune_date)
{
	global $forum_db, $db_type;

	$return = ($hook = get_hook('ca_fn_prune_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Fetch topics to prune
	$query = array(
		'SELECT'	=> 't.id',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.forum_id='.$forum_id
	);

	if ($prune_date != -1)
		$query['WHERE'] .= ' AND last_post<'.$prune_date;
	if (!$prune_sticky)
		$query['WHERE'] .= ' AND sticky=\'0\'';

	($hook = get_hook('ca_fn_prune_qr_get_topics_to_prune')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$topic_ids = array();
	while ($row = $forum_db->fetch_row($result))
		$topic_ids[] = $row[0];

	if (!empty($topic_ids))
	{
		$topic_ids = implode(',', $topic_ids);

		// Fetch posts to prune (used lated for updating the search index)
		$query = array(
			'SELECT'	=> 'p.id',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.topic_id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_get_posts_to_prune')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$post_ids = array();
		while ($row = $forum_db->fetch_row($result))
			$post_ids[] = $row[0];

		// Delete topics
		$query = array(
			'DELETE'	=> 'topics',
			'WHERE'		=> 'id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_prune_topics')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'voting',
			'WHERE'		=> 'topic_id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_prune_voting')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'answers',
			'WHERE'		=> 'topic_id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_prune_answers')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Delete posts
		$query = array(
			'DELETE'	=> 'posts',
			'WHERE'		=> 'topic_id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_prune_posts')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Delete subscriptions
		$query = array(
			'DELETE'	=> 'subscriptions',
			'WHERE'		=> 'topic_id IN('.$topic_ids.')'
		);

		($hook = get_hook('ca_fn_prune_qr_prune_subscriptions')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// We removed a bunch of posts, so now we have to update the search index
		if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/search_idx.php';

		strip_search_index($post_ids);
	}
}

($hook = get_hook('ca_new_function')) ? eval($hook) : null;
