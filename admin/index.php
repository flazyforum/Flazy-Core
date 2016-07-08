<?php
/**
 * Главная страница панели администратора.
 *
 * Даёт обзор некоторых статистических данных для администраторов и модераторов.
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

($hook = get_hook('ain_start')) ? eval($hook) : null;

if (!$forum_user['is_admmod'])
	message($lang_common['No permission']);

// Load the admin.php language files
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_index.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

// Show phpinfo() output
if ($action == 'phpinfo' && $forum_user['g_id'] == FORUM_ADMIN)
{
	($hook = get_hook('ain_phpinfo_selected')) ? eval($hook) : null;

	// Is phpinfo() a disabled function?
	if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false)
		message($lang_admin_index['phpinfo disabled']);

	phpinfo();
	die;
}

else if ($action == 'update')
{
	($hook = get_hook('ain_action_update')) ? eval($hook) : null;

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_updates_cache();

	redirect(forum_link('admin/index.php'), $lang_admin_common['Redirect']);
}
else if ($action == 'remove_install_file')
{
	$deleted = @unlink(FORUM_ROOT.'admin/install.php');
	if ($deleted)
		{
		?>
<div class="alert alert-success alert-dismissable">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h4><i class="icon fa fa-check"></i> <?php echo $lang_admin_index['Alerts success'] ?></h4>
      <?php echo $lang_admin_index['Deleted install.php'] ?>
</div>
		<?php	
		}
	else
		{
		?>
<div class="alert alert-danger alert-dismissable">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
	<h4><i class="icon fa fa-ban"></i> <?php echo $lang_admin_index['Alerts'] ?></h4>
	<?php echo $lang_admin_index['Delete install.php failed'] ?>
</div>
		<?php
		}
}
$install_file_exists = is_file(FORUM_ROOT.'admin/install.php');

// Generate check for updates text block
if ($forum_user['g_id'] == FORUM_ADMIN)
{
	if ($forum_config['o_maintenance'])
		$forum_page['alert']['maintenance'] = '<p id="maint-alert" class="warn">'.sprintf($lang_admin_index['Maintenance alert'], forum_link('admin/settings.php?section=maintenance')).'</p>';
 
	if ($forum_config['o_check_for_updates'])
	{
		if ($forum_updates['fail'])
			$forum_page['alert']['update_fail'] = '<p><strong>'.$lang_admin_index['Updates'].'</strong> '.$lang_admin_index['Updates failed'].'</p>';
		else if (isset($forum_updates['version']) && isset($forum_updates['hotfix']))
			$forum_page['alert']['update_version_hotfix'] = '<p><strong>'.$lang_admin_index['Updates'].'</strong> '.sprintf($lang_admin_index['Updates version n hf'], $forum_updates['version'], forum_link('admin/extensions.php?section=hotfixes')).'</p>';
		else if (isset($forum_updates['version']))
			$forum_page['alert']['update_version'] = '<p><strong>'.$lang_admin_index['Updates'].'</strong> '.sprintf($lang_admin_index['Updates version'], $forum_updates['version']).'</p>';
		else if (isset($forum_updates['hotfix']))
			$forum_page['alert']['update_hotfix'] = '<p><strong>'.$lang_admin_index['Updates'].'</strong> '.sprintf($lang_admin_index['Updates hf'], forum_link('admin/extensions.php?section=hotfixes')).'</p>';

		if (strpos($forum_config['o_cur_version'], 'dev') !== false)
			$forum_page['alert']['dev_version'] = '<p>'.$lang_admin_index['Dev version'].'</p>';

		// Warn the admin that their version of the database is newer than the version supported by the code
		if ($forum_config['o_database_revision'] > FORUM_DB_REVISION)
			$forum_page['alert']['newer_database'] = '<p><strong>'.$lang_admin_index['Database mismatch'].'</strong> '.$lang_admin_index['Database mismatch alert'].'</p>';

		// Warn the admin that the engines used in the database don't correspond with the chosen DB layer
		if (($db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb') && $forum_config['o_database_engine'] != 'InnoDB')
			$forum_page['alert']['update_fail'] = '<p><strong>'.$lang_admin_index['Storage mismatch'].'</strong> '.sprintf($lang_admin_index['Storage mismatch alert'], 'MyISAM', 'InnoDB', forum_link('misc.php?admin_action=change_engine')).'</p>';
		else if (($db_type == 'mysql' || $db_type == 'mysqli') && $forum_config['o_database_engine'] != 'MyISAM')
			$forum_page['alert']['update_fail'] = '<p><strong>'.$lang_admin_index['Database mismatch'].'</strong> '.sprintf($lang_admin_index['Storage mismatch alert'], 'InnoDB', 'MyISAM', forum_link('misc.php?admin_action=change_engine')).'</p>';

		$updates = $lang_admin_index['Check for updates enabled'].' <a href="'.forum_link('admin/index.php?action=update').'">'.$lang_admin_index['Check for updates'].'</a>';
	}
	else
	{
		// Get a list of installed hotfix extensions
		$query = array(
			'SELECT'	=> 'e.id',
			'FROM'		=> 'extensions AS e',
			'WHERE'		=> 'e.id LIKE \'hotfix_%\''
		);

		($hook = get_hook('ain_update_check_qr_get_hotfixes')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$num_hotfixes = $forum_db->num_rows($result);

		$hotfixes = array();
		for ($i = 0; $i < $num_hotfixes; ++$i)
			$hotfixes[] = urlencode($forum_db->result($result, $i));

		$updates = '<a href="'.forum_link('admin/index.php?action=update').'">'.$lang_admin_index['Check for updates'].'</a>';
	}
}


// Get the server load averages (if possible)
if (function_exists('sys_getloadavg') && is_array(sys_getloadavg()))
{
	$load_averages = sys_getloadavg();
	array_walk($load_averages, create_function('&$v', '$v = round($v, 3);'));
	$server_load = $load_averages[0].' '.$load_averages[1].' '.$load_averages[2];

}
else if (@file_exists('/proc/loadavg') && is_readable('/proc/loadavg'))
{
	// We use @ just in case
	$fh = @fopen('/proc/loadavg', 'r');
	$load_averages = @fread($fh, 64);
	@fclose($fh);

	$load_averages = explode(' ', $load_averages);
	$server_load = isset($load_averages[2]) ? $load_averages[0].' '.$load_averages[1].' '.$load_averages[2] : 'Not available';
}
else if (!in_array(PHP_OS, array('WINNT', 'WIN32')) && preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/i', @exec('uptime'), $load_averages))
	$server_load = $load_averages[1].' '.$load_averages[2].' '.$load_averages[3];
else
	$server_load = $lang_admin_index['Not available'];

// Get number of current visitors
$query = array(
	'SELECT'	=> 'COUNT(o.user_id)',
	'FROM'		=> 'online AS o',
	'WHERE'		=> 'o.idle=0'
);

($hook = get_hook('ain_qr_get_users_online')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$num_online = $forum_db->result($result);

// Collect some additional info about MySQL
if ($db_type == 'mysql' || $db_type == 'mysqli' || $db_type == 'mysql_innodb' || $db_type == 'mysqli_innodb')
{
	function file_size($size)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
		for ($i = 0; $size > 1024; $i++)
			$size /= 1024;
		return round($size, 2).' '.$units[$i];
	}

	// Calculate total db size/row count
	$result = $forum_db->query('SHOW TABLE STATUS FROM `'.$db_name.'` LIKE \''.$db_prefix.'%\'') or error(__FILE__, __LINE__);

	$total_records = $total_size = 0;
	while ($status = $forum_db->fetch_assoc($result))
	{
		$total_records += $status['Rows'];
		$total_size += $status['Data_length'] + $status['Index_length'];
	}

	$total_size = $total_size / 1024;

	$total_size = file_size($total_size);
}


// Check for the existance of various PHP opcode caches/optimizers
if (function_exists('mmcache'))
	$php_accelerator = '<a href="http://turck-mmcache.sourceforge.net/">Turck MMCache</a>';
else if (isset($_PHPA))
	$php_accelerator = '<a href="http://www.php-accelerator.co.uk/">IonCube PHP Accelerator</a>';
else if (ini_get('apc.enabled'))
	$php_accelerator ='<a href="http://www.php.net/apc/">Alternative PHP Cache (APC)</a>';
else if (ini_get('zend_optimizer.optimization_level'))
	$php_accelerator = '<a href="http://www.zend.com/products/zend_optimizer/">Zend Optimizer</a>';
else if (ini_get('eaccelerator.enable'))
	$php_accelerator = '<a href="http://eaccelerator.net/">eAccelerator</a>';
else if (ini_get('xcache.cacher'))
	$php_accelerator = '<a href="http://xcache.lighttpd.net/">XCache</a>';
else
	$php_accelerator = $lang_admin_index['Not applicable'];

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/index.php'))
);
if ($forum_user['g_id'] == FORUM_ADMIN)
	$forum_page['crumbs'][] = array($lang_admin_common['Start'], forum_link('admin/index.php'));
$forum_page['crumbs'][] = array($lang_admin_common['Information'], forum_link('admin/index.php'));

($hook = get_hook('ain_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'start');
define('FORUM_PAGE', 'admin-information');
require FORUM_ROOT.'header.php';

$forum_page['item_count'] = 0;

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('ain_main_output_start')) ? eval($hook) : null;

?>
<?php if ($install_file_exists) : ?>
<div class="alert alert-danger alert-dismissable">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h4><i class="icon fa fa-ban"></i> <?php echo $lang_admin_index['Alerts'] ?></h4>
      <?php printf($lang_admin_index['Install file exists'], '<a href="index.php?action=remove_install_file">'.$lang_admin_index['Delete install file'].'</a>') ?>
</div>
<?php endif; ?>
<?php if (!empty($forum_page['alert'])): ?>
			<div class="alert alert-warning ">
				<h4><i class="icon fa fa-ban"></i><?php echo $lang_admin_index['Alerts'] ?></h4>
				<?php echo implode(' ', $forum_page['alert'])."\n" ?>
			</div>
<?php endif; ?>
		<div class="ct-group">
<?php ($hook = get_hook('ain_pre_version')) ? eval($hook) : null; ?>
          <div class="box ic<?php echo ++$forum_page['item_count'] ?>">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo $lang_admin_index['Flazy version'] ?></h3>
              <div class="box-tools pull-right">
                <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="box-body">
              Flazy -<?php echo $forum_config['o_cur_version'] ?>
              <?php if (isset($updates)): ?>
			  <?php echo $updates ?>
<?php endif; ?>
            </div><!-- /.box-body -->
          </div><!-- /.box -->
<?php ($hook = get_hook('ain_pre_server_load')) ? eval($hook) : null; ?>
          <div class="box ic<?php echo ++$forum_page['item_count'] ?>">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo $lang_admin_index['Server load'] ?></h3>
              <div class="box-tools pull-right">
                <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="box-body">
             <?php echo $server_load ?> (<?php echo $num_online.' '.$lang_admin_index['users online']?>)
            </div><!-- /.box-body -->
          </div><!-- /.box -->
<?php ($hook = get_hook('ain_pre_environment')) ? eval($hook) : null; if ($forum_user['g_id'] == FORUM_ADMIN): ?>
          <div class="box ic<?php echo ++$forum_page['item_count'] ?>">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo $lang_admin_index['Environment'] ?></h3>
              <div class="box-tools pull-right">
                <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="box-body">
             <h5><?php echo $lang_admin_index['Operating system'] ?>: <small><?php echo PHP_OS ?></small></h5>
			 <h5>PHP: <small><?php echo PHP_VERSION ?></small> — <a href=""  class="btn btn-sm btn-primary" data-toggle="modal" data-target="#phpinfo"><?php echo $lang_admin_index['Show info'] ?></a></h5>
			 <h5><?php echo $lang_admin_index['Accelerator'] ?>: <small><?php echo $php_accelerator ?></small></h5>
            </div><!-- /.box-body -->
          </div><!-- /.box -->

<?php ($hook = get_hook('ain_pre_database')) ? eval($hook) : null; ?>
          <div class="box ic<?php echo ++$forum_page['item_count'] ?>">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo $lang_admin_index['Database'] ?></h3>
              <div class="box-tools pull-right">
                <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="box-body">

             	<h5><?php echo implode(' ', $forum_db->get_version()) ?></h5>
<?php if (isset($total_records) && isset($total_size)): ?>
						<h5><?php echo $lang_admin_index['Rows'] ?>: <small><?php echo forum_number_format($total_records) ?></small></h5>
						<h5><?php echo $lang_admin_index['Size'] ?>: <small><?php echo $total_size ?></small></h5>
<?php endif; ?>
            </div><!-- /.box-body -->
          </div><!-- /.box -->
<?php endif; ($hook = get_hook('ain_items_end')) ? eval($hook) : null; ?>
		</div>

	<div class="example-modal">
            <div class="modal" id="phpinfo">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  </div>
                  <div class="modal-body">
                   <?php
ob_start();
phpinfo();
$phpinfo = ob_get_clean();

# Body-Content rausholen
$phpinfo = preg_replace('#^.*<body>(.*)</body>.*$#s', '$1', $phpinfo);
# XHTML-Fehler korrigieren
$phpinfo = str_replace('module_Zend Optimizer', 'module_Zend_Optimizer', $phpinfo);
# <font> durch <span> ersetzen
$phpinfo = str_replace('<font', '<span', $phpinfo);
$phpinfo = str_replace('</font>', '</span>', $phpinfo);
#Table
$phpinfo = str_replace( 'border="0" cellpadding="3"', 'class="table" style="table-layout: fixed;word-wrap: break-word;"', $phpinfo );
$phpinfo = str_replace('<tr class="h"><th>', '<thead><tr><th>', $phpinfo);
$phpinfo = str_replace('</th></tr>', '</th></tr></thead><tbody>', $phpinfo);
$phpinfo = str_replace('</table>', '</tbody></table>', $phpinfo);
# Schlüsselwörter grün oder rot einfärben
$phpinfo = preg_replace('#>(on|enabled|active)#i', '><span class="text-success">$1</span>', $phpinfo);
$phpinfo = preg_replace('#>(off|disabled)#i', '><span class="text-error">$1</span>', $phpinfo);

echo '<div id="phpinfo">';
echo $phpinfo;
echo '</div>';
?>
                  </div>
                </div><!-- /.modal-content -->
              </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
          </div>
<?php

($hook = get_hook('ain_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'admin/footer_adm.php';
