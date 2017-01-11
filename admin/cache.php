<?php
/**
 * Скрипт для пересоздания кеш файлов и синхронизацией с базой данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if( !defined ( 'FORUM_ROOT' ) )
	define( 'FORUM_ROOT', '../' );
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/functions/admin.php';

($hook = get_hook('acs_start')) ? eval($hook) : null;

if ($forum_user['g_id'] != FORUM_ADMIN)
	message($lang_common['No permission']);

// Load the language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_cache.php';


$hook = get_hook('acs_pre_cache') ? eval($hook) : null;

function redirect_cache($cache)
{
	global $lang_admin_cache;

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Update '.$cache.' cache']);
}

if (isset($_POST['form_sent']))
{
	$cache = $_POST['cache'];
	$sync = $_POST['sync'];

	if ($cache != '')
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		if ($cache == 'all')
		{
			generate_bans_cache();
			generate_censors_cache();
			generate_config_cache();
			generate_hooks_cache();
			generate_ranks_cache();
			generate_updates_cache();

			if (!defined('FORUM_CACHE_QUICKJUMP_LOADED'))
				require FORUM_ROOT.'include/cache/quickjump.php';

			generate_quickjump_cache();

			if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
				require FORUM_ROOT.'include/cache/stat_user.php';

			generate_stat_user_cache();
		}
		else if ($cache == 'bans')
			generate_bans_cache();
		else if ($cache == 'censor')
			generate_censors_cache();
		else if ($cache == 'config')
			generate_config_cache();
		else if ($cache == 'hooks')
			generate_hooks_cache();
		else if ($cache == 'ranks')
			generate_ranks_cache();
		else if ($cache == 'quickjump')
		{
			if (!defined('FORUM_CACHE_QUICKJUMP_LOADED'))
				require FORUM_ROOT.'include/cache/quickjump.php';

			generate_quickjump_cache();
		}
		else if ($cache == 'stats')
		{
			if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
				require FORUM_ROOT.'include/cache/stat_user.php';

			generate_stat_user_cache();
		}

		$hook = get_hook('acs_cache') ? eval($hook) : null;

		redirect(forum_link('admin/cache.php'), $lang_admin_cache['Update cache']);
	}
	else if ($sync != '')
	{
		if ($sync == 'forum_post')
		{
			if ($db_type == 'pgsql')
			{
				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'forum_posts AS SELECT t.forum_id, count(t.id) AS posts FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id GROUP BY t.forum_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums',
					'SET'		=> 'num_posts=posts FROM '.$forum_db->prefix.'forum_posts',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_pg_update_forum_post_posts')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'forum_topics AS SELECT forum_id, count(id) AS topics FROM '.$forum_db->prefix.'topics GROUP BY forum_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums',
					'SET'		=> 'num_topics=topics FROM '.$forum_db->prefix.'forum_topics',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_pg_update_forum_post_topics')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_posts SELECT t.forum_id, count(*) AS posts FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id GROUP BY t.forum_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums, '.$forum_db->prefix.'forum_posts',
					'SET'		=> 'num_posts=posts',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_update_forum_post_posts')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_topics SELECT forum_id, count(*) AS topics FROM '.$forum_db->prefix.'topics GROUP BY forum_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums, '.$forum_db->prefix.'forum_topics',
					'SET'		=> 'num_topics=topics',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_update_forum_post_topics')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else if ($sync == 'forum_last_post')
		{
			if ($db_type == 'pgsql')
			{
				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'forum_last AS SELECT p.posted AS post, p.id AS post_id, p.poster AS poster, t.forum_id FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums',
					'SET'		=> 'last_post_id=post_id, last_post=post, last_poster=poster FROM '.$forum_db->prefix.'forum_last',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_pg_update_forum_last_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_last SELECT p.posted AS post, p.id AS post_id, p.poster AS poster, t.forum_id FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums, '.$forum_db->prefix.'forum_last',
					'SET'		=> 'last_post_id=post_id, last_post=post, last_poster=poster',
					'WHERE'		=> 'id=forum_id'
				);

				($hook = get_hook('acs_qr_update_forum_last_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else if ($sync == 'topic_replies')
		{
			if ($db_type == 'pgsql')
			{
				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'topic_posts AS SELECT topic_id, count(*)-1 AS replies FROM '.$forum_db->prefix.'posts GROUP BY topic_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics',
					'SET'		=> 'num_replies=replies FROM '.$forum_db->prefix.'topic_posts',
					'WHERE'		=> 'id=topic_id'
				);

				($hook = get_hook('acs_qr_pg_update_topic_replies')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_posts SELECT topic_id, count(*)-1 AS replies FROM '.$forum_db->prefix.'posts GROUP BY topic_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics, '.$forum_db->prefix.'topic_posts',
					'SET'		=> 'num_replies=replies',
					'WHERE'		=> 'id=topic_id'
				);

				($hook = get_hook('acs_qr_update_topic_replies')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else if ($sync == 'topic_last_post')
		{
			if ($db_type == 'pgsql')
			{
				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'topic_last AS SELECT p.posted AS tmp_last_post, p.id AS tmp_last_post_id, p.poster AS tmp_last_poster, u0.id AS tmp_poster_id, u1.id AS tmp_last_poster_id, p.topic_id FROM '.$forum_db->prefix.'posts AS p  LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$forum_db->prefix.'users AS u0 ON t.poster=u0.username LEFT JOIN '.$forum_db->prefix.'users AS u1 ON t.last_poster=u1.username ORDER BY p.posted DESC') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics',
					'SET'		=> 'last_post_id=tmp_last_post_id, last_post=tmp_last_post, last_poster=tmp_last_poster, poster_id=tmp_poster_id, last_poster_id=tmp_last_poster_id FROM '.$forum_db->prefix.'topic_last',
					'WHERE'		=> 'id=topic_id'
				);

				($hook = get_hook('acs_qr_pg_update_topic_last_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$forum_db->query('CREATE TEMPORARY TABLE TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_last SELECT p.posted AS tmp_last_post, p.id AS tmp_last_post_id, p.poster AS tmp_last_poster, u0.id AS tmp_poster_id, u1.id AS tmp_last_poster_id, p.topic_id FROM '.$forum_db->prefix.'posts AS p  LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$forum_db->prefix.'users AS u0 ON t.poster=u0.username LEFT JOIN '.$forum_db->prefix.'users AS u1 ON t.last_poster=u1.username ORDER BY p.posted DESC') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics, '.$forum_db->prefix.'topic_lastb',
					'SET'		=> 'last_post_id=tmp_last_post_id, last_post=tmp_last_post, last_poster=tmp_last_poster, poster_id=tmp_poster_id, last_poster_id=tmp_last_poster_id',
					'WHERE'		=> 'id=topic_id'
				);

				($hook = get_hook('acs_qr_update_topic_last_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else if ($sync == 'user_post')
		{
			if ($db_type == 'pgsql')
			{
				$forum_db->query('CREATE TEMPORARY TABLE '.$forum_db->prefix.'user_posts AS SELECT p.poster_id, count(p.id) AS posts FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$forum_db->prefix.'forums AS f ON t.forum_id=f.id WHERE f.counter=1 GROUP BY p.poster_id') or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'num_posts=posts FROM '.$forum_db->prefix.'user_posts',
					'WHERE'		=> 'id=poster_id'
				);

				($hook = get_hook('acs_qr_pg_update_user_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'user_posts SELECT p.poster_id, count(p.id) AS posts FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$forum_db->prefix.'forums AS f ON t.forum_id=f.id WHERE f.counter=1 GROUP BY p.poster_id');

				$query = array(
					'UPDATE'	=> 'users, '.$forum_db->prefix.'user_posts',
					'SET'		=> 'num_posts=posts',
					'WHERE'		=> 'id=poster_id'
				);

				($hook = get_hook('acs_qr_update_user_post')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		$hook = get_hook('acs_sync') ? eval($hook) : null;

		redirect(forum_link('admin/cache.php'), $lang_admin_cache['Synchronized']);
	}
}

$hook = get_hook('acs_qr_db_data') ? eval($hook) : null;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),	
	array($lang_admin_common['Management'], forum_link('admin/reports.php')),
	array($lang_admin_common['Cache'], forum_link('admin/cache.php'))
);

$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-cache');
require FORUM_ROOT.'header.php';
// START SUBST - <forum_main>
ob_start();

($hook = get_hook('acs_cache_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_admin_cache['About cache'] ?></span></h2>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('/admin/cache.php') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('/admin/cache.php')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_cache['About cache'] ?></strong></legend>
<?php ($hook = get_hook('acs_pre_select_cache')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate cache'] ?></span><small><?php echo $lang_admin_cache['Select cache'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="cache">
							<option value="" selected="selected"><?php echo $lang_admin_cache['Select'] ?></option>
							<option value="all"><?php echo $lang_admin_cache['All сache'] ?></option>
							<option value="bans"><?php echo $lang_admin_cache['Bans cache'] ?></option>
							<option value="censor"><?php echo $lang_admin_cache['Censor cache'] ?></option>
							<option value="сonfig"><?php echo $lang_admin_cache['Config cache'] ?></option>
							<option value="hooks"><?php echo $lang_admin_cache['Hooks cache'] ?></option>
							<option value="ranks"><?php echo $lang_admin_cache['Ranks cache'] ?></option>
							<option value="updates"><?php echo $lang_admin_cache['Updates cache'] ?></option>
							<option value="quickjum"><?php echo $lang_admin_cache['Quickjump cache'] ?></option>
							<option value="stats"><?php echo $lang_admin_cache['Stats cache'] ?></option>
<?php ($hook = get_hook('acs_cache_end_select')) ? eval($hook) : null; ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('acs_pre_select_sync')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Syns'] ?></span><small><?php echo $lang_admin_cache['Select syns'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="sync">
							<option value="" selected="selected"><?php echo $lang_admin_cache['Select'] ?></option>
							<option value="forum_post"><?php echo $lang_admin_cache['Forum post sync'] ?></option>
							<option value="forum_last_post"><?php echo $lang_admin_cache['Forum last post'] ?></option>
							<option value="topic_replies"><?php echo $lang_admin_cache['Topic replies sync'] ?></option>
							<option value="topic_last_post"><?php echo $lang_admin_cache['Topic last post'] ?></option>
							<option value="user_post"><?php echo $lang_admin_cache['User post sync'] ?></option>
<?php ($hook = get_hook('acs_sync_end_select')) ? eval($hook) : null; ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('acs_sync_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('acs_sync_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_cache['Syns bottom'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'footer.php';
