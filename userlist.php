<?php
/**
 * Displays the list of users and allows the you to search among them.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2014-2018 Flazy
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */
if (!defined('FORUM_ROOT')) {
    define('FORUM_ROOT', './');
}
require FORUM_ROOT . 'include/common.php';

($hook = get_hook('ul_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board']) {
    message($lang_common['No view']);
} else if (!$forum_user['g_view_users']) {
    message($lang_common['No permission']);
}

// Load the userlist.php language file
require FORUM_ROOT . 'lang/' . $forum_user['language'] . '/userlist.php';

// Miscellaneous setup
$forum_page['show_post_count'] = ($forum_config['o_show_post_count'] || $forum_user['is_admmod']) ? true : false;
$forum_page['username']        = (isset($_GET['username']) && $_GET['username'] != '-' && $forum_user['g_search_users']) ? $_GET['username'] : '';
$forum_page['show_group']      = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);
$forum_page['sort_by']         = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && ($_GET['sort_by'] != 'num_posts' || !$forum_page['show_post_count'])) ? 'username' : $_GET['sort_by'];
$forum_page['sort_dir']        = (!isset($_GET['sort_dir']) || strtoupper($_GET['sort_dir']) != 'ASC' && strtoupper($_GET['sort_dir']) != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);

$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1) ? 1 : intval($_GET['p']);

// Check for use of incorrect URLs
if (isset($_GET['username']) || isset($_GET['show_group']) || isset($_GET['sort_by']) || isset($_GET['sort_dir']) || $forum_page['page'] != 1) {
    if ($forum_page['page'] != 1) {
        confirm_current_url(forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page']), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')));
    }
}

// Create any SQL for the WHERE clause
$where_sql    = array();
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if ($forum_user['g_search_users'] && $forum_page['username'] != '') {
    $where_sql[] = 'u.username ' . $like_command . ' \'' . $forum_db->escape(str_replace('*', '%', $forum_page['username'])) . '\'';
}
if ($forum_page['show_group'] > -1) {
    $where_sql[] = 'u.group_id=' . $forum_page['show_group'];
}

// Load cached
if (file_exists(FORUM_CACHE_DIR . 'cache_stat_user.php')) {
    include FORUM_CACHE_DIR . 'cache_stat_user.php';
} else {
    if (!defined('FORUM_CACHE_STAT_USER_LOADED')) {
        require FORUM_ROOT . 'include/cache/stat_user.php';
    }

    generate_stat_user_cache();
    require FORUM_CACHE_DIR . 'cache_stat_user.php';
}

if (!empty($where_sql)) {
    $query = array(
        'SELECT' => 'COUNT(u.id)',
        'FROM'   => 'users AS u',
        'WHERE'  => 'u.id > 1 AND u.group_id != ' . FORUM_UNVERIFIED . ' AND ' . implode(' AND ', $where_sql)
    );

    ($hook                           = get_hook('ul_qr_get_user_count')) ? eval($hook) : null;
    $result                         = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    $forum_stat_user['total_users'] = $forum_db->result($result);
}

// Determine the user offset (based on $_GET['p'])
$forum_page['num_pages']  = ceil($forum_stat_user['total_users'] / 15);
$forum_page['page']       = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = 15 * ($forum_page['page'] - 1);
$forum_page['finish_at']  = min(($forum_page['start_from'] + 15), ($forum_stat_user['total_users']));

$forum_page['users_searched'] = (($forum_user['g_search_users'] && $forum_page['username'] != '') || $forum_page['show_group'] > -1);

if ($forum_stat_user['total_users'] > 0)
    $forum_page['items_info'] = generate_items_info((($forum_page['users_searched']) ? $lang_ul['Users found'] : $lang_ul['Users']), ($forum_page['start_from'] + 1), $forum_stat_user['total_users']);
else
    $forum_page['items_info'] = $lang_ul['Users'];

