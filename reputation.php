<?php
/**
 * Просмотр репутации участников.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */
if (!defined('FORUM_ROOT'))
    define('FORUM_ROOT', './');
require FORUM_ROOT . 'include/common.php';

($hook = get_hook('rp_fl_start')) ? eval($hook) : null;

// Load the reputation.php language file
require FORUM_ROOT . 'lang/' . $forum_user['language'] . '/reputation.php';

if (!$forum_config['o_rep_enabled'])
    message($lang_reputation['Disabled']);
if (!$forum_user['g_rep_enable'])
    message($lang_reputation['Group disabled']);
if (!$forum_user['rep_enable_adm'])
    message($lang_reputation['Individual disabled']);
if (!$forum_user['rep_enable'])
    message($lang_reputation['Your disabled']);

$id      = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid     = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$method  = isset($_GET['method']) ? $_GET['method'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;

if (isset($_GET['section']) && $section != 'reputation' && $section != 'positive')
    message($lang_common['Bad request']);

$errors = array();

($hook = get_hook('rp_fl_rep_info')) ? eval($hook) : null;

if (isset($_POST['form_sent'])) {
    ($hook = get_hook('rp_fl_form_reputation')) ? eval($hook) : null;

    if (isset($_POST['delete'])) {
        confirm_current_url(forum_link($forum_url['reputation'], array($id, $section)));

        if (!$forum_user['is_admmod'])
            message($lang_common['No permission']);

        ($hook = get_hook('rp_fl_form_delete')) ? eval($hook) : null;

        if ($id < 2)
            message($lang_common['Bad request']);

        // Delete reputation
        $query = array(
            'DELETE' => 'reputation',
            'WHERE'  => 'id IN(' . implode(',', array_values($_POST['delete'])) . ')'
        );

        ($hook = get_hook('rp_fl_delete_reputation_qr_get')) ? eval($hook) : null;
        $forum_db->query_build($query) or error(__FILE__, __LINE__);

        $query = array(
            'SELECT'   => 'SUM(rp.plus) AS plus, SUM(rp.minus) AS minus',
            'FROM'     => 'reputation AS rp',
            'WHERE'    => 'rp.user_id=' . $id,
            'GROUP BY' => 'rp.user_id'
        );

        ($hook   = get_hook('rp_fl_sum_plus_minus_qr_get')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        if (!$forum_db->num_rows($result))
            $rep['plus']  = $rep['minus'] = 0;
        else
            $rep          = $forum_db->fetch_assoc($result);

        $query = array(
            'SELECT'   => 'SUM(rp.plus) AS plus, SUM(rp.minus) AS minus',
            'FROM'     => 'reputation AS rp',
            'WHERE'    => 'rp.from_user_id=' . $id,
            'GROUP BY' => 'rp.from_user_id'
        );

        ($hook   = get_hook('rp_fl_sum_plus_minus_qr_get')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        if (!$forum_db->num_rows($result))
            $pos['plus']  = $pos['minus'] = 0;
        else
            $pos          = $forum_db->fetch_assoc($result);

        $query = array(
            'UPDATE' => 'users',
            'SET'    => 'reputation_plus=' . $rep['plus'] . ', reputation_minus=' . $rep['minus'] . ', positive_plus=' . $pos['plus'] . ', positive_minus=' . $pos['minus'],
            'WHERE'  => 'id=' . $id
        );

        ($hook = get_hook('rp_fl_update_delete_rep_qr_get')) ? eval($hook) : null;
        $forum_db->query_build($query) or error(__FILE__, __LINE__);

        ($hook = get_hook('rp_fl_form_delete_rep_id_pre_redirect')) ? eval($hook) : null;

        redirect(forum_link($forum_url['reputation'], array($id, $section)), $lang_reputation['Deleted redirect']);
    }
    else {
        if ($forum_user['is_guest'])
            message($lang_common['No permission']);

        $poster_id = intval($_POST['poster_id']);

        if ($poster_id < 1 && $pid < 1 && ($method != 'positive' && $method != 'negative'))
            message($lang_common['Bad request']);

        confirm_current_url(forum_link($forum_url['reputation_change'], array($poster_id, $pid, $method)));

        require FORUM_ROOT . 'include/utf8/substr_replace.php';
        require FORUM_ROOT . 'include/utf8/ucwords.php';

        ($hook = get_hook('rp_fl_form_reputation_pre_qr')) ? eval($hook) : null;

        $query = array(
            'SELECT'   => 'p.poster_id, p.id, p.topic_id, t.subject, u.rep_enable, rp.time',
            'FROM'     => 'posts AS p',
            'JOINS'    => array(
                array(
                    'INNER JOIN' => 'topics AS t',
                    'ON'         => 'p.topic_id=t.id'
                ),
                array(
                    'LEFT JOIN' => 'users AS u',
                    'ON'        => 'p.poster_id=u.id'
                ),
                array(
                    'LEFT JOIN' => 'reputation AS rp',
                    'ON'        => 'rp.from_user_id=' . $forum_user['id'] . ' AND rp.user_id=u.id'
                )
            ),
            'WHERE'    => 'p.id=' . $pid . ' AND p.poster_id=' . $poster_id,
            'ORDER BY' => 'rp.time DESC',
            'LIMIT'    => '0, 1'
        );

        ($hook   = get_hook('rp_fl_reputation_qr_get')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        if (!$forum_db->num_rows($result))
            message($lang_common['Bad request']);

        $cur_rep = $forum_db->fetch_assoc($result);

        //Check last reputation point given timestamp
        if ($cur_rep['time']) {
            if ($forum_config['o_rep_timeout'] * 60 > (time() - $cur_rep['time']))
                message(sprintf($lang_reputation['Timeout'], $forum_config['o_rep_timeout']));
        }

        if ($cur_rep['rep_enable'] != 1)
            message($lang_reputation['User Disable']);

        // Prevent people from voting for themselves via URL hacking.
        if ($forum_user['id'] == $cur_rep['poster_id'])
            $errors[] = $lang_reputation['Silly user'];

        if ((($forum_user['g_rep_minus_min'] > $forum_user['num_posts']) && $method == 'negitive') || (($forum_user['g_rep_plus_min'] > $forum_user['num_posts']) && $method == 'positive'))
            $errors[] = $lang_reputation['Small Number of post'];

        // Clean up message from POST
        $message = forum_linebreaks(forum_trim($_POST['req_message']));

        // Check message
        if ($message == '')
            $errors[] = $lang_reputation['No message'];
        else if (utf8_strlen($message) > 100)
            $errors[] = $lang_reputation['Too long message'];
        else if (!$forum_config['p_message_all_caps'] && check_is_all_caps($message) && !$forum_page['is_admmod'])
            $errors[] = $lang_reputation['All caps message'];

        // Validate BBCode syntax
        if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links']) {
            if (!defined('FORUM_PARSER_LOADED'))
                require FORUM_ROOT . 'include/parser.php';

            $message = preparse_bbcode($message, $errors);
        }

        if (empty($errors)) {
            $column = ($method == 'positive') ? 'plus' : 'minus';

            //Add voice
            $query = array(
                'INSERT' => 'user_id, from_user_id, time, post_id, reason, topics_id, ' . $column,
                'INTO'   => 'reputation',
                'VALUES' => $cur_rep['poster_id'] . ', ' . $forum_user['id'] . ', ' . mktime() . ', ' . $cur_rep['id'] . ', \'' . $forum_db->escape($message) . '\', ' . $cur_rep['topic_id'] . ', 1'
            );

            ($hook = get_hook('rp_fl_insert_rep_column_qr_get')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);

            $query = array(
                'UPDATE' => 'users',
                'SET'    => 'reputation_' . $column . '=reputation_' . $column . '+1',
                'WHERE'  => 'id=' . $cur_rep['poster_id']
            );

            ($hook = get_hook('rp_fl_update_rep_column_qr_get')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);

            $query = array(
                'UPDATE' => 'users',
                'SET'    => 'positive_' . $column . '=positive_' . $column . '+1',
                'WHERE'  => 'id=' . $forum_user['id']
            );

            ($hook = get_hook('rp_fl_update_pos_column_qr_get')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);

            ($hook = get_hook('rp_fl_update_rep_pre_redirect')) ? eval($hook) : null;

            redirect(forum_link($forum_url['post'], $pid), $lang_reputation['Redirect Message']);
        }
    }
}


if ($section) {
    if ($id < 2)
        message($lang_common['Bad request']);

    $query = array(
        'SELECT' => 'u.username, ' . $section . '_plus, ' . $section . '_minus',
        'FROM'   => 'users AS u',
        'WHERE'  => 'u.id=' . $id
    );

    ($hook   = get_hook('rp_fl_current_page_qr_get')) ? eval($hook) : null;
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    if (!$forum_db->num_rows($result))
        message($lang_common['Bad request']);

    $user_rep = $forum_db->fetch_assoc($result);

    $forum_links['reputation'] = array(
        '<li' . ($section == 'respect' ? ' class="active"' : ' class="normal"') . '><a href="' . forum_link($forum_url['reputation'], array($id, 'reputation')) . '">' . $lang_reputation['reputation'] . '</a></li>',
        '<li' . ($section == 'positive' ? ' class="active"' : ' class="normal"') . '><a href="' . forum_link($forum_url['reputation'], array($id, 'positive')) . '">' . $lang_reputation['positive'] . '</a></li>'
    );

    $query = array(
        'SELECT' => 'COUNT(rp.id)',
        'FROM'   => 'reputation AS rp',
        'WHERE'  => 'rp.' . ($section == 'positive' ? 'from_user_id' : 'user_id') . '=' . $id
    );

    ($hook   = get_hook('rp_fl_count_used_id_qr_get')) ? eval($hook) : null;
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    list($num_rows) = $forum_db->fetch_row($result);

    if ($num_rows > 0) {
        $forum_page['num_pages']  = ceil(($num_rows + 1) / $forum_user['disp_posts']);
        $forum_page['page']       = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
        $forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);

        if ($forum_page['page'] < $forum_page['num_pages']) {
            $forum_page['nav']['last'] = '<link rel="last" href="' . forum_link($forum_url['reputation'], $forum_url['page'], $forum_page['num_pages'], array($id, $section)) . '" title="' . $lang_common['Page'] . ' ' . $forum_page['num_pages'] . '" />';
            $forum_page['nav']['next'] = '<link rel="next" href="' . forum_link($forum_url['reputation'], $forum_url['page'], $forum_page['num_pages'] + 1, array($id, $section)) . '" title="' . $lang_common['Page'] . ' ' . ($forum_page['page'] + 1) . '" />';
        }

        if ($forum_page['page'] > 1) {
            $forum_page['nav']['prev']  = '<link rel="prev" href="' . forum_link($forum_url['reputation'], $forum_url['page'] - 1, $forum_page['num_pages'], array($id, $section)) . '" title="' . $lang_common['Page'] . ' ' . ($forum_page['page'] - 1) . '" />';
            $forum_page['nav']['first'] = '<link rel="first" href="' . forum_link($forum_url['reputation'], array($id, $section)) . '" title="' . $lang_common['Page'] . ' 1" />';
        }

        $forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">' . $lang_common['Pages'] . '</span> ' . paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['reputation'], $lang_common['Paging separator'], array($id, $section)) . '</p>';
    }

    // Setup breadcrumbs
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array(sprintf($lang_reputation[$section . ' user'], forum_htmlencode($user_rep['username'])), forum_link($forum_url['reputation'], array($id, $section)))
    );

    // Setup main header
    $forum_page['main_title'] = $lang_reputation[$section];
    $forum_page['items_info'] = sprintf($lang_reputation[$section . ' user head'], forum_htmlencode($user_rep['username'])) . ' <strong>[+' . $user_rep[$section . '_plus'] . ' / -' . $user_rep[$section . '_minus'] . ']</strong>';

    if ($forum_user['is_admmod'] && $num_rows > 0) {
        $forum_page['main_head_options']['select_all'] = '<span ' . (empty($forum_page['main_foot_options']) ? ' class="first-item"' : '') . '><a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById(\'rep-actions-form\'))">' . $lang_reputation['Select all'] . '</a></span>';
        $forum_page['main_foot_options']['select_all'] = '<span ' . (empty($forum_page['main_foot_options']) ? ' class="first-item"' : '') . '><a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById(\'rep-actions-form\'))">' . $lang_reputation['Select all'] . '</a></span>';
    }

    ($hook = get_hook('rp_fl_pre_reputation')) ? eval($hook) : null;

    define('FORUM_PAGE', $section);
    require FORUM_ROOT . 'header.php';

    // START SUBST - <forum_main>
    ob_start();
    ?>
    <div class="main-head">
        <?php
        if (!empty($forum_page['main_head_options']))
            echo "\t\t" . '<p class="options">' . implode(' ', $forum_page['main_head_options']) . '</p>' . "\n";
        ?>
        <h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
    </div>
    <div class="admin-submenu gen-content">
        <ul>
            <?php echo implode("\n\t\t\t", $forum_links['reputation']) . "\n" ?>
        </ul>
    </div>
    <?php
    if ($num_rows > 0) {
        $query = array(
            'SELECT'   => 'rp.id, rp.time, rp.reason, rp.post_id, rp.plus, rp.minus, rp.user_id, t.subject, u1.username AS from_user_name, u1.id AS from_user_id',
            'FROM'     => 'reputation AS rp',
            'JOINS'    => array(
                array(
                    'INNER JOIN' => 'topics AS t',
                    'ON'         => 't.id=rp.topics_id'
                )
            ),
            'WHERE'    => 'u0.id=' . $id,
            'ORDER BY' => 'rp.time DESC',
            'LIMIT'    => $forum_page['start_from'] . ',' . $forum_user['disp_posts']
        );

        if ($section == 'positive') {
            $query['JOINS'][] = array(
                'INNER JOIN' => 'users AS u0',
                'ON'         => 'rp.from_user_id=u0.id'
            );
            $query['JOINS'][] = array(
                'INNER JOIN' => 'users AS u1',
                'ON'         => 'rp.user_id=u1.id'
            );
        } else {
            $query['JOINS'][] = array(
                'INNER JOIN' => 'users AS u0',
                'ON'         => 'rp.user_id=u0.id'
            );
            $query['JOINS'][] = array(
                'INNER JOIN' => 'users AS u1',
                'ON'         => 'rp.from_user_id=u1.id'
            );
        }

        ($hook   = get_hook('rp_fl_reputation_list_qr_get')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        if ($forum_user['is_admmod']) {
            $forum_page['fld_count']   = 0;
            $forum_page['form_action'] = forum_link($forum_url['reputation'], array($id, 'reputation'));

            $forum_page['hidden_fields'] = array(
                'form_sent'  => '<input type="hidden" name="form_sent" value="1" />',
                'csrf_token' => '<input type="hidden" name="csrf_token" value="' . generate_form_token($forum_page['form_action']) . '" />'
            );
            ?>
            <form id="rep-actions-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
                <div class="hidden">
                    <?php echo implode("\n\t\t\t", $forum_page['hidden_fields']) . "\n" ?>
                </div>
                <?php
            }

            $forum_page['item_header']               = array();
            $forum_page['item_header']['username']   = '<th class="tc' . count($forum_page['item_header']) . '" style="width:10%">' . $lang_reputation[($section == 'positive' ? 'User' : 'From user')] . '</th>';
            $forum_page['item_header']['subject']    = '<th class="tc' . count($forum_page['item_header']) . '" style="width:25%">' . $lang_reputation['For topic'] . '</th>';
            $forum_page['item_header']['reason']     = '<th class="tc' . count($forum_page['item_header']) . '" style="width:' . ($forum_user['is_admmod'] ? '35' : '45') . '%">' . $lang_reputation['Reason'] . '</th>';
            $forum_page['item_header']['estimation'] = '<th class="tc' . count($forum_page['item_header']) . '" style="width:6%; text-align:center;">' . $lang_reputation['Estimation'] . '</th>';
            $forum_page['item_header']['time']       = '<th class="tc' . count($forum_page['item_header']) . '" style="width:20%">' . $lang_reputation['Date'] . '</th>';

            if ($forum_user['is_admmod'])
                $forum_page['item_header']['select'] = '<th class="info-select"></th>';

            ($hook = get_hook('rp_results_pre_header_output')) ? eval($hook) : null;
            ?>
            <div class="main-content main-frm">
                <table cellspacing="0">
                    <thead>
                        <tr>
                            <?php echo implode("\n\t\t\t\t\t", $forum_page['item_header']) . "\n" ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!defined('FORUM_PARSER_LOADED'))
                            require FORUM_ROOT . 'include/parser.php';

                        $forum_page['item_count'] = 0;
                        while ($cur_rep                  = $forum_db->fetch_assoc($result)) {
                            ($hook = get_hook('vp_pre_cur_rep')) ? eval($hook) : null;

                            $forum_page['table_row']               = array();
                            $forum_page['table_row']['username']   = '<td class="tc' . count($forum_page['table_row']) . '">' . ($cur_rep['from_user_name'] ? '<a href="' . forum_link($forum_url['user'], $cur_rep['from_user_id']) . '">' . forum_htmlencode($cur_rep['from_user_name']) . '</a>' : $lang_reputation['Profile deleted']) . '</td>';
                            $forum_page['table_row']['subject']    = '<td class="tc' . count($forum_page['table_row']) . '">' . ($cur_rep['subject'] ? '<a href="' . forum_link($forum_url['post'], $cur_rep['post_id']) . '">' . forum_htmlencode($cur_rep['subject']) . '</a>' : $lang_reputation['Removed or deleted']) . '</td>';
                            $forum_page['table_row']['reason']     = '<td class="tc' . count($forum_page['table_row']) . '">' . parse_message($cur_rep['reason'], 0) . '</td>';
                            $forum_page['table_row']['estimation'] = '<td style="text-align:center;"><img src="' . $base_url . '/img/style/' . ($cur_rep['plus'] == 1 ? 'plus.gif" alt="+"' : 'minus.gif" alt="-"') . ' /></td>';
                            $forum_page['table_row']['time']       = '<td class="tc' . count($forum_page['table_row']) . '">' . format_time($cur_rep['time']) . '</td>';

                            if ($forum_user['is_admmod'])
                                $forum_page['table_row']['select'] = '<td class="info-select"><input type="checkbox" name="delete[]" value="' . $cur_rep['id'] . '" /></td>';

                            ++$forum_page['item_count'];

                            ($hook = get_hook('rp_results_row_pre_data_output')) ? eval($hook) : null;
                            ?>
                            <tr class="<?php echo ($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php echo ($forum_page['item_count'] == 1) ? ' row1' : '' ?>">
                                <?php echo implode("\n\t\t\t\t\t", $forum_page['table_row']) . "\n" ?>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
            if ($forum_user['is_admmod']) {
                $forum_page['mod_options']['mod_delete'] = '<span class="submit"><input type="submit" name="submit" value="' . $lang_common['Delete'] . '" onclick="return confirm(' . $lang_reputation['Are you sure'] . ')" /></span>';

                ($hook = get_hook('rp_actions_pre_mod_options')) ? eval($hook) : null;
                ?>
                <div class="main-options mod-options gen-content">
                    <p class="options"><?php echo implode(' ', $forum_page['mod_options']) ?></p>
                </div>
            </form>
            <?php
        }
    } else {
        ?>
        <div class="main-content main-frm">
            <div class="ct-box user-box">
                <h2 class="hn"><span><?php echo $lang_reputation['No reputation'] ?></span></h2>
            </div>
        </div>
        <?php
    }
    ?>
    <div class="main-foot">
        <?php
        if (!empty($forum_page['main_foot_options']))
            echo "\t\t" . '<p class="options">' . implode(' ', $forum_page['main_foot_options']) . '</p>' . "\n";
        ?>
        <h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
    </div>
    <?php
    $tpl_temp = forum_trim(ob_get_contents());
    $tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
    ob_end_clean();
    // END SUBST - <forum_main>

    require FORUM_ROOT . 'footer.php';
}
else if ($method) {
    if ($forum_user['is_guest'])
        message($lang_common['No permission']);
    if ($forum_user['id'] == $id)
        message($lang_reputation['Silly user']);

    confirm_current_url(forum_link($forum_url['reputation_change'], array($id, $pid, $method)));

    $query = array(
        'SELECT'   => 'rp.time, u.id, u.username',
        'FROM'     => 'users AS u',
        'JOINS'    => array(
            array(
                'LEFT JOIN' => 'reputation AS rp',
                'ON'        => 'rp.user_id=' . $id . ' AND rp.from_user_id=' . $forum_user['id']
            )
        ),
        'WHERE'    => 'u.id=' . $id,
        'ORDER BY' => 'rp.time DESC',
        'LIMIT'    => '0, 1'
    );

    ($hook   = get_hook('rp_fl_reputation_add_vote_qr_get')) ? eval($hook) : null;
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    if (!$forum_db->num_rows($result))
        message($lang_common['Bad request']);

    $cur_rep = $forum_db->fetch_assoc($result);

    //Check last reputation point given timestamp
    if ($cur_rep['time']) {
        if ($forum_config['o_rep_timeout'] * 60 > (time() - $cur_rep['time']))
            message(sprintf($lang_reputation['Timeout'], $forum_config['o_rep_timeout']));
    }

    // Prevent people from voting for themselves via URL hacking.
    if ($forum_user['id'] == $id)
        message($lang_reputation['Silly user']);

    if ((($forum_user['g_rep_minus_min'] > $forum_user['num_posts']) && ($method == 'negative') ) || (($forum_user['g_rep_plus_min'] > $forum_user['num_posts']) && ($method == 'positive')))
        message($lang_reputation['Small Number of post']);

    $forum_page['group_count'] = $forum_page['item_count']  = $forum_page['fld_count']   = 0;
    $forum_page['form_action'] = forum_link($forum_url['reputation_change'], array($id, $pid, $method));

    $forum_page['hidden_fields'] = array(
        'form_sent'  => '<input type="hidden" name="form_sent" value="1" />',
        'pid'        => '<input type="hidden" name="pid" value="' . $pid . '" />',
        'poster_id'  => '<input type="hidden" name="poster_id" value="' . $cur_rep['id'] . '" />',
        'method'     => '<input type="hidden" name="method" value="' . $method . '" />',
        'csrf_token' => '<input type="hidden" name="csrf_token" value="' . generate_form_token($forum_page['form_action']) . '" />'
    );

    // Setup breadcrumbs
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array(sprintf($lang_reputation['reputation user'], forum_htmlencode($cur_rep['username'])), forum_link($forum_url['reputation'], array($id, 'reputation'))),
        $method == 'positive' ? $lang_reputation['Plus'] : $lang_reputation['Minus']
    );

    ($hook = get_hook('rp_fl_pre_add_reputation')) ? eval($hook) : null;

    define('FORUM_PAGE', 'reputation-vote');
    require FORUM_ROOT . 'header.php';

    // START SUBST - <forum_main>
    ob_start();
    ?>
    <div class="main-subhead">
        <h2 class="hn"><span><?php echo $lang_reputation['Form header'] ?></span></h2>
    </div>
    <div class="main-content main-frm">
        <?php
        if (!empty($errors)) {
            $forum_page['errors']   = array();
            foreach ($errors as $cur_error)
                $forum_page['errors'][] = '<li class="warn"><span>' . $cur_error . '</span></li>';

            ($hook = get_hook('po_pre_post_errors')) ? eval($hook) : null;
            ?>
            <div class="ct-box error-box">
                <h2 class="warn hn"><?php echo $lang_reputation['Post errors'] ?></h2>
                <ul class="error-list">
                    <?php echo implode("\n\t\t\t\t", $forum_page['errors']) . "\n" ?>
                </ul>
            </div>
            <?php
        }
        ?>
        <div id="req-msg" class="req-warn ct-box error-box">
            <p><?php printf($lang_common['Required warn'], '<em>' . $lang_common['Required'] . '</em>') ?></p>
        </div>
        <form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
            <div class="hidden">
                <?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields']) . "\n" ?>
            </div>
            <fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
                <legend class="group-legend"><strong>Оформите свое сообщение</strong></legend>
                <?php ($hook = get_hook('rp_fl_new_rep_username')) ? eval($hook) : null; ?>
                <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text required">
                        <label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form your name'] ?></span></label><br />
                        <span class="fld-input"><?php echo forum_htmlencode($forum_user['username']) ?></span>
                    </div>
                </div>
                <?php ($hook = get_hook('rp_fl_new_rep_poster')) ? eval($hook) : null; ?>
                <div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="sf-box text required">
                        <label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form to name'] ?></span></label><br />
                        <span class="fld-input"><?php echo forum_htmlencode($cur_rep['username']) ?></span>
                    </div>
                </div>
                <?php ($hook = get_hook('rp_fl_new_rep_message')) ? eval($hook) : null; ?>
                <div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
                    <div class="txt-box textarea required">
                        <label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form reason'] ?><em><?php echo $lang_common['Required'] ?></em></span></label>
                        <div class="txt-input"><span class="fld-input"><textarea id="fld1" name="req_message"  rows="7" cols="95"><?php echo isset($_POST['req_message']) ? forum_htmlencode($message) : '' ?></textarea></span></div>
                    </div>
                </div>
            </fieldset>
            <?php ($hook = get_hook('rp_fl_fl_new_rep_buttons')) ? eval($hook) : null; ?>
            <div class="frm-buttons">
                <span class="submit"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /></span>
            </div>
        </form>
    </div>
    <?php
    ($hook = get_hook('rp_fl_end')) ? eval($hook) : null;

    $tpl_temp = forum_trim(ob_get_contents());
    $tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
    ob_end_clean();
    // END SUBST - <forum_main>

    require FORUM_ROOT . 'footer.php';
}
