<?php
/**
 * Forum settings management page
 *
 * Allows administrators to control many of the settings used in the site.
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

($hook = get_hook('aop_start')) ? eval($hook) : null;

if ($forum_user['g_id'] != FORUM_ADMIN)
	message($lang_common['No permission']);

// Load the admin.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_settings.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;

if (isset($_POST['form_sent']))
{
	$form = array_map('trim', $_POST['form']);

	($hook = get_hook('aop_form_submitted')) ? eval($hook) : null;

	// Validate input depending on section
	switch ($section)
	{
		case 'setup':
		{
			($hook = get_hook('aop_setup_validation')) ? eval($hook) : null;

			if ($form['board_title'] == '')
				message($lang_admin_settings['Error no board title']);

			// Clean default_lang, default_style, and sef
			$form['default_style'] = preg_replace('#[\.\\\/]#', '', $form['default_style']);
			$forced_style = preg_replace('#[\.\\\/]#', '', $form['forced_style']);
			$form['default_lang'] = preg_replace('#[\.\\\/]#', '', $form['default_lang']);
			$forced_lang = preg_replace('#[\.\\\/]#', '', $form['forced_lang']);
			$form['sef'] = preg_replace('#[\.\\\/]#', '', $form['sef']);

			// Make sure default_lang, default_style, and sef exist
			if (!file_exists(FORUM_ROOT.'style/'.$form['default_style'].'/'.$form['default_style'].'.php'))
				message($lang_common['Bad request']);
			if (!file_exists(FORUM_ROOT.'style/'.$forced_style.'/'.$forced_style.'.php') && $forced_style = '')
				message($lang_common['Bad request']);
			if (!file_exists(FORUM_ROOT.'lang/'.$form['default_lang'].'/common.php'))
				message($lang_common['Bad request']);
			if (!file_exists(FORUM_ROOT.'lang/'.$forced_lang.'/common.php') && $forced_lang = '')
				message($lang_common['Bad request']);
			if (!file_exists(FORUM_ROOT.'include/url/'.$form['sef'].'/forum_urls.php'))
				message($lang_common['Bad request']);

			if (!isset($form['user_style']) || $form['user_style'] != '1') $form['user_style'] = '0';

			if (!isset($form['default_dst']) || $form['default_dst'] != '1')
				$form['default_dst'] = '0';

			$form['timeout_visit'] = intval($form['timeout_visit']);
			$form['timeout_online'] = intval($form['timeout_online']);
			$form['redirect_delay'] = intval($form['redirect_delay']);

			if ($form['timeout_online'] >= $form['timeout_visit'])
				message($lang_admin_settings['Error timeout value']);

			if ($form['disp_topics_default'] != '' && intval($form['disp_topics_default']) < 3) $form['disp_topics_default'] = 3;
			if ($form['disp_topics_default'] != '' && intval($form['disp_topics_default']) > 75) $form['disp_topics_default'] = 75;
			if ($form['disp_posts_default'] != '' && intval($form['disp_posts_default']) < 3) $form['disp_posts_default'] = 3;
			if ($form['disp_posts_default'] != '' && intval($form['disp_posts_default']) > 75) $form['disp_posts_default'] = 75;

			if (!isset($form['report_enabled']) || $form['report_enabled'] != '1') $form['report_enabled'] = '0';

			if ($form['additional_navlinks'] != '')
				$form['additional_navlinks'] = forum_trim(forum_linebreaks($form['additional_navlinks']));

			if ($forced_style != '')
			{
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'style=\''.$forum_db->escape($forced_style).'\''
				);

				($hook = get_hook('aop_qr_update_forced_style')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}

			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'language=\''.$forum_db->escape($forced_lang).'\''
			);

			($hook = get_hook('aop_qr_update_forced_lang')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			break;
		}

		case 'features':
		{
			($hook = get_hook('aop_features_validation')) ? eval($hook) : null;

			if (!isset($form['search_all_forums']) || $form['search_all_forums'] != '1') $form['search_all_forums'] = '0';
			if (!isset($form['ranks']) || $form['ranks'] != '1') $form['ranks'] = '0';
			if (!isset($form['censoring']) || $form['censoring'] != '1') $form['censoring'] = '0';
			if (!isset($form['quickjump']) || $form['quickjump'] != '1') $form['quickjump'] = '0';
			if (!isset($form['show_version']) || $form['show_version'] != '1') $form['show_version'] = '0';
			if (!isset($form['users_online']) || $form['users_online'] != '1') $form['users_online'] = '0';
			if (!isset($form['online_today']) || $form['online_today'] != '1') $form['online_today'] = '0';
			if (!isset($form['record']) || $form['record'] != '1') $form['record'] = '0';
			if (!isset($form['statistic']) || $form['statistic'] != '1') $form['statistic'] = '0';
			if (!isset($form['online_ft']) || $form['online_ft'] != '1') $form['online_ft'] = '0';

			if (!isset($form['quickpost']) || $form['quickpost'] != '1') $form['quickpost'] = '0';
			if (!isset($form['subscriptions']) || $form['subscriptions'] != '1') $form['subscriptions'] = '0';
			if (!isset($form['force_guest_email']) || $form['force_guest_email'] != '1') $form['force_guest_email'] = '0';
			if (!isset($form['show_dot']) || $form['show_dot'] != '1') $form['show_dot'] = '0';
			if (!isset($form['topic_views']) || $form['topic_views'] != '1') $form['topic_views'] = '0';
			if (!isset($form['show_post_count']) || $form['show_post_count'] != '1') $form['show_post_count'] = '0';
			if (!isset($form['show_user_info']) || $form['show_user_info'] != '1') $form['show_user_info'] = '0';
			if (!isset($form['show_ua_info']) || $form['show_ua_info'] != '1') $form['show_ua_info'] = '0';
			if (!isset($form['enable_bb_panel']) || $form['enable_bb_panel'] != '1') $form['enable_bb_panel'] = '0';

			if (!isset($form['message_bbcode']) || $form['message_bbcode'] != '1') $form['message_bbcode'] = '0';
			if (!isset($form['message_img_tag']) || $form['message_img_tag'] != '1') $form['message_img_tag'] = '0';
			if (!isset($form['smilies']) || $form['smilies'] != '1') $form['smilies'] = '0';
			if (!isset($form['make_links']) || $form['make_links'] != '1') $form['make_links'] = '0';
			$form['post_edit'] = intval($form['post_edit']);

			if (!isset($form['message_all_caps']) || $form['message_all_caps'] != '1') $form['message_all_caps'] = '0';
			if (!isset($form['subject_all_caps']) || $form['subject_all_caps'] != '1') $form['subject_all_caps'] = '0';

			$form['indent_num_spaces'] = intval($form['indent_num_spaces']);
			$form['quote_depth'] = intval($form['quote_depth']);

			if (!isset($form['rep_enabled']) || $form['rep_enabled'] != '1') $form['rep_enabled'] = '0';
			$form['rep_timeout'] = intval($form['rep_timeout']);

			if (!isset($form['signatures']) || $form['signatures'] != '1') $form['signatures'] = '0';
			if (!isset($form['sig_bbcode']) || $form['sig_bbcode'] != '1') $form['sig_bbcode'] = '0';
			if (!isset($form['sig_img_tag']) || $form['sig_img_tag'] != '1') $form['sig_img_tag'] = '0';
			if (!isset($form['smilies_sig']) || $form['smilies_sig'] != '1') $form['smilies_sig'] = '0';
			if (!isset($form['sig_all_caps']) || $form['sig_all_caps'] != '1') $form['sig_all_caps'] = '0';

			$form['sig_length'] = intval($form['sig_length']);
			$form['sig_lines'] = intval($form['sig_lines']);

			if (!isset($form['avatars']) || $form['avatars'] != '1') $form['avatars'] = '0';

			$form['avatars_width'] = intval($form['avatars_width']);
			$form['avatars_height'] = intval($form['avatars_height']);
			$form['avatars_size'] = intval($form['avatars_size']);

			if (!isset($form['poll_enable_revote']) || $form['poll_enable_revote'] != '1') $form['poll_enable_revote'] = '0';
			if (!isset($form['poll_enable_read']) || $form['poll_enable_read'] != '1') $form['poll_enable_read'] = '0';
			$form['poll_max_answers'] = intval($form['poll_max_answers']);
			if ($form['poll_max_answers'] > 100)
				$form['poll_max_answers'] = 100;
			if ($form['poll_max_answers'] < 2)
				$form['poll_max_answers'] = 2;

			$form['poll_min_posts'] = intval($form['poll_min_posts']);

			if (!isset($form['check_for_updates']) || $form['check_for_updates'] != '1') $form['check_for_updates'] = '0';
			if (!isset($form['gzip']) || $form['gzip'] != '1') $form['gzip'] = '0';

			$form['pm_inbox_size'] = (!isset($form['pm_inbox_size']) || (int) $form['pm_inbox_size'] <= 0) ? '0' : (string)(int) $form['pm_inbox_size'];
			$form['pm_outbox_size'] = (!isset($form['pm_outbox_size']) || (int) $form['pm_outbox_size'] <= 0) ? '0' : (string)(int) $form['pm_outbox_size'];
			if (!isset($form['pm_show_new_count']) || $form['pm_show_new_count'] != '1')
				$form['pm_show_new_count'] = '0';
			if (!isset($form['pm_show_global_link']) || $form['pm_show_global_link'] != '1')
				$form['pm_show_global_link'] = '0';
			if (!isset($form['pm_get_mail']) || $form['pm_get_mail'] != '1') $form['pm_get_mail'] = '0';

			break;
		}

		case 'email':
		{
			($hook = get_hook('aop_email_validation')) ? eval($hook) : null;

			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/functions/email.php';

			$form['admin_email'] = utf8_strtolower(forum_trim($form['admin_email']));
			if (!is_valid_email($form['admin_email']))
				message($lang_admin_settings['Error invalid admin e-mail']);

			$form['webmaster_email'] = utf8_strtolower(forum_trim($form['webmaster_email']));
			if (!is_valid_email($form['webmaster_email']))
				message($lang_admin_settings['Error invalid web e-mail']);

			if (!isset($form['smtp_ssl']) || $form['smtp_ssl'] != '1') $form['smtp_ssl'] = '0';

			break;
		}

		case 'announcements':
		{
			($hook = get_hook('aop_announcements_validation')) ? eval($hook) : null;

			if (!isset($form['announcement']) || $form['announcement'] != '1') $form['announcement'] = '0';

			if ($form['announcement_message'] != '')
				$form['announcement_message'] = forum_linebreaks($form['announcement_message']);
			else
				$form['announcement_message'] = $lang_admin_settings['Announcement message default'];

			if (!isset($form['html_top']) || $form['html_top'] != '1') $form['html_top'] = '0';

			if ($form['html_top_message'] != '')
				$form['html_top_message'] = forum_linebreaks($form['html_top_message']);
			else
				$form['html_top_message'] = $lang_admin_settings['HTML message default'];
	
			if (!isset($form['html_bottom']) || $form['html_bottom'] != '1') $form['html_bottom'] = '0';

			if ($form['html_bottom_message'] != '')
				$form['html_bottom_message'] = forum_linebreaks($form['html_bottom_message']);
			else
				$form['html_bottom_message'] = $lang_admin_settings['HTML message default'];

			if (!isset($form['adbox']) || $form['adbox'] != '1') $form['adbox'] = '0';

			if ($form['adbox_message'] != '')
				$form['adbox_message'] = forum_linebreaks($form['adbox_message']);
			else
				$form['adbox_message'] = $lang_admin_settings['Adbox message default'];

			if (!isset($form['guestbox']) || $form['guestbox'] != '1') $form['guestbox'] = '0';

			if ($form['guestbox_message'] != '')
				$form['guestbox_message'] = forum_linebreaks($form['guestbox_message']);
			else
				$form['guestbox_message'] = $lang_admin_settings['Guestbox message default'];

			if ($form['topicbox'] != '')
				$form['topicbox'] = intval($form['topicbox']);
			else
				$form['topicbox'] = '0';

			if ($form['topicbox_message'] != '')
				$form['topicbox_message'] = forum_linebreaks($form['topicbox_message']);
			else
				$form['topicbox_message'] = $lang_admin_settings['HTML message default'];

			if (!isset($form['externbox']) || $form['externbox'] != '1') $form['externbox'] = '0';

			if ($form['externbox_message'] != '')
				$form['externbox_message'] = forum_linebreaks($form['externbox_message']);
			else
				$form['externbox_message'] = $lang_admin_settings['HTML message default'];

			break;

		}

		case 'registration':
		{
			($hook = get_hook('aop_registration_validation')) ? eval($hook) : null;

			if (!isset($form['regs_allow']) || $form['regs_allow'] != '1') $form['regs_allow'] = '0';
			if (!isset($form['regs_verify']) || $form['regs_verify'] != '1') $form['regs_verify'] = '0';
			if (!isset($form['allow_banned_email']) || $form['allow_banned_email'] != '1') $form['allow_banned_email'] = '0';
			if (!isset($form['allow_dupe_email']) || $form['allow_dupe_email'] != '1') $form['allow_dupe_email'] = '0';
			if (!isset($form['regs_report']) || $form['regs_report'] != '1') $form['regs_report'] = '0';

			if (isset($form['register_timeout']))
				$form['register_timeout'] = round($form['register_timeout']);
			else
				$form['register_timeout'] = 3600;

			if (!isset($form['spam_ip']) || $form['spam_ip'] != '1') $form['spam_ip'] = '0';
			if (!isset($form['spam_email']) || $form['spam_email'] != '1') $form['spam_email'] = '0';
			if (!isset($form['spam_name']) || $form['spam_name'] != '1') $form['spam_name'] = '0';

			if (!isset($form['rules']) || $form['rules'] != '1') $form['rules'] = '0';

			if ($form['rules_message'] != '')
				$form['rules_message'] = forum_linebreaks($form['rules_message']);
			else
				$form['rules_message'] = $lang_admin_settings['Rules default'];

			break;
		}

		case 'maintenance':
		{
			($hook = get_hook('aop_maintenance_validation')) ? eval($hook) : null;

			if (!isset($form['maintenance']) || $form['maintenance'] != '1') $form['maintenance'] = '0';

			if ($form['maintenance_message'] != '')
				$form['maintenance_message'] = forum_linebreaks($form['maintenance_message']);
			else
				$form['maintenance_message'] = $lang_admin_settings['Maintenance message default'];

			break;
		}

		default:
		{
			($hook = get_hook('aop_new_section_validation')) ? eval($hook) : null;
			break;
		}
	}

	($hook = get_hook('aop_pre_update_configuration')) ? eval($hook) : null;

	foreach ($form as $key => $input)
	{
		// Only update permission values that have changed
		if (array_key_exists('p_'.$key, $forum_config) && $forum_config['p_'.$key] != $input)
		{
			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.intval($input),
				'WHERE'		=> 'conf_name=\'p_'.$forum_db->escape($key).'\''
			);

			($hook = get_hook('aop_qr_update_permission_conf')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// Only update option values that have changed
		if (array_key_exists('o_'.$key, $forum_config) && $forum_config['o_'.$key] != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$forum_db->escape($input).'\'';
			else
				$value = 'NULL';

			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.$value,
				'WHERE'		=> 'conf_name=\'o_'.$forum_db->escape($key).'\''
			);

			($hook = get_hook('aop_qr_update_permission_option')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_config_cache();

	($hook = get_hook('aop_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/settings.php?section='.$section), $lang_admin_settings['Settings updated'].' '.$lang_admin_common['Redirect']);
}


if (isset($_POST['add_user']) && $_POST['add_user'] == 1)
{
	$forum_extension['admin_add_user']['user_added'] = false;
	$errors_add_users = array();
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

	$username = forum_trim($_POST['req_username']);
	$email = utf8_strtolower(forum_trim($_POST['req_email']));

	// Validate the username
	if (!defined('FORUM_FUNCTIONS_VALIDATE_USERNAME'))
		require FORUM_ROOT.'include/functions/validate_username.php';

	$errors_add_users = array_merge($errors_add_users, validate_username($username));

	// Check if it's a banned e-mail address
	if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/functions/email.php';

	if (!is_valid_email($email))
		$errors_add_users[] = $lang_common['Invalid e-mail'];

	$banned_email = is_banned_email($email);
	if ($banned_email && !$forum_config['p_allow_banned_email'])
		$errors_add_users[] = $lang_profile['Banned e-mail'];

	// Check if someone else already has registered with that e-mail address
	$query = array(
		'SELECT'	=> 'u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.email=\''.$email.'\''
	);

	($hook = get_hook('aop_qr_add_user_dupe_email')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ((!$forum_config['p_allow_dupe_email']) && ($forum_db->num_rows($result) ))
		$errors_add_users[] = $lang_profile['Dupe e-mail'];

	if (empty($errors_add_users))
	{
		$password = random_key(8, true);
		$salt = random_key(12);
		$password_hash = forum_hash($password, $salt);

		$user_info = array(
			'username'				=>	$username,
			'salt'					=>	$salt,
			'password_hash'			=>	$password_hash,
			'email'					=>	$email,
			'dst'					=>	0,
			'activate_key'			=>	'\''.random_key(8, true).'\'',
			'require_verification'	=>	'1',
		);

		if (!defined('FORUM_FUNCTIONS_ADD_USER'))
			require FORUM_ROOT.'include/functions/add_user.php';

		add_user($user_info, $new_uid);

		if (isset($_POST['edit_identity']) && $_POST['edit_identity'] == 1)
			redirect(forum_link($forum_url['profile'], array($new_uid, 'identity')), $lang_admin_settings['User added'].' '.$lang_admin_common['Redirect']);
		else
			redirect(forum_link('admin/settings.php?section=registration'), $lang_admin_settings['User added'].' '.$lang_admin_common['Redirect']);

		$add_user = true;
	}
	else
		$add_user = false;
}


if (!$section || $section == 'setup')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup')),
		array($lang_admin_common['Setup'], forum_link('admin/settings.php?section=setup')) 
	);

	//$forum_page['main_head'] = $lang_admin_common['Settings'];

	($hook = get_hook('aop_setup_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-setup');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('aop_setup_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=setup') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=setup')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup personal'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_personal_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup personal legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_board_title')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_admin_settings['Board title label'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[board_title]" size="50" maxlength="255" value="<?php echo forum_htmlencode($forum_config['o_board_title']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_board_descrip')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_admin_settings['Board description label'] ?></span>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[board_desc]" size="50" maxlength="255" value="<?php echo forum_htmlencode($forum_config['o_board_desc']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_default_style')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_admin_settings['Default style label'] ?></span>
						</label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[default_style]">
<?php

	$styles = get_style_packs();
	foreach ($styles as $temp)
	{
		if ($forum_config['o_default_style'] == $temp)
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode(str_replace('_', ' ', $temp)).'</option>'."\n";
	}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_setup_pre_forced_style')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_admin_settings['Forced style'] ?></span><small><?php echo $lang_admin_settings['Forced style help'] ?></small>
						</label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[forced_style]">
							<option value="" selected="selected"><?php echo $lang_admin_settings['Select'] ?></option>
<?php

	$styles = get_style_packs();
	foreach ($styles as $temp)
	{
		echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode(str_replace('_', ' ', $temp)).'</option>'."\n";
	}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_user_style')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[user_style]" value="1" <?php if ($forum_config['o_user_style'] == 1) echo 'checked="checked" ' ?>/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['User style'] ?></span> <?php echo $lang_admin_settings['User style help'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_personal_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_personal_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup local'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_local_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup local legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_default_language')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Default language label'] ?></span><small><?php echo $lang_admin_settings['Default language help'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[default_lang]">
<?php

		$languages = get_language_packs();
		foreach ($languages as $temp)
		{
			if ($forum_config['o_default_lang'] == $temp)
				echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode($temp).'</option>'."\n";
		}

		// Load the profile.php language file
		require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_default_language')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Forced language'] ?></span><small><?php echo $lang_admin_settings['Forced language help'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[forced_lang]">
							<option value="" selected="selected"><?php echo $lang_admin_settings['Select'] ?></option>
<?php

		$languages = get_language_packs();
		foreach ($languages as $temp)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode($temp).'</option>'."\n";
		}

		// Load the profile.php language file
		require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_forced_timezone')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Default timezone label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[default_timezone]">
							<option value="-12"<?php if ($forum_config['o_default_timezone'] == -12) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-12:00'] ?></option>
							<option value="-11"<?php if ($forum_config['o_default_timezone'] == -11) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-11:00'] ?></option>
							<option value="-10"<?php if ($forum_config['o_default_timezone'] == -10) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-10:00'] ?></option>
							<option value="-9.5"<?php if ($forum_config['o_default_timezone'] == -9.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-09:30'] ?></option>
							<option value="-9"<?php if ($forum_config['o_default_timezone'] == -9) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-09:00'] ?></option>
							<option value="-8"<?php if ($forum_config['o_default_timezone'] == -8) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-08:00'] ?></option>
							<option value="-7"<?php if ($forum_config['o_default_timezone'] == -7) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-07:00'] ?></option>
							<option value="-6"<?php if ($forum_config['o_default_timezone'] == -6) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-06:00'] ?></option>
							<option value="-5"<?php if ($forum_config['o_default_timezone'] == -5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-05:00'] ?></option>
							<option value="-4"<?php if ($forum_config['o_default_timezone'] == -4) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-04:00'] ?></option>
							<option value="-3.5"<?php if ($forum_config['o_default_timezone'] == -3.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-03:30'] ?></option>
							<option value="-3"<?php if ($forum_config['o_default_timezone'] == -3) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-03:00'] ?></option>
							<option value="-2"<?php if ($forum_config['o_default_timezone'] == -2) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-02:00'] ?></option>
							<option value="-1"<?php if ($forum_config['o_default_timezone'] == -1) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-01:00'] ?></option>
							<option value="0"<?php if ($forum_config['o_default_timezone'] == 0) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC'] ?></option>
							<option value="1"<?php if ($forum_config['o_default_timezone'] == 1) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+01:00'] ?></option>
							<option value="2"<?php if ($forum_config['o_default_timezone'] == 2) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+02:00'] ?></option>
							<option value="3"<?php if ($forum_config['o_default_timezone'] == 3) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+03:00'] ?></option>
							<option value="3.5"<?php if ($forum_config['o_default_timezone'] == 3.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+03:30'] ?></option>
							<option value="4"<?php if ($forum_config['o_default_timezone'] == 4) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+04:00'] ?></option>
							<option value="4.5"<?php if ($forum_config['o_default_timezone'] == 4.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+04:30'] ?></option>
							<option value="5"<?php if ($forum_config['o_default_timezone'] == 5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:00'] ?></option>
							<option value="5.5"<?php if ($forum_config['o_default_timezone'] == 5.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:30'] ?></option>
							<option value="5.75"<?php if ($forum_config['o_default_timezone'] == 5.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:45'] ?></option>
							<option value="6"<?php if ($forum_config['o_default_timezone'] == 6) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+06:00'] ?></option>
							<option value="6.5"<?php if ($forum_config['o_default_timezone'] == 6.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+06:30'] ?></option>
							<option value="7"<?php if ($forum_config['o_default_timezone'] == 7) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+07:00'] ?></option>
							<option value="8"<?php if ($forum_config['o_default_timezone'] == 8) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+08:00'] ?></option>
							<option value="8.75"<?php if ($forum_config['o_default_timezone'] == 8.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+08:45'] ?></option>
							<option value="9"<?php if ($forum_config['o_default_timezone'] == 9) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+09:00'] ?></option>
							<option value="9.5"<?php if ($forum_config['o_default_timezone'] == 9.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+09:30'] ?></option>
							<option value="10"<?php if ($forum_config['o_default_timezone'] == 10) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+10:00'] ?></option>
							<option value="10.5"<?php if ($forum_config['o_default_timezone'] == 10.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+10:30'] ?></option>
							<option value="11"<?php if ($forum_config['o_default_timezone'] == 11) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+11:00'] ?></option>
							<option value="11.5"<?php if ($forum_config['o_default_timezone'] == 11.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+11:30'] ?></option>
							<option value="12"<?php if ($forum_config['o_default_timezone'] == 12) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+12:00'] ?></option>
							<option value="12.75"<?php if ($forum_config['o_default_timezone'] == 12.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+12:45'] ?></option>
							<option value="13"<?php if ($forum_config['o_default_timezone'] == 13) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+13:00'] ?></option>
							<option value="14"<?php if ($forum_config['o_default_timezone'] == 14) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+14:00'] ?></option>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_default_dst')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[default_dst]" value="1" <?php if ($forum_config['o_default_dst'] == 1) echo 'checked="checked" ' ?>/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Adjust for DST'] ?></span> <?php echo $lang_admin_settings['DST label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_time_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Time format label'] ?></span><small><?php printf($lang_admin_settings['Current format'], format_time(time(), 2, null, $forum_config['o_time_format']), $lang_admin_settings['External format help']) ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[time_format]" size="25" maxlength="25" value="<?php echo forum_htmlencode($forum_config['o_time_format']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_date_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Date format label'] ?></span><small><?php printf($lang_admin_settings['Current format'], format_time(time(), 1, $forum_config['o_date_format'], null, true), $lang_admin_settings['External format help']) ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[date_format]" size="25" maxlength="25" value="<?php echo forum_htmlencode($forum_config['o_date_format']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_local_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_local_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup timeouts'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_timeouts_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup timeouts legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_visit_timeout')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Visit timeout label'] ?></span><small><?php echo $lang_admin_settings['Visit timeout help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[timeout_visit]" size="5" maxlength="5" value="<?php echo $forum_config['o_timeout_visit'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_online_timeout')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Online timeout label'] ?></span><small><?php echo $lang_admin_settings['Online timeout help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[timeout_online]" size="5" maxlength="5" value="<?php echo $forum_config['o_timeout_online'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_redirect_time')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Redirect time label'] ?></span><small><?php echo $lang_admin_settings['Redirect time help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[redirect_delay]" size="5" maxlength="5" value="<?php echo $forum_config['o_redirect_delay'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_timeouts_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_timeouts_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup pagination'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_pagination_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup pagination legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_topics_per_page')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Topics per page label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[disp_topics_default]" size="3" maxlength="3" value="<?php echo $forum_config['o_disp_topics_default'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_posts_per_page')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Posts per page label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[disp_posts_default]" size="3" maxlength="3" value="<?php echo $forum_config['o_disp_posts_default'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_topic_review')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box frm-short text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Topic review label'] ?></span><small><?php echo $lang_admin_settings['Topic review help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[topic_review]" size="3" maxlength="3" value="<?php echo $forum_config['o_topic_review'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_pagination_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_pagination_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup reports'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_reports_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup reports legend'] ?></strong></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[report_enabled]" value="1" <?php if ($forum_config['o_report_enabled']) echo 'checked="checked" ' ?>/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Report enabled'] ?></span> <?php echo $lang_admin_settings['Report enabled help'] ?></label>
					</div>
				</div>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Reporting method'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[report_method]" value="0"<?php if ($forum_config['o_report_method'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Report internal label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[report_method]" value="1"<?php if ($forum_config['o_report_method']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Report email label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[report_method]" value="2"<?php if ($forum_config['o_report_method'] == '2') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Report both label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_setup_new_reporting_method')) ? eval($hook) : null; ?>
					</div>
				</fieldset>
<?php ($hook = get_hook('aop_setup_pre_reports_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_reports_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup URL'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<div class="ct-box">
				<p class="warn"><?php echo $lang_admin_settings['URL scheme info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_url_scheme_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup URL legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_url_scheme')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['URL scheme label'] ?></span><small><?php echo $lang_admin_settings['URL scheme help'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[sef]">
<?php

		$url_schemes = get_scheme_packs();
		foreach ($url_schemes as $temp)
		{
			if ($forum_config['o_sef'] == $temp)
				echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode(str_replace('_', ' ', $temp)).'</option>'."\n";
		}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_url_scheme_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_setup_url_scheme_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Setup links'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<div class="ct-box">
				<p class="warn"><?php echo $lang_admin_settings['Setup links info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_setup_pre_links_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Setup links legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_additional_navlinks')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enter links label'] ?></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[additional_navlinks]" rows="3" cols="55"><?php echo forum_htmlencode($forum_config['o_additional_navlinks']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_setup_pre_links_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_setup_links_fieldset_end')) ? eval($hook) : null; ?>
			<div id="buttons" class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}

else if ($section == 'features')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup')),
		array($lang_admin_common['Features'], forum_link('admin/settings.php?section=features'))
	);

	($hook = get_hook('aop_features_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-features');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('aop_features_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=features') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=features')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features general'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_general_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Features general legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_search_all_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[search_all_forums]" value="1"<?php if ($forum_config['o_search_all_forums']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Searching'] ?></span> <?php echo $lang_admin_settings['Search all label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_ranks_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[ranks]" value="1"<?php if ($forum_config['o_ranks']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['User ranks'] ?></span> <?php echo $lang_admin_settings['User ranks label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_censoring_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[censoring]" value="1"<?php if ($forum_config['o_censoring']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Censor words'] ?></span> <?php echo $lang_admin_settings['Censor words label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_quickjump_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[quickjump]" value="1"<?php if ($forum_config['o_quickjump']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Quick jump'] ?></span> <?php echo $lang_admin_settings['Quick jump label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_version_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_version]" value="1"<?php if ($forum_config['o_show_version']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Show version'] ?></span> <?php echo $lang_admin_settings['Show version label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_users_online_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[users_online]" value="1"<?php if ($forum_config['o_users_online']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Online list'] ?></span> <?php echo $lang_admin_settings['Users online label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_today_users_online_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[online_today]" value="1"<?php if ($forum_config['o_online_today']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Today online list'] ?></span> <?php echo $lang_admin_settings['Today online label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_record_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[record]" value="1"<?php if ($forum_config['o_record']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Record list'] ?></span> <?php echo $lang_admin_settings['Record label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_stats_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[statistic]" value="1"<?php if ($forum_config['o_statistic']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Stats list'] ?></span> <?php echo $lang_admin_settings['Stats label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_online_ft_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[online_ft]" value="1"<?php if ($forum_config['o_online_ft']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Online ft list'] ?></span> <?php echo $lang_admin_settings['Online ft label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_general_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php
	($hook = get_hook('aop_features_general_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features posting'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_posting_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Features posting legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_quickpost_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[quickpost]" value="1"<?php if ($forum_config['o_quickpost']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Quick post'] ?></span> <?php echo $lang_admin_settings['Quick post label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_subscriptions_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[subscriptions]" value="1"<?php if ($forum_config['o_subscriptions']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Subscriptions'] ?></span> <?php echo $lang_admin_settings['Subscriptions label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_force_guest_email_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[force_guest_email]" value="1"<?php if ($forum_config['p_force_guest_email']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Guest posting'] ?></span> <?php echo $lang_admin_settings['Guest posting label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_dot_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_dot]" value="1"<?php if ($forum_config['o_show_dot']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['User has posted'] ?></span> <?php echo $lang_admin_settings['User has posted label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_topic_views_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[topic_views]" value="1"<?php if ($forum_config['o_topic_views']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Topic views'] ?></span> <?php echo $lang_admin_settings['Topic views label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_post_count_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_post_count]" value="1"<?php if ($forum_config['o_show_post_count']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['User post count'] ?></span> <?php echo $lang_admin_settings['User post count label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_user_info_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_user_info]" value="1"<?php if ($forum_config['o_show_user_info']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['User info'] ?></span> <?php echo $lang_admin_settings['User info label'] ?> <small><?php echo $lang_admin_settings['Load server'] ?></small></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_merge_posts_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Merge info'] ?></span><small><?php echo $lang_admin_settings['Merge info label'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[merge_timeout]" size="5" maxlength="5" value="<?php echo $forum_config['o_merge_timeout'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_enable_bb_panel_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[enable_bb_panel]" value="1"<?php if ($forum_config['p_enable_bb_panel']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable bb panel'] ?></span> <?php echo $lang_admin_settings['Enable bb panel label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_bb_panel_smilies_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['BB panel smilies'] ?></span><small><?php echo $lang_admin_settings['BB panel smilies label'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[bb_panel_smilies]" size="3" maxlength="3" value="<?php echo $forum_config['p_bb_panel_smilies'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_posting_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_posting_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features posts'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_message_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Features posts legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_message_content_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Post content group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[message_bbcode]" value="1"<?php if ($forum_config['p_message_bbcode']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Allow BBCode label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[message_img_tag]" value="1"<?php if ($forum_config['p_message_img_tag']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Allow img label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[smilies]" value="1"<?php if ($forum_config['o_smilies']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Smilies in posts label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[make_links]" value="1"<?php if ($forum_config['o_make_links']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Make clickable links label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_message_content_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_message_content_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Post period label'] ?></span><small><?php echo $lang_admin_settings['Post period help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[post_edit]" size="3" maxlength="3" value="<?php echo $forum_config['o_post_edit'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_message_content_fieldset_end')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Allow capitals group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[message_all_caps]" value="1"<?php if ($forum_config['p_message_all_caps']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['All caps message label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[subject_all_caps]" value="1"<?php if ($forum_config['p_subject_all_caps']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['All caps subject label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_message_caps_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_message_caps_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_features_message_caps_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Indent size label'] ?></span><small><?php echo $lang_admin_settings['Indent size help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[indent_num_spaces]" size="3" maxlength="3" value="<?php echo $forum_config['o_indent_num_spaces'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_quote_depth')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Quote depth label'] ?></span><small><?php echo $lang_admin_settings['Quote depth help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[quote_depth]" size="3" maxlength="3" value="<?php echo $forum_config['o_quote_depth'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_message_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_message_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features sigs'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_sig_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Features sigs legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_signature_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[signatures]" value="1"<?php if ($forum_config['o_signatures']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Allow signatures'] ?></span> <?php echo $lang_admin_settings['Allow signatures label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_sig_content_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Signature content group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sig_bbcode]" value="1"<?php if ($forum_config['p_sig_bbcode']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['BBCode in sigs label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sig_img_tag]" value="1"<?php if ($forum_config['p_sig_img_tag']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Img in sigs label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[smilies_sig]" value="1"<?php if ($forum_config['o_smilies_sig']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Smilies in sigs label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sig_all_caps]" value="1"<?php if ($forum_config['p_sig_all_caps']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['All caps sigs label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_sig_content_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_sig_content_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_features_sig_content_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Max sig length label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[sig_length]" size="5" maxlength="5" value="<?php echo $forum_config['p_sig_length'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_max_sig_lines')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Max sig lines label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[sig_lines]" size="5" maxlength="3" value="<?php echo $forum_config['p_sig_lines'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_sig_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_sig_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features Avatars'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_avatars_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Features Avatars legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_avatar_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[avatars]" value="1"<?php if ($forum_config['o_avatars']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Allow avatars'] ?></span> <?php echo $lang_admin_settings['Allow avatars label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_width')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Avatar Max width label'] ?></span><small><?php echo $lang_admin_settings['Avatar Max width help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[avatars_width]" size="6" maxlength="5" value="<?php echo $forum_config['o_avatars_width'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_height')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Avatar Max height label'] ?></span><small><?php echo $lang_admin_settings['Avatar Max height help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[avatars_height]" size="6" maxlength="5" value="<?php echo $forum_config['o_avatars_height'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_size')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Avatar Max size label'] ?></span><small><?php echo $lang_admin_settings['Avatar Max size help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[avatars_size]" size="6" maxlength="6" value="<?php echo $forum_config['o_avatars_size'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_gravatar')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Allow gravatar'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gravatar]" value="0"<?php if ($forum_config['o_gravatar'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Gravatar disabled label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gravatar]" value="G"<?php if ($forum_config['o_gravatar'] == 'G') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Gravatar G label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gravatar]" value="PG"<?php if ($forum_config['o_gravatar'] == 'PG') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Gravatar PG label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gravatar]" value="R"<?php if ($forum_config['o_gravatar'] == 'R') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Gravatar R label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gravatar]" value="X"<?php if ($forum_config['o_gravatar'] == 'X') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Gravatar X label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_setup_new_reporting_method')) ? eval($hook) : null; ?>
					</div>
				</fieldset>
<?php ($hook = get_hook('aop_features_pre_avatars_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_avatars_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Settings for polls'] ?></span></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<fieldset class="frm-group group1">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="checkbox" name="form[poll_enable_revote]" value="1"<?php if ($forum_config['p_poll_enable_revote']) echo ' checked="checked"' ?>/></span>
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Disable revoting'] ?></span><?php echo $lang_admin_settings['Disable revoting info'] ?></label>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="checkbox" name="form[poll_enable_read]" value="1"<?php if ($forum_config['p_poll_enable_read']) echo ' checked="checked"' ?>/></span>
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Disable see results'] ?></span><?php echo $lang_admin_settings['Disable see results info'] ?></label></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Maximum answers'] ?></span><small><?php echo $lang_admin_settings['Maximum answers info'] ?></small></label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[poll_max_answers]" size="6" maxlength="6" value="<?php echo $forum_config['p_poll_max_answers'] ?>" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Poll min posts'] ?></span><small><?php echo $lang_admin_settings['Poll min posts info'] ?></small></label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[poll_min_posts]" size="6" maxlength="6" value="<?php echo $forum_config['p_poll_min_posts'] ?>" /></span>
					</div>
				</div>
			</fieldset>
<?php

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features reputation'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php ($hook = get_hook('aop_fl_features_pre_per_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Features reputation legend'] ?></span></legend>
<?php ($hook = get_hook('aop_fl_features_pre_rep_enabled')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[rep_enabled]" value="1"<?php if ($forum_config['o_rep_enabled']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Allow reputation'] ?></span> <?php echo $lang_admin_settings['Allow reputation label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_reputation_timeout')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Reputation timeout'] ?></span><small><?php echo $lang_admin_settings['Reputation timeout help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[rep_timeout]" size="5" maxlength="5" value="<?php echo $forum_config['o_rep_timeout'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_sigs_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features title'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Features title'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_features_pre_pm_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld32"><span><?php echo $lang_admin_settings['Inbox limit'] ?></span><small><?php echo $lang_admin_settings['Inbox limit info'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_inbox_size]" size="6" maxlength="5" value="<?php echo $forum_config['o_pm_inbox_size'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_outbox_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld32"><span><?php echo $lang_admin_settings['Outbox limit'] ?></span><small><?php echo $lang_admin_settings['Outbox limit info'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_outbox_size]" size="6" maxlength="5" value="<?php echo $forum_config['o_pm_outbox_size'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_features_pre_navigation_checkbox')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Navigation links'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_show_new_count]" value="1"<?php if ($forum_config['o_pm_show_new_count']) echo ' checked="checked"' ?> /></span>
							<label for="fld24"><?php echo $lang_admin_settings['Snow new count'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_show_global_link]" value="1"<?php if ($forum_config['o_pm_show_global_link']) echo ' checked="checked"' ?> /></span>
							<label for="fld23"><?php echo $lang_admin_settings['Show global link'] ?></label>
						</div>
					</div>
				</fieldset>
<?php ($hook = get_hook('aop_fl_features_pre_pm_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="checkbox" name="form[pm_get_mail]" value="1"<?php if ($forum_config['o_pm_get_mail']) echo ' checked="checked"' ?>/></span>
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Disable pm get mail'] ?></span><?php echo $lang_admin_settings['Disable pm get mail info'] ?></label></span>
					</div>
				</div>
			</fieldset>
<?php

($hook = get_hook('aop_fl_features_pm_fieldset_end')) ? eval($hook) : null;

$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Google Analytics'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Google Analytics'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_features_pre_google_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>">
							<span><?php echo $lang_admin_settings['Tracker'] ?></span>
							<small><?php echo $lang_admin_settings['Tracker help'] ?></small>
						</label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[google_analytics]" size="50" maxlength="255" value="<?php echo forum_htmlencode($forum_config['o_google_analytics']) ?>"  /></span>
					</div>
				</div>
			</fieldset>
<?php

($hook = get_hook('aop_fl_features_google_fieldset_end')) ? eval($hook) : null;

$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features update'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
<?php if (function_exists('curl_init') || function_exists('fsockopen') || in_array(strtolower(@ini_get('allow_url_fopen')), array('on', 'true', '1'))): ?>
			<div class="ct-box">
				<p><?php echo $lang_admin_settings['Features update info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_updates_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Features update legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_updates_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[check_for_updates]" value="1"<?php if ($forum_config['o_check_for_updates']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Update check'] ?></span> <?php echo $lang_admin_settings['Update check label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_updates_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_features_updates_fieldset_end')) ? eval($hook) : null; ?>
<?php else: ?>
			<div class="ct-box">
				<p><?php echo $lang_admin_settings['Features update disabled info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_post_updates_disabled_box')) ? eval($hook) : null;

endif;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Features gzip'] ?></span></h2>
				<p class="content-options options"><a href="#buttons"><?php echo $lang_admin_common['Save changes'] ?></a></p>
			</div>
			<div class="ct-box">
				<p><?php echo $lang_admin_settings['Features gzip info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_gzip_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Features gzip legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_gzip_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[gzip]" value="1"<?php if ($forum_config['o_gzip']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable gzip'] ?></span> <?php echo $lang_admin_settings['Enable gzip label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_gzip_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_features_gzip_fieldset_end')) ? eval($hook) : null; ?>
			<div id="buttons" class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}
else if ($section == 'announcements')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup')),
		array($lang_admin_common['Announcements'], forum_link('admin/settings.php?section=announcements'))
	);

	($hook = get_hook('aop_announcements_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-announcements');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('aop_announcements_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_admin_settings['Announcements head'] ?></span></h2>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=announcements') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=announcements')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Announcements legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_announcements_pre_enable_announcement_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[announcement]" value="1"<?php if ($forum_config['o_announcement']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable announcement'] ?></span> <?php echo $lang_admin_settings['Enable announcement label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_heading')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Announcement heading label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[announcement_heading]" size="50" maxlength="255" value="<?php echo forum_htmlencode($forum_config['o_announcement_heading']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Announcement message label'] ?></span><small><?php echo $lang_admin_settings['Announcement message help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[announcement_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_announcement_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_fl_announcements_pre_html_top_fieldset')) ? eval($hook) : null; 

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['HTML head'] ?></span></h2>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_html_top_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[html_top]" value="1"<?php if ($forum_config['o_html_top']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable HTML top'] ?></span> <?php echo $lang_admin_settings['HTML label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_html_top_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['HTML top part'] ?></span><small><?php echo $lang_admin_settings['HTML top help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[html_top_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_html_top_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_html_top_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

($hook = get_hook('aop_fl_announcements_pre_html_top_fieldset')) ? eval($hook) : null;

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_html_bottom_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[html_bottom]" value="1"<?php if ($forum_config['o_html_bottom']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable HTML bottom'] ?></span> <?php echo $lang_admin_settings['HTML label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_html_bottom_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['HTML bottom part'] ?></span><small><?php echo $lang_admin_settings['HTML bottom help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[html_bottom_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_html_bottom_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_html_bottom_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

($hook = get_hook('aop_fl_announcements_pre_adbox_fieldset')) ? eval($hook) : null;

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Ad head'] ?></span></h2>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_adbox_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[adbox]" value="1"<?php if ($forum_config['o_adbox']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable Adbox'] ?></span> <?php echo $lang_admin_settings['Adbox label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_adbox_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Adbox part'] ?></span><small><?php echo $lang_admin_settings['Adbox help'].$lang_admin_settings['HTML help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[adbox_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_adbox_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_html_adbox_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_fl_announcements_pre_guestbox_fieldset')) ? eval($hook) : null; 

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_guestbox_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[guestbox]" value="1"<?php if ($forum_config['o_guestbox']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable Guestbox'] ?></span> <?php echo $lang_admin_settings['Adbox label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_guestbox_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Adbox part'] ?></span><small><?php echo $lang_admin_settings['Guestbox help'].$lang_admin_settings['HTML help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[guestbox_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_guestbox_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_html_guestbox_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php 

($hook = get_hook('aop_fl_announcements_pre_topicbox_fieldset')) ? eval($hook) : null;

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_topicbox_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable Topicbox'] ?></span><small><?php echo $lang_admin_settings['Topic legend'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld22" name="form[topicbox]" size="3" maxlength="3" value="<?php echo $forum_config['o_topicbox'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_topicbox_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Adbox part'] ?></span><small><?php echo $lang_admin_settings['Topicbox help'].$lang_admin_settings['HTML help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[topicbox_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_topicbox_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_html_topicbox_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php 

($hook = get_hook('aop_fl_announcements_pre_externbox_fieldset')) ? eval($hook) : null;

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['HTML legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_announcements_pre_enable_externbox_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[externbox]" value="1"<?php if ($forum_config['o_externbox']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Enable Externbox'] ?></span> <?php echo $lang_admin_settings['Adbox label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_pre_externbox_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Adbox part'] ?></span><small><?php echo $lang_admin_settings['Externbox help'].$lang_admin_settings['HTML help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[externbox_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_externbox_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_announcements_html_externbox_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}
else if ($section == 'registration')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup')),
		array($lang_admin_common['Registration'], forum_link('admin/settings.php?section=registration'))
	);

	$username = '';
	$email = '';
	$edit_identity = '';
	$result_message = '';

	if (isset($_POST['add_user']) && $_POST['add_user'] == 1)
	{
		if ($add_user === true)
			$result_message = '<div class="frm-info"><p>User added successfully/p></div>';
		else
		{
			$username = $_POST['req_username'];
			$email = $_POST['req_email'];
			$edit_identity = isset($_POST['edit_identity']);
		}
	}

	($hook = get_hook('aop_registration_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-registration');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('aop_registration_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=registration') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=registration')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Registration new'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_registration_pre_new_regs_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Registration new legend'] ?></span></legend>
				<div class="ct-box set<?php echo ++$forum_page['item_count'] ?>">
					<p><?php echo $lang_admin_settings['New reg info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_registration_pre_allow_new_regs_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[regs_allow]" value="1"<?php if ($forum_config['o_regs_allow']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Allow new reg'] ?></span> <?php echo $lang_admin_settings['Allow new reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_verify_regs_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[regs_verify]" value="1"<?php if ($forum_config['o_regs_verify']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Verify reg'] ?></span> <?php echo $lang_admin_settings['Verify reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_email_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Reg e-mail group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[allow_banned_email]" value="1"<?php if ($forum_config['p_allow_banned_email']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Allow banned label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[allow_dupe_email]" value="1"<?php if ($forum_config['p_allow_dupe_email']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Allow dupe label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_registration_new_email_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_registration_pre_email_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_registration_email_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[regs_report]" value="1"<?php if ($forum_config['o_regs_report']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Report new reg'] ?></span> <?php echo $lang_admin_settings['Report new reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_email_setting_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['E-mail setting group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="0"<?php if ($forum_config['o_default_email_setting'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Display e-mail label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="1"<?php if ($forum_config['o_default_email_setting'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Allow form e-mail label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="2"<?php if ($forum_config['o_default_email_setting'] == '2') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Disallow form e-mail label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_registration_new_email_setting_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_registration_pre_email_setting_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_fl_registration_pre_register_timeout_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld11"><span><?php echo $lang_admin_settings['Registration timeout'] ?></span><small><?php echo $lang_admin_settings['Registration timeout help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[register_timeout]" size="5" maxlength="10" value="<?php echo forum_htmlencode($forum_config['o_register_timeout']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_email_setting_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="ct-box">
				<p><?php echo $lang_admin_settings['Spam check info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_fl_registration_pre_spamforum_setting_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_admin_settings['Spam check legend'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[spam_ip]" value="1"<?php if ($forum_config['o_spam_ip']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Spam ip info'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[spam_email]" value="1"<?php if ($forum_config['o_spam_email']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Spam email info'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[spam_name]" value="1"<?php if ($forum_config['o_spam_username']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_admin_settings['Spam name info'] ?></label>
						</div>
<?php ($hook = get_hook('aop_fl_registration_spamforum_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_fl_registration_pre_rules_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
			</fieldset>
<?php

	($hook = get_hook('aop_fl_registration_new_regs_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Registration rules'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_fl_registration_pre_rules_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Registration rules legend'] ?></span></legend>
				<div class="ct-box set<?php echo ++$forum_page['item_count'] ?>">
					<p><?php echo $lang_admin_settings['Registration rules info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_fl_registration_pre_rules_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[rules]" value="1"<?php if ($forum_config['o_rules']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Require rules'] ?></span><?php echo $lang_admin_settings['Require rules label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_registration_pre_rules_text')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Compose rules label'] ?></span><small><?php echo $lang_admin_settings['Compose rules help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[rules_message]" rows="10" cols="55"><?php echo forum_htmlencode($forum_config['o_rules_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_registration_pre_rules_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_fl_registration_rules_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
<?php

	// Reset counter
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
		<form class="frm-form" id="frm-adduser" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=registration') ?>#adduser">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=registration')) ?>" />
				<input type="hidden" name="add_user" value="1" />
			</div>
			<div class="main-subhead" id="adduser">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Add user'] ?></span></h2>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_settings['Add user'] ?></span></legend>
<?php ($hook = get_hook('aop_fl_register_pre_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><? echo $lang_admin_settings['Username'] ?></span> <small><?php echo $lang_admin_settings['Username help'] ?></small></label>
						<span class="fld-input"><input type="text" id="add_user_username" name="req_username" size="35" value="<?php echo $username ?>" maxlength="25" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_register_pre_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>E-mail</span> <small><?php echo $lang_admin_settings['E-mail help'] ?></small></label>
						<span class="fld-input"><input type="text" id="add_user_email" name="req_email" size="35" value="<?php echo $email ?>" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_register_pre_email_identity')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="add_user_edit_user_identity" name="edit_identity" value="1"<?php echo $edit_identity ? ' checked="checked"' : '' ?> /></span>
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Edit user'] ?></span> <?php echo $lang_admin_settings['Edit help'] ?></label>
					</div>
				</div>
			</fieldset>
<?php ($hook = get_hook('aop_fl_register_group_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_admin_settings['Add user'] ?>" /></span>
			</div>
		</form>
<?php

	if (!empty($errors_add_users))
	{
		$error_li = array();
		for ($i = 0; $i < count($errors_add_users); $i++)
			$error_li[] = '<li class="warn"><span>'.$errors_add_users[$i].'</span></li>';

?>
		<div class="ct-box error-box">
			<h2 class="hn"><span><?php echo $lang_admin_settings['There are some errors']?></span></h2>
			<ul>
				<?php echo implode("\n\t\t\t\t\t", $error_li)."\n" ?>
			</ul>
		</div>
<?php

	}

?>
	</div>
<?php

}

else if ($section == 'maintenance')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Management'], forum_link('admin/reports.php')),
		array($lang_admin_common['Maintenance mode'], forum_link('admin/settings.php?section=maintenance'))
	);

	($hook = get_hook('aop_maintenance_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'management');
	define('FORUM_PAGE', 'admin-maintenance');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('aop_maintenance_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_settings['Maintenance head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=maintenance') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=maintenance')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="ct-box warn-box">
				<p class="important"><?php echo $lang_admin_settings['Maintenance mode info'] ?></p>
				<p class="warn"><?php echo $lang_admin_settings['Maintenance mode warn'] ?></p>
			</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['Maintenance legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[maintenance]" value="1"<?php if ($forum_config['o_maintenance']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Maintenance mode'] ?></span> <?php echo $lang_admin_settings['Maintenance mode label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Maintenance message label'] ?></span><small><?php echo $lang_admin_settings['Maintenance message help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[maintenance_message]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_maintenance_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_maintenance_maintenance_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}

else if ($section == 'email')
{
	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup')),
		array($lang_admin_common['E-mail'], forum_link('admin/settings.php?section=email'))
	);

	($hook = get_hook('aop_email_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-email');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	if (isset($_POST['mass_mail']))
	{
		if (trim($_POST['req_mass_subject']) == '')
			message($lang_admin_settings['Error no subject']);
		if (trim($_POST['req_mass_message']) == '')
			message($lang_admin_settings['Error no massage']);
		if (trim($_POST['mass_group']) == '')
			message($lang_admin_settings['Error no group']);
		if (trim($_POST['per_page']) == '' || intval($_POST['per_page']) < 0)
			message($lang_admin_settings['Error no partition']);

		$per_page = intval($_POST['per_page']);
		$report = array();
		if (isset($_POST['send_mail']))
		{
			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/functions/email.php';

			$mail_subject = forum_htmlencode($_POST['req_mass_subject']);
			$mail_message = forum_htmlencode($_POST['req_mass_message']);
			$send_mail = intval($_POST['send_mail']);

			$query = array(
				'SELECT'	=> 'id, username, email',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id>1',
				'ORDER BY'	=> 'u.username'
			);

			if (trim($_POST['mass_group']) != 0)
				$query['WHERE'] .= ' AND u.group_id='.$_POST['mass_group'];

			if ($per_page != 0)
				$query['LIMIT'] = $send_mail.', '.$per_page;

			($hook = get_hook('aop_qr_send_mail_count_mass_group')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			while($mass_mail = $forum_db->fetch_assoc($result))
			{
				$mass_user = '<a href="'.forum_link($forum_url['user'] ,$mass_mail['id']).'">'.forum_htmlencode($mass_mail['username']).'</a>';
				forum_mail($mass_mail['email'], $mail_subject, $mail_message);
				$report[] = $mass_user;
			}

			$forum_db->free_result($result);
			$send_mail += $per_page;
		}
		else
			$send_mail = 0;

		$forum_page['group_count'] = $forum_page['item_count'] = 0;

		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id>1',
		);

		if (forum_trim($_POST['mass_group']) != 0)
			$query['WHERE'] .= ' AND u.group_id='.$_POST['mass_group'];

		if ($per_page != 0)
			$query['LIMIT'] = $send_mail.', '.$per_page;

		($hook = get_hook('aop_qr_count_mass_group')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$usercount = $forum_db->result($result);
		$preview_message = nl2br(forum_htmlencode($_POST['req_mass_message']));

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_settings['Preview mail']; ?></span></h2>
	</div>
	<div class="main-content main-frm">	
<?php if (!empty($report)): ?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><strong><?php echo $lang_admin_settings['Successfully sent'] ?></strong>: <?php echo implode(', ', $report) ?></span></h2>
		</div>
<?php endif; ?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=email') ?>" onsubmit="this.submit.disabled=true; return true;">
			<div class="hidden">
				<input type="hidden" name="mass_mail" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=email')) ?>" />
				<input type="hidden" name="req_mass_subject" value="<?php echo forum_htmlencode($_POST['req_mass_subject']) ?>" />
				<input type="hidden" name="req_mass_message" value="<?php echo forum_htmlencode($_POST['req_mass_message']) ?>" />
				<input type="hidden" name="mass_group" value="<?php echo forum_htmlencode($_POST['mass_group']) ?>" />
				<input type="hidden" name="send_mail" value="<?php echo $send_mail ?>" />
				<input type="hidden" name="per_page" value="<?php echo $per_page ?>" />
			</div>
			<fieldset class="frm-group groupp<?php echo ++$forum_page['group_count'] ?>">
				<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box data-box">
						<h4 class="ct-legend hn"><span><?php echo $lang_admin_settings['Mass subject label']; ?></span></h4>
						<h4 class="hn"><?php echo forum_htmlencode($_POST['req_mass_subject']) ?></h4>
					</div>
				</div>
<?php if ($usercount): ?>
				<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box data-box">
						<h4 class="ct-legend hn"><span><?php echo $lang_admin_settings['Mass recipient label']; ?></span></h4>
						<h4 class="hn"><?php echo $usercount ?></h4>
					</div>
				</div>
<?php endif; ?>
				<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>"></div>
				<div class="post-entry">
					<div class="entry-content">
						<p><?php echo $preview_message ?></p>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /> <?php echo $lang_admin_settings['Сlick only once'] ?></span>
			</div>
		</form>
	</div>
<?php

	}
	else
	{

	($hook = get_hook('aop_email_output_start')) ? eval($hook) : null;

?>
	<div class="main-content frm parted">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=email') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=email')) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['E-mail head'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_email_pre_addresses_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['E-mail addresses legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_email_pre_admin_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Admin e-mail'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[admin_email]" size="50" maxlength="80" value="<?php echo forum_htmlencode($forum_config['o_admin_email']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_webmaster_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Webmaster e-mail label'] ?></span><small><?php echo $lang_admin_settings['Webmaster e-mail help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[webmaster_email]" size="50" maxlength="80" value="<?php echo forum_htmlencode($forum_config['o_webmaster_email']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_mailing_list')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Mailing list label'] ?></span><small><?php echo $lang_admin_settings['Mailing list help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="form[mailing_list]" rows="5" cols="55"><?php echo forum_htmlencode($forum_config['o_mailing_list']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_addresses_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

($hook = get_hook('aop_email_addresses_fieldset_end')) ? eval($hook) : null;

// Reset counter
$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['E-mail server'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_email_pre_smtp_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['E-mail server legend'] ?></strong></legend>
				<div class="ct-box set<?php echo ++$forum_page['item_count'] ?>">
					<p><?php echo $lang_admin_settings['E-mail server info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_host')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['SMTP address label'] ?></span><small><?php echo $lang_admin_settings['SMTP address help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[smtp_host]" size="35" maxlength="100" value="<?php echo forum_htmlencode($forum_config['o_smtp_host']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_user')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['SMTP username label'] ?></span><small><?php echo $lang_admin_settings['SMTP help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[smtp_user]" size="35" maxlength="50" value="<?php echo forum_htmlencode($forum_config['o_smtp_user']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_pass')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['SMTP password label'] ?></span><small><?php echo $lang_admin_settings['SMTP help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[smtp_pass]" size="35" maxlength="50" value="<?php echo forum_htmlencode($forum_config['o_smtp_pass']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_ssl')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[smtp_ssl]" value="1"<?php if ($forum_config['o_smtp_ssl']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['SMTP SSL'] ?></span> <?php echo $lang_admin_settings['SMTP SSL label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_email_smtp_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>
<?php

// Reset counter
$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/settings.php?section=email') ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/settings.php?section=email')) ?>" />
				<input type="hidden" name="mass_mail" value="1" />
			</div>
			<div class="main-subhead">
				<h2 class="hn"><span><?php echo $lang_admin_settings['Mass e-mail'] ?></span></h2>
			</div>
			<div id="req-msg" class="req-warn ct-box error-box">
				<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
			</div>
<?php ($hook = get_hook('aop_fl_email_pre_massmail_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_settings['E-mail addresses legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_fl_email_pre_massmail_subjetc')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld1"><span><?php echo $lang_admin_settings['Mass subject label'] ?><em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_mass_subject" value="<?php echo(isset($_POST['req_subject']) ? forum_htmlencode($_POST['req_subject']) : '') ?>" size="75" maxlength="70" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_email_pre_massmail_massage')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld2"><span><?php echo $lang_admin_settings['Mass massage label'] ?>  <em><?php echo $lang_common['Required'] ?></em></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_mass_message" rows="10" cols="95"><?php echo(isset($_POST['req_message']) ? forum_htmlencode($_POST['req_message']) : '') ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_email_pre_massmail_group')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Mass recipient label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="mass_group">
						<option value="0"><?php echo $lang_admin_settings['All group'] ?></option>
<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id!=2',
		'ORDER BY'	=> 'g.g_id'
	);

	($hook = get_hook('aop_qr_get_group_select')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	while ($groups = $forum_db->fetch_assoc($result))
		echo "\t\t\t\t\t\t\t\t".'<option value="'.$groups['g_id'].'">'.$groups['g_title'].'</option>'."\n";;

?>
						</select></span>
					</div>
				</div>
				<div class="sf-set set3">
					<div class="sf-box frm-short text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_settings['Mass partition label'] ?></span><small><?php echo $lang_admin_settings['Mass partition help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="per_page" size="5" maxlength="5" value="<?php echo(isset($_POST['per_page']) ? forum_htmlencode($_POST['per_page']) : '0') ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_fl_email_pre_massmail_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_admin_settings['Preview'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	}
}
else
{
	($hook = get_hook('aop_new_section')) ? eval($hook) : null;
}

($hook = get_hook('aop_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'footer.php';