// Generate paging links
$forum_page['page_post']['paging'] = paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['users_browse'], $lang_common['Paging separator'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-'));

// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages']) {
    $forum_page['nav']['last'] = '<link rel="last" href="' . forum_sublink($forum_url['users_browse'], $forum_url['page'], $forum_page['num_pages'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')) . '" title="' . $lang_common['Page'] . ' ' . $forum_page['num_pages'] . '" />';
    $forum_page['nav']['next'] = '<link rel="next" href="' . forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page'] + 1), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')) . '" title="' . $lang_common['Page'] . ' ' . ($forum_page['page'] + 1) . '" />';
}
if ($forum_page['page'] > 1) {
    $forum_page['nav']['prev']  = '<link rel="prev" href="' . forum_sublink($forum_url['users_browse'], $forum_url['page'], ($forum_page['page'] - 1), array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')) . '" title="' . $lang_common['Page'] . ' ' . ($forum_page['page'] - 1) . '" />';
    $forum_page['nav']['first'] = '<link rel="first" href="' . forum_link($forum_url['users_browse'], array($forum_page['show_group'], $forum_page['sort_by'], $forum_page['sort_dir'], ($forum_page['username'] != '') ? urlencode($forum_page['username']) : '-')) . '" title="' . $lang_common['Page'] . ' 1" />';
}

// Setup main heading
$forum_page['main_title'] = $lang_common['User list'];

// Setup form
$forum_page['group_count'] = $forum_page['item_count']  = $forum_page['fld_count']   = 0;
$forum_page['form_action'] = $base_url . '/userlist.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
    array($forum_config['o_board_title'], forum_link($forum_url['index'])),
    array($lang_common['User list'], forum_link($forum_url['users']))
);

$forum_page['frm-info'] = array(
    'wildcard' => '<p>' . $lang_ul['Wildcard info'] . '</p>',
    'group'    => '<p>' . $lang_ul['Group info'] . '</p>',
    'sort'     => '<p>' . $lang_ul['Sort info'] . '</p>'
);

// Setup main heading
if ($forum_page['num_pages'] > 1) {
    $forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);
}

($hook = get_hook('ul_pre_header_load')) ? eval($hook) : null;

define('FORUM_ALLOW_INDEX', 1);
$forum_js->file(array('jquery', 'material', 'flazy', 'common'));
$forum_js->code('$(document).ready(function() {
    $(\'select\').material_select();
  });');
define('FORUM_PAGE', 'userlist');
require FORUM_ROOT . 'header.php';

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('ul_main_output_start')) ? eval($hook) : null;
?>
<div class="row">
    <div class="col s12 m12 l12">
        <div class="card">
            <div class="card-content">
                 <span class="card-title"><?php echo $forum_page['items_info'] ?></span>
                   <?php echo implode("\n\t\t\t\t", $forum_page['frm-info']) . "\n" ?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col s12 m12 l12">
        <div class="card">
            <div class="card-content">
                <span class="card-title"><?php echo $lang_ul['User find legend'] ?></span>
            <div class="row">
                <form id="afocus" class="col s12 m12 l12" method="get" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
                <?php ($hook = get_hook('ul_pre_username')) ? eval($hook) : null;
                if ($forum_user['g_search_users']):
                    ?>
      <div class="row set<?php echo ++$forum_page['item_count'] ?>">
        <div class="input-field col s12 m12 l5">
          <input placeholder="<?php echo $lang_ul['Search for username'] ?>" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="username" type="text" value="<?php echo forum_htmlencode($forum_page['username']) ?>" class="validate">
          <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><?php echo $lang_ul['Search for username'] ?> <small><?php echo $lang_ul['Username help'] ?></small></label>
        </div>
      
                <?php endif; ?>
