<?php
/**
 *
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */
 
if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('pm_fl_start')) ? eval($hook) : null;

if ($forum_user['is_guest'])
	message($lang_common['No permission'], false, '403 Forbidden');

$section = isset($_GET['section']) ? $_GET['section'] : 'inbox';
$errors = array();

// Load the profile.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/profile.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/pm.php';

if ($section == 'delete')
{
	if (isset($_GET['id']))
	{
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if ($id < 1)
			message($lang_common['Bad request'], false, '404 Not Found');

		confirm_current_url(forum_link($forum_url['pm_delete'], $id));

		$query = array(
			'SELECT'	=> 'm.receiver_id',
			'FROM'		=> 'pm AS m',
			'WHERE'		=> 'm.id='.$id
		);

		($hook = get_hook('pm_fl_pm_get_message_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!$forum_db->num_rows($result))
			message($lang_common['Bad request'], false, '404 Not Found');

		$cur_delete = $forum_db->fetch_assoc($result);
		$section = ($cur_delete['receiver_id'] == $forum_user['id'] ? 'inbox' : 'outbox');
		$ids[0] = $id;
	}
	else if (isset($_POST['id']))
	{
		confirm_current_url(forum_link($forum_url['pm_delete_section']));

		$ids = $_POST['id'];
		$section = $_POST['section'];

		foreach ($ids as $key => $id)
			$ids[$key] = intval($id);
	}

	if (!defined('FORUM_FUNCTIONS_DELETE_POST'))
		require FORUM_ROOT.'include/functions/delete_pm.php';
	delete_pm($ids, $section);

	redirect(forum_link($forum_url['pm'], $section), $lang_pm['Message deleted']);
}

if (isset($_POST['form_sent']))
{
	$receiver = isset($_POST['req_receiver']) ? forum_trim($_POST['req_receiver']) : null;
	$subject = forum_trim($_POST['subject']);
	$message = forum_linebreaks(forum_trim($_POST['req_message']));

	$receiver_id = 'NULL';
	if ($receiver)
	{	
		$query = array(
			'SELECT'	=> 'u.id, u.email, u.pm_get_mail',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.username=\''.$forum_db->escape($receiver).'\''
		);

		($hook = get_hook('pm_fl_pm_get_receiver_id_qr')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->num_rows($result))
			list($receiver_id, $receiver_email, $receiver_mail) = $forum_db->fetch_row($result);
		else
			$errors[] = sprintf($lang_pm['Non-existent username'], forum_htmlencode($receiver));

		if ($forum_user['id'] == $receiver_id)
			$errors[] = $lang_pm['Message to yourself'];
		if ($receiver_id == '1')
			$errors[] = $lang_pm['Message to guest'];
	}

	if (isset($_POST['draft']))
	{
		if ($message == '' && $subject == '' && $receiver == '')
			$errors[] = $lang_pm['Empty all fields'];
		$status = 'draft';
	}
	else
	{
		if ($forum_user['pm_outbox'] >= $forum_config['o_pm_outbox_size'])
			$errors[] = sprintf($lang_pm['Limit outbox'], $forum_config['o_pm_outbox_size']);

		if ($receiver == '')
			$errors[] = $lang_pm['Empty receiver'];

		if (utf8_strlen($message) > FORUM_MAX_POSTSIZE)
			$errors[] = sprintf($lang_pm['Too long message'], forum_number_format(utf8_strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE));
		else if (!$forum_config['p_message_all_caps'] && is_all_uppercase($message) && !$forum_user['is_admmod'])
			$errors[] = $lang_pm['All caps message'];

		// Validate BBCode syntax
		if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
		{
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';

			$message = preparse_bbcode($message, $errors);
		}

		if ($message == '')
			$errors[] = $lang_pm['Empty body'];
		$status = 'sent';
	}

	$now = time();

	($hook = get_hook('pp_fl_end_validation')) ? eval($hook) : null;

	if (empty($errors))
	{
		if (isset($_POST['edit']))
		{
			$id = intval($_POST['edit']);

			$query = array(
				'UPDATE'	=> 'pm',
				'SET'		=> 'receiver_id='.$receiver_id.', subject=\''.$forum_db->escape($subject).'\', message=\''.$forum_db->escape($message).'\', edited='.$now,
				'WHERE'		=> 'id='.$id.' AND (status=\'draft\' OR status=\'sent\')'
			);

			($hook = get_hook('pm_fl_edit_update_status_qr_get')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			redirect(forum_link($forum_url['pm_view'], $id), $lang_pm['Message send']);
		}
		else if (isset($_POST['submit']) || isset($_POST['draft']))
		{
			$post_info = array(
				'sender_id'		=> $forum_user['id'],
				'sender_name'	=> $forum_user['username'],
				'receiver_id'	=> $receiver_id,
				'status'		=> $status,
				'posted'		=> $now,
				'subject'		=> $subject,
				'message'		=> $message,
			);

			$redirect_message = $lang_pm['Message saved'];

			if (isset($_POST['submit']))
			{
				$post_info['receiver'] =  $receiver;
				$post_info['email'] = $receiver_email;
				$post_info['mail'] = $receiver_mail;
				$redirect_message = $lang_pm['Message sent'];
			}

			($hook = get_hook('pm_pre_add_pm')) ? eval($hook) : null;

			if (!defined('FORUM_FUNCTIONS_ADD_PM'))
				require FORUM_ROOT.'include/functions/add_pm.php';
			add_pm($post_info, $new_pid);

			redirect(forum_link($forum_url['pm_view'], $new_pid), $redirect_message);
		}
	}
}

// Setup navigation menu
$forum_page['main_menu'] = array();
$forum_page['main_menu']['about'] = '<li class="first-item"><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'about')).'"><span>'.$lang_profile['Section about'].'</span></a></li>';
$forum_page['main_menu']['identity'] = '<li><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'identity')).'"><span>'.$lang_profile['Section identity'].'</span></a></li>';
$forum_page['main_menu']['settings'] = '<li><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'settings')).'"><span>'.$lang_profile['Section settings'].'</span></a></li>';

if ($forum_config['o_signatures'])
	$forum_page['main_menu']['signature'] = '<li><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'signature')).'"><span>'.$lang_profile['Section signature'].'</span></a></li>';

if ($forum_config['o_avatars'])
	$forum_page['main_menu']['avatar'] = '<li><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'avatar')).'"><span>'.$lang_profile['Section avatar'].'</span></a></li>';

$forum_page['main_menu']['pm'] = '<li class="active"><a href="'.forum_link($forum_url['pm'], 'inbox').'"><span>'.$lang_profile['Private messages'].'</span></a></li>';

if ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && $forum_user['g_mod_ban_users'] && !$forum_page['own_profile']))
	$forum_page['main_menu']['admin'] = '<li><a href="'.forum_link($forum_url['profile'], array($forum_user['id'], 'admin')).'#brd-crumbs-top"><span>'.$lang_profile['Section admin'].'</span></a></li>';

$forum_links['pm'] = array(
 	'<li'.($section == 'inbox' ? ' class="active-subsection first-item"' : ' class="normal"').'><a href="'.forum_link($forum_url['pm'], 'inbox').'">'.$lang_pm['Inbox'].'</a></li>',
 	'<li'.($section == 'outbox' ? ' class="active-subsection first-item"' : ' class="normal"').'><a href="'.forum_link($forum_url['pm'], 'outbox').'">'.$lang_pm['Outbox'].'</a></li>',
 	'<li'.($section == 'write' ? ' class="active-subsection first-item"' : ' class="normal"').'><a href="'.forum_link($forum_url['pm'], 'write').'">'.$lang_pm['Compose message'].'</a></li>'
);

($hook = get_hook('pm_change_details_modify_main_menu')) ? eval($hook) : null;

if ($section == 'inbox' || $section == 'outbox')
{
	confirm_current_url(forum_link($forum_url['pm'], $section));

 	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
 		array(sprintf($lang_profile['Users profile'], $forum_user['username']), forum_link($forum_url['profile'], array($forum_user['id'], 'about'))),
 		array($lang_profile['Private messages'], forum_link($forum_url['pm'], $section))
	);

	if ($forum_config['o_pm_'.$section.'_size'])
	{
		if (!($forum_user['pm_'.$section] < 1 * $forum_config['o_pm_'.$section.'_size']))
			$forum_page['full_box'] = sprintf($lang_pm['Limit '.$section], $forum_config['o_pm_'.$section.'_size']);
		else if (!($forum_user['pm_'.$section] < 0.75 * $forum_config['o_pm_'.$section.'_size']))
			$forum_page['full_box'] = sprintf($lang_pm['Limit almost '.$section], $forum_config['o_pm_'.$section.'_size']);
	}

	if ($section == 'inbox')
	{
		$forum_page['heading'] = $lang_pm['Inbox'].($forum_config['o_pm_inbox_size'] ? sprintf($lang_pm['Status box'], substr(($forum_user['pm_inbox'] / ($forum_config['o_pm_inbox_size'] * 0.01)), 0, 5), $forum_user['pm_inbox'], $forum_config['o_pm_inbox_size']) : '');
		$forum_page['user_role'] = $lang_pm['Sender'];
	}
	else
	{
		$forum_page['heading'] = $lang_pm['Outbox'].($forum_config['o_pm_outbox_size'] ? sprintf($lang_pm['Status box'], substr(($forum_user['pm_outbox'] / ($forum_config['o_pm_outbox_size'] * 0.01)), 0, 5), $forum_user['pm_outbox'], $forum_config['o_pm_outbox_size']) : '');
		$forum_page['user_role'] = $lang_pm['Receiver'];
	}

	($hook = get_hook('pm_fl_section_box_pre_header_load')) ? eval($hook) : null;

 	define('FORUM_PAGE', 'pm-message');
	require FORUM_ROOT.'header.php';

 	// START SUBST - <forum_main>
	ob_start();

?>
<div id="cp-menu">
	<div id="navigation" role="navigation">
		<ul>
			<?php echo implode("\n\t\t\t\t", $forum_links['pm'])."\n" ?>
		</ul>
	</div>
</div>
	<div class="main-subhead">
		<h2 class="hn"><span></span></h2>
	</div>
<?php

	if (!empty($forum_page['full_box']))
	{

?>
	<div class="main-content main-frm">
		<div class="ct-box error-box">
			<p class="important"><?php echo $forum_page['full_box']."\n" ?></p>
		</div>
	</div>
<?php

	}

	if (!$forum_user['pm_'.$section])
	{

?>
	<div class="main-content main-frm">
		<div class="ct-box">
			<h2 class="hn"><span><?php echo $lang_pm['Intro'] ?></span></h2>
		</div>
		<div class="ct-box">
			<h2 class="hn"><strong><?php echo $lang_pm['Not PM'] ?></strong></h2>
		</div>
	</div>
<?php

	}
	else
	{
		$forum_page['num_pages'] = ceil($forum_user['pm_'.$section] / $forum_user['disp_topics']);
		$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
		$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
		$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($forum_user['pm_'.$section]));

		$query = array(
			'SELECT'	=> 'm.id, m.status, m.subject, m.message, m.edited, u.id AS user_id, u.username',
			'FROM'		=> 'pm AS m',
			'ORDER BY'	=> 'm.edited DESC',
			'LIMIT'		=> $forum_page['start_from'].', '.$forum_user['disp_topics']
		);

		if ($section == 'inbox')
		{
			$query['JOINS'][] = array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> '(u.id=m.sender_id)'
			);
			$query['WHERE'] = 'm.receiver_id='.$forum_user['id'].' AND m.deleted_by_receiver=0 AND (m.status=\'sent\' OR m.status=\'delivered\')';
		}
		else
		{
			$query['JOINS'][] = array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> '(u.id=m.receiver_id)'
			);
			$query['WHERE'] = 'm.sender_id='.$forum_user['id'].' AND m.deleted_by_sender=0';
		}

		($hook = get_hook('pf_fl_pm_list_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_page['set_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['pm_delete_section']);

		$forum_page['mod_options'] = array(
			'mod_delete'	=> '<span class="submit"><input type="submit" name="delete" value="'.$lang_pm['Delete message'].'" onclick="return confirm_delete();"/></span>'
		);

		$forum_page['hidden_fields'] = array(
			'send_action'	=> '<input type="hidden" name="send_action" value="" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
			'section'		=> '<input type="hidden" name="section" value="'.$section.'" />'
		);

		$forum_page['items_info'] = $lang_profile['Private messages'];
		$forum_page['main_foot_options']['select_all'] = '<span '.(empty($forum_page['main_foot_options']) ? ' class="first-item"' : '').'><a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById(\'pm-actions-form\'))">'.$lang_pm['Select all'].'</a></span>';

		$forum_page['table_header'] = array();
		$forum_page['table_header']['subject'] = '<dt><div class="list-inner with-mark">'.$lang_pm['Subject'].'</div></dt>';
		$forum_page['table_header']['select'] = '<dd class="mark">Mark</dd>';

		($hook = get_hook('pm_fl_box_results_pre_header_output')) ? eval($hook) : null;

?>
<div id="cp-main-pm" class="ucp-main panel-container">
	<div id="cp-main-inner">
		<form id="viewfolder" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="panel">
				<div class="inner">
					<p>
						<?php echo $forum_page['heading'] ?>
					</p>
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields']), "\n" ?>
			</div>
			<p>
				<?php echo $lang_pm['Intro'] ?>
			</p>
			
					<ul class="topiclist two-columns">
						<li class="header">
							<dl>
<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
							</dl>
						</li>
					</ul>

<?php

		while ($cur_pm = $forum_db->fetch_assoc($result))
		{
			$message_link = forum_link($forum_url['pm_view'], $cur_pm['id']);

			($hook = get_hook('pm_fl_box_pre_message_link')) ? eval($hook) : null;
			$forum_page['table_row'] = array();
			$forum_page['table_row']['subject'] = '<dt><div class="list-inner with-mark"><a class="topictitle" href="'.$message_link.'">'.forum_trim($cur_pm['subject'] ? forum_htmlencode($cur_pm['subject']) : $lang_pm['Empty']).'</a><br>'.($forum_user['pm_long_subject'] ? ' <a class="topictitle" href="'.$message_link.'">'.forum_htmlencode(preg_replace('#(?:\s*(?:\[quote(?:=(&quot;|"|\'|)(?:.*?)\\1)?\](?:.*)\[\/quote\])*)((?:\S*\s*){20})(?:.*)$#su', '$2', $cur_pm['message'])).'</a><br>' : '').'</span>';
			$forum_page['table_row']['username'] = 'by '.($cur_pm['username'] ? '<a class="username" href="'.forum_link($forum_url['user'], $cur_pm['user_id']).'">'.forum_htmlencode($cur_pm['username']).'</a>' : $lang_pm['Empty']).'';
			$forum_page['table_row']['edited'] = '»'.format_time($cur_pm['edited']).'<div class="responsive-show" style="display:none;"></div></div></dt>';
			$forum_page['table_row2'] = array();
			$forum_page['table_row2']['select'] = '<dd class="mark"><input type="checkbox" name="id[]" value="'.$cur_pm['id'].'" /></dd>';

			++$forum_page['item_count'];

			($hook = get_hook('pm_fl_box_results_row_pre_data_output')) ? eval($hook) : null;

?>
					<ul class="topiclist cplist pmlist responsive-show-all two-columns">
						<li class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'row' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' bg1' : 'bg2' ?>">
							<dl class="icon <?php echo ($cur_pm['status'] == 'delivered') ? ' pm unread' : 'pm read' ?>">
								
<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
								
<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['table_row2'])."\n" ?>
							</dl>
						</li>
					</ul>
<?php

		}

