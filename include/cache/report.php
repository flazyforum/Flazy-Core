<?php
/**
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Создаёт кеш количества жалоб.
 */
function generate_report_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_fl_generate_reports_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'COUNT(r.id)',
		'FROM'		=> 'reports AS r',
		'WHERE'		=> 'r.zapped IS NULL',
	);

	($hook = get_hook('ch_fn_fl_generate_reports_cache_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$report = false;
	if ($forum_db->result($result))
		$report = true;

	$load_info = '$forum_report = array('."\n".
		'\'report\'	=> \''.$report.'\','."\n".
		')';

	$fh = @fopen(FORUM_CACHE_DIR.'cache_report.php', 'wb');
	if (!$fh)
		error('Can not write the files in the \'cache\' directory. Please make sure that PHP has writen access to the folder.', __FILE__, __LINE__);

	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_REPORT_LOADED\', 1);'."\n\n".$load_info.';'."\n\n".'?>');
	fclose($fh);
	
}

define('FORUM_CACHE_REPORT_LOADED', 1);