<?php ($hook = get_hook('ul_pre_group_select')) ? eval($hook) : null; ?>
        <div class="input-field col s12 m12 l3 set<?php echo ++$forum_page['item_count'] ?>">
                            <select id="fld<?php echo $forum_page['fld_count'] ?>" name="show_group">
                                <option value="-1"<?php if ($forum_page['show_group'] == -1) echo ' selected="selected"' ?>><?php echo $lang_ul['All users'] ?></option>
                                <?php
                                ($hook = get_hook('ul_search_new_group_option')) ? eval($hook) : null;

// Get the list of user groups (excluding the guest group)
                                $query = array(
                                    'SELECT'   => 'g.g_id, g.g_title',
                                    'FROM'     => 'groups AS g',
                                    'WHERE'    => 'g.g_id!=' . FORUM_GUEST,
                                    'ORDER BY' => 'g.g_id'
                                );

                                ($hook   = get_hook('ul_qr_get_groups')) ? eval($hook) : null;
                                $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

                                while ($cur_group = $forum_db->fetch_assoc($result)) {
                                    if ($cur_group['g_id'] == $forum_page['show_group'])
                                        echo "\t\t\t\t\t\t" . '<option value="' . $cur_group['g_id'] . '" selected="selected">' . forum_htmlencode($cur_group['g_title']) . '</option>' . "\n";
                                    else
                                        echo "\t\t\t\t\t\t" . '<option value="' . $cur_group['g_id'] . '">' . forum_htmlencode($cur_group['g_title']) . '</option>' . "\n";
                                }
                                ?>
                            </select>
          <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><?php echo $lang_ul['User group'] ?></label>
        </div>
                
<?php ($hook = get_hook('ul_pre_sort_by')) ? eval($hook) : null; ?>
                <div class="input-field col s12 m12 l2 set<?php echo ++$forum_page['item_count'] ?>">
                    
                        
                        <select id="fld<?php echo $forum_page['fld_count'] ?>" name="sort_by">
                                <option value="username"<?php if ($forum_page['sort_by'] == 'username') echo ' selected="selected"' ?>><?php echo $lang_ul['Username 2'] ?></option>
                                <option value="registered"<?php if ($forum_page['sort_by'] == 'registered') echo ' selected="selected"' ?>><?php echo $lang_ul['Registered 2'] ?></option>
                                <?php if ($forum_page['show_post_count']): ?>
                                    <option value="num_posts"<?php if ($forum_page['sort_by'] == 'num_posts') echo ' selected="selected"' ?>><?php echo $lang_ul['No of posts'] ?></option>
                                <?php endif ?>
                                <option value="last_visit"<?php if ($forum_page['sort_by'] == 'last_visit') echo ' selected="selected"' ?>><?php echo $lang_ul['Last visit 2'] ?></option>
                                <?php if ($forum_config['o_rep_enabled'] && $forum_user['g_rep_enable'] && $forum_user['rep_enable']): ?>
                                    <option value="reputation"<?php if ($forum_page['sort_by'] == 'reputation') echo ' selected="selected"' ?>><?php echo $lang_ul['Reputation 2'] ?></option>
                                <?php endif;
                                ($hook  = get_hook('ul_new_sort_by_option')) ? eval($hook) : null;
                                ?>
                            </select>
                    <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_ul['Sort users by'] ?></span></label>
                    
                </div>

<?php ($hook  = get_hook('ul_pre_sort_order')) ? eval($hook) : null; ?>
                    <div class="col s12 m12 l2">
                        <label><?php echo $lang_ul['User sort order'] ?></label>
                        <div class="mf-item">
                            <input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="sort_dir" value="ASC"<?php if ($forum_page['sort_dir'] == 'ASC') echo ' checked="checked"' ?> />
                            <label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_ul['Ascending'] ?></label>
                        </div>
                        <div class="mf-item">
                            <input type="radio" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="sort_dir" value="DESC"<?php if ($forum_page['sort_dir'] == 'DESC') echo ' checked="checked"' ?> />
                            <label for="fld<?php echo $forum_page['fld_count'] ?>"><?php echo $lang_ul['Descending'] ?></label>
                        </div>
                    </div>

      </div>
