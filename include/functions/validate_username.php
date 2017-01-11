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
 * Проверяет занято ли имя пользователя.
 * @param string Имя пользователя.
 * @param int ID зарегистрированого участника которого нужно исключить из проверки, по умолчанию null.
 * @return mixed FALSE - имя свободно, если занято вернёт username.
 */
function check_username_dupe($username, $exclude_id = null)
{
	global $forum_db;

	$return = ($hook = get_hook('fn_check_username_dupe_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$query = array(
		'SELECT'	=> 'u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> '(UPPER(username)=UPPER(\''.$forum_db->escape($username).'\') OR UPPER(username)=UPPER(\''.$forum_db->escape(preg_replace('/[^\w]/u', '', $username)).'\')) AND id>1'
	);

	if ($exclude_id)
		$query['WHERE'] .= ' AND id!='.$exclude_id;

	($hook = get_hook('fn_check_username_dupe_qr_check_username_dupe')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	return $forum_db->num_rows($result) ? $forum_db->result($result) : false;
}


/**
 * Проверяет соотвеетствует ли имя пользователя критериям необходимым для включения в Базу Данных.
 * @param string Имя пользователя.
 * @param int ID зарегистрированого участника которого нужно исключить из проверки, по умолчанию null.
 * @return array Массив с причинами ошибок.
 */
function validate_username($username, $exclude_id = null)
{
	global $lang_common, $lang_register, $lang_profile, $forum_config, $forum_bans;

	$errors = array();

	$return = ($hook = get_hook('fn_validate_username_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
	$username = preg_replace('#\s+#s', ' ', $username);

	// Validate username
	if (utf8_strlen($username) < 2)
		$errors[] = $lang_profile['Username too short'];
	else if (utf8_strlen($username) > 25)
		$errors[] = $lang_profile['Username too long'];
	if (strpos($username, '@') !== false )
		$errors[] = $lang_profile['Username reserved chars'];
	else if (strtolower($username) == 'guest' || utf8_strtolower($username) == utf8_strtolower($lang_common['Guest']))
		$errors[] = $lang_profile['Username guest'];
	else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username) || preg_match('/((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))/', $username))
		$errors[] = $lang_profile['Username IP'];
	else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		$errors[] = $lang_profile['Username reserved chars'];
	else if (preg_match('/(?:\[\/?(?:b|u|i|h|s|colou?r|quote|code|img|url|wiki|spoiler|size|font|left|right|center|email|list|hr|video)\]|\[(?:code|quote|list)=)/i', $username))
		$errors[] = $lang_profile['Username BBCode'];

	// Check username for any censored words
	if ($forum_config['o_censoring'] && censor_words($username) != $username)
		$errors[] = $lang_profile['Username censor'];

	// Check for username dupe
	$dupe = check_username_dupe($username, $exclude_id);
	if ($dupe !== false)
		$errors[] = sprintf($lang_profile['Username dupe'], forum_htmlencode($dupe));

	foreach ($forum_bans as $cur_ban)
	{
		if ($cur_ban['username'] != '' && utf8_strtolower($username) == utf8_strtolower($cur_ban['username']))
		{
			$errors[] = $lang_profile['Banned username'];
			break;
		}
	}

	($hook = get_hook('fn_validate_username_end')) ? eval($hook) : null;

	return $errors;
}

define('FORUM_FUNCTIONS_VALIDATE_USERNAME', 1);
