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
 * Добавление нового участника.
 * @param array Массив, содержащий следующие ключи - и соответствующие значения:
 *  - Обязательные:
 *    -# username: Имя участника, должно быть проверено validate_username().
 *    -# salt: Соль - случайно сгенерированный ключ из не более 12 символом.
 *    -# password_hash: Хеш пароля, сгенерированный из самого пароля и соли.
 *    -# email: Электроный адрес, должен быть проверен is_valid_email().
 *  - Второстепенные:
 *    -# group_id: Группа к которой будет относиться участник. По умолчанию 3
 *       или 0 (константа FORUM_UNVERIFIED, если требуется проверка emal'а на подлинность.
 *    -# email_setting: Настройка отображения email:
 *       0 - Показывать e-mail адрес другим участникам.
 *       1 - Скрывать e-mail адрес, но разрешить отправлять e-mail сообщения через форум.
 *       2 - Скрывать e-mail адрес и запретить отправлять e-mail сообщения через форум.
 *    -# timezone: Часовой пояс. Можно использовать float значения от -12 до 14.
 *    -# dst: Летнее время: 0 - не переводить время на летнее время и обратно, 1 - переводить.
 *    -# style: Стиль оформления, который располагается в папке style.
 *    -# registered: Время регистрации участника, используется get_remote_address().
 *    -# registration_ip: IP-адрес участника, используется get_remote_address().
 *    -# user_agent: User-agent участника, используется get_user_agent().
 *    -# activate_key: Ключ активации, если требуется проверка emal'а на подлинность.
 *    -# require_verification: 1 - отправляется письмо с ссылкой активации (activate_key).
 *    -# notify_admins: 1 - отправить письмо администраторам о зарегистрированом участнике.
 *    -# banned_email: Отправить письмо о использовании заблокированого email, результат проверки is_banned_email().
 *    -# dupe_email: Отправить письмо о использовании уже существующего в базе email, результат проверки is_dupe_email().
 * @param int ID нового участника.
 * @see validate_username()
 * @see random_key()
 * @see forum_hash()
 * @see is_valid_email()
 * @see get_remote_address()
 * @see get_user_agent()
 * @see is_banned_email()
 * @see is_dupe_email()
 */
function add_user($user_info, &$new_uid)
{
	global $forum_db, $base_url, $lang_common, $forum_config, $forum_url;

	$return = ($hook = get_hook('fn_add_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$user_info_default = array(
		'group_id' 				=>	(!$forum_config['o_regs_verify']) ? $forum_config['o_default_user_group'] : FORUM_UNVERIFIED,
		'email_setting'			=>	$forum_config['o_default_email_setting'],
		'timezone'				=>	$forum_config['o_default_timezone'],
		'dst'					=>	$forum_config['o_default_dst'],
		'language'				=>	$forum_config['o_default_lang'],
		'style'					=>	$forum_config['o_default_style'],
		'registered' 			=>	time(),
		'registration_ip'		=>	get_remote_address(),
		'user_agent'			=>	get_user_agent(),
		'activate_key'			=>	($forum_config['o_regs_verify']) ? '\''.random_key(8, true).'\'' : 'NULL',
		'require_verification'	=>	($forum_config['o_regs_verify'] == '1'),
		'notify_admins'			=>	($forum_config['o_regs_report'] == '1'),
		'banned_email'			=>	'',
		'dupe_email'			=>	''
	);

	($hook = get_hook('fn_add_user_pre_user_info_default')) ? eval($hook) : null;

	foreach ($user_info_default as $key => $input)
		$user_info[$key] = (empty($user_info[$key])) ? $input : $user_info[$key];

	// Add the user
	$query = array(
		'INSERT'	=> 'username, group_id, password, email, email_setting, timezone, dst, language, style, registered, registration_ip, last_visit, salt, activate_key, user_agent',
		'INTO'		=> 'users',
		'VALUES'	=> '\''.$forum_db->escape($user_info['username']).'\', '.$user_info['group_id'].', \''.$forum_db->escape($user_info['password_hash']).'\', \''.$forum_db->escape($user_info['email']).'\', '.$user_info['email_setting'].', '.floatval($user_info['timezone']).', '.$user_info['dst'].', \''.$forum_db->escape($user_info['language']).'\', \''.$forum_db->escape($user_info['style']).'\', '.$user_info['registered'].', \''.$forum_db->escape($user_info['registration_ip']).'\', '.$user_info['registered'].', \''.$forum_db->escape($user_info['salt']).'\', '.$user_info['activate_key'].',\''.$user_info['user_agent'].'\''
	);

	($hook = get_hook('fn_add_user_qr_insert_user')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_uid = $forum_db->insert_id();

	// Must the user verify the registration?
	if ($user_info['require_verification'])
	{
		// Load the "welcome" template
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$user_info['language'].'/mail_templates/welcome.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<board_title>', $forum_config['o_board_title'], $mail_subject);
		$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
		$mail_message = str_replace('<username>', $user_info['username'], $mail_message);
		$mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', forum_link($forum_url['change_password_key'], array($new_uid, substr($user_info['activate_key'], 1, -1)))), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_send_verification')) ? eval($hook) : null;

		forum_mail($user_info['email'], $mail_subject, $mail_message);
	}

	// Should we alert people on the admin mailing list that a new user has registered?
	if ($user_info['notify_admins'] && $forum_config['o_mailing_list'] != '')
	{
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/mail_templates/new_user.tpl'));

		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $lang_common['New user notification'], $mail_subject);
		$mail_message = str_replace('<user>', $user_info['username'], $mail_message);
		$mail_message = str_replace('<board>', $base_url, $mail_message);
		$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_send_new_user')) ? eval($hook) : null;

		forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
	}

	// If we previously found out that the e-mail was banned
	if ($user_info['banned_email'] && $forum_config['o_mailing_list'] != '')
	{
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/mail_templates/banned_email.tpl'));

		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $lang_common['Banned email notification'], $mail_subject);
		$mail_message = str_replace('<user>', $username, $mail_message);
		$mail_message = str_replace('<new_email>', $email1, $mail_message);
		$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_banned_email')) ? eval($hook) : null;

		forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
	}

	// If we previously found out that the e-mail was a dupe
	if (!empty($user_info['dupe_email']) && $forum_config['o_mailing_list'] != '')
	{
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_config['o_default_lang'].'/mail_templates/dupe_email.tpl'));
		$first_crlf = strpos($mail_tpl, "\n");

		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $lang_common['Duplicate email notification'], $mail_subject);
		$mail_message = str_replace('<user>', $username, $mail_message);
		$mail_message = str_replace('<first_user>', implode(', ', $user_info['dupe_email']), $mail_message);
		$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_dupe_email')) ? eval($hook) : null;

		forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
	}

	// Regenerate cache
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache($new_uid, $user_info['username']);

	($hook = get_hook('fn_add_user_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_ADD_USER', 1);