<?php ($hook  = get_hook('ul_search_fieldset_end')) ? eval($hook) : null; ?>
            <div class="card-actions">
                <button  type="submit" class="btn orange" name="search"><?php echo $lang_ul['Submit user search'] ?></button>
            </div>
        </div>
    </form>
    <?php
// Grab the users
    $query = array(
        'SELECT'   => 'u.id, u.username, u.avatar, u.email, u.title, u.num_posts, u.registered, u.last_visit, u.reputation_plus, u.reputation_minus, g.g_id, g.g_user_title',
        'FROM'     => 'users AS u',
        'JOINS'    => array(
            array(
                'LEFT JOIN' => 'groups AS g',
                'ON'        => 'g.g_id=u.group_id'
            )
        ),
        'WHERE'    => 'u.id>1 AND u.group_id!=' . FORUM_UNVERIFIED,
        'ORDER BY' => $forum_page['sort_by'] . ' ' . $forum_page['sort_dir'] . ', u.id ASC',
        'LIMIT'    => $forum_page['start_from'] . ', 15'
    );

    if (!empty($where_sql))
        $query['WHERE'] .= ' AND ' . implode(' AND ', $where_sql);

    ($hook                     = get_hook('ul_qr_get_users')) ? eval($hook) : null;
    $result                   = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    $forum_page['item_count'] = 0;

    if ($forum_db->num_rows($result)) {
        ?>
            </div>
        </div>
    </div>
</div>
<div class="col s12 m12 l12">
    <ul class="collection">
                    <?php
                    
                    while ($user_data = $forum_db->fetch_assoc($result)) {
                        ($hook = get_hook('ul_results_row_pre_data')) ? eval($hook) : null;
                        $forum_page['reputation'] = ($user_data['reputation_plus'] == 0 && $user_data['reputation_minus'] == 0) ? 0 : '+' . forum_number_format($user_data['reputation_plus']) . ' \ -' . forum_number_format($user_data['reputation_minus']);

                        $forum_page['table_row']               = array();
                        if ($forum_config['o_avatars']) {
                            if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
                                require FORUM_ROOT . 'include/functions/generate_avatar_markup.php';
                                $type_image = 'circle';
                            $forum_page['avatar_markup'] = generate_avatar_markup($user_data['id'], $user_data['avatar'], $user_data['email'], $type_image);

                            if (!empty($forum_page['avatar_markup']))
                                $forum_page['table_row']['avatar'] =  $forum_page['avatar_markup'];
                        }
                        
                        $forum_page['table_row']['username']   = '<span class="title tc' . count($forum_page['table_row']) . '"><a href="' . forum_link($forum_url['user'], $user_data['id']) . '">' . forum_htmlencode($user_data['username']) . '</a></span>';
                        $forum_page['table_row']['registered'] = '<p class="tc' . count($forum_page['table_row']) . '">' .$lang_ul['Registered']. format_time($user_data['registered'], 1) . '<br>';
                        $forum_page['table_row']['title']      = get_title($user_data);
                        if ($forum_page['show_post_count']) {
                            $forum_page['table_row']['posts'] =  '&#45;&#32;'.forum_number_format($user_data['num_posts']).$lang_ul['Posts'] .'</p>';
                        }
                        ++$forum_page['item_count'];

                        ($hook = get_hook('ul_results_row_pre_data_output')) ? eval($hook) : null;
                        ?>
        <li class="collection-item avatar">
            <?php echo implode("\n\t\t\t\t\t\t", $forum_page['table_row']) . "\n" ?>

      <a href="#!" class="secondary-content"><i class="material-icons">email</i></a>
    </li>
                        <?php
                    }
                    ?>
    </ul>
</div>           
        <?php
    }
    else {
        ?>
        <div class="ct-box">
            <p><strong><?php echo $lang_ul['No users found'] ?></strong></p>
        </div>
        <?php
    }
    ?>

<?php
($hook = get_hook('ul_end')) ? eval($hook) : null;


$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT . 'footer.php';
