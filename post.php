<?php
/**
 * Добавляет новое сообщение в указанную тему или новую тему в указанный форум.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */
define('FORUM_SKIP_CSRF_CONFIRM', 1);

if (!defined('FORUM_ROOT'))
    define('FORUM_ROOT', './');
require FORUM_ROOT . 'include/common.php';

($hook = get_hook('po_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'])
    message($lang_common['No view']);

// Load the post.php language file
require FORUM_ROOT . 'lang/' . $forum_user['language'] . '/post.php';

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($tid < 1 && $fid < 1 || $tid > 0 && $fid > 0)
    message($lang_common['Bad request']);


// Fetch some info about the topic and/or the forum
if ($tid) {
    $query = array(
        'SELECT' => 'f.id, f.forum_name, f.moderators, f.redirect_url, f.counter, fp.post_replies, fp.post_topics, t.subject, t.description, t.closed, s.user_id AS is_subscribed',
        'FROM'   => 'topics AS t',
        'JOINS'  => array(
            array(
                'INNER JOIN' => 'forums AS f',
                'ON'         => 'f.id=t.forum_id'
            ),
            array(
                'LEFT JOIN' => 'forum_perms AS fp',
                'ON'        => 'fp.forum_id=f.id AND fp.group_id=' . $forum_user['g_id']
            ),
            array(
                'LEFT JOIN' => 'subscriptions AS s',
                'ON'        => 't.id=s.topic_id AND s.user_id=' . $forum_user['id']
            )
        ),
        'WHERE'  => '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id=' . $tid
    );

    if ($forum_config['o_merge_timeout']) {
        $query['SELECT']  .= ', p.id AS post_id, p.poster_id, p.message, p.posted';
        $query['JOINS'][] = array(
            'LEFT JOIN' => 'posts AS p',
            'ON'        => '(t.last_post_id=p.id AND p.poster_id=' . $forum_user['id'] . ')'
        );
    }

    ($hook = get_hook('po_qr_get_topic_forum_info')) ? eval($hook) : null;
} else {
    $query = array(
        'SELECT' => 'f.id, f.forum_name, f.moderators, f.redirect_url, f.counter, fp.post_replies, fp.post_topics',
        'FROM'   => 'forums AS f',
        'JOINS'  => array(
            array(
                'LEFT JOIN' => 'forum_perms AS fp',
                'ON'        => 'fp.forum_id=f.id AND fp.group_id=' . $forum_user['g_id']
            )
        ),
        'WHERE'  => '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id=' . $fid
    );

    ($hook = get_hook('po_qr_get_forum_info')) ? eval($hook) : null;
}

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

if (!$forum_db->num_rows($result))
    message($lang_common['Bad request']);

$cur_posting   = $forum_db->fetch_assoc($result);
$is_subscribed = $tid && $cur_posting['is_subscribed'];


