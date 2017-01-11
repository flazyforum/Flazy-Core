<?php
/**
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */

if (!defined('FORUM'))
	die;

/**
 * Удаляет аватары.
 * @param int ID зарегистрированого участника.
 */
function delete_avatar($user_id)
{
	$filetypes = array('jpg', 'gif', 'png');

	$return = ($hook = get_hook('fn_delete_avatar_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Удалить аватар участника
	foreach ($filetypes as $cur_type)
		@unlink(FORUM_ROOT.FORUM_AVATAR_DIR.'/'.$user_id.'.'.$cur_type);
}

define('FORUM_FUNCTIONS_DELETE_AVATAR', 1);