?>

				
			
		</div>	</div>
		<div class="main-options mod-options gen-content">
			<p class="options"><?php echo implode(' ', $forum_page['mod_options']) ?></p>
		</div>
	</form>
</div>
</div>
	<script type="text/javascript">
	function confirm_delete()
	{
		var a = document.all && !window.opera ? document.all : document.getElementsByTagName("*");
		var count = 0;
		for (var i = a.length; i--;)
		{
			if (a[i].tagName.toLowerCase() == 'input' && a[i].getAttribute("type") == "checkbox" && a[i].getAttribute("name") == "id[]" && a[i].checked)
				count++;
		}
		if (!count)
		{
			alert("<?php echo $lang_pm['Not selected']?>");
			return false;
		}
		return confirm("<?php echo $lang_pm['Selected messages']?> " +count+ "\n<?php echo $lang_pm['Delete confirmation']?>");
	}
	</script>
	<div class="main-foot">
<?php

	if (!empty($forum_page['main_foot_options']))
		echo "\t\t".'<p class="options">'.implode(' ', $forum_page['main_foot_options']).'</p>'."\n";

?>
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div id="brd-pagepost-end" class="main-pagepost gen-content">
		<p class="paging"><span class="pages"><?php echo $lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['pm'.($section ? '' : '_outbox')], $lang_common['Paging separator'], $forum_user['id']) ?></p>
		<p class="posting"><a class="newpost" href="<?php echo forum_link($forum_url['pm'], 'write') ?>"><span><?php echo $lang_pm['New message'] ?></span></a></p>
	</div>
