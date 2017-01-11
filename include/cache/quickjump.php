<?php
/**
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Кеш быстрого перехода.
 * @param int ID группы пользователей, если переменная определена, создаем кэш только для этой группы.
 */
function generate_quickjump_cache($group_id = false)
{
	global $forum_db, $lang_common, $forum_url, $forum_config, $forum_user, $base_url;

	$return = ($hook = get_hook('ch_fn_generate_quickjump_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// If a group_id was supplied, we generate the quickjump cache for that group only
	if ($group_id !== false)
		$groups[0] = $group_id;
	else
	{
		// A group_id was not supplied, so we generate the quickjump cache for all groups
		$query = array(
			'SELECT'	=> 'g.g_id',
			'FROM'		=> 'groups AS g'
		);

		($hook = get_hook('ch_fn_generate_quickjump_cache_qr_get_groups')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$num_groups = $forum_db->num_rows($result);

		for ($i = 0; $i < $num_groups; ++$i)
			$groups[] = $forum_db->result($result, $i);
	}

	// Loop through the groups in $groups and output the cache for each of them
	foreach ($groups as $group_id)
	{
		// Output quickjump as PHP code
		$fh = @fopen(FORUM_CACHE_DIR.'cache_quickjump_'.$group_id.'.php', 'wb');
		if (!$fh)
			error('Невозможно записать файл быстрого перехода в кеш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);

		$output = '<?php'."\n\n".'if (!defined(\'FORUM\')) die;'."\n".'define(\'FORUM_QJ_LOADED\', 1);'."\n".'$forum_id = isset($forum_id) ? $forum_id : 0;'."\n\n".'?>';
		$output .= '<form id="qjump" method="get" accept-charset="utf-8" action="'.$base_url.'/viewforum.php">'."\n\t".'<div class="frm-fld frm-select">'."\n\t\t".'<label for="qjump-select"><span><?php echo $lang_common[\'Jump to\'] ?>'.'</span></label><br />'."\n\t\t".'<span class="frm-input"><select id="qjump-select" name="id">'."\n";

		// Get the list of categories and forums from the DB
		$query = array(
			'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url',
			'FROM'		=> 'categories AS c',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'c.id=f.cat_id'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$group_id.')'
				)
			),
			'WHERE'		=> 'fp.read_forum IS NULL OR fp.read_forum=1',
			'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
		);

		($hook = get_hook('ch_fn_generate_quickjump_cache_qr_get_cats_and_forums')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$cur_category = 0;
		$forum_count = 0;
		$sef_friendly_names = array();
		while ($cur_forum = $forum_db->fetch_assoc($result))
		{
			($hook = get_hook('ch_fn_generate_quickjump_cache_forum_loop_start')) ? eval($hook) : null;

			if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
			{
				if ($cur_category)
					$output .= "\t\t\t".'</optgroup>'."\n";

				$output .= "\t\t\t".'<optgroup label="'.forum_htmlencode($cur_forum['cat_name']).'">'."\n";
				$cur_category = $cur_forum['cid'];
			}

			$sef_friendly_names[$cur_forum['fid']] = sef_friendly($cur_forum['forum_name']);
			$redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';
			$output .= "\t\t\t\t".'<option value="'.$cur_forum['fid'].'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.forum_htmlencode($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";
			$forum_count++;
		}

		$output .= "\t\t\t".'</optgroup>'."\n\t\t".'</select>'."\n\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" onclick="return Forum.doQuickjumpRedirect(forum_quickjump_url, sef_friendly_url_array);" /></span>'."\n\t".'</div>'."\n".'</form>'."\n";
		$output .= '<script type="text/javascript">'."\n\t".'var forum_quickjump_url = "'.forum_link($forum_url['forum']).'";'."\n\t".'var sef_friendly_url_array = new Array('.$forum_db->num_rows($result).');';

		foreach ($sef_friendly_names as $forum_id => $forum_name)
			$output .= "\n\t".'sef_friendly_url_array['.$forum_id.'] = "'.forum_htmlencode($forum_name).'";';

		$output .= "\n".'</script>'."\n";

		if ($forum_count < 2)
			$output = '<?php'."\n\n".'if (!defined(\'FORUM\')) die;'."\n".'define(\'FORUM_QJ_LOADED\', 1);';

		fwrite($fh, $output);

		fclose($fh);
	}
}

define('FORUM_CACHE_QUICKJUMP_LOADED', 1);