// Is someone trying to post into a redirect forum?
if ($cur_posting['redirect_url'] != '')
    message($lang_common['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array              = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

($hook = get_hook('po_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to post?
if ((($tid && (($cur_posting['post_replies'] == '' && !$forum_user['g_post_replies']) || $cur_posting['post_replies'] == '0')) ||
        ($fid && (($cur_posting['post_topics'] == '' && !$forum_user['g_post_topics']) || $cur_posting['post_topics'] == '0')) ||
        (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
        !$forum_page['is_admmod'])
    message($lang_common['No permission']);

($hook = get_hook('po_posting_location_selected')) ? eval($hook) : null;

//Can unvoted user read voting results?
$read_unvote = ($forum_config['p_poll_enable_read'] && isset($_POST['read_unvote']) && $_POST['read_unvote'] == 1) ? 1 : 0;
//Can user change opinion?
$revote      = ($forum_config['p_poll_enable_revote'] && isset($_POST['revouting']) && $_POST['revouting'] == 1) ? 1 : 0;

// Start with a clean slate
$errors = array();

$options_count = 2;
if (isset($_POST['update_poll'])) {
    ($hook = get_hook('po_form_update_poll')) ? eval($hook) : null;

    // Check for use of incorrect URLs
    confirm_current_url(forum_link($forum_url['new_topic'], $fid));

    $subject     = forum_trim($_POST['req_subject']);
    $description = forum_trim($_POST['description']);
    $message     = forum_linebreaks(forum_trim($_POST['req_message']));
    $question    = forum_trim($_POST['question']);
    $answers     = array_values($_POST['answer']);
    $days        = intval($_POST['days']);
    $votes       = intval($_POST['votes']);

    ($hook = get_hook('po_form_update_poll_post')) ? eval($hook) : null;

    $options_count = (!isset($_POST['ans_count']) || intval($_POST['ans_count']) < 2) ? 0 : intval($_POST['ans_count']);

    if ($options_count == 0) {
        $errors[]      = $lang_post['Min count options'];
        $options_count = 2;
    }
    if ($options_count > $forum_config['p_poll_max_answers']) {
        $errors[]      = sprintf($lang_post['Max count options'], $forum_config['p_poll_max_answers']);
        $options_count = $forum_config['p_poll_max_answers'];
    }

    ($hook = get_hook('po_form_update_poll_end')) ? eval($hook) : null;
}

// Did someone just hit "Submit" or "Preview"?
else if (isset($_POST['form_sent'])) {
    ($hook = get_hook('po_form_submitted')) ? eval($hook) : null;

    // Make sure form_user is correct
    if (($forum_user['is_guest'] && $_POST['form_user'] != 'Guest') || (!$forum_user['is_guest'] && $_POST['form_user'] != $forum_user['username']))
        message($lang_common['Bad request']);

    // Check for use of incorrect URLs
    confirm_current_url($fid ? forum_link($forum_url['new_topic'], $fid) : forum_link($forum_url['new_reply'], $tid));

    if (empty($cur_posting['posted']))
        $cur_posting['posted'] = 0;

    // Flood protection
    if (!isset($_POST['preview']) && $forum_user['last_post'] != '' && (time() - $forum_user['last_post']) < $forum_user['g_post_flood'] && (time() - $forum_user['last_post']) >= 0 && time() - $cur_posting['posted'] > $forum_config['o_merge_timeout'])
        $errors[] = sprintf($lang_post['Flood'], $forum_user['g_post_flood']);

    // If it's a new topic
    if ($fid) {
        $subject  = forum_trim($_POST['req_subject']);
        if ($subject == '')
            $errors[] = $lang_post['No subject'];
        else if (utf8_strlen($subject) > 70)
            $errors[] = $lang_post['Too long subject'];
        else if (!$forum_config['p_subject_all_caps'] && check_is_all_caps($subject) && !$forum_page['is_admmod'])
            $errors[] = $lang_post['All caps subject'];

        $description = forum_trim($_POST['description']);
        if (utf8_strlen($description) > 100)
            $errors[]    = $lang_post['Too long description'];
        else if (!$forum_config['p_subject_all_caps'] && check_is_all_caps($description) && !$forum_page['is_admmod'])
            $errors[]    = $lang_post['All caps description'];

        $question = forum_trim($_POST['question']);
        if (utf8_strlen($question) < 6 && $question != '')
            $errors[] = $lang_post['Poll question info'];

        $answers_form = $_POST['answer'];
        $answers      = array();
        if ($question != '') {
            foreach ($answers_form as $ans_num => $ans)
                if ($ans != '')
                    $answers[] = forum_trim($ans);

            $answers  = array_unique($answers);
            if (count($answers) < 2)
                $errors[] = $lang_post['Min count options'];
        }

        $read_unvote = ($forum_config['p_poll_enable_read'] && (isset($_POST['read_unvote']) && $_POST['read_unvote'] == '1')) ? '1' : '0';
        $revote      = ($forum_config['p_poll_enable_revote'] && (isset($_POST['revote']) && $_POST['revote'] == '1')) ? '1' : '0';

        $days     = intval($_POST['days']);
        if ($days > 90 || $days < 0)
            $errors[] = $lang_post['Days limit'];

        $votes    = intval($_POST['votes']);
        if ($votes != 0 && ($votes > 1000 || $votes < 10))
            $errors[] = $lang_post['Votes count'];

        if ($question != '' && $days < 0 && $votes == 0)
            $errors[] = $lang_post['Input error'];
    }

    // If the user is logged in we get the username and e-mail from $forum_user
    if (!$forum_user['is_guest']) {
        $username = $forum_user['username'];
        $email    = $forum_user['email'];
    }
    // Otherwise it should be in $_POST
    else {
        $username = forum_trim($_POST['req_username']);
        $email    = utf8_strtolower(forum_trim(($forum_config['p_force_guest_email']) ? $_POST['req_email'] : $_POST['email']));

        // It's a guest, so we have to validate the username
        require FORUM_ROOT . 'include/functions/validate_username.php';
        $errors = array_merge($errors, validate_username($username));

        if ($forum_config['p_force_guest_email'] || $email != '') {
            if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
                require FORUM_ROOT . 'include/functions/email.php';

            if (!is_valid_email($email))
                $errors[] = $lang_post['Invalid e-mail'];
            if (is_banned_email($email))
                $errors[] = $lang_post['Banned e-mail'];
        }

        $stop_spam = array(
            'email'    => $email,
            'ip'       => get_remote_address(),
            'username' => $username
        );

        if (stop_spam($stop_spam))
            $errors[] = $lang_post['Blocked spamer'];
    }

    // If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
    if ($forum_user['is_admmod'] && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url())))
        $errors[] = $lang_post['CSRF token mismatch'];

    // Clean up message from POST
    $message = forum_linebreaks(forum_trim($_POST['req_message']));

    if (utf8_strlen($message) > FORUM_MAX_POSTSIZE)
        $errors[] = sprintf($lang_post['Too long message'], forum_number_format(utf8_strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE));
    else if (!$forum_config['p_message_all_caps'] && check_is_all_caps($message) && !$forum_page['is_admmod'])
        $errors[] = $lang_post['All caps message'];

    $merged = false;
    if (!$fid && ((!empty($forum_config['o_merge_timeout']) && time() - $cur_posting['posted'] < $forum_config['o_merge_timeout']) || $forum_config['o_merge_timeout'] == '1') && !$forum_user['is_guest'] && !empty($cur_posting['post_id'])) {
        $merge = isset($_POST['merge']) ? 1 : 0;
        if ((($forum_user['is_admmod'] && $merge == 1) || !$forum_user['is_admmod'])) {
            $message = forum_linebreaks('[color=#808080][i]' . $lang_post['Added'] . flazy_format_time($cur_posting['posted'], 2) . '[/i][/color]') . "\n" . $message;
            $message = $cur_posting['message'] . "\n\n" . $message;
            $merged  = true;
        }
    }

    // Validate BBCode syntax
    if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links']) {
        if (!defined('FORUM_PARSER_LOADED'))
            require FORUM_ROOT . 'include/parser.php';

        $message = preparse_bbcode($message, $errors);
    }

    if ($message == '')
        $errors[] = $lang_post['No message'];

    $hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;
    $subscribe    = isset($_POST['subscribe']) ? 1 : 0;

    $now = time();

    ($hook = get_hook('po_end_validation')) ? eval($hook) : null;

    // Did everything go according to plan?
    if (empty($errors) && !isset($_POST['preview'])) {
        // If it's a reply
        if ($tid && !$merged) {
            $post_info = array(
                'is_guest'      => $forum_user['is_guest'],
                'poster'        => $username,
                'poster_id'     => $forum_user['id'],
                'poster_email'  => ($forum_user['is_guest'] && $email != '') ? $email : null,
                'subject'       => $cur_posting['subject'],
                'message'       => $message,
                'hide_smilies'  => $hide_smilies,
                'posted'        => $now,
                'subscr_action' => ($forum_config['o_subscriptions'] && $subscribe && !$is_subscribed) ? 1 : (($forum_config['o_subscriptions'] && !$subscribe && $is_subscribed) ? 2 : 0),
                'topic_id'      => $tid,
                'forum_id'      => $cur_posting['id'],
                'update_user'   => true,
                'counter'       => $cur_posting['counter'],
                'update_unread' => true
            );

            ($hook = get_hook('po_pre_add_post')) ? eval($hook) : null;
            if (!defined('FORUM_FUNCTIONS_ADD_POST'))
                require FORUM_ROOT . 'include/functions/add_post.php';
            add_post($post_info, $new_pid);
        }
        else if ($tid && $merged) {
            $post_info = array(
                'message'      => $message,
                'hide_smilies' => $hide_smilies,
                'poster_id'    => $forum_user['id'],
                'post_id'      => $cur_posting['post_id'],
                'posted'       => $now,
                'topic_id'     => $tid,
                'forum_id'     => $cur_posting['id'],
            );

            ($hook = get_hook('po_pre_merged_post')) ? eval($hook) : null;
            if (!defined('FORUM_FUNCTIONS_MERGET_POST'))
                require FORUM_ROOT . 'include/functions/merged_post.php';
            merged_post($post_info, $new_pid);
        }
        // If it's a new topic
        else if ($fid) {
            $post_info = array(
                'is_guest'      => $forum_user['is_guest'],
                'poster'        => $username,
                'poster_id'     => $forum_user['id'],
                'poster_email'  => ($forum_user['is_guest'] && $email != '') ? $email : null,
                'subject'       => $subject,
                'description'   => $description,
                'message'       => $message,
                'hide_smilies'  => $hide_smilies,
                'posted'        => $now,
                'question'      => $question,
                'answers'       => $answers,
                'days'          => $days,
                'votes'         => $votes,
                'read_unvote'   => $read_unvote,
                'revote'        => $revote,
                'poll_created'  => ($question) ? $now : 0,
                'subscribe'     => ($forum_config['o_subscriptions'] && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
                'forum_id'      => $fid,
                'update_user'   => true,
                'counter'       => $cur_posting['counter'],
                'update_unread' => true
            );

            ($hook = get_hook('po_pre_add_topic')) ? eval($hook) : null;
            if (!defined('FORUM_FUNCTIONS_ADD_TOPIC'))
                require FORUM_ROOT . 'include/functions/add_topic.php';

            add_topic($post_info, $new_tid, $new_pid);
        }

        ($hook = get_hook('po_pre_redirect')) ? eval($hook) : null;

        redirect(forum_link($forum_url['post'], $new_pid), $lang_post['Post redirect']);
    }
}


// Are we quoting someone?
if ($tid && isset($_GET['qid'])) {
    $qid = intval($_GET['qid']);
    if ($qid < 1)
        message($lang_common['Bad request']);

    // Check for use of incorrect URLs
    confirm_current_url(forum_link($forum_url['quote'], array($tid, $qid)));

    // Get the quote and quote poster
    $query = array(
        'SELECT' => 'p.poster, p.message',
        'FROM'   => 'posts AS p',
        'WHERE'  => 'id=' . $qid . ' AND topic_id=' . $tid
    );

    if (isset($_POST['post_msg']))
        $query['SELECT'] = 'p.poster, \'' . $forum_db->escape($_POST['post_msg']) . '\'';

    ($hook   = get_hook('po_qr_get_quote')) ? eval($hook) : null;
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    if (!$forum_db->num_rows($result))
        message($lang_common['Bad request']);

    list($q_poster, $q_message) = $forum_db->fetch_row($result);

    ($hook = get_hook('po_modify_quote_info')) ? eval($hook) : null;

    if ($forum_config['p_message_bbcode']) {
        // If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
        if (strpos($q_poster, '[') !== false || strpos($q_poster, ']') !== false) {
            if (strpos($q_poster, '\'') !== false)
                $q_poster = '"' . $q_poster . '"';
            else
                $q_poster = '\'' . $q_poster . '\'';
        }
        else {
            // Get the characters at the start and end of $q_poster
            $ends = utf8_substr($q_poster, 0, 1) . utf8_substr($q_poster, -1, 1);

            // Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
            if ($ends == '\'\'')
                $q_poster = '"' . $q_poster . '"';
            else if ($ends == '""')
                $q_poster = '\'' . $q_poster . '\'';
        }

        $forum_page['quote'] = '[quote=' . $q_poster . ']' . $q_message . '[/quote]' . "\n";
    } else
        $forum_page['quote'] = '> ' . $q_poster . ' ' . $lang_common['wrote'] . ':' . "\n\n" . '> ' . $q_message . "\n";
}

if (!isset($_GET['qid']))
    confirm_current_url($fid ? forum_link($forum_url['new_topic'], $fid) : forum_link($forum_url['new_reply'], $tid));


// Setup form
$forum_page['group_count']     = $forum_page['item_count']      = $forum_page['fld_count']       = 0;
$forum_page['form_action']     = ($tid ? forum_link($forum_url['new_reply'], $tid) : forum_link($forum_url['new_topic'], $fid));
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
    'form_sent'  => '<input type="hidden" name="form_sent" value="1" />',
    'form_user'  => '<input type="hidden" name="form_user" value="' . ((!$forum_user['is_guest']) ? forum_htmlencode($forum_user['username']) : 'Guest') . '" />',
    'csrf_token' => '<input type="hidden" name="csrf_token" value="' . generate_form_token($forum_page['form_action']) . '" />'
);

// Setup help
$forum_page['text_options']            = array();
if ($forum_config['p_message_bbcode'])
    $forum_page['text_options']['bbcode']  = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . forum_link($forum_url['help'], 'bbcode') . '" title="' . sprintf($lang_common['Help page'], $lang_common['BBCode']) . '">' . $lang_common['BBCode'] . '</a></span>';
if ($forum_config['p_message_img_tag'])
    $forum_page['text_options']['img']     = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . forum_link($forum_url['help'], 'img') . '" title="' . sprintf($lang_common['Help page'], $lang_common['Images']) . '">' . $lang_common['Images'] . '</a></span>';
if ($forum_config['o_smilies'])
    $forum_page['text_options']['smilies'] = '<span' . (empty($forum_page['text_options']) ? ' class="first-item"' : '') . '><a class="exthelp" href="' . forum_link($forum_url['help'], 'smilies') . '" title="' . sprintf($lang_common['Help page'], $lang_common['Smilies']) . '">' . $lang_common['Smilies'] . '</a></span>';

// Setup breadcrumbs
$forum_page['crumbs'][] = array($forum_config['o_board_title'], forum_link($forum_url['index']));
$forum_page['crumbs'][] = array($cur_posting['forum_name'], forum_link($forum_url['forum'], array($cur_posting['id'], sef_friendly($cur_posting['forum_name']))));
if ($tid)
    $forum_page['crumbs'][] = array($cur_posting['subject'], forum_link($forum_url['topic'], array($tid, sef_friendly($cur_posting['subject']))));
$forum_page['crumbs'][] = $tid ? $lang_post['Post reply'] : $lang_post['Post new topic'];

($hook = get_hook('po_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE', (isset($fid) ? 'new-' : '') . 'post');
require FORUM_ROOT . 'header.php';

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('po_main_output_start')) ? eval($hook) : null;

// If preview selected and there are no errors
if (isset($_POST['preview']) && empty($errors)) {
    $forum_js->code('$(document).ready( function() {
		$(\'.hide-head\').toggle(
			function() {
			$(this).children().text(\'' . $lang_common['Hidden text'] . '\');
				$(this).next().show("slow");
			},
			function() {
				$(this).children().text(\'' . $lang_common['Hidden show text'] . '\');
				$(this).next().hide("slow");
			}
		);
	})');

    if (!defined('FORUM_PARSER_LOADED'))
        require FORUM_ROOT . 'include/parser.php';

    $forum_page['preview_message'] = parse_message(forum_trim($message), $hide_smilies);

    // Generate the post heading
    $forum_page['post_ident']           = array();
    $forum_page['post_ident']['num']    = '<span class="post-num">#</span>';
    $forum_page['post_ident']['byline'] = '<span class="post-byline">' . sprintf((($tid) ? $lang_post['Reply byline'] : $lang_post['Topic byline']), '<strong>' . forum_htmlencode($forum_user['username']) . '</strong>') . '</span>';
    $forum_page['post_ident']['link']   = '<span class="post-link">' . format_time(time()) . '</span>';

    ($hook = get_hook('po_preview_pre_display')) ? eval($hook) : null;
    ?>
    <div id="post-preview" class="card">
        <div class="card-content">
            <span class="card-title"><?php echo $tid ? $lang_post['Preview reply'] : $lang_post['Preview new topic']; ?> <small><?php echo implode(' ', $forum_page['post_ident']) ?></small></span>
                <?php echo $forum_page['preview_message'] . "\n" ?>
                <?php ($hook = get_hook('po_preview_new_post_entry_data')) ? eval($hook) : null; ?>
        </div>
    </div>
    <?php
}
?>
    <?php
// If there were any errors, show them
    if (!empty($errors)) {
        $forum_page['errors']   = array();
        foreach ($errors as $cur_error)
            $forum_page['errors'][] = '<span>' . $cur_error . '</span>';

        ($hook = get_hook('po_pre_post_errors')) ? eval($hook) : null;
        ?>
        <div class="alert alert-danger">
            <h5 class="alert-title"><?php echo $lang_post['Post errors'] ?></h5>
            <p>
                <?php echo implode("\n\t\t\t\t", $forum_page['errors']) . "\n" ?>
            </p>
        </div>
        <?php
    }
    ?>
<div id="post-form" class="card">
    <div class="card-content">
        <span class="card-title"><?php echo ($tid) ? $lang_post['Compose your reply'] : $lang_post['Compose your topic'] ?></span>
    
    <form id="afocus" class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' ' . implode(' ', $forum_page['form_attributes']) ?>>
        <div class="hidden">
            <?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields']) . "\n" ?>
        </div>
        <?php
        if ($forum_user['is_guest']) {
            $forum_page['email_form_name'] = ($forum_config['p_force_guest_email']) ? 'req_email' : 'email';

            ($hook = get_hook('po_pre_guest_info_fieldset')) ? eval($hook) : null;
            ?>
            <fieldset class="row group<?php echo ++$forum_page['group_count'] ?>">
                <legend class="group-legend"><strong><?php echo $lang_post['Guest post legend'] ?></strong></legend>
                <?php ($hook = get_hook('po_pre_guest_username')) ? eval($hook) : null; ?>
                <div class="col s12 m12 l6 set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text required">
                        <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Guest name'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label><br />
                        <span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_username" value="<?php if (isset($_POST['req_username'])) echo forum_htmlencode($username); ?>" size="35" maxlength="25" /></span>
                    </div>
                </div>
                <?php ($hook = get_hook('po_pre_guest_email')) ? eval($hook) : null; ?>
                <div class="col s12 m12 l6 set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text<?php if ($forum_config['p_force_guest_email']) echo ' required' ?>">
                        <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Guest e-mail'] ?><?php if ($forum_config['p_force_guest_email']) echo ' <em>' . $lang_common['Required'] . '</em>' ?></span></label><br />
                        <span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="<?php echo $forum_page['email_form_name'] ?>" value="<?php if (isset($_POST[$forum_page['email_form_name']])) echo forum_htmlencode($email); ?>" size="35" maxlength="80" /></span>
                    </div>
                </div>
                <?php ($hook = get_hook('po_pre_guest_info_fieldset_end')) ? eval($hook) : null; ?>
            </fieldset>
            <?php
            ($hook = get_hook('po_guest_info_fieldset_end')) ? eval($hook) : null;

            // Reset counters
            $forum_page['group_count'] = $forum_page['item_count']  = 0;
        }

        ($hook = get_hook('po_pre_req_info_fieldset')) ? eval($hook) : null;
        ?>
        <fieldset class="row group<?php echo ++$forum_page['group_count'] ?>">
            <?php
            if ($fid) {
                ($hook = get_hook('po_pre_req_subject')) ? eval($hook) : null;
                ?>
                <div class="col m12 s12 l6 set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text required longtext">
                        <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Topic subject'] ?> <?php if ($forum_config['p_force_guest_email']) echo '<em>' . $lang_common['Required'] . '</em>' ?></span></label><br />
                        <span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="req_subject" value="<?php if (isset($_POST['req_subject'])) echo forum_htmlencode($subject); ?>" size="80" maxlength="70" /></span>
                    </div>
                </div>
                <div class="col m12 s12 l6 set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text longtext">
                        <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Topic description'] ?><em></em></span></label>
                        <span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="description" value="<?php if (isset($_POST['description'])) echo forum_htmlencode($description); ?>" size="80" maxlength="100" /></span>
                    </div>
                </div>
                <?php
            }

            ($hook = get_hook('po_pre_post_contents')) ? eval($hook) : null;
            ?>
            <div class="col m12 s12 l12 set<?php echo ++$forum_page['item_count'] ?>">
                <div class="row">
                   
                     <?php require FORUM_ROOT . '/resources/editor/post_bb.php'; ?>
                    <div class="txt-input">
                        <span class="fld-input">
                            <textarea id="text" name="req_message" rows="14" cols="95">
                                <?php echo isset($_POST['req_message']) ? forum_htmlencode($message) : (isset($forum_page['quote']) ? forum_htmlencode($forum_page['quote']) : '') ?>
                            </textarea>
                        </span>
                    </div>
                </div>
            </div>
            <?php
            $forum_page['checkboxes']                 = array();
            if ($forum_config['o_smilies'])
                $forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld' . ( ++$forum_page['fld_count']) . '" name="hide_smilies" value="1"' . (isset($_POST['hide_smilies']) ? ' checked="checked"' : '') . ' /></span> <label for="fld' . $forum_page['fld_count'] . '">' . $lang_post['Hide smilies'] . '</label></div>';
            if ($tid && $forum_config['o_merge_timeout'])
                $forum_page['checkboxes']['merge']        = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld' . ( ++$forum_page['fld_count']) . '" name="merge" value="1"' . (!isset($_POST['merge']) ? ' checked="checked"' : '') . ' /></span> <label for="fld' . $forum_page['fld_count'] . '">' . $lang_post['Merge posts'] . '</label></div>';

// Check/uncheck the checkbox for subscriptions depending on scenario
            if (!$forum_user['is_guest'] && $forum_config['o_subscriptions']) {
                $subscr_checked = false;

                // If it's a preview
                if (isset($_POST['preview']))
                    $subscr_checked = isset($_POST['subscribe']) ? true : false;
                // If auto subscribed
                else if ($forum_user['auto_notify'])
                    $subscr_checked = true;
                // If already subscribed to the topic
                else if ($is_subscribed)
                    $subscr_checked = true;

                $forum_page['checkboxes']['subscribe'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld' . ( ++$forum_page['fld_count']) . '" name="subscribe" value="1"' . ($subscr_checked ? ' checked="checked"' : '') . ' /></span> <label for="fld' . $forum_page['fld_count'] . '">' . ($is_subscribed ? $lang_post['Stay subscribed'] : $lang_post['Subscribe']) . '</label></div>';
            }

            ($hook = get_hook('po_pre_optional_fieldset')) ? eval($hook) : null;

            if (!empty($forum_page['checkboxes'])) {
                ?>
                <fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
                    <legend><span><?php echo $lang_post['Post settings'] ?></span></legend>
                    <div class="mf-box checkbox">
                        <?php echo implode("\n\t\t\t\t\t\t", $forum_page['checkboxes']) . "\n"; ?>
                    </div>
                    <?php ($hook = get_hook('po_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
                </fieldset>
                <?php
            }

            ($hook = get_hook('po_pre_req_info_fieldset_end')) ? eval($hook) : null;
            ?>
        </fieldset>
        <?php
        ($hook = get_hook('po_req_info_fieldset_end')) ? eval($hook) : null;

//Show form for creation of poll
        if ($fid && ($forum_user['g_poll_add'] || $forum_user['g_id'] == FORUM_ADMIN)) {
            require FORUM_ROOT . 'include/functions/form_poll.php';

            form_poll(isset($_POST['question']) ? $_POST['question'] : '', isset($answers) ? $answers : array(), $options_count, isset($days) ? $days : '', isset($votes) ? $votes : '');
        }
        ?>
        <div class="frm-buttons">
            <span class="submit"><input type="submit" name="submit" value="<?php echo ($tid) ? $lang_post['Submit reply'] : $lang_post['Submit topic'] ?>" /></span>
            <span class="submit"><input type="submit" name="preview" value="<?php echo ($tid) ? $lang_post['Preview reply'] : $lang_post['Preview topic'] ?>" /></span>
        </div>
    </form>
</div>
</div>
<?php
// Check if the topic review is to be displayed
if ($tid && $forum_config['o_topic_review'] != '0') {
    if (!defined('FORUM_PARSER_LOADED'))
        require FORUM_ROOT . 'include/parser.php';

    // Get the amount of posts in the topic 
    $query = array(
        'SELECT' => 'count(p.id)',
        'FROM'   => 'posts AS p',
        'WHERE'  => 'topic_id=' . $tid
    );

    ($hook                           = get_hook('po_topic_review_qr_get_post_count')) ? eval($hook) : null;
    $result                         = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    $forum_page['total_post_count'] = $forum_db->result($result, 0);

    // Get posts to display in topic review
    $query = array(
        'SELECT'   => 'p.id, p.poster, p.message, p.hide_smilies, p.posted',
        'FROM'     => 'posts AS p',
        'WHERE'    => 'topic_id=' . $tid,
        'ORDER BY' => 'id DESC',
        'LIMIT'    => $forum_config['o_topic_review']
    );

    ($hook                     = get_hook('po_topic_review_qr_get_topic_review_posts')) ? eval($hook) : null;
    $result                   = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    ?>
    <div class="main-subhead">
        <h2 class="hn"><span><?php echo $lang_post['Topic review'] ?></span></h2>
    </div>
    <div  id="topic-review" class="main-content main-frm">
        <?php
        $forum_page['item_count'] = 0;
        $forum_page['item_total'] = $forum_db->num_rows($result);

        while ($cur_post = $forum_db->fetch_assoc($result)) {
            ++$forum_page['item_count'];

            $forum_page['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

            // Generate the post heading
            $forum_page['post_ident']           = array();
            $forum_page['post_ident']['num']    = '<span class="post-num">' . forum_number_format($forum_page['total_post_count'] - $forum_page['item_count'] + 1) . '</span>';
            $forum_page['post_ident']['byline'] = '<span class="post-byline">' . sprintf($lang_post['Post byline'], '<strong>' . forum_htmlencode($cur_post['poster']) . '</strong>') . '</span>';
            $forum_page['post_ident']['link']   = '<span class="post-link"><a class="permalink" rel="bookmark" title="' . $lang_post['Permalink post'] . '" href="' . forum_link($forum_url['post'], $cur_post['id']) . '">' . format_time($cur_post['posted']) . '</a></span>';

            ($hook = get_hook('po_topic_review_row_pre_display')) ? eval($hook) : null;
            ?>
            <div class="post<?php echo ($forum_page['item_count'] == 1) ? ' firstpost' : '' ?><?php echo ($forum_page['item_total'] == $forum_page['item_count']) ? ' lastpost' : '' ?>">
                <div class="posthead">
                    <h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
                    <?php ($hook = get_hook('po_topic_review_new_post_head_option')) ? eval($hook) : null; ?>
                </div>
                <div class="postbody">
                    <div class="post-entry">
                        <div class="entry-content">
                            <?php echo $forum_page['message'] . "\n" ?>
                            <?php ($hook = get_hook('po_topic_review_new_post_entry_data')) ? eval($hook) : null; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

$forum_id = $cur_posting['id'];

($hook = get_hook('po_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT . 'footer.php';