<?php

	}

 	$tpl_temp = trim(ob_get_contents());
 	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
 	ob_end_clean();
 	// END SUBST - <forum_main>

 	require FORUM_ROOT.'footer.php';
}
else if ($section == 'write' || $section == 'edit')
{
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	if ($section == 'edit')
	{
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if ($id < 1)
			message($lang_common['Bad request'], false, '404 Not Found');

		confirm_current_url(forum_link($forum_url['pm_edit'], $id));

		$query = array(
			'SELECT'	=> 'u.username, m.subject, m.message, m.status',
			'FROM'		=> 'pm AS m',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=m.receiver_id'
				)
			),
			'WHERE'		=> 'm.id='.$id.' AND (status=\'draft\' OR status=\'sent\')'
		);

		($hook = get_hook('pm_fl_pm_get_message_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!$forum_db->num_rows($result))
			message($lang_common['Bad request'], false, '404 Not Found');

		list($receiver, $subject, $message, $status) = $forum_db->fetch_row($result);

		$forum_page['form_action'] = forum_link($forum_url['pm_edit'], $id);
	}
	else
	{
		if ($forum_config['o_pm_outbox_size'] && !($forum_user['pm_outbox'] < 1 * $forum_config['o_pm_outbox_size']))
			message(sprintf($lang_pm['Limit outbox'], $forum_config['o_pm_outbox_size']));

		if (isset($_GET['id']))
		{
			$id = intval($_GET['id']);
			if ($id < 2)
				message($lang_common['Bad request'], false, '404 Not Found');

			$query = array(
				'SELECT'	=> 'u.username',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id='.$id
			);

			($hook = get_hook('pm_fl_pm_get_receiver_id_qr')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			list($receiver) = $forum_db->fetch_row($result);

			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['pm_post'], $id));
			$forum_page['form_action'] = forum_link($forum_url['pm'], 'write');
		}
		else
		{
			// Check for use of incorrect URLs
			confirm_current_url(forum_link($forum_url['pm'], $section));
			$forum_page['form_action'] = forum_link($forum_url['pm'], $section);
		}
	}

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
	);

	if ($section == 'edit')
	{
		$forum_page['hidden_fields']['edit'] = '<input type="hidden" name="edit" value="'.$id.'" />';
		if ($status != 'draft')
			$forum_page['hidden_fields']['edit'] = '<input type="hidden" name="req_receiver" value="'.forum_htmlencode($receiver).'" />';
	}

	// Setup help
	$forum_page['text_options'] = array();
	if ($forum_config['p_message_bbcode'])
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
	if ($forum_config['p_message_img_tag'])
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
	if ($forum_config['o_smilies'])
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

 	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
 		array(sprintf($lang_profile['Users profile'], $forum_user['username']), forum_link($forum_url['profile'], array($forum_user['id'], 'about'))),
 		array($lang_profile['Private messages'], forum_link($forum_url['pm'], $section))
	);

	($hook = get_hook('pm_fl_section_write_pre_header_load')) ? eval($hook) : null;

 	define('FORUM_PAGE', 'pm-write');
	require FORUM_ROOT.'header.php';

 	// START SUBST - <forum_main>
	ob_start();

