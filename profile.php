<?php
/**
 * Позволяет пользователям просматривать и редактировать информацию о себе.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

require FORUM_ROOT.'include/utf8/substr_replace.php';
require FORUM_ROOT.'include/utf8/ucwords.php';

($hook = get_hook('pf_start')) ? eval($hook) : null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : 'about'; // Default to section "about"
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 2)
	message($lang_common['Bad request']);

$errors = array();

if ($action != 'change_pass' || !isset($_GET['key']))
{
	if (!$forum_user['g_read_board'])
		message($lang_common['No view']);
	else if (!$forum_user['g_view_users'] && ($forum_user['is_guest'] || $forum_user['id'] != $id))
		message($lang_common['No permission']);
}

// Load the profile.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/country.php';


// Fetch info about the user whose profile we're viewing
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_user_title, g.g_moderator',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'groups AS g',
			'ON'		=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id='.$id
);

($hook = get_hook('pf_qr_get_user_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
if (!$forum_db->num_rows($result))
	message($lang_common['Bad request']);

$user = $forum_db->fetch_assoc($result);


if ($action == 'change_pass')
{
	($hook = get_hook('pf_change_pass_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect(forum_link($forum_url['profile'], array($id, 'about')), $lang_common['Cancel redirect']);

	if (isset($_GET['key']))
	{
		$key = $_GET['key'];
		
		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['change_password_key'], array($id, $key)));

		// If the user is already logged in we shouldn't be here :)
		if (!$forum_user['is_guest'])
			message($lang_profile['Pass logout']);

		($hook = get_hook('pf_change_pass_key_supplied')) ? eval($hook) : null;

		if ($key == '' || $key != $user['activate_key'])
			message(sprintf($lang_profile['Pass key bad'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>'));
		else
		{
			if (isset($_POST['form_sent']))
			{
				($hook = get_hook('pf_change_pass_key_form_submitted')) ? eval($hook) : null;

				$new_password1 = forum_trim($_POST['req_new_password1']);
				$new_password2 = forum_trim($_POST['req_new_password2']);

				if (utf8_strlen($new_password1) < 4)
					$errors[] = $lang_profile['Pass too short'];
				else if ($new_password1 != $new_password2)
					$errors[] = $lang_profile['Pass not match'];

				// Did everything go according to plan?
				if (empty($errors))
				{
					$new_password_hash = forum_hash($new_password1, $user['salt']);

					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'password=\''.$new_password_hash.'\', activate_key=NULL',
						'WHERE'		=> 'id='.$id
					);

					($hook = get_hook('pf_change_pass_key_qr_update_password')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);

					($hook = get_hook('pf_change_pass_key_pre_redirect')) ? eval($hook) : null;

					redirect(forum_link($forum_url['index']), $lang_profile['Pass updated']);
				}
			}

			// Is this users own profile
			$forum_page['own_profile'] = ($forum_user['id'] == $id) ? true : false;

			// Setup form
			$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
			$forum_page['form_action'] = forum_link($forum_url['change_password_key'], array($id, $key));

			// Setup breadcrumbs
			$forum_page['crumbs'] = array(
				array($forum_config['o_board_title'], forum_link($forum_url['index'])),
				array(sprintf($lang_profile['Users profile'], $user['username'], $lang_profile['Section about']), forum_link($forum_url['profile'], array($id, 'about'))),
				($forum_page['own_profile']) ? array($lang_profile['Change your password'], forum_link($forum_url['change_password'], $id)) : array(sprintf($lang_profile['Change user password'], forum_htmlencode($user['username'])), forum_link($forum_url['change_password']), $id)
			);

			($hook = get_hook('pf_change_pass_key_pre_header_load')) ? eval($hook) : null;

			$forum_js->file(array('jquery', 'pstrength'));
			$forum_js->code('$(function() {
				$(\'.password\').pstrength();
			});');

			define('FORUM_PAGE', 'profile-changepass');
			require FORUM_ROOT.'header.php';

			// START SUBST - <forum_main>
			ob_start();

			($hook = get_hook('pf_change_pass_key_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
<?php

			// If there were any errors, show them
			if (!empty($errors))
			{
				$forum_page['errors'] = array();
				foreach ($errors as $cur_error)
					$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

				($hook = get_hook('pf_change_pass_key_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Change pass errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

			}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
			</div>
<?php ($hook = get_hook('pf_change_pass_key_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_pass_key_pre_new_password')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['New password'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small>&#160;</small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password1" size="35" class="password" value="<?php echo(isset($_POST['req_new_password1']) ? (forum_htmlencode($_POST['req_new_password1'])) : ''); ?>"/></span><br />
					</div>
				</div>
<?php ($hook = get_hook('pf_change_pass_key_pre_new_password_confirm')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Confirm new password'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['Confirm password help'] ?></small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password2" size="35" value="<?php echo(isset($_POST['req_new_password2']) ? (forum_htmlencode($_POST['req_new_password2'])) : ''); ?>"/></span><br />
					</div>
				</div>
<?php ($hook = get_hook('pf_change_pass_key_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_pass_key_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>

<?php

			($hook = get_hook('pf_change_pass_key_end')) ? eval($hook) : null;

			$tpl_temp = forum_trim(ob_get_contents());
			$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
			ob_end_clean();
			// END SUBST - <forum_main>

			require FORUM_ROOT.'footer.php';
		}
	}
	
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['change_password'], $id));

	// Make sure we are allowed to change this user's password
	if ($forum_user['id'] != $id &&
		$forum_user['g_id'] != FORUM_ADMIN &&
		($forum_user['g_moderator'] != '1' || !$forum_user['g_mod_edit_users'] || !$forum_user['g_mod_change_passwords'] || $user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
		message($lang_common['No permission']);

	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('pf_change_pass_normal_form_submitted')) ? eval($hook) : null;

		$old_password = isset($_POST['req_old_password']) ? forum_trim($_POST['req_old_password']) : '';
		$new_password1 = forum_trim($_POST['req_new_password1']);
		$new_password2 = forum_trim($_POST['req_new_password2']);

		if (utf8_strlen($new_password1) < 4)
			$errors[] = $lang_profile['Pass too short'];
		else if ($new_password1 != $new_password2)
			$errors[] = $lang_profile['Pass not match'];

		$authorized = false;
		if (!empty($user['password']))
		{
			$old_password_hash = forum_hash($old_password, $user['salt']);

			if (($user['password'] == $old_password_hash) || $forum_user['is_admmod'])
				$authorized = true;
		}

		if (!$authorized)
			$errors[] = $lang_profile['Wrong old password'];

		// Did everything go according to plan?
		if (empty($errors))
		{
			$new_password_hash = forum_hash($new_password1, $user['salt']);

			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'password=\''.$new_password_hash.'\'',
				'WHERE'		=> 'id='.$id
			);

			($hook = get_hook('pf_change_pass_normal_qr_update_password')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			if ($forum_user['id'] == $id)
			{
				$cookie_data = @explode('|', base64_decode($_COOKIE[$cookie_name]));

				$expire = ($cookie_data[2] > time() + $forum_config['o_timeout_visit']) ? time() + 1209600 : time() + $forum_config['o_timeout_visit'];
				forum_setcookie($cookie_name, base64_encode($forum_user['id'].'|'.$new_password_hash.'|'.$expire.'|'.sha1($user['salt'].$new_password_hash.forum_hash($expire, $user['salt']))), $expire);
			}

			($hook = get_hook('pf_change_pass_normal_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link($forum_url['profile'], array($id, 'about')), $lang_profile['Pass updated redirect']);
		}
	}

	// Is this users own profile
	$forum_page['own_profile'] = ($forum_user['id'] == $id) ? true : false;

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['change_password'], $id);

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['profile'], array($id, 'about'))),
		($forum_page['own_profile']) ? array($lang_profile['Change your password'], forum_link($forum_url['change_password'], $id)) : array(sprintf($lang_profile['Change user password'], forum_htmlencode($user['username'])), forum_link($forum_url['change_password'], $id))
	);

	($hook = get_hook('pf_change_pass_normal_pre_header_load')) ? eval($hook) : null;

	$forum_js->file(array('jquery', 'pstrength'));
	$forum_js->code('$(function() {
		$(\'.password\').pstrength();
	});');

	define('FORUM_PAGE', 'profile-changepass');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('pf_change_pass_normal_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
<?php

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = get_hook('pf_change_pass_normal_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Change pass errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_profile['Change pass info'] ?></span></h2>

		</div>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']  ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_pass_normal_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_pass_normal_pre_old_password')) ? eval($hook) : null; ?>
<?php if (!$forum_user['is_admmod']): ?>
								<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Old password'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['Old password help'] ?></small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_old_password" size="35" value="<?php echo(isset($_POST['req_old_password']) ? (forum_htmlencode($_POST['req_old_password'])) : ''); ?>"/></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_pass_normal_pre_new_password')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['New password'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small>&#160;</small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password1" size="35" class="password" value="<?php echo(isset($_POST['req_new_password1']) ? (forum_htmlencode($_POST['req_new_password1'])) : ''); ?>"/></span><br />
					</div>
				</div>
<?php ($hook = get_hook('pf_change_pass_normal_pre_new_password_confirm')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Confirm new password'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['Confirm password help'] ?></small></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_password2" size="35" value="<?php echo(isset($_POST['req_new_password2']) ? (forum_htmlencode($_POST['req_new_password2'])) : ''); ?>"/></span><br />
					</div>
				</div>
<?php ($hook = get_hook('pf_change_pass_normal_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_pass_normal_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_change_pass_normal_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}


else if ($action == 'change_email')
{
	// Make sure we are allowed to change this user's e-mail
	if ($forum_user['id'] != $id &&
		$forum_user['g_id'] != FORUM_ADMIN &&
		($forum_user['g_moderator'] != '1' || !$forum_user['g_mod_edit_users'] || $user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
		message($lang_common['No permission']);

	($hook = get_hook('pf_change_email_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect(forum_link($forum_url['profile'], array($id, 'about')), $lang_common['Cancel redirect']);

	if (isset($_GET['key']))
	{
		$key = $_GET['key'];

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['change_email_key'], array($id, $key)));

		($hook = get_hook('pf_change_email_key_supplied')) ? eval($hook) : null;

		if ($key == '' || $key != $user['activate_key'])
			message(sprintf($lang_profile['E-mail key bad'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>'));
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'email=activate_string, activate_string=NULL, activate_key=NULL',
				'WHERE'		=> 'id='.$id
			);

			($hook = get_hook('pf_change_email_key_qr_update_email')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			message($lang_profile['E-mail updated']);
		}
	}
	
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['change_email'], $id));
	
	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('pf_change_email_normal_form_submitted')) ? eval($hook) : null;

		if (forum_hash($_POST['req_password'], $forum_user['salt']) !== $forum_user['password'])
			$errors[] = $lang_profile['Wrong password'];

		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/functions/email.php';

		// Validate the email-address
		$new_email = utf8_strtolower(forum_trim($_POST['req_new_email']));
		if (!is_valid_email($new_email))
			$errors[] = $lang_common['Invalid e-mail'];

		// Check if it's a banned e-mail address
		if (is_banned_email($new_email))
		{
			($hook = get_hook('pf_change_email_normal_banned_email')) ? eval($hook) : null;

			if (!$forum_config['p_allow_banned_email'])
				$errors[] = $lang_profile['Banned e-mail'];
			else if ($forum_config['o_mailing_list'] != '')
			{
				$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/banned_email.tpl'));
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

				$mail_subject = str_replace('<mail_subject>', $lang_common['Banned email notification'], $mail_subject);
				$mail_message = str_replace('<user>', $forum_user['username'], $mail_message);
				$mail_message = str_replace('<new_email>', $new_email, $mail_message);
				$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

				forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
			}
		}

		$dupe_email = is_dupe_email($new_email);
		if ($dupe_email)
		{
			($hook = get_hook('pf_change_email_normal_dupe_email')) ? eval($hook) : null;

			if (!$forum_config['p_allow_dupe_email'])
				$errors[] = $lang_profile['Dupe e-mail'];
			else if ($forum_config['o_mailing_list'] != '')
			{
				$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/dupe_email.tpl'));
				$first_crlf = strpos($mail_tpl, "\n");

				$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

				$mail_subject = str_replace('<mail_subject>', $lang_common['Duplicate email notification'], $mail_subject);
				$mail_message = str_replace('<user>', $forum_user['username'], $mail_message);
				$mail_message = str_replace('<first_user>', implode(', ', $dupe_email), $mail_message);
				$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

				forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
			}
		}

		// Did everything go according to plan?
		if (empty($errors))
		{
			if ($forum_config['o_regs_verify'] != '1')
			{
				// We have no confirmed e-mail so we change e-mail right now
				$query = array(
					'UPDATE'    => 'users',
					'SET'       => 'email=\''.$forum_db->escape($new_email).'\'',
					'WHERE'     => 'id='.$id
				);

				($hook = get_hook('pf_change_email_key_qr_update_email')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				redirect(forum_link($forum_url['user'], $id), $lang_profile['E-mail updated redirect']);
			}
			// We have a confirmed e-mail so we going to send an activation link

			$new_email_key = random_key(8, true);

			// Save new e-mail and activation key
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'activate_string=\''.$forum_db->escape($new_email).'\', activate_key=\''.$new_email_key.'\'',
				'WHERE'		=> 'id='.$id
			);

			($hook = get_hook('pf_change_email_normal_qr_update_email_activation')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Load the "activate e-mail" template
			$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/activate_email.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

			$mail_message = str_replace('<username>', $forum_user['username'], $mail_message);
			$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
			$mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', forum_link($forum_url['change_email_key'], array($id, $new_email_key))), $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

			($hook = get_hook('pf_change_email_normal_pre_activation_email_sent')) ? eval($hook) : null;

			forum_mail($new_email, $mail_subject, $mail_message);

			message(sprintf($lang_profile['Activate e-mail sent'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>'));
		}
	}

	// Is this users own profile
	$forum_page['own_profile'] = ($forum_user['id'] == $id) ? true : false;

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['change_email'], $id);

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup form information
	$forum_page['frm_info'] = '<p class="important"><span>'.$lang_profile['E-mail info'].'</span></p>';

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_profile['Users profile'], $user['username'], $lang_profile['Section about']), forum_link($forum_url['profile'], array($id, 'about'))),
		($forum_page['own_profile']) ? $lang_profile['Change your e-mail'] : sprintf($lang_profile['Change user e-mail'], forum_htmlencode($user['username']))
	);

	($hook = get_hook('pf_change_email_normal_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'profile-changemail');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('pf_change_email_normal_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<?php echo $forum_page['frm_info']."\n" ?>
		</div>
<?php

	// If there were any errors, show them
	if (!empty($errors))
	{
		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = get_hook('pf_change_email_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Change e-mail errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_email_normal_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_email_normal_pre_new_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['New e-mail'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_new_email" size="50" maxlength="80" value="<?php echo(isset($_POST['req_new_email']) ? forum_htmlencode($_POST['req_new_email']) : ''); ?>"/></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_email_normal_pre_password')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Password'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label><br />
						<span class="fld-input"><input type="password" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_password" size="25" value="<?php echo(isset($_POST['req_password']) ? (forum_htmlencode($_POST['req_password'])) : ''); ?>"/></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_email_normal_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_email_normal_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_change_email_normal_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

else if ($action == 'delete_user' || isset($_POST['delete_user_comply']) || isset($_POST['cancel']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['delete_user'], $id));
	
	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect(forum_link($forum_url['profile'], array($id, 'admin')), $lang_common['Cancel redirect']);

	($hook = get_hook('pf_delete_user_selected')) ? eval($hook) : null;

	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	if ($user['g_id'] == FORUM_ADMIN)
		message($lang_profile['Cannot delete admin']);

	if (isset($_POST['delete_user_comply']))
	{
		($hook = get_hook('pf_delete_user_form_submitted')) ? eval($hook) : null;

		if (!defined('FORUM_FUNCTIONS_DELETE_USER'))
			require FORUM_ROOT.'include/functions/delete_user.php';
		delete_user($id, isset($_POST['delete_posts']));

		($hook = get_hook('pf_delete_user_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url['index']), $lang_profile['User delete redirect']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['delete_user'], $id);

	// Setup form information
	$forum_page['frm_info'] = array(
		'<li class="warn"><span>'.$lang_profile['Delete warning'].'</span></li>',
		'<li class="warn"><span>'.$lang_profile['Delete posts info'].'</span></li>'
	);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_profile['Users profile'], $user['username'], $lang_profile['Section about']), forum_link($forum_url['profile'], array($id, 'admin'))),
		array($lang_profile['Delete user'], forum_link($forum_url['delete_user'], $id))
	);

	($hook = get_hook('pf_delete_user_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('pf_delete_user_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<ul class="info-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['frm_info'])."\n" ?>
			</ul>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($forum_page['form_action']) ?>" />
			</div>
<?php ($hook = get_hook('pf_delete_user_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_delete_user_pre_confirm_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="delete_posts" value="1" checked="checked" /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Delete posts'] ?></span> <?php printf($lang_profile['Delete posts label'], forum_htmlencode($user['username'])) ?></label>
					</div>
				</div>
<?php ($hook = get_hook('pf_delete_user_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_delete_user_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="delete_user_comply" value="<?php echo $lang_common['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_delete_user_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}


else if ($action == 'delete_avatar')
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['delete_avatar'], array($id, isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '')));
	
	// Make sure we are allowed to delete this user's avatar
	if ($forum_user['id'] != $id &&
		$forum_user['g_id'] != FORUM_ADMIN &&
		(!$forum_user['g_moderator'] != '1' || !$forum_user['g_mod_edit_users'] || $user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
		message($lang_common['No permission']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('delete_avatar'.$id.$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('pf_delete_avatar_selected')) ? eval($hook) : null;

	if (!defined('FORUM_FUNCTIONS_DELETE_AVATAR'))
		require FORUM_ROOT.'include/functions/delete_avatar.php';

	delete_avatar($id);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'avatar=NULL',
		'WHERE'		=> 'id='.$id
	);

	($hook = get_hook('pf_fl_delete_avatar_qr')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('pf_delete_avatar_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['profile'], array($id, 'avatar')), $lang_profile['Avatar deleted redirect']);
}


else if (isset($_POST['update_group_membership']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['profile'], array($id, 'admin')));

	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	($hook = get_hook('pf_change_group_form_submitted')) ? eval($hook) : null;

	$new_group_id = intval($_POST['group_id']);

	$query = array(
		'SELECT'	=> 'g.g_moderator',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id='.$new_group_id
	);

	($hook = get_hook('pf_change_group_qr_check_new_group_mod')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_group_mod = $forum_db->result($result);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'group_id='.$new_group_id,
		'WHERE'		=> 'id='.$id
	);

	if ($new_group_id = $new_group_mod || $new_group_id == FORUM_ADMIN)
		$query['SET'] .= ', security_ip=\''.$user['registration_ip'].'\'';

	($hook = get_hook('pf_change_group_qr_update_group')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// If the user was a moderator or an administrator (and no longer is), we remove him/her from the moderator list in all forums
	if (($user['g_id'] == FORUM_ADMIN || $user['g_moderator']) && $new_group_id != FORUM_ADMIN && $new_group_mod != '1')
	{
		require FORUM_ROOT.'include/functions/clean_forum_moderators.php';

		clean_forum_moderators();
	}

	($hook = get_hook('pf_change_group_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['profile'], array($id, 'admin')), $lang_profile['Group membership redirect']);
}
else if (isset($_POST['update_forums']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['profile'], array($id, 'admin')));

	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	require FORUM_ROOT.'include/utf8/strcasecmp.php';

	($hook = get_hook('pf_forum_moderators_form_submitted')) ? eval($hook) : null;

	$moderator_in = (isset($_POST['moderator_in'])) ? array_keys($_POST['moderator_in']) : array();

	// Loop through all forums
	$query = array(
		'SELECT'	=> 'f.id, f.moderators',
		'FROM'		=> 'forums AS f'
	);

	($hook = get_hook('pf_forum_moderators_qr_get_all_forum_mods')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	while ($cur_forum = $forum_db->fetch_assoc($result))
	{
		$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

		// If the user should have moderator access (and he/she doesn't already have it)
		if (in_array($cur_forum['id'], $moderator_in) && !in_array($id, $cur_moderators))
		{
			$cur_moderators[$user['username']] = $id;
			uksort($cur_moderators, 'utf8_strcasecmp');
		}
		// If the user shouldn't have moderator access (and he/she already has it)
		else if (!in_array($cur_forum['id'], $moderator_in) && in_array($id, $cur_moderators))
			unset($cur_moderators[$user['username']]);

		$cur_moderators = (!empty($cur_moderators)) ? '\''.$forum_db->escape(serialize($cur_moderators)).'\'' : 'NULL';

		$query = array(
			'UPDATE'	=> 'forums',
			'SET'		=> 'moderators='.$cur_moderators,
			'WHERE'		=> 'id='.$cur_forum['id']
		);

		($hook = get_hook('pf_forum_moderators_qr_update_forum_moderators')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	($hook = get_hook('pf_forum_moderators_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['profile'], array($id, 'admin')), $lang_profile['Moderate forums redirect']);
}

else if (isset($_POST['reputation']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['profile'], array($id, 'admin')));

	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	$rep_enable_adm = (!isset($_POST['rep_enable_adm']) || $_POST['rep_enable_adm'] != '0') ? '1' : '0';

	$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'rep_enable_adm='.$rep_enable_adm,
			'WHERE'		=> 'id='.$id
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	redirect(forum_link($forum_url['profile'], array($id, 'admin')), $lang_profile['Moderate forums redirect']);
}

else if (isset($_POST['ban']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['profile'], array($id, 'admin')));

	if ($forum_user['g_id'] != FORUM_ADMIN && ($forum_user['g_moderator'] != '1' || !$forum_user['g_mod_ban_users']))
		message($lang_common['No permission']);

	($hook = get_hook('pf_ban_user_selected')) ? eval($hook) : null;

	redirect(forum_link('admin/bans.php').'?add_ban='.$id, $lang_profile['Ban redirect']);
}


else if (isset($_POST['form_sent']))
{
	// Make sure we are allowed to edit this user's profile
	if ($forum_user['id'] != $id &&
		$forum_user['g_id'] != FORUM_ADMIN &&
		($forum_user['g_moderator'] != '1' || !$forum_user['g_mod_edit_users'] || $user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
		message($lang_common['No permission']);

	($hook = get_hook('pf_change_details_form_submitted')) ? eval($hook) : null;

	// Extract allowed elements from $_POST['form']
	function extract_elements($allowed_elements)
	{
		$form = array();

		foreach ($_POST['form'] as $key => $value)
		{
			if (in_array($key, $allowed_elements))
				$form[$key] = $value;
		}

		return $form;
	}

	$username_updated = false;

	// Validate input depending on section
	switch ($section)
	{
		case 'identity':
		{
			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['profile'], array($id, 'identity')));

			$form = extract_elements(array('realname', 'sex', 'url', 'location', 'country', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'skype', 'magent', 'vkontakte', 'classmates', 'mirtesen', 'moikrug', 'facebook', 'twitter', 'lastfm'));

			($hook = get_hook('pf_change_details_identity_validation')) ? eval($hook) : null;

			if ($forum_user['is_admmod'])
			{
				// Are we allowed to change usernames?
				if ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && $forum_user['g_mod_rename_users']))
				{
					$form['username'] = forum_trim($_POST['req_username']);
					$old_username = forum_trim($_POST['old_username']);

					// Validate the new username
					if (!defined('FORUM_FUNCTIONS_VALIDATE_USERNAME'))
						require FORUM_ROOT.'include/functions/validate_username.php';

					$errors = array_merge($errors, validate_username($form['username'], $id));

					if ($form['username'] != $old_username)
						$username_updated = true;
				}

				// We only allow administrators to update the post count
				if ($forum_user['g_id'] == FORUM_ADMIN)
					$form['num_posts'] = intval($_POST['num_posts']);
			}

			if ($forum_user['is_admmod'])
			{
				// Validate the email-address
				$form['email'] = utf8_strtolower(forum_trim($_POST['req_email']));

				if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/functions/email.php';

				if (!is_valid_email($form['email']))
					$errors[] = $lang_common['Invalid e-mail'];
				else if (!$forum_config['p_allow_banned_email'] && is_banned_email($form['email']))
					$errors[] = $lang_profile['Banned e-mail'];
				else if (!$forum_config['p_allow_dupe_email'] && $form['email'] != $user['email'] && is_dupe_email($form['email']))
					$errors[] = $lang_profile['Dupe e-mail'];
			}

			if ($forum_user['is_admmod'])
				$form['admin_note'] = forum_trim($_POST['admin_note']);

			if ($forum_user['g_id'] == FORUM_ADMIN)
				$form['title'] = forum_trim($_POST['title']);
			else if ($forum_user['g_set_title'])
			{
				$form['title'] = forum_trim($_POST['title']);

				if ($form['title'] != '')
				{
					// A list of words that the title may not contain
					// If the language is English, there will be some duplicates, but it's not the end of the world
					$forbidden = array('Участник', 'Модератор', 'Администратор', 'Заблокирован', 'Гость', $lang_common['Member'], $lang_common['Moderator'], $lang_common['Administrator'], $lang_common['Banned'], $lang_common['Guest']);

					if (in_array($form['title'], $forbidden))
						$errors[] = $lang_profile['Forbidden title'];
				}
			}

			// Add http:// if the URL doesn't contain it or https:// already
			if ($form['url'] != '' && strpos(utf8_strtolower($form['url']), 'http://') !== 0  && strpos(utf8_strtolower($form['url']), 'https://') !== 0)
				$form['url'] = 'http://'.$form['url'];

			// If the ICQ UIN contains anything other than digits it's invalid
			if ($form['icq'] != '' && !ctype_digit($form['icq']))
				$errors[] = $lang_profile['Bad ICQ'];

			if ($form['vkontakte'] != '')
			{
				if (preg_match('#http://(www.)?vkontakte.ru/(.*)#s', $form['vkontakte'], $matches))
					$form['vkontakte'] = $matches['2'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Vkontakte']);
			}

			if ($form['classmates'] != '' && strpos(strtolower($form['classmates']), 'http://') === 0  && strpos(strtolower($form['classmates']), 'odnoklassniki.ru') === 0)
				$errors[] = $lang_profile['Bad Сlassmates'];

			if ($form['mirtesen'] != '')
			{
				if (preg_match('#http://(www.)?mirtesen.ru/people/([0-9]+)#s', $form['mirtesen'], $matches))
					$form['mirtesen'] = $matches['2'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Mirtesen']);
			}

			if ($form['moikrug'] != '')
			{
				if (preg_match('#http://(.*).moikrug.ru#s', $form['moikrug'], $matches))
					$form['moikrug'] = $matches['1'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Moikrug']);
			}
			if ($form['facebook'] != '')
			{
				if (preg_match('#http://(www.)?facebook.com/profile.php(.+?)id=([0-9]+)#s', $form['facebook'], $matches))
					$form['facebook'] = $matches['3'];
				else if (preg_match('#http://(www.)?facebook.com/(.*)#s', $form['facebook'], $matches))
					$form['facebook'] = $matches['2'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Facebook']);
			}

			if ($form['twitter'] != '')
			{
				if (preg_match('#http://(www.)?twitter.com/(.*)#s', $form['twitter'], $matches))
					$form['twitter'] = $matches['2'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Twitter']);
			}

			if ($form['lastfm'] != '')
			{
				if (preg_match('#http://(www.)?last.fm/user/(.*)#s', $form['lastfm'], $matches))
					$form['lastfm'] = $matches['2'];
				else
					$errors[] = sprintf($lang_profile['Bad url page'], $lang_profile['Last.fm']);
			}

			break;
		}

		case 'settings':
		{
			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['profile'], array($id, 'settings')));

			$form = extract_elements(array('dst', 'timezone', 'language', 'email_setting', 'notify_with_post', 'auto_notify', 'time_format', 'date_format', 'disp_topics', 'disp_posts', 'show_smilies', 'show_img', 'show_img_sig', 'show_avatars', 'show_sig', 'show_bb_panel', 'style', 'pm_long_subject', 'pm_get_mail', 'rep_enable', 'security_ip'));

			($hook = get_hook('pf_change_details_settings_validation')) ? eval($hook) : null;

			$form['dst'] = (isset($form['dst'])) ? 1 : 0;
			$form['time_format'] = (isset($form['time_format'])) ? intval($form['time_format']) : 0;
			$form['date_format'] = (isset($form['date_format'])) ? intval($form['date_format']) : 0;

			$form['timezone'] = (isset($form['timezone'])) ? floatval($form['timezone']) : $forum_config['o_default_timezone'];

			// Validate timezone
			if (($form['timezone'] > 14.0) || ($form['timezone'] < -12.0))
				message($lang_common['Bad request']);


			$form['email_setting'] = intval($form['email_setting']);
			if ($form['email_setting'] < 0 || $form['email_setting'] > 2) $form['email_setting'] = 1;

			if ($forum_config['o_subscriptions'])
			{
				if (!isset($form['notify_with_post']) || $form['notify_with_post'] != '1') $form['notify_with_post'] = '0';
				if (!isset($form['auto_notify']) || $form['auto_notify'] != '1') $form['auto_notify'] = '0';
			}

			// Make sure we got a valid language string
			if (isset($form['language']))
			{
				$form['language'] = preg_replace('#[\.\\\/]#', '', $form['language']);
				if (!file_exists(FORUM_ROOT.'lang/'.$form['language'].'/common.php'))
					message($lang_common['Bad request']);
			}

			if ($form['disp_topics'] != '' && intval($form['disp_topics']) < 3) $form['disp_topics'] = 3;
			if ($form['disp_topics'] != '' && intval($form['disp_topics']) > 75) $form['disp_topics'] = 75;
			if ($form['disp_posts'] != '' && intval($form['disp_posts']) < 3) $form['disp_posts'] = 3;
			if ($form['disp_posts'] != '' && intval($form['disp_posts']) > 75) $form['disp_posts'] = 75;

			if (!isset($form['show_smilies']) || $form['show_smilies'] != '1') $form['show_smilies'] = '0';
			if (!isset($form['show_img']) || $form['show_img'] != '1') $form['show_img'] = '0';
			if (!isset($form['show_img_sig']) || $form['show_img_sig'] != '1') $form['show_img_sig'] = '0';
			if (!isset($form['show_avatars']) || $form['show_avatars'] != '1') $form['show_avatars'] = '0';
			if (!isset($form['show_sig']) || $form['show_sig'] != '1') $form['show_sig'] = '0';
			if (!isset($form['show_bb_panel']) || $form['show_bb_panel'] != '1') $form['show_bb_panel'] = '0';

			// Make sure we got a valid style string
			if (isset($form['style']))
			{
				$form['style'] = preg_replace('#[\.\\\/]#', '', $form['style']);
				if (!file_exists(FORUM_ROOT.'style/'.$form['style'].'/'.$form['style'].'.php'))
					message($lang_common['Bad request']);
			}

			if (!isset($form['pm_long_subject']) || $form['pm_long_subject'] != '1') $form['pm_long_subject'] = '0';
			if (!isset($form['pm_get_mail']) || $form['pm_get_mail'] != '1') $form['pm_get_mail'] = '0';

			if (!isset($form['rep_enable']) || $form['rep_enable'] != '1') $form['rep_enable'] = '0';

			if ($form['security_ip'] == '0')
				$form['security_ip'] = get_remote_address();
			else if ($form['security_ip'] == '1')
			{
				$netmask = (preg_match('/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/', get_remote_address(), $matches) || preg_match('/^([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4})$/', get_remote_address(), $matches)) ? $matches[1].'.'.$matches[2] : '0';
				$form['security_ip'] = $netmask;
			}
			else
				$form['security_ip'] = '0';

			break;
		}

		case 'signature':
		{
			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['profile'], array($id, 'signature')));

			if (!$forum_config['o_signatures'])
				message($lang_profile['Signatures disabled']);

			($hook = get_hook('pf_change_details_signature_validation')) ? eval($hook) : null;

			// Clean up signature from POST
			$form['signature'] = forum_linebreaks(forum_trim($_POST['signature']));

			// Validate signature
			if (utf8_strlen($form['signature']) > $forum_config['p_sig_length'])
				$errors[] = sprintf($lang_profile['Sig too long'], forum_number_format($forum_config['p_sig_length']), forum_number_format(utf8_strlen($form['signature']) - $forum_config['p_sig_length']));
			if (substr_count($form['signature'], "\n") > ($forum_config['p_sig_lines'] - 1))
				$errors[] = sprintf($lang_profile['Sig too many lines'], forum_number_format($forum_config['p_sig_lines']));

			if ($form['signature'] != '' && !$forum_config['p_sig_all_caps'] && is_all_uppercase($form['signature']) && !$forum_user['is_admmod'])
				$form['signature'] = utf8_ucwords(utf8_strtolower($form['signature']));

			// Validate BBCode syntax
			if ($forum_config['p_sig_bbcode'] || $forum_config['o_make_links'])
			{
				if (!defined('FORUM_PARSER_LOADED'))
					require FORUM_ROOT.'include/parser.php';

				$form['signature'] = preparse_bbcode($form['signature'], $errors, true);
			}

			break;
		}

		case 'avatar':
		{
			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['profile'], array($id, 'avatar')));

			if (!$forum_config['o_avatars'])
				message($lang_profile['Avatars disabled']);

			($hook = get_hook('pf_change_details_avatar_validation')) ? eval($hook) : null;

			if (!isset($_FILES['req_file']))
			{
				$errors[] = $lang_profile['No file'];
				break;
			}
			else
				$uploaded_file = $_FILES['req_file'];

			// Make sure the upload went smooth
			if (isset($uploaded_file['error']) && empty($errors))
			{
				switch ($uploaded_file['error'])
				{
					case 1: // UPLOAD_ERR_INI_SIZE
					case 2: // UPLOAD_ERR_FORM_SIZE
						$errors[] = $lang_profile['Too large ini'];
						break;

					case 3: // UPLOAD_ERR_PARTIAL
						$errors[] = $lang_profile['Partial upload'];
						break;

					case 4: // UPLOAD_ERR_NO_FILE
						$errors[] = $lang_profile['No file'];
						break;

					case 6: // UPLOAD_ERR_NO_TMP_DIR
						$errors[] = $lang_profile['No tmp directory'];
						break;

					default: // No error occured, but was something actually uploaded?
						if ($uploaded_file['size'] == 0)
							$errors[] = $lang_profile['No file'];
						break;
				}
			}

			if (is_uploaded_file($uploaded_file['tmp_name']) && empty($errors))
			{
				$allowed_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');

				($hook = get_hook('pf_change_details_avatar_allowed_types')) ? eval($hook) : null;

				if (!in_array($uploaded_file['type'], $allowed_types))
					$errors[] = $lang_profile['Bad type'];
				else
				{
					// Make sure the file isn't too big
					if ($uploaded_file['size'] > $forum_config['o_avatars_size'])
						$errors[] = sprintf($lang_profile['Too large'], forum_number_format($forum_config['o_avatars_size']));
				}

				if (empty($errors))
				{
					// Determine type
					$extension = null;
					if ($uploaded_file['type'] == 'image/gif')
						$extension = '.gif';
					else if ($uploaded_file['type'] == 'image/jpeg' || $uploaded_file['type'] == 'image/pjpeg')
						$extension = '.jpg';
					else
						$extension = '.png';

					$form['avatar'] = str_replace('.', '', $extension);

					($hook = get_hook('pf_change_details_avatar_determine_extension')) ? eval($hook) : null;

					// Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions.
					if (!@move_uploaded_file($uploaded_file['tmp_name'], FORUM_AVATAR_DIR.'/'.$id.'.tmp'))
						$errors[] = sprintf($lang_profile['Move failed'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>');

					if (empty($errors))
					{
						($hook = get_hook('pf_change_details_avatar_modify_size')) ? eval($hook) : null;

						// Now check the width/height
						list($width, $height, $type,) = getimagesize(FORUM_AVATAR_DIR.'/'.$id.'.tmp');
						if (empty($width) || empty($height) || $width > $forum_config['o_avatars_width'] || $height > $forum_config['o_avatars_height'])
						{
							@unlink(FORUM_AVATAR_DIR.'/'.$id.'.tmp');
							$errors[] = sprintf($lang_profile['Too wide or high'], $forum_config['o_avatars_width'], $forum_config['o_avatars_height']);
						}
						else if ($type == 1 && $uploaded_file['type'] != 'image/gif') // Prevent dodgy uploads
						{
							@unlink(FORUM_AVATAR_DIR.'/'.$id.'.tmp');
							$errors[] = $lang_profile['Bad type'];
						}

						($hook = get_hook('pf_change_details_avatar_validate_file')) ? eval($hook) : null;

						if (empty($errors))
						{
							// Delete any old avatars
							if (!defined('FORUM_FUNCTIONS_DELETE_AVATAR'))
								require FORUM_ROOT.'include/functions/delete_avatar.php';
							delete_avatar($id);

							// Put the new avatar in its place
							@rename(FORUM_AVATAR_DIR.'/'.$id.'.tmp', FORUM_AVATAR_DIR.'/'.$id.$extension);
							@chmod(FORUM_AVATAR_DIR.'/'.$id.$extension, 0644);
						}
					}
				}
			}
			else if (empty($errors))
				$errors[] = $lang_profile['Unknown failure'];

			break;
		}

		default:
		{
			($hook = get_hook('pf_change_details_new_section_validation')) ? eval($hook) : null;
			break;
		}
	}

	//$skip_db_update_sections = array('avatar');
	$skip_db_update_sections = array();

	($hook = get_hook('pf_change_details_pre_database_validation')) ? eval($hook) : null;

	// All sections apart from avatar potentially affect the database
	if (!in_array($section, $skip_db_update_sections) && empty($errors))
	{
		($hook = get_hook('pf_change_details_database_validation')) ? eval($hook) : null;

		// Singlequotes around non-empty values and NULL for empty values
		$temp = array();
		foreach ($form as $key => $input)
		{
			$value = ($input !== '') ? '\''.$forum_db->escape($input).'\'' : 'NULL';

			$temp[] = $key.'='.$value;
		}

		// Make sure we have something to update
		if (empty($temp))
			message($lang_common['Bad request']);

		// Run the update
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> implode(',', $temp),
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('pf_change_details_qr_update_user')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// If we changed the username we have to update some stuff
		if ($username_updated)
		{
			($hook = get_hook('pf_change_details_username_changed')) ? eval($hook) : null;

			$query = array(
				'UPDATE'	=> 'posts',
				'SET'		=> 'poster=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'poster_id='.$id
			);

			($hook = get_hook('pf_change_details_qr_update_posts_poster')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'poster=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'poster=\''.$forum_db->escape($old_username).'\''
			);

			($hook = get_hook('pf_change_details_qr_update_topics_poster')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'last_poster=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'last_poster=\''.$forum_db->escape($old_username).'\''
			);

			($hook = get_hook('pf_change_details_qr_update_topics_last_poster')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'forums',
				'SET'		=> 'last_poster=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'last_poster=\''.$forum_db->escape($old_username).'\''
			);

			($hook = get_hook('pf_change_details_qr_update_forums_last_poster')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'ident=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'ident=\''.$forum_db->escape($old_username).'\''
			);

			($hook = get_hook('pf_change_details_qr_update_online_ident')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'posts',
				'SET'		=> 'edited_by=\''.$forum_db->escape($form['username']).'\'',
				'WHERE'		=> 'edited_by=\''.$forum_db->escape($old_username).'\''
			);

			($hook = get_hook('pf_change_details_qr_update_posts_edited_by')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// If the user is a moderator or an administrator we have to update the moderator lists and bans cache
			if ($user['g_id'] == FORUM_ADMIN || $user['g_moderator'])
			{
				require FORUM_ROOT.'include/utf8/strcasecmp.php';

				$query = array(
					'SELECT'	=> 'f.id, f.moderators',
					'FROM'		=> 'forums AS f'
				);

				($hook = get_hook('pf_change_details_qr_get_all_forum_mods')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				while ($cur_forum = $forum_db->fetch_assoc($result))
				{
					$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

					if (in_array($id, $cur_moderators))
					{
						unset($cur_moderators[$old_username]);
						$cur_moderators[$form['username']] = $id;
						uksort($cur_moderators, 'utf8_strcasecmp');

						$query = array(
							'UPDATE'	=> 'forums',
							'SET'		=> 'moderators=\''.$forum_db->escape(serialize($cur_moderators)).'\'',
							'WHERE'		=> 'id='.$cur_forum['id']
						);

						($hook = get_hook('pf_change_details_qr_update_forum_moderators')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
				}

				// Regenerate the bans cache
				if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/cache.php';

				generate_bans_cache();
			}
		}

		($hook = get_hook('pf_change_details_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url['profile'], array($id, $section)), $lang_profile['Profile redirect']);
	}
}

($hook = get_hook('pf_new_action')) ? eval($hook) : null;

if ($user['signature'] != '')
{
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$parsed_signature = parse_signature($user['signature']);
}

// View or edit?
if ($forum_user['id'] != $id &&
	$forum_user['g_id'] != FORUM_ADMIN &&
	($forum_user['g_moderator'] != '1' || !$forum_user['g_mod_edit_users'] || $user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['user'], $id));

	// Setup user identification
	$forum_page['user_ident'] = array();

	($hook = get_hook('pf_view_details_selected')) ? eval($hook) : null;

	if ($user['sex'] == 1)
	{
		$sex_pic = ' <img src="'.$base_url.'/img/style/male.png" width="16" height="16" alt="'.$lang_profile['Male'].'"/>';
		$sex = $lang_profile['Male'];
	}
	else if ($user['sex'] == 2)
	{
		$sex_pic = ' <img src="'.$base_url.'/img/style/female.png" width="16" height="16" alt="'.$lang_profile['Female'].'"/>';
		$sex = $lang_profile['Female'];
	}

	$forum_page['user_ident']['username'] = '<li class="username'.(($user['realname'] =='') ? ' fn nickname' :  ' nickname').'"><strong>'.forum_htmlencode($user['username']).'</strong>'.(($user['sex'] != '0') ? $sex_pic :  '').'</li>';

	$forum_page['user_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($user).'</span></li>';

	if ($forum_config['o_avatars'])
	{
		if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
			require FORUM_ROOT.'include/functions/generate_avatar_markup.php';

		$forum_page['avatar_markup'] = generate_avatar_markup($id, $user['avatar'], $user['email']);

		if (!empty($forum_page['avatar_markup']))
			$forum_page['user_ident']['avatar'] = '<li class="useravatar">'.$forum_page['avatar_markup'].'</li>';
	}

	// Setup user information
	$forum_page['user_info'] = array();

	if ($user['realname'] !='')
		$forum_page['user_info']['realname'] = '<li><span>'.$lang_profile['Realname'].': <strong class="fn">'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['realname']) : $user['realname']).'</strong></span></li>';

	if ($user['sex'] != '0')
		$forum_page['user_info']['sex'] = '<li><span>'.$lang_profile['Sex'].': <strong class="fn">'.$sex.'</strong></span></li>';

	if ($user['location'] !='')
		$forum_page['user_info']['location'] = '<li><span>'.$lang_profile['From'].': <strong> '.forum_htmlencode(($forum_config['o_censoring'] == '1') ? censor_words($user['location']) : $user['location']).'</strong></span></li>';

	if ($user['country'] != '')
		$forum_page['user_info']['country'] = '<li><span>'.$lang_profile['Country'].': <strong>'.$lang_country[$user['country']].'</strong> <img src="'.$base_url.'/img/flags/'.$user['country'].'.gif" alt="'.$lang_country[$user['country']].'"/></span></li>';

	$forum_page['user_info']['registered'] = '<li><span>'.$lang_profile['Registered'].': <strong> '.format_time($user['registered'], 1).'</strong></span></li>';
	$forum_page['user_info']['lastpost'] = '<li><span>'.$lang_profile['Last post'].': <strong> '.format_time($user['last_post']).'</strong></span></li>';
	$forum_page['user_info']['lastvisit'] = '<li><span>'.$lang_profile['Last visit'].': <strong> '.format_time($user['last_visit']).'</strong></span></li>';

	$num_posts_day = $user['num_posts'] > 0 ? substr($user['num_posts']/(floor((time()-$user['registered'])/84600) + (((time()-$user['registered'])%84600)?1:0)), 0, 5) : 0;

	if ($forum_config['o_show_post_count'] || $forum_user['is_admmod'])
		$forum_page['user_info']['posts'] = '<li><span>'.$lang_profile['Posts'].': <strong>'.forum_number_format($user['num_posts']).'</strong> <strong>'.($user['num_posts'] > 0 ? sprintf($lang_profile['Posts in day'], $num_posts_day) : '').'</strong></span></li>';

	// Setup user address
	$forum_page['user_contact'] = array();

	if ($user['email_setting'] == '0' && !$forum_user['is_guest'] && $forum_user['g_send_email'])
		$forum_page['user_contact']['email'] = '<li><span class="prof email">'.$lang_profile['E-mail'].': <a href="mailto:'.forum_htmlencode($user['email']).'" class="email">'.forum_htmlencode(($forum_config['o_censoring'] == '1' ? censor_words($user['email']) : $user['email'])).'</a></span></li>';

	if ($user['email_setting'] != '2' && !$forum_user['is_guest'] && $forum_user['g_send_email'])
		$forum_page['user_contact']['forum-mail'] = '<li><span class="prof email">'.$lang_profile['E-mail'].': <a href="'.forum_link($forum_url['email'], $id).'">'.$lang_profile['Send forum e-mail'].'</a></span></li>';

	if (!$forum_user['is_guest'] && $forum_user['id'] != $user['id'])
		$forum_page['user_contact']['pm']= '<li><span class="prof pm"><a class="contact" href="'.forum_link($forum_url['pm_post'], $id).'">'.$lang_profile['Send PM'].'</a></span></li>';

	if ($user['url'] != '')
	{
		if ($forum_config['o_censoring'])
			$user['url'] = censor_words($user['url']);

		$forum_page['user_contact']['website'] = '<li><span class="prof website">'.$lang_profile['Website'].': <a href="'.forum_link('click.php').'?'.forum_htmlencode($user['url']).'" class="external url" rel="me" onclick="window.open(this.href); return false">'.forum_htmlencode($user['url']).'</a></span></li>';
	}

	if ($user['jabber'] != '')
		$forum_page['user_contact']['jabber'] = '<li><span class="prof jabber">'.$lang_profile['Jabber'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['jabber']) : $user['jabber']).'</strong></span></li>';
	if ($user['icq'] != '')
		$forum_page['user_contact']['icq'] = '<li><span class="prof icq">'.$lang_profile['ICQ'].': <strong><a href="'.forum_link('click.php').'?http://icq.com/people/'.forum_htmlencode($user['icq']).'" class="external url" rel="me" onclick="window.open(this.href); return false">'.forum_htmlencode($user['icq']).'</a></strong></span></li>';
	if ($user['msn'] != '')
		$forum_page['user_contact']['msn'] = '<li><span class="prof msn">'.$lang_profile['MSN'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['msn']) : $user['msn']).'</strong></span></li>';
	if ($user['aim'] != '')
		$forum_page['user_contact']['aim'] = '<li><span class="prof" aim">'.$lang_profile['AOL IM'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['aim']) : $user['aim']).'</strong></span></li>';
	if ($user['yahoo'] != '')
		$forum_page['user_contact']['yahoo'] = '<li><span class="prof yahoo">'.$lang_profile['Yahoo'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['yahoo']) : $user['yahoo']).'</strong></span></li>';
	if ($user['skype'] != '')
		$forum_page['user_contact']['skype'] = '<li><span class="prof skype">'.$lang_profile['Skype'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['skype']) : $user['skype']).'</strong></span></li>';
	if ($user['magent'] != '')
		$forum_page['user_contact']['magent'] = '<li><span class="prof magent">'.$lang_profile['Mail Agent'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['magent']) : $user['magent']).'</strong></span></li>';
	if ($user['vkontakte'] != '')
		$forum_page['user_contact']['vkontakte'] = '<li><span class="prof vkontakte"><a href="'.forum_link('click.php').'?http://vkontakte.ru/'.forum_htmlencode($user['vkontakte']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Vkontakte'].'</a></span></li>';
	if ($user['classmates'] != '')
		$forum_page['user_contact']['classmates'] = '<li><span class="prof classmates"><a href="'.forum_link('click.php').'?'.forum_htmlencode($user['classmates']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Сlassmates'].'</a></span></li>';
	if ($user['mirtesen'] != '')
		$forum_page['user_contact']['mirtesen'] = '<li><span class="prof mirtesen"><a href="'.forum_link('click.php').'?http://mirtesen.ru/people/'.forum_htmlencode($user['mirtesen']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Mirtesen'].'</a></span></li>';
	if ($user['moikrug'] != '')
		$forum_page['user_contact']['moikrug'] = '<li><span class="prof moikrug"><a href="'.forum_link('click.php').'?http://'.forum_htmlencode($user['moikrug']).'.moikrug.ru/" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Moikrug'].'</a></span></li>';
	if ($user['facebook'] != '')
	{
		$facebook_url = preg_match('([0-9])', $user['facebook'], $matches) ? 'profile.php?id=' : '';
		$forum_page['user_contact']['facebook'] = '<li><span class="prof facebook"><a href="'.forum_link('click.php').'?http://facebook.com/'.$facebook_url.forum_htmlencode($user['facebook']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Facebook'].'</a></span></li>';
	}
	if ($user['twitter'] != '')
		$forum_page['user_contact']['twitter'] = '<li><span class="prof twitter"><a href="'.forum_link('click.php').'?http://twitter.com/'.forum_htmlencode($user['twitter']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Twitter'].'</a></span></li>';
	if ($user['lastfm'] != '')
		$forum_page['user_contact']['lastfm'] = '<li><span class="prof lastfm"><a href="'.forum_link('click.php').'?http://last.fm/user/'.forum_htmlencode($user['lastfm']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Last.fm'].'</a></span></li>';

	// Setup signature demo
	if ($forum_config['o_signatures'] && isset($parsed_signature))
		$forum_page['sig_demo'] = $parsed_signature;

	// Setup search links
	if ($forum_user['g_search'])
	{
		$forum_page['user_activity'] = array();
		$forum_page['user_activity']['search_posts'] = '<span'.(empty($forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['search_user_posts'], $id).'">'.sprintf($lang_profile['View user posts'], forum_htmlencode($user['username'])).'</a></span>';
		$forum_page['user_activity']['search_topics'] = '<span'.(empty($forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['search_user_topics'], $id).'">'.sprintf($lang_profile['View user topics'], forum_htmlencode($user['username'])).'</a></span>';
	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id))
	);

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	($hook = get_hook('pf_view_details_pre_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'profile');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	($hook = get_hook('pf_view_details_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
<?php ($hook = get_hook('pf_view_details_pre_user_info')) ? eval($hook) : null; ?>
		<div class="profile ct-group data-group vcard">
<?php ($hook = get_hook('pf_view_details_pre_user_ident_info')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<ul class="user-ident ct-legend">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_ident']) ?>
					</ul>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_info'])."\n" ?>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('pf_view_details_pre_user_contact_info')) ? eval($hook) : null; ?>
<?php if (!empty($forum_page['user_contact'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo $lang_profile['Contact info'] ?></span></h3>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_contact'])."\n" ?>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('pf_view_details_pre_user_activity_info')) ? eval($hook) : null; ?>
<?php endif; if (!empty($forum_page['user_activity'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo $lang_profile['Posts and topics'] ?></span></h3>
					<p class="options"><?php echo implode(' ', $forum_page['user_activity']) ?></p>
				</div>
			</div>
<?php ($hook = get_hook('pf_view_details_pre_user_sig_info')) ? eval($hook) : null; ?>
<?php endif; if (isset($forum_page['sig_demo'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo $lang_profile['Current signature'] ?></span></h3>
					<div class="sig-demo"><?php echo $forum_page['sig_demo']."\n" ?></div>
				</div>
			</div>
<?php endif; ?>		</div>
<?php ($hook = get_hook('pf_view_details_user_info_end')) ? eval($hook) : null; ?>
	</div>
<?php

	($hook = get_hook('pf_view_details_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}


else
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id))
	);

	// Is this users own profile
	$forum_page['own_profile'] = ($forum_user['id'] == $id) ? true : false;

	// Setup navigation menu
	$forum_page['main_menu'] = array();
	$forum_page['main_menu']['about'] = '<li class="first-item'.(($section == 'about')  ? ' active' : '').'"><a href="'.forum_link($forum_url['profile'], array($id, 'about')).'#brd-crumbs-top"><span>'.$lang_profile['Section about'].'</span></a></li>';
	$forum_page['main_menu']['identity'] = '<li'.(($section == 'identity')  ? ' class="active"' : '').'><a href="'.forum_link($forum_url['profile'], array($id, 'identity')).'#brd-crumbs-top"><span>'.$lang_profile['Section identity'].'</span></a></li>';
	$forum_page['main_menu']['settings'] = '<li'.(($section == 'settings') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['profile'], array($id, 'settings')).'#brd-crumbs-top"><span>'.$lang_profile['Section settings'].'</span></a></li>';

	if ($forum_config['o_signatures'])
		$forum_page['main_menu']['signature'] = '<li'.(($section == 'signature') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['profile'], array($id, 'signature')).'#brd-crumbs-top"><span>'.$lang_profile['Section signature'].'</span></a></li>';

	if ($forum_config['o_avatars'])
		$forum_page['main_menu']['avatar'] = '<li'.(($section == 'avatar') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['profile'], array($id, 'avatar')).'#brd-crumbs-top"><span>'.$lang_profile['Section avatar'].'</span></a></li>';

	if ($forum_user['id'] == $id)
		$forum_page['main_menu']['pm'] = '<li'.(($section == 'pm') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['pm'], 'inbox').'#brd-crumbs-top"><span>'.$lang_profile['Private messages'].'</span></a></li>';

	if ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && $forum_user['g_mod_ban_users'] && !$forum_page['own_profile']))
		$forum_page['main_menu']['admin'] = '<li'.(($section == 'admin') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['profile'], array($id, 'admin')).'#brd-crumbs-top"><span>'.$lang_profile['Section admin'].'</span></a></li>';

	($hook = get_hook('pf_change_details_modify_main_menu')) ? eval($hook) : null;
	// End navigation menu
 

	if ($section == 'about')
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array(sprintf($lang_profile['Section about']), forum_link($forum_url['profile'], array($id, 'about'))),
		);

		// Check for use of incorrect URLs
		confirm_current_url(isset($_GET['section']) ? forum_link($forum_url['profile'], array($id, 'about')) : forum_link($forum_url['user'], $id));
		
		// Setup user identification
		$forum_page['user_ident'] = array();

		($hook = get_hook('pf_change_details_about_selected')) ? eval($hook) : null;

		if ($user['sex'] == 1)
		{
			$sex_pic = ' <img src="'.$base_url.'/img/style/male.png" width="16" height="16" alt="'.$lang_profile['Male'].'"/>';
			$sex = $lang_profile['Male'];
		}
		else if ($user['sex'] == 2)
		{
			$sex_pic = ' <img src="'.$base_url.'/img/style/female.png" width="16" height="16" alt="'.$lang_profile['Female'].'"/>';
			$sex = $lang_profile['Female'];
		}

		$forum_page['user_ident']['username'] = '<li class="username'.(($user['realname'] =='') ? ' fn nickname' :  ' nickname').'"><strong>'.forum_htmlencode($user['username']).'</strong>'.(($user['sex'] != '0') ? $sex_pic :  '').'</li>';

		$forum_page['user_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($user).'</span></li>';

		if ($forum_config['o_avatars'])
		{
			if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
				require FORUM_ROOT.'include/functions/generate_avatar_markup.php';

			$forum_page['avatar_markup'] = generate_avatar_markup($id, $user['avatar'], $user['email']);

			if (!empty($forum_page['avatar_markup']))
				$forum_page['user_ident']['avatar'] = '<li class="useravatar">'.$forum_page['avatar_markup'].'</li>';
		}

		// Create array for private information
		$forum_page['user_private'] = array();

		// Setup user information
		$forum_page['user_info'] = array();

		if ($user['realname'] !='')
			$forum_page['user_info']['realname'] = '<li><span>'.$lang_profile['Realname'].': <strong class="fn">'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['realname']) : $user['realname']).'</strong></span></li>';

		if ($user['sex'] != '0')
			$forum_page['user_info']['sex'] = '<li><span>'.$lang_profile['Sex'].': <strong class="fn">'.$sex.'</strong></span></li>';

		if ($user['location'] != '')
			$forum_page['user_info']['location'] = '<li><span>'.$lang_profile['From'].': <strong> '.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['location']) : $user['location']).'</strong></span></li>';

		if ($user['country'] != '')
			$forum_page['user_info']['country'] = '<li><span>'.$lang_profile['Country'].': <strong>'.$lang_country[$user['country']].'</strong> <img src="'.$base_url.'/img/flags/'.$user['country'].'.gif" alt="'.$lang_country[$user['country']].'"/></span></li>';

		$forum_page['user_info']['registered'] = '<li><span>'.$lang_profile['Registered'].': <strong> '.format_time($user['registered'], 1).'</strong></span></li>';
		$forum_page['user_info']['lastpost'] = '<li><span>'.$lang_profile['Last post'].': <strong> '.format_time($user['last_post']).'</strong></span></li>';
		$forum_page['user_info']['lastvisit'] = '<li><span>'.$lang_profile['Last visit'].': <strong> '.format_time($user['last_visit']).'</strong></span></li>';

		$num_posts_day = $user['num_posts'] > 0 ? substr($user['num_posts']/(floor((time()-$user['registered'])/84600) + (((time()-$user['registered'])%84600)?1:0)), 0, 5) : 0;

 		if ($forum_config['o_show_post_count'] || $forum_user['is_admmod'])
			$forum_page['user_info']['posts'] = '<li><span>'.$lang_profile['Posts'].': <strong>'.forum_number_format($user['num_posts']).'</strong> <strong>'.($user['num_posts'] > 0 ? sprintf($lang_profile['Posts in day'], $num_posts_day) : '').'</strong></span></li>';
		else
			$forum_page['user_private']['posts'] = '<li><span>'.$lang_profile['Posts'].': <strong>'.forum_number_format($user['num_posts']).'</strong> <strong>'.($user['num_posts'] > 0 ? sprintf($lang_profile['Posts in day'], $num_posts_day) : '').'</strong></span></li>';

			
			
		if ($forum_user['is_admmod'] && $user['admin_note'] != '')
			$forum_page['user_private']['note'] = '<li><span>'.$lang_profile['Note'].': <strong>'.forum_htmlencode($user['admin_note']).'</strong></span></li>';

		// Setup user address
		$forum_page['user_contact'] = array();

		if (($user['email_setting'] == '0' && !$forum_user['is_guest']) && $forum_user['g_send_email'])
			$forum_page['user_contact']['email'] = '<li><span>'.$lang_profile['E-mail'].': <a href="mailto:'.forum_htmlencode($user['email']).'" class="email">'.forum_htmlencode(($forum_config['o_censoring'] ? censor_words($user['email']) : $user['email'])).'</a></span></li>';
		else if ($forum_page['own_profile'] || $forum_user['is_admmod'])
				$forum_page['user_private']['email'] = '<li><span class="prof email">'.$lang_profile['E-mail'].': <a href="mailto:'.forum_htmlencode($user['email']).'" class="email-i">'.forum_htmlencode(($forum_config['o_censoring'] ? censor_words($user['email']) : $user['email'])).'</a></span></li>';

		if ($user['email_setting'] != '2')
			$forum_page['user_contact']['forum-mail'] = '<li><span class="prof email">'.$lang_profile['E-mail'].': <a href="'.forum_link($forum_url['email'], $id).'">'.$lang_profile['Send forum e-mail'].'</a></span></li>';
		else if ($forum_user['id'] == $id || ($forum_user['is_admmod'] && $user['email_setting'] == '2'))
			$forum_page['user_private']['forum-mail'] = '<li><span class="prof email">'.$lang_profile['E-mail'].': <a href="'.forum_link($forum_url['email'], $id).'">'.$lang_profile['Send forum e-mail'].'</a></span></li>';

		if (!$forum_user['is_guest'] && $forum_user['id'] != $user['id'])
			$forum_page['user_contact']['pm']= '<li><span class="prof pm"><a class="contact" href="'.forum_link($forum_url['pm_post'], $id).'">'.$lang_profile['Send PM'].'</a></span></li>';

		if ($user['url'] != '')
		{
			$user['url'] = forum_htmlencode($user['url']);

			if ($forum_config['o_censoring'])
				$user['url'] = censor_words($user['url']);

			$forum_page['url'] = '<a href="'.forum_link('click.php').'?'.$user['url'].'" class="external url" rel="me" onclick="window.open(this.href); return false">'.$user['url'].'</a>';
			$forum_page['user_contact']['website'] = '<li><span class="prof website">'.$lang_profile['Website'].': '.$forum_page['url'].'</span></li>';
		}

		if ($forum_user['is_admmod'])
			$forum_page['user_private']['ip']= '<li><span class="prof ip">'.$lang_profile['IP'].': <a href="'.forum_link($forum_url['get_host'], forum_htmlencode($user['registration_ip'])).'">'.forum_htmlencode($user['registration_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext='.forum_htmlencode($user['registration_ip']).'&amp;do_search=Search" onclick="window.open(this.href); return false">Whois</a></span></li>';

		// Setup user messaging
		if ($user['jabber'] != '')
			$forum_page['user_contact']['jabber'] = '<li><span class="prof jabber">'.$lang_profile['Jabber'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['jabber']) : $user['jabber']).'</strong></span></li>';
		if ($user['icq'] != '')
			$forum_page['user_contact']['icq'] = '<li><span class="prof icq">'.$lang_profile['ICQ'].': <strong><a href="'.forum_link('click.php').'?http://icq.com/people/'.forum_htmlencode($user['icq']).'" class="external url" rel="me" onclick="window.open(this.href); return false">'.forum_htmlencode($user['icq']).'</a></strong></span></li>';
		if ($user['msn'] != '')
			$forum_page['user_contact']['msn'] = '<li><span class="prof msn">'.$lang_profile['MSN'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['msn']) : $user['msn']).'</strong></span></li>';
		if ($user['aim'] != '')
			$forum_page['user_contact']['aim'] = '<li><span class="prof aim">'.$lang_profile['AOL IM'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['aim']) : $user['aim']).'</strong></span></li>';
		if ($user['yahoo'] != '')
			$forum_page['user_contact']['yahoo'] = '<li><span class="prof yahoo">'.$lang_profile['Yahoo'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['yahoo']) : $user['yahoo']).'</strong></span></li>';
		if ($user['skype'] != '')
			$forum_page['user_contact']['skype'] = '<li><span class="prof skype">'.$lang_profile['Skype'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['skype']) : $user['skype']).'</strong></span></li>';
		if ($user['magent'] != '')
			$forum_page['user_contact']['magent'] = '<li><span class="prof magent">'.$lang_profile['Mail Agent'].': <strong>'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($user['magent']) : $user['magent']).'</strong></span></li>';
		if ($user['vkontakte'] != '')
			$forum_page['user_contact']['vkontakte'] = '<li><span class="prof vkontakte"><a href="'.forum_link('click.php').'?http://vkontakte.ru/'.forum_htmlencode($user['vkontakte']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Vkontakte'].'</a></span></li>';
		if ($user['classmates'] != '')
			$forum_page['user_contact']['classmates'] = '<li><span class="prof classmates"><a href="'.forum_link('click.php').'?'.forum_htmlencode($user['classmates']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Сlassmates'].'</a></span></li>';
		if ($user['mirtesen'] != '')
			$forum_page['user_contact']['mirtesen'] = '<li><span class="prof mirtesen"><a href="'.forum_link('click.php').'?http://mirtesen.ru/people/'.forum_htmlencode($user['mirtesen']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Mirtesen'].'</a></span></li>';
		if ($user['moikrug'] != '')
			$forum_page['user_contact']['moikrug'] = '<li><span class="prof moikrug"><a href="'.forum_link('click.php').'?http://'.forum_htmlencode($user['moikrug']).'.moikrug.ru/" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Moikrug'].'</a></span></li>';
		if ($user['facebook'] != '')
		{
			$facebook_url = preg_match('([0-9])', $user['facebook'], $matches) ? 'profile.php?id=' : '';
			$forum_page['user_contact']['facebook'] = '<li><span class="prof facebook"><a href="'.forum_link('click.php').'?http://facebook.com/'.$facebook_url.forum_htmlencode($user['facebook']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Facebook'].'</a></span></li>';
		}
		if ($user['twitter'] != '')
			$forum_page['user_contact']['twitter'] = '<li><span class="prof twitter"><a href="'.forum_link('click.php').'?http://twitter.com/'.forum_htmlencode($user['twitter']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Twitter'].'</a></span></li>';
		if ($user['lastfm'] != '')
			$forum_page['user_contact']['lastfm'] = '<li><span class="prof lastfm"><a href="'.forum_link('click.php').'?http://last.fm/user/'.forum_htmlencode($user['lastfm']).'" onclick="window.open(this.href); return false" rel="nofollow">'.$lang_profile['Last.fm'].'</a></span></li>';

		// Setup signature demo
		if ($forum_config['o_signatures'] && isset($parsed_signature))
			$forum_page['sig_demo'] = $parsed_signature;

		// Setup search links
		if ($forum_user['g_search'] || $forum_user['is_admmod'])
		{
			$forum_page['user_activity'] = array();
			$forum_page['user_activity']['search_posts'] = '<span'.(empty($forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['search_user_posts'], $id).'">'.(($forum_page['own_profile']) ? $lang_profile['View your posts'] : sprintf($lang_profile['View user posts'], forum_htmlencode($user['username']))).'</a></span>';
			$forum_page['user_activity']['search_topics'] = '<span'.(empty($forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['search_user_topics'], $id).'">'.(($forum_page['own_profile']) ? $lang_profile['View your topics'] : sprintf($lang_profile['View user topics'], forum_htmlencode($user['username']))).'</a></span>';
		}

		if (($forum_page['own_profile'] || $forum_user['g_id'] == FORUM_ADMIN) && $forum_config['o_subscriptions'])
			 $forum_page['user_activity']['search_subs'] = '<span'.(empty($forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['search_subscriptions'], $id).'">'.(($forum_page['own_profile']) ? $lang_profile['View your subscriptions'] : sprintf($lang_profile['View user subscriptions'], forum_htmlencode($user['username']))).'</a></span>';

		// Setup user options
		$forum_page['user_options'] = array();

		if ($forum_page['own_profile'] || $forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && $forum_user['g_mod_change_passwords']))
			$forum_page['user_options']['change_password'] = '<span'.(empty($forum_page['user_options']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['change_password'], $id).'">'.(($forum_page['own_profile']) ? $lang_profile['Change your password'] : sprintf($lang_profile['Change user password'], forum_htmlencode($user['username']))).'</a></span>';

		if (!$forum_user['is_admmod'])
			$forum_page['user_options']['change_email'] = '<span'.(empty($forum_page['user_options']) ? ' class="first-item"' : '').'><a href="'.forum_link($forum_url['change_email'], $id).'">'.(($forum_page['own_profile']) ? $lang_profile['Change your e-mail'] : sprintf($lang_profile['Change user e-mail'], forum_htmlencode($user['username']))).'</a></span>';

		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

		($hook = get_hook('pf_change_details_about_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-about');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_about_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_user['id'] == $id) ? $lang_profile['Profile welcome'] : $lang_profile['Profile welcome user'], forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<p class="content-options options"><?php echo implode(' ', $forum_page['user_options']) ?></p>
<?php ($hook = get_hook('pf_change_details_about_pre_user_info')) ? eval($hook) : null; ?>
		<div class="profile ct-group data-group vcard">
<?php ($hook = get_hook('pf_change_details_about_pre_user_ident_info')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<ul class="user-ident ct-legend">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_ident']) ?>
					</ul>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_info'])."\n" ?>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('pf_change_details_about_pre_user_contact_info')) ? eval($hook) : null; ?>
<?php if (!empty($forum_page['user_contact'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Contact info'] ?></span></h4>
					<ul class="data-box">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_contact'])."\n" ?>
					</ul>
				</div>
			</div>
<?php endif; ($hook = get_hook('pf_change_details_about_pre_user_activity_info')) ? eval($hook) : null; ?>
<?php if (!empty($forum_page['user_activity'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Posts and topics'] ?></span></h4>
					<p class="options"><?php echo implode(' ', $forum_page['user_activity']) ?></p>
				</div>
			</div>
<?php endif; ($hook = get_hook('pf_change_details_about_pre_user_sig_info')) ? eval($hook) : null; ?>
<?php if (isset($forum_page['sig_demo'])): ?>			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Current signature'] ?></span></h4>
					<div class="sig-demo"><?php echo $forum_page['sig_demo'] ?></div>
				</div>
			</div>
<?php endif; ?>
<?php ($hook = get_hook('pf_change_details_about_pre_user_private_info')) ? eval($hook) : null; ?>
<?php if (!empty($forum_page['user_private'])): ?>			<div id="private-profile" class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo $lang_profile['Private info'] ?></span></h3>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['user_private'])."\n" ?>
					</ul>
				</div>
			</div>
<?php endif; ?>
		</div>
<?php ($hook = get_hook('pf_change_details_about_user_info_end')) ? eval($hook) : null; ?>
	</div>
<?php

		($hook = get_hook('pf_change_details_about_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	else if ($section == 'identity')
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array($lang_profile['Section identity'],forum_link($forum_url['profile'], array($id, 'identity')))
		);

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['profile'], array($id, 'identity')));
		
		// Setup the form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['profile'], array($id, 'identity'));

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
		);

		if ($forum_user['is_admmod'] && ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['g_mod_rename_users']))
			$forum_page['hidden_fields']['old_username'] = '<input type="hidden" name="old_username" value="'.forum_htmlencode($user['username']).'" />';

		// Does the form have required fields
		$forum_page['has_required'] = ((($forum_user['is_admmod'] && ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['g_mod_rename_users'])) || $forum_user['is_admmod']) ? true : false);

		($hook = get_hook('pf_change_details_identity_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-identity');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_identity_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Identity welcome'] : $lang_profile['Identity welcome user'], forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

		// If there were any errors, show them
		if (!empty($errors))
		{
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('pf_change_details_identity_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

		}

if ($forum_page['has_required']): ?>		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
<?php endif; ?>		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php if ($forum_page['has_required']): ($hook = get_hook('pf_change_details_identity_pre_req_info_fieldset')) ? eval($hook) : null; ?>			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_common['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_username')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod'] && ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['g_mod_rename_users'])): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Username'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['Username help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_username" value="<?php echo(isset($_POST['req_username']) ? forum_htmlencode($_POST['req_username']) : forum_htmlencode($user['username'])) ?>" size="35" maxlength="25" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_email')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['E-mail'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['E-mail help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_email" value="<?php echo(isset($_POST['req_email']) ? forum_htmlencode($_POST['req_email']) : forum_htmlencode($user['email'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_req_info_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_req_info_fieldset_end')) ? eval($hook) : null; ?>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_personal_fieldset')) ? eval($hook) : null; ?><?php $forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Personal legend'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_realname')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Realname'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[realname]" value="<?php echo(isset($form['realname']) ? forum_htmlencode($form['realname']) : forum_htmlencode($user['realname'])) ?>" size="35" maxlength="40" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_sex')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Sex'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[sex]">
							<option value="1"<?php if ($user['sex'] == 1) echo ' selected="selected"' ?>><?php echo $lang_profile['Male'] ?></option>
							<option value="2"<?php if ($user['sex'] == 2) echo ' selected="selected"' ?>><?php echo $lang_profile['Female'] ?></option>
							<option value="0"<?php if ($user['sex'] == 0) echo ' selected="selected"' ?>><?php echo $lang_profile['Do not show'] ?></option>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_title')) ? eval($hook) : null; ?>
<?php if ($forum_user['g_set_title']): ?>				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Title'] ?></span><small><?php echo $lang_profile['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="title" value="<?php echo(isset($_POST['title']) ? forum_htmlencode($_POST['title']) : forum_htmlencode($user['title'])) ?>" size="35" maxlength="50" /></span><br />
					</div>
				</div>
<?php endif; ?>
<?php ($hook = get_hook('pf_change_details_identity_pre_location')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Location'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[location]" value="<?php echo((isset($form['location']) ? forum_htmlencode($form['location']) : forum_htmlencode($user['location']))) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_country')) ? eval($hook) : null; ?>
<?php

	foreach ($lang_country as $country_text => $country_name)
		$country_option[] = '<option value="'.$country_text.'"'.($user['country'] == $country_text ? ' selected="selected"' : '').'>'.$country_name.'</option>';
	
?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Country'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[country]">
							<option value=""<?php if ($user['country'] == '') echo ' selected="selected"' ?>><?php echo $lang_profile['Do not show'] ?></option>
							<?php echo implode("\n\t\t\t\t\t\t\t", $country_option) ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_num_posts')) ? eval($hook) : null; ?>
<?php if ($forum_user['g_id'] == FORUM_ADMIN): ?>				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Edit count'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="num_posts" value="<?php echo $user['num_posts'] ?>" size="8" maxlength="8" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_admin_note')) ? eval($hook) : null; ?>
<?php if ($forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Admin note'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="admin_note" value="<?php echo(isset($_POST['admin_note']) ? forum_htmlencode($_POST['admin_note']) : forum_htmlencode($user['admin_note'])) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_personal_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_personal_fieldset_end')) ? eval($hook) : null; ?><?php $forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Contact legend'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_url')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="website"><?php echo $lang_profile['Website'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[url]" value="<?php echo(isset($form['url']) ? forum_htmlencode($form['url']) : forum_htmlencode($user['url'])) ?>" size="35" maxlength="80" class="website inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_jabber')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="jabber"><?php echo $lang_profile['Jabber'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[jabber]" value="<?php echo(isset($form['jabber']) ? forum_htmlencode($form['jabber']) : forum_htmlencode($user['jabber'])) ?>" size="35" maxlength="80" class="jabber inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_icq')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="icq"><?php echo $lang_profile['ICQ'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[icq]" value="<?php echo(isset($form['icq']) ? forum_htmlencode($form['icq']) : $user['icq']) ?>" size="12" maxlength="12" class="icq inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_msn')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="msn"><?php echo $lang_profile['MSN'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[msn]" value="<?php echo(isset($form['msn']) ? forum_htmlencode($form['msn']) : forum_htmlencode($user['msn'])) ?>" size="35" maxlength="80" class="msn inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_aim')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="aim"><?php echo $lang_profile['AOL IM'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[aim]" value="<?php echo(isset($form['aim']) ? forum_htmlencode($form['aim']) : forum_htmlencode($user['aim'])) ?>" size="20" maxlength="30" class="aim inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_yahoo')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="yahoo"><?php echo $lang_profile['Yahoo'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[yahoo]" value="<?php echo(isset($form['yahoo']) ? forum_htmlencode($form['yahoo']) : forum_htmlencode($user['yahoo'])) ?>" size="20" maxlength="30" class="yahoo inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_skype')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="skype"><?php echo $lang_profile['Skype'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[skype]" value="<?php echo(isset($form['skype']) ? forum_htmlencode($form['skype']) : forum_htmlencode($user['skype'])) ?>" size="20" maxlength="30" class="skype inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_magent')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="magent"><?php echo $lang_profile['Mail Agent'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[magent]" value="<?php echo(isset($form['magent']) ? forum_htmlencode($form['magent']) : forum_htmlencode($user['magent'])) ?>" size="35" maxlength="30" class="magent inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_contact_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_contact_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_vkontakte')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="vkontakte"><?php echo $lang_profile['Vkontakte'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[vkontakte]" value="<?php echo(isset($form['vkontakte']) && empty($form['vkontakte']) ? forum_htmlencode($form['vkontakte']) : (empty($user['vkontakte']) ? '' : 'http://vkontakte.ru/'.forum_htmlencode($user['vkontakte']))) ?>" size="35" maxlength="30" class="vkontakte inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_classmates')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="classmates"><?php echo $lang_profile['Сlassmates'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[classmates]" value="<?php echo(isset($form['classmates']) && empty($form['classmates']) ? forum_htmlencode($form['classmates']) : forum_htmlencode($user['classmates'])) ?>" size="35" maxlength="30" class="classmates inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_mirtesen')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="mirtesen"><?php echo $lang_profile['Mirtesen'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[mirtesen]" value="<?php echo(isset($form['mirtesen']) && empty($form['mirtesen']) ? forum_htmlencode($form['mirtesen']) : (empty($user['mirtesen']) ? '' : 'http://mirtesen.ru/people/'.forum_htmlencode($user['mirtesen']))) ?>" size="35" maxlength="30" class="mirtesen inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_moikrug')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="moikrug"><?php echo $lang_profile['Moikrug'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[moikrug]" value="<?php echo(isset($form['moikrug']) && empty($form['moikrug']) ? forum_htmlencode($form['moikrug']) : (empty($user['moikrug']) ? '' : 'http://'.forum_htmlencode($user['moikrug']).'.moikrug.ru/')) ?>" size="35" maxlength="30" class="moikrug inim" /></span>
					</div>
				</div>
<?php

($hook = get_hook('pf_fl_change_details_identity_pre_facebook')) ? eval($hook) : null;

$facebook_url = preg_match('([0-9])', $user['facebook'], $matches) ? 'profile.php?id=' : '';

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="facebook"><?php echo $lang_profile['Facebook'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[facebook]" value="<?php echo(isset($form['facebook']) && empty($form['facebook']) ? forum_htmlencode($form['facebook']) : (empty($user['facebook']) ? '' : 'http://facebook.com/'.$facebook_url.forum_htmlencode($user['facebook']))) ?>" size="35" maxlength="80" class="facebook inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_twitter')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="twitter"><?php echo $lang_profile['Twitter'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[twitter]" value="<?php echo(isset($form['twitter']) && empty($form['twitter']) ? forum_htmlencode($form['twitter']) : (empty($user['twitter']) ? '' : 'http://twitter.com/'.forum_htmlencode($user['twitter']))) ?>" size="35" maxlength="80" class="twitter inim" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_fl_change_details_identity_pre_lastfm')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="prof" id="lastfm"><?php echo $lang_profile['Last.fm'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="form[lastfm]" value="<?php echo(isset($form['lastfmr']) && empty($form['lastfm']) ? forum_htmlencode($form['lastfm']) : (empty($user['lastfm']) ? '' : 'http://last.fm/user/'.forum_htmlencode($user['lastfm']))) ?>" size="35" maxlength="80" class="lastfm inim" /></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /> </span><span><?php echo $lang_profile['Instructions'] ?></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('pf_change_details_identity_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	else if ($section == 'settings')
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array($lang_profile['Section settings'], forum_link($forum_url['profile'], array($id, 'settings')))
		);

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['profile'], array($id, 'settings')));
		
		$forum_page['styles'] = get_style_packs();
		$forum_page['languages'] = get_language_packs();

		// Setup the form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['profile'], array($id, 'settings'));

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
		);

		($hook = get_hook('pf_change_details_settings_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-settings');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_settings_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Settings welcome'] : $lang_profile['Settings welcome user'], forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action']  ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_local_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Local settings'] ?></strong></legend>
<?php

		($hook = get_hook('pf_change_details_settings_pre_language')) ? eval($hook) : null;

		// Only display the language selection box if there's more than one language available
		if (count($forum_page['languages']) > 1)
		{

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Language'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[language]">
<?php

			foreach ($forum_page['languages'] as $temp)
			{
				if ($forum_user['language'] == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
			}

?>
						</select></span>
					</div>
				</div>
<?php

		}

		($hook = get_hook('pf_change_details_settings_pre_timezone')) ? eval($hook) : null;

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Timezone'] ?></span> <small><?php echo $lang_profile['Timezone info'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[timezone]">
						<option value="-12"<?php if ($user['timezone'] == -12) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-12:00'] ?></option>
						<option value="-11"<?php if ($user['timezone'] == -11) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-11:00'] ?></option>
						<option value="-10"<?php if ($user['timezone'] == -10) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-10:00'] ?></option>
						<option value="-9.5"<?php if ($user['timezone'] == -9.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-09:30'] ?></option>
						<option value="-9"<?php if ($user['timezone'] == -9) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-09:00'] ?></option>
						<option value="-8"<?php if ($user['timezone'] == -8) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-08:00'] ?></option>
						<option value="-7"<?php if ($user['timezone'] == -7) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-07:00'] ?></option>
						<option value="-6"<?php if ($user['timezone'] == -6) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-06:00'] ?></option>
						<option value="-5"<?php if ($user['timezone'] == -5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-05:00'] ?></option>
						<option value="-4"<?php if ($user['timezone'] == -4) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-04:00'] ?></option>
						<option value="-3.5"<?php if ($user['timezone'] == -3.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-03:30'] ?></option>
						<option value="-3"<?php if ($user['timezone'] == -3) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-03:00'] ?></option>
						<option value="-2"<?php if ($user['timezone'] == -2) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-02:00'] ?></option>
						<option value="-1"<?php if ($user['timezone'] == -1) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC-01:00'] ?></option>
						<option value="0"<?php if ($user['timezone'] == 0) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC'] ?></option>
						<option value="1"<?php if ($user['timezone'] == 1) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+01:00'] ?></option>
						<option value="2"<?php if ($user['timezone'] == 2) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+02:00'] ?></option>
						<option value="3"<?php if ($user['timezone'] == 3) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+03:00'] ?></option>
						<option value="3.5"<?php if ($user['timezone'] == 3.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+03:30'] ?></option>
						<option value="4"<?php if ($user['timezone'] == 4) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+04:00'] ?></option>
						<option value="4.5"<?php if ($user['timezone'] == 4.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+04:30'] ?></option>
						<option value="5"<?php if ($user['timezone'] == 5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:00'] ?></option>
						<option value="5.5"<?php if ($user['timezone'] == 5.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:30'] ?></option>
						<option value="5.75"<?php if ($user['timezone'] == 5.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+05:45'] ?></option>
						<option value="6"<?php if ($user['timezone'] == 6) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+06:00'] ?></option>
						<option value="6.5"<?php if ($user['timezone'] == 6.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+06:30'] ?></option>
						<option value="7"<?php if ($user['timezone'] == 7) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+07:00'] ?></option>
						<option value="8"<?php if ($user['timezone'] == 8) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+08:00'] ?></option>
						<option value="8.75"<?php if ($user['timezone'] == 8.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+08:45'] ?></option>
						<option value="9"<?php if ($user['timezone'] == 9) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+09:00'] ?></option>
						<option value="9.5"<?php if ($user['timezone'] == 9.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+09:30'] ?></option>
						<option value="10"<?php if ($user['timezone'] == 10) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+10:00'] ?></option>
						<option value="10.5"<?php if ($user['timezone'] == 10.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+10:30'] ?></option>
						<option value="11"<?php if ($user['timezone'] == 11) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+11:00'] ?></option>
						<option value="11.5"<?php if ($user['timezone'] == 11.5) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+11:30'] ?></option>
						<option value="12"<?php if ($user['timezone'] == 12) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+12:00'] ?></option>
						<option value="12.75"<?php if ($user['timezone'] == 12.75) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+12:45'] ?></option>
						<option value="13"<?php if ($user['timezone'] == 13) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+13:00'] ?></option>
						<option value="14"<?php if ($user['timezone'] == 14) echo ' selected="selected"' ?>><?php echo $lang_profile['UTC+14:00'] ?></option>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_dst_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[dst]" value="1" <?php if ($user['dst'] == 1) echo 'checked="checked" ' ?>/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Adjust for DST'] ?></span> <?php echo $lang_profile['DST label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_time_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Time format'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[time_format]">
<?php

		foreach (array_unique($forum_time_formats) as $key => $time_format)
		{
			echo "\t\t\t\t\t\t".'<option value="'.$key.'"';
			if ($user['time_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 2, null, $time_format);
			if ($key == 0)
				echo ' ('.$lang_profile['Default'].')';
			echo "</option>\n";
		}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_date_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="legend"><?php echo $lang_profile['Date format'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[date_format]">
<?php

		foreach (array_unique($forum_date_formats) as $key => $date_format)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'"';
			if ($user['date_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 1, $date_format, null, true);
			if ($key == 0)
				echo ' ('.$lang_profile['Default'].')';
			echo "</option>\n";
		}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_local_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_local_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_display_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Display settings'] ?></strong></legend>
<?php

		($hook = get_hook('pf_change_details_settings_pre_style')) ? eval($hook) : null;

		// Only display the style selection box if there's more than one style available
		if (count($forum_page['styles']) == 1)
			echo "\t\t\t\t".'<input type="hidden" name="form[style]" value="'.$forum_page['styles'][0].'" />'."\n";
		else if (count($forum_page['styles']) > 1 && $forum_config['o_user_style'])
		{
?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Styles'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="form[style]">
<?php

			foreach ($forum_page['styles'] as $temp)
			{
				if ($user['style'] == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.forum_htmlencode(str_replace('_', ' ', $temp)).'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.forum_htmlencode(str_replace('_', ' ', $temp)).'</option>'."\n";
			}

?>
						</select></span>
					</div>
				</div>
<?php

		}

		($hook = get_hook('pf_change_details_settings_pre_image_display_fieldset')) ? eval($hook) : null;

?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['Image display'] ?></span></legend>
					<div class="mf-box">
<?php if ($forum_config['o_smilies'] || $forum_config['o_smilies_sig']): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_smilies]" value="1"<?php if ($user['show_smilies']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Show smilies'] ?></label>
						</div>
<?php endif; if ($forum_config['o_avatars']): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_avatars]" value="1"<?php if ($user['show_avatars']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Show avatars'] ?></label>
						</div>
<?php endif; if ($forum_config['p_message_img_tag']): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_img]" value="1"<?php if ($user['show_img'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Show images'] ?></label>
						</div>
<?php endif; if ($forum_config['o_signatures'] && $forum_config['p_sig_img_tag']): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_img_sig]" value="1"<?php if ($user['show_img_sig']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Show images sigs'] ?></label>
						</div>
<?php endif; ($hook = get_hook('pf_change_details_settings_new_image_display_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_image_display_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_pre_show_sigs_checkbox')) ? eval($hook) : null; if ($forum_config['o_signatures']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_sig]" value="1"<?php if ($user['show_sig']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Signature display'] ?></span> <?php echo $lang_profile['Show sigs'] ?></label>
					</div>
				</div>
<?php  endif; ($hook = get_hook('pf_change_details_settings_pre_show_bb_panel_checkbox')) ? eval($hook) : null; if ($forum_config['p_enable_bb_panel']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[show_bb_panel]" value="1"<?php if ($user['show_bb_panel']) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_profile['BB panel display'] ?></span> <?php echo $lang_profile['Show bb panel'] ?></label>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_settings_pre_display_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_display_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_pagination_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Pagination settings'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_settings_pre_disp_topics')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Topics per page'] ?></span> <small><?php echo $lang_profile['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[disp_topics]" value="<?php echo $user['disp_topics'] ?>" size="6" maxlength="3" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_disp_posts')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Posts per page'] ?></span>	<small><?php echo $lang_profile['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[disp_posts]" value="<?php echo $user['disp_posts'] ?>" size="6" maxlength="3" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_pagination_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_pagination_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['E-mail and sub settings'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_settings_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['E-mail settings'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[email_setting]" value="0"<?php if ($user['email_setting'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['E-mail setting 1'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[email_setting]" value="1"<?php if ($user['email_setting'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['E-mail setting 2'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[email_setting]" value="2"<?php if ($user['email_setting'] == '2') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['E-mail setting 3'] ?></label>
						</div>
<?php ($hook = get_hook('pf_change_details_settings_new_email_setting_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_settings_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_email_settings_fieldset_end')) ? eval($hook) : null; ?>
<?php if ($forum_config['o_subscriptions']): ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['Subscription settings'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[notify_with_post]" value="1"<?php if ($user['notify_with_post']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Notify full'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[auto_notify]" value="1"<?php if ($user['auto_notify']) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Subscribe by default'] ?></label>
						</div>
<?php ($hook = get_hook('pf_change_details_settings_new_subscription_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_private_messages_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php endif; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['Private messages'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_long_subject]" value="1"<?php if ($user['pm_long_subject']) echo ' checked="checked"' ?> /></span>
							<label for="fld14"><?php echo $lang_profile['Begin message quote'] ?></label>
						</div>
<?php if ($forum_config['o_pm_get_mail']): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[pm_get_mail]" value="1"<?php if ($user['pm_get_mail']) echo ' checked="checked"' ?> /></span>
							<label for="fld14"><?php echo $lang_profile['Get mail'] ?></label>
						</div>
					</div>
<?php endif; ($hook = get_hook('pf_change_details_settings_pre_reputation_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php if ($forum_config['o_rep_enabled']): ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['Manage reputation'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[rep_enable]" value="1"<?php if ($user['rep_enable']) echo ' checked="checked"' ?> /></span>
							<label for="fld14"><?php echo $lang_profile['Manage reputation label'] ?></label>
						</div>
					</div>
				</fieldset>
<?php

endif;

($hook = get_hook('pf_change_details_settings_subscription_fieldset_end')) ? eval($hook) : null;
$ip = get_remote_address();
$netmask = (preg_match('/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/', $ip, $matches) || preg_match('/^([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4}):([0-9A-Fa-f]{1,4})$/', $ip, $matches)) ? $matches[1].'.'.$matches[2] : '0';

?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_profile['Security level'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[security_ip]" value="0"<?php if ($user['security_ip'] == $ip) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['High security'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[security_ip]" value="1"<?php if ($user['security_ip'] == $netmask) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Medium security'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[security_ip]" value="2"<?php if ($user['security_ip'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_profile['Low security'] ?></label>
						</div>
					</div>
				</fieldset>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_email_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /> </span><span><?php echo $lang_profile['Instructions'] ?></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('pf_change_details_settings_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	else if ($section == 'signature' && $forum_config['o_signatures'])
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array($lang_profile['Section signature'], forum_link($forum_url['profile'], array($id, 'signature')))
		);

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['profile'], array($id, 'signature')));
		
		$forum_page['sig_info'][] = '<li>'.$lang_profile['Signature info'].'</li>';

		if ($user['signature'] != '')
			$forum_page['sig_demo'] = $parsed_signature;

		// Setup the form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['profile'], array($id, 'signature'));

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
		);

		// Setup help
		$forum_page['text_options'] = array();
		if ($forum_config['p_sig_bbcode'])
			$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
		if ($forum_config['p_sig_img_tag'])
			$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
		if ($forum_config['o_smilies_sig'])
			$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

		($hook = get_hook('pf_change_details_signature_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-signature');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_signature_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Sig welcome'] : $lang_profile['Sig welcome user'], forum_htmlencode($user['username'])) ?></span></h2>
	</div>

	<div class="main-content main-frm">

<?php

		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="content-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

		// If there were any errors, show them
		if (!empty($errors))
		{
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('pf_change_details_signature_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

		}

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_details_signature_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Signature'] ?></strong></legend>
				<div class="ct-box set<?php echo ++$forum_page['item_count'] ?>">
					<p><?php echo $lang_profile['Signature info'] ?></p>
				</div>
<?php ($hook = get_hook('pf_change_details_signature_pre_signature_demo')) ? eval($hook) : null; ?>
<?php if (isset($forum_page['sig_demo'])): ?>
				<div class="ct-set info-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box info-box">
						<h3 class="ct-legend hn"><?php echo $lang_profile['Current signature'] ?></h3>
						<div class="sig-demo"><?php echo $forum_page['sig_demo'] ?></div>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_signature_pre_signature_text')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Compose signature'] ?></span> <small><?php printf($lang_profile['Sig max size'], forum_number_format($forum_config['p_sig_length']), forum_number_format($forum_config['p_sig_lines'])) ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="signature" rows="4" cols="65"><?php echo(isset($_POST['signature']) ? forum_htmlencode($_POST['signature']) : forum_htmlencode($user['signature'])) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_signature_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_signature_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /> </span><span><?php echo $lang_profile['Instructions'] ?></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('pf_change_details_signature_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	else if ($section == 'avatar' && $forum_config['o_avatars'])
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array($lang_profile['Section avatar'],forum_link($forum_url['profile'], array($id, 'avatar')))
		);

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['profile'], array($id, 'avatar')));

		if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
			require FORUM_ROOT.'include/functions/generate_avatar_markup.php';
	
		$forum_page['avatar_markup'] = generate_avatar_markup($id, $user['avatar'], $user['email']);

		$filetypes = array('jpg', 'gif', 'png');
		foreach ($filetypes as $cur_type)
		{
				$path = FORUM_AVATAR_DIR.'/'.$id.'.'.$cur_type;
				if (file_exists($path))
					$avatar_markup = true;
		}

		// Setup the form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['profile'], array($id, 'avatar'));

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'max_file_size'	=> '<input type="hidden" name="MAX_FILE_SIZE" value="'.$forum_config['o_avatars_size'].'" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
		);

		// Setup form information
		$forum_page['frm_info'] = array();

		if (isset($avatar_markup))
			$forum_page['frm_info']['avatar_replace'] = '<li><span>'.$lang_profile['Avatar info replace'].'</span></li>';
		else
			$forum_page['frm_info']['avatar_none'] = '<li><span>'.$lang_profile['Avatar info none'].'</span></li>';

		$forum_page['frm_info']['avatar_type'] = '<li><span>'.$lang_profile['Avatar info type'].'</span></li>';
		$forum_page['frm_info']['avatar_size'] = '<li><span>'.sprintf($lang_profile['Avatar info size'], $forum_config['o_avatars_width'], $forum_config['o_avatars_height'], forum_number_format($forum_config['o_avatars_size']), forum_number_format(ceil($forum_config['o_avatars_size'] / 1024))).'</span></li>';

		($hook = get_hook('pf_change_details_avatar_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-avatar');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_avatar_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(($forum_page['own_profile']) ? $lang_profile['Avatar welcome'] : $lang_profile['Avatar welcome user'], forum_htmlencode($user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

		// If there were any errors, show them
		if (!empty($errors))
		{
			$forum_page['errors'] = array();
			foreach ($errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('pf_change_details_avatar_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_profile['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

		}

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>" enctype="multipart/form-data">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Avatar'] ?></strong></legend>
					<div class="ct-box set<?php echo ++$forum_page['item_count'] ?>">
						<p><?php echo $lang_profile['Avatar info'] ?></p>
					</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_cur_avatar_info')) ? eval($hook) : null; ?>
				<div class="ct-set info-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box info-box">
						<h3 class="hn ct-legend"><?php echo $lang_profile['Current avatar'] ?></h3>
						<p><?php echo (isset($avatar_markup)) ? '<a href="'.forum_link($forum_url['delete_avatar'], array($id, generate_form_token('delete_avatar'.$id.$forum_user['id']))).'"><strong>'.$lang_profile['Delete avatar info'].'</strong></a>' : $lang_profile['No avatar info'] ?></p>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_avatar_demo')) ? eval($hook) : null; ?>
				<div class="ct-set info-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="ct-box info-box">
<?php if (isset($forum_page['avatar_markup'])): ?>
						<h3 class="hn ct-legend avatar-demo"><span><?php echo $forum_page['avatar_markup'] ?></span></h3>
<?php endif; ?>
						<ul class="info-list">
							<?php echo implode("\n\t\t\t\t\t", $forum_page['frm_info'])."\n\t\t\t" ?>
						</ul>
					</div> 
						<div id="req-msg" class="req-warn ct-box info-box"> 
							<p class="important"><?php echo $lang_profile['No upload warn'] ?></p> 
						</div>
					</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_avatar_upload')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Upload avatar file'] ?></span><small><?php echo $lang_profile['Avatar upload help'] ?></small></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" name="req_file" type="file" size="40" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_avatar_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update" value="<?php echo $lang_profile['Update profile'] ?>" /> </span><span><?php echo $lang_profile['Instructions'] ?></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('pf_change_details_avatar_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	else if ($section == 'admin')
	{
		// Setup breadcrumbs
		$forum_page['crumbs'] = array(
			array($forum_config['o_board_title'], forum_link($forum_url['index'])),
			array(sprintf($lang_profile['Users profile'], $user['username']), forum_link($forum_url['user'], $id)),
			array($lang_profile['Section admin'], forum_link($forum_url['profile'], array($id, 'admin')))
		);

		// Check for use of incorrect URLs
		confirm_current_url(forum_link($forum_url['profile'], array($id, 'admin')));
		
		if ($forum_user['g_id'] != FORUM_ADMIN && ($forum_user['g_moderator'] != '1' || $forum_user['g_mod_ban_users'] == '0' || $forum_user['id'] == $id))
			message($lang_common['Bad request']);

		// Setup form
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['profile'], array($id, 'admin'));

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
		);

		// Setup ban and delete options
		$forum_page['user_management'] = array();

		if ($forum_user['g_moderator'])
			$forum_page['user_management']['ban'] = '<div class="ct-set info-set set'.++$forum_page['item_count'].'">'."\n\t\t".'<div class="ct-box info-box"><h3 class="ct-legend hn">'.$lang_profile['Ban user'].'</h3>'."\n\t\t\t".'<p><a href="'.forum_link('admin/bans.php').'?add_ban='.$id.'">'.$lang_profile['Ban user info'].'</a></p></div></div>';
		else if (!$forum_user['g_moderator'] && $user['g_id'] != FORUM_ADMIN)
		{
			$forum_page['user_management']['ban'] = '<div class="ct-set info-set set'.++$forum_page['item_count'].'">'."\n\t\t".'<div class="ct-box info-box"><h3 class="ct-legend hn">'.$lang_profile['Ban user'].'</h3>'."\n\t\t\t".'<p><a href="'.forum_link('admin/bans.php').'?add_ban='.$id.'">'.$lang_profile['Ban user info'].'</a></p></div></div>';
			$forum_page['user_management']['delete'] = '<div class="ct-set info-set set'.++$forum_page['item_count'].'">'."\n\t\t".'<div class="ct-box info-box"><h3 class="ct-legend hn">'.$lang_profile['Delete user'].'</h3>'."\n\t\t\t\t".'<p><a href="'.forum_link($forum_url['delete_user'], $id).'">'.$lang_profile['Delete user info'].'</a></p></div></div>';
		}

		($hook = get_hook('pf_change_details_admin_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-admin');
		require FORUM_ROOT.'header.php';

		// START SUBST - <forum_main>
		ob_start();

		($hook = get_hook('pf_change_details_admin_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_profile['User management'] ?></span></h2>
	</div>
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
		<div class="hidden">
			<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
		<div class="main-content main-frm">
			<div class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
<?php

		($hook = get_hook('pf_change_details_admin_pre_user_management')) ? eval($hook) : null;

		if (!empty($forum_page['user_management']))
		{
			echo "\t\t\t".implode("\n\t\t\t", $forum_page['user_management'])."\n";

			($hook = get_hook('pf_change_details_admin_pre_membership')) ? eval($hook) : null;
		}

		if ($forum_user['id'] != $user['id'] && !$forum_page['own_profile'])
		{
			($hook = get_hook('pf_change_details_admin_pre_group_membership')) ? eval($hook) : null;

?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['User group'] ?></span></label><br />
					<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="group_id">
<?php

				$query = array(
					'SELECT'	=> 'g.g_id, g.g_title',
					'FROM'		=> 'groups AS g',
					'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
					'ORDER BY'	=> 'g.g_title'
				);

				($hook = get_hook('pf_change_details_admin_qr_get_groups')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				while ($cur_group = $forum_db->fetch_assoc($result))
				{
					if ($cur_group['g_id'] == $user['g_id'] || ($cur_group['g_id'] == $forum_config['o_default_user_group'] && $user['g_id'] == ''))
						echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
					else
						echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
				}

?>
					</select></span>
				</div>
			</div>
<?php ($hook = get_hook('pf_change_details_admin_pre_membership_submit')) ? eval($hook) : null; ?>
			<div class="sf-set button-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
	 				<span class="submit"><input type="submit" name="update_group_membership" value="<?php echo $lang_profile['Update groups'] ?>" /></span>
	 			</div>
			</div>
<?php

		}

		($hook = get_hook('pf_change_details_admin_pre_mod_assignment')) ? eval($hook) : null;

		if ($forum_user['g_id'] == FORUM_ADMIN && ($user['g_id'] == FORUM_ADMIN || $user['g_moderator']))
		{
			($hook = get_hook('pf_change_details_admin_pre_mod_assignment_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
				<legend><span><?php echo $lang_profile['Moderator assignment'] ?></span></legend>
<?php ($hook = get_hook('pf_change_details_admin_pre_forum_checklist')) ? eval($hook) : null; ?>
				<div class="mf-box">
					<div class="checklist">
<?php

			$query = array(
				'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.moderators',
				'FROM'		=> 'categories AS c',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'forums AS f',
						'ON'			=> 'c.id=f.cat_id'
					)
				),
				'WHERE'		=> 'f.redirect_url IS NULL',
				'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
			);

			($hook = get_hook('pf_change_details_admin_qr_get_cats_and_forums')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$cur_category = 0;
			while ($cur_forum = $forum_db->fetch_assoc($result))
			{
				if ($cur_forum['cid'] != $cur_category) // A new category since last iteration?
				{
					if ($cur_category)
						echo "\n\t\t\t\t\t".'</fieldset>'."\n";

					echo "\t\t\t\t\t".'<fieldset>'."\n\t\t\t\t\t\t".'<legend><span>'.$cur_forum['cat_name'].':</span></legend>'."\n";
					$cur_category = $cur_forum['cid'];
				}

				$moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

				echo "\t\t\t\t\t\t".'<div class="checklist-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="moderator_in['.$cur_forum['fid'].']" value="1"'.((in_array($id, $moderators)) ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.forum_htmlencode($cur_forum['forum_name']).'</label></div>'."\n";
			}

?>
					</fieldset>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_admin_pre_mod_assignment_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_admin_mod_assignment_fieldset_end')) ? eval($hook) : null; ?>
			<div class="mf-set button-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="mf-box text">
					<span class="submit"><input type="submit" name="update_forums" value="<?php echo $lang_profile['Update forums'] ?>" /></span>
				</div>
			</div>
<?php

			($hook = get_hook('pf_change_details_admin_form_end')) ? eval($hook) : null;
		}

?>
		</div>
<?php

		if ($forum_config['o_rep_enabled'])
		{
			$query = array(
				'SELECT'	=> 'u.rep_enable_adm',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id='.$user['id']
			);

			($hook = get_hook('pf_fl_change_details_qr_rep_enable_adm')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$rep_enable_adm = $forum_db->result($result);

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_profile['Reputation adm'] ?></span></h2>
	</div>
		<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
			<div class="sf-box checkbox">
				<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="rep_enable_adm" value="0"<?php if ($rep_enable_adm == '0') echo ' checked="checked"' ?> /></span>
				<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Disable reputation'] ?></span> <?php echo $lang_profile['Disable reputation help'] ?></label>
			</div>
		</div>
<?php ($hook = get_hook('pf_change_details_rep_enable_adm_pre_submit')) ? eval($hook) : null; ?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="reputation" value="<?php echo $lang_profile['Update profile'] ?>" /></span><span><?php echo $lang_profile['Instructions'] ?></span>
		</div>
	</div>
<?php

		}

?>
	</form>
<?php

		($hook = get_hook('pf_change_details_admin_end')) ? eval($hook) : null;

		$tpl_temp = forum_trim(ob_get_contents());
		$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
		ob_end_clean();
		// END SUBST - <forum_main>

		require FORUM_ROOT.'footer.php';
	}

	($hook = get_hook('pf_change_details_new_section')) ? eval($hook) : null;

	message($lang_common['Bad request']);
}