?>
	<div class="admin-submenu gen-content">
 		<ul>
			<?php echo implode("\n\t\t\t\t", $forum_links['pm'])."\n" ?>
 		</ul>
 	</div>
<?php

// If preview selected and there are no errors
if (isset($_POST['preview']) && empty($errors))
{
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$forum_page['preview_message'] = parse_message(forum_trim($message), false);

	// Generate the post heading
	$forum_page['post_ident'] = array();
	$forum_page['post_ident']['byline'] = '<span class="post-byline"><strong>'.forum_htmlencode($forum_user['username']).'</strong>'.'</span>';
	$forum_page['post_ident']['link'] = '<span class="post-link">'.format_time(time()).'</span>';

	($hook = get_hook('pm_preview_pre_display')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_pm['Preview message'] ?></span></h2>
	</div>
	<div id="post-preview" class="main-content main-frm">
		<div class="post singlepost">
			<div class="posthead">
				<h3 class="hn"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = get_hook('pm_preview_new_post_head_option')) ? eval($hook) : null; ?>
			</div>
			<div class="postbody">
				<div class="post-entry">
					<div class="entry-content">
						<?php echo $forum_page['preview_message']."\n" ?>
					</div>
<?php ($hook = get_hook('pm_preview_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
		</div>
	</div>
<?php

}

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_pm['New message'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

	if (!empty($forum_page['text_options']))
		echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

	if (!empty($errors))
	{

		$forum_page['errors'] = array();
		foreach ($errors as $cur_error)
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
?>
		<div class="ct-box error-box">
			<h2 class="warn"><?php echo $lang_pm['Messsage errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<form class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_pm['Send message'] ?></strong></legend>

<?php

	if ($section != 'edit' || $status == 'draft')
	{
		($hook = get_hook('pm_fl_pre_receiver')) ? eval($hook) : null;
?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pm['To'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_pm['Receivers username'] ?></small></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="req_receiver" value="<?php if (isset($_POST['req_receiver']) || isset($receiver)) echo forum_htmlencode($receiver); ?>" size="80" maxlength="255" /></span>
					</div>
				</div>
<?php

	}

	($hook = get_hook('pm_fl_pre_subject')) ? eval($hook) : null;

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pm['Subject'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="subject" value="<?php if (isset($_POST['subject']) || isset($subject)) echo forum_htmlencode($subject); ?>" size="80" maxlength="255" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pm_fl_pre_pm_req_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pm['Message'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label>
<?php require FORUM_ROOT.'resources/editor/post_bb.php'; ?>
						<div class="txt-input"><span class="fld-input"><textarea id="text" name="req_message" rows="10" cols="65"><?php if (isset($_POST['req_message']) || isset($message)) echo forum_htmlencode($message); ?></textarea></span></div>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_pm['Send button'] ?>" /></span>
				<span class="submit"><input type="submit" name="preview" value="<?php echo $lang_pm['Preview'] ?>" /></span>
				<span class="submit"><input type="submit" name="draft" value="<?php echo $lang_pm['Save draft'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

 	$tpl_temp = trim(ob_get_contents());
 	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
 	ob_end_clean();
 	// END SUBST - <forum_main>

 	require FORUM_ROOT.'footer.php';
}
else if ($section == 'message')
{
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id < 1)
		message($lang_common['Bad request'], false, '404 Not Found');

	confirm_current_url(forum_link($forum_url['pm_view'], $id));

	require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';

	($hook = get_hook('pm_fl_pre_message_qr_get')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'm.id, m.sender_id, m.receiver_id, m.status, u0.username, u0.email, u0.title, u0.avatar, u0.registered, u0.location, u0.num_posts, u0.timezone, u0.admin_note, u0.url, u0.email_setting, g.g_id, g.g_user_title, o.user_id AS is_online, u1.username AS receiver, readed, edited, subject, message',
		'FROM'		=> 'pm AS m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u0',
				'ON'			=> 'u0.id=m.sender_id'
			),
			array(
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> 'u1.id=m.receiver_id'
			),
			array(
				'INNER JOIN'	=> 'groups AS g',
				'ON'			=> 'g.g_id=u0.group_id'
			),
			array(
				'LEFT JOIN'		=> 'online AS o',
				'ON'			=> '(o.user_id=u0.id AND o.user_id!=1 AND o.idle=0)'
			),
		),
		'WHERE'		=> 'm.id='.$id.' AND (m.receiver_id='.$forum_user['id'].' OR m.sender_id='.$forum_user['id'].')'
	);

	($hook = get_hook('pm_fl_get_message_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request'], false, '404 Not Found');

	$cur_message = $forum_db->fetch_assoc($result);
	$type = ($cur_message['receiver_id'] == $forum_user['id'] ? 'inbox' : 'outbox');

	if ($type == 'inbox')
	{
		$forum_page['heading'] = $lang_pm['Incoming message'];
		$forum_page['user_text'] = $lang_pm['Sender'];
		$forum_page['user_content'] = $cur_message['username'] != '' ? '<a href="'.forum_link($forum_url['user'], $cur_message['sender_id']).'">'.forum_htmlencode($cur_message['username']).'</a>' : $lang_pm['Empty'];

		// Update the status of an read message
		if ($cur_message['status'] == 'delivered')
		{
			$query = array(
				'UPDATE'	=> 'pm',
				'SET'		=> 'status=\'read\', readed='.time(),
				'WHERE'		=> 'id='.$id,
			);

			($hook = get_hook('pm_fl_qr_update_status')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'pm_new=pm_new-1',
				'WHERE'		=> 'id='.$cur_message['receiver_id'],
			);

			($hook = get_hook('pm_fl_qr_minus_pm_new')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}
	else
	{
		$forum_page['heading'] = $lang_pm['Outgoing message'];
		$forum_page['user_text'] = $lang_pm['Receiver'];
		$forum_page['user_content'] = $cur_message['receiver'] != '' ? '<a href="'.forum_link($forum_url['user'], $cur_message['receiver_id']).'">'.forum_htmlencode($cur_message['receiver']).'</a>' : $lang_pm['Empty'];
	}

	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$forum_page['post_ident']['byline'] = '<span class="post-byline">'.($cur_message['username'] != '' ? '<a href="'.forum_link($forum_url['user'], $cur_message['sender_id']).'">'.forum_htmlencode($cur_message['username']).'</a>' : $lang_pm['Empty']).'</span>';
	$forum_page['post_ident']['link'] = '<span class="post-link">'.format_time($cur_message['edited']).$lang_common['Title separator'].flazy_format_time($cur_message['edited']).'</span>';

	$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_message).'</span></li>';
	if ($forum_config['o_avatars'] && $forum_user['show_avatars'])
	{
		if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
			require FORUM_ROOT.'include/functions/generate_avatar_markup.php';

		$forum_page['avatar_markup'] = generate_avatar_markup($cur_message['sender_id'], $cur_message['avatar'], $cur_message['email']);

		if (!empty($forum_page['avatar_markup']))
			$forum_page['author_ident']['avatar'] = '<li class="useravatar">'.$forum_page['avatar_markup'].'</li>';
	}

	if ($cur_message['is_online'] == $cur_message['sender_id'])
		$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Online'].'</span></li>';
	else
		$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Offline'].'</span></li>';

	if ($forum_config['o_show_user_info'])
	{
		if ($cur_message['location'] != '')
		{
			if ($forum_config['o_censoring'])
				$cur_message['location'] = censor_words($cur_message['location']);

			$forum_page['author_info']['from'] = '<li><span>'.$lang_topic['From'].' <strong> '.forum_htmlencode($cur_message['location']).'</strong></span></li>';
		}

		$forum_page['author_info']['registered'] = '<li><span>'.$lang_topic['Registered'].' <strong> '.format_time($cur_message['registered'], 1).'</strong></span></li>';

		// Разница во времени
		$time_dif = $forum_user['timezone'] - $cur_message['timezone'];
		if ($time_dif != 0 && $forum_user['id'] > 1)
			$forum_page['author_info']['timezone'] = '<li><span><strong>'.$lang_topic['Timezone'].' </strong>'.$time_dif.' '.$lang_topic['From yours'].'</span></li>';
	}

	if ($forum_config['o_show_post_count'] || $forum_user['is_admmod'])
		$forum_page['author_info']['posts'] = '<li><span>'.$lang_topic['Posts info'].' <strong><a href="'.forum_link($forum_url['search_user_posts'], $cur_message['sender_id']).'">'.forum_number_format($cur_message['num_posts']).'</a></strong></span></li>';

	if ($forum_user['is_admmod'])
	{
		if ($cur_message['admin_note'] != '')
			$forum_page['author_info']['note'] = '<li><span>'.$lang_topic['Note'].' <strong> '.forum_htmlencode($cur_message['admin_note']).'</strong></span></li>';
	}

	$forum_page['message']['message'] = parse_message($cur_message['message'], false);

	if ($cur_message['url'] != '')
		$forum_page['post_contacts']['url'] = '<span class="user-url'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a class="external" href="'.forum_link('click.php').'?'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($cur_message['url']) : $cur_message['url']).'" onclick="window.open(this.href); return false" rel="nofollow">'.sprintf($lang_topic['Visit website'], '<span>'.sprintf($lang_topic['User possessive'], forum_htmlencode($cur_message['username'])).'</span>').'</a></span>';
	if ((($cur_message['email_setting'] == '0' && !$forum_user['is_guest']) || $forum_user['is_admmod']) && $forum_user['g_send_email'])
		$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.forum_htmlencode($cur_message['email']).'">'.$lang_profile['E-mail'].'<span>&#160;'.forum_htmlencode($cur_message['username']).'</span></a></span>';
	else if ($cur_message['email_setting'] == '1' && !$forum_user['is_guest'] && $forum_user['g_send_email'])
		$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['email'], $cur_message['sender_id']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_message['username']).'</span></a></span>';

	if (!empty($forum_page['post_contacts']))
		$forum_page['post_options']['contacts'] = '<p class="post-contacts">'.implode(' ', $forum_page['post_contacts']).'</p>';

	if ($forum_config['o_report_enabled'])
		$forum_page['post_actions']['report'] = '<span class="report-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['report'], array($cur_message['id'], 'pm')).'">'.$lang_topic['Report'].'</a></span>';

	$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['pm_delete'], $cur_message['id']).'" onclick="return confirm(\''.$lang_pm['Delete confirmation 1'].'\');" >'.$lang_pm['Delete message'].'</a></span>';

	if ($type == 'outbox' && ($cur_message['status'] == 'draft' || $cur_message['status'] == 'sent'))
		$forum_page['post_actions']['edit'] = '<span class="edit-topic'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['pm_edit'], $cur_message['id']).'">'.$lang_pm['Edit message'].'</a></span>';

	if (!empty($forum_page['post_actions']))
		$forum_page['post_options']['actions'] = '<p class="post-actions">'.implode(' ', $forum_page['post_actions']).'</p>';

 	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
 		array(sprintf($lang_profile['Users profile'], $forum_user['username']), forum_link($forum_url['profile'], array($forum_user['id'], 'about'))),
 		array($lang_profile['Private messages'], forum_link($forum_url['pm'], $section))
	);

	($hook = get_hook('pm_fl_section_mesage_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'pm-message');
	require FORUM_ROOT.'header.php';

	// START SUBST - <forum_main>
	ob_start();

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
	<div class="admin-submenu gen-content">
 		<ul>
			<?php echo implode("\n\t\t\t\t", $forum_links['pm'])."\n" ?>
 		</ul>
 	</div>
	<div class="main-subhead">
		<h3 class="hn"><span><?php echo $forum_page['heading'] ?></span></h3>
	</div>
	<div class="main-content main-frm">
<?php

	if ($type == 'outbox' && $cur_message['status'] == 'sent')
	{

?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_pm['Sent note']?></span></h2>
		</div>
<?php

	}

?>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $forum_page['user_text'] ?></span></h4>
					<h4 class="hn"><?php echo $forum_page['user_content'] ?></h4>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
<?php

	if ($type == 'inbox')
	{
		($hook = get_hook('pm_fl_pre_legend_edited')) ? eval($hook) : null;
?>
					<h4 class="ct-legend hn"><span><?php echo $lang_pm['Sent'] ?></span></h4>
					<h4 class="hn"><?php echo $cur_message['edited'] ? format_time($cur_message['edited'])."\n" : $lang_profile['Not sent'] ?></h4>
<?php

	}
	else
	{
		($hook = get_hook('pm_fl_pre_legend_status')) ? eval($hook) : null;
?>
					<h4 class="ct-legend hn"><span><?php echo $lang_pm['Status'] ?></span></h4>
					<h4 class="hn"><?php echo $lang_pm['Status '.$cur_message['status']], $cur_message['status'] == 'read' ? ' '.format_time($cur_message['readed']) : '' ?></h4>
<?php

	}

?>
				</div>
			</div>
<?php ($hook = get_hook('pm_fl_pre_legend_subject')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $lang_pm['Subject'] ?></span></h4>
					<h4 class="hn"><?php echo $cur_message['subject'] ? forum_htmlencode($cur_message['subject'])."\n" : $lang_pm['Empty'] ?></h4>
				</div>
			</div>
		</fieldset>
		<div class="post">
			<div class="posthead">
				<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
			</div>
			<div class="postbody online">
				<div class="post-author">
					<ul class="author-ident">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_ident'])."\n" ?>
					</ul>
					<ul class="author-info">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_info'])."\n" ?>
					</ul>
				</div>
				<div class="post-entry">
					<div class="entry-content">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['message'])."\n" ?>
					</div>
<?php ($hook = get_hook('pm_fl_row_message_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
			<div class="postfoot">
				<div class="post-options">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['post_options'])."\n" ?>
				</div>
			</div>
		</div>
	</div>
<?php

	if (isset($cur_message['id']) && $type == 'inbox')
	{
		$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
		$forum_page['form_action'] = forum_link($forum_url['pm'], 'write');

		$forum_page['hidden_fields'] = array(
			'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
			'send_action'	=> '<input type="hidden" name="req_receiver" value="'.forum_htmlencode($cur_message['username']).'" />'
		);

		// Setup help
		$forum_page['text_options'] = array();
		if ($forum_config['p_message_bbcode'])
			$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
		if ($forum_config['p_message_img_tag'])
			$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
		if ($forum_config['o_smilies'])
			$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

		($hook = get_hook('pm_fl_message_review_row_pre_display')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h3 class="hn"><span><?php echo $lang_pm['Reply'] ?></span></h3>
	</div>
	<div class="main-content main-frm">
<?php
		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

	if (substr($cur_message['subject'], 0, 4) == 'Ответ: ')
		$cur_message['subject'] = 'Oтвет[2]: ' . substr($cur_message['subject'], 4);

	$subject_reply = preg_replace_callback('#^Ответ\[(\d{1,10})\]: #eu', '\'Ответ[\'.(\\1 + 1).\']: \'', $cur_message['subject']);

	$cur_message['subject'] = $cur_message['subject'] == $subject_reply ? 'Ответ: ' . $cur_message['subject'] : $subject_reply;

?>
		<form class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_pm['Send message'] ?></strong></legend>
<?php ($hook = get_hook('pm_fl_message_pre_subject')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pm['Subject'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="subject" value="<?php echo forum_htmlencode($cur_message['subject']) ?>" size="80" maxlength="255" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pm_fl_message_pre_req_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_pm['Message'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label>
<?php require FORUM_ROOT.'resources/editor/post_bb.php'; ?>
						<div class="txt-input"><span class="fld-input"><textarea id="text" name="req_message" rows="10" cols="65"></textarea></span></div>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_pm['Send button'] ?>" /></span>
				<span class="submit"><input type="submit" name="preview" value="<?php echo $lang_pm['Preview'] ?>" /></span>
				<span class="submit"><input type="submit" name="draft" value="<?php echo $lang_pm['Save draft'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	}

	$tpl_temp = trim(ob_get_contents());
	$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_main>

	require FORUM_ROOT.'footer.php';
}

($hook = get_hook('pm_change_details_new_section')) ? eval($hook) : null;

message($lang_common['Bad request'], false, '404 Not Found');