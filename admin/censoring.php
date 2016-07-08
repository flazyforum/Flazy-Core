<?php
/**
 * Word censor management page
 *
 * Allows administrators and moderators to add, modify, and delete the word censors used by
 * the software when censoring is enabled.
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

($hook = get_hook('acs_start')) ? eval($hook) : null;

if (!$forum_user['is_admmod'])
	message($lang_common['No permission']);

// Load the admin.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_censoring.php';


// Add a censor word
if (isset($_POST['add_word']))
{
	$search_for = forum_trim($_POST['new_search_for']);
	$replace_with = forum_trim($_POST['new_replace_with']);

	if ($search_for == '' || $replace_with == '')
		message($lang_admin_censoring['Must enter text message']);

	($hook = get_hook('acs_add_word_form_submitted')) ? eval($hook) : null;

	$query = array(
		'INSERT'	=> 'search_for, replace_with',
		'INTO'		=> 'censoring',
		'VALUES'	=> '\''.$forum_db->escape($search_for).'\', \''.$forum_db->escape($replace_with).'\''
	);

	($hook = get_hook('acs_add_word_qr_add_censor')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Regenerate the censor cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();

	($hook = get_hook('acs_add_word_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/censoring.php'), $lang_admin_censoring['Censor word added'].' '.$lang_admin_common['Redirect']);
}


// Update a censor word
else if (isset($_POST['update']))
{
	$id = intval(key($_POST['update']));

	$search_for = forum_trim($_POST['search_for'][$id]);
	$replace_with = forum_trim($_POST['replace_with'][$id]);

	if ($search_for == '' || $replace_with == '')
		message($lang_admin_censoring['Must enter text message']);

	($hook = get_hook('acs_update_form_submitted')) ? eval($hook) : null;

	$query = array(
		'UPDATE'	=> 'censoring',
		'SET'		=> 'search_for=\''.$forum_db->escape($search_for).'\', replace_with=\''.$forum_db->escape($replace_with).'\'',
		'WHERE'		=> 'id='.$id
	);

	($hook = get_hook('acs_update_qr_update_censor')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Regenerate the censor cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();

	($hook = get_hook('acs_update_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/censoring.php'), $lang_admin_censoring['Censor word updated'].' '.$lang_admin_common['Redirect']);
}


// Remove a censor word
else if (isset($_POST['remove']))
{
	$id = intval(key($_POST['remove']));

	($hook = get_hook('acs_remove_form_submitted')) ? eval($hook) : null;

	$query = array(
		'DELETE'	=> 'censoring',
		'WHERE'		=> 'id='.$id
	);

	($hook = get_hook('acs_remove_qr_delete_censor')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Regenerate the censor cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();

	($hook = get_hook('acs_remove_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link('admin/censoring.php'), $lang_admin_censoring['Censor word removed'].' '.$lang_admin_common['Redirect']);
}


// Load the cached censors
if (file_exists(FORUM_CACHE_DIR.'cache_censors.php'))
	include FORUM_CACHE_DIR.'cache_censors.php';

if (!defined('FORUM_CENSORS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();
	require FORUM_CACHE_DIR.'cache_censors.php';
}


// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/index.php'))
);

if ($forum_user['g_id'] == FORUM_ADMIN)
	$forum_page['crumbs'][] = array($lang_admin_common['Settings'], forum_link('admin/settings.php?section=setup'));
$forum_page['crumbs'][] = array($lang_admin_common['Censoring'], forum_link('admin/censoring.php'));

($hook = get_hook('acs_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'settings');
define('FORUM_PAGE', 'admin-censoring');
require FORUM_ROOT.'header.php';

// START SUBST - <forum_main>
ob_start();

($hook = get_hook('acs_main_output_start')) ? eval($hook) : null;

?>
<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/censoring.php') ?>?action=foo">
	<div class="hidden">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/censoring.php').'?action=foo') ?>" />
	</div>
<div class="box">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo $lang_admin_censoring['Censored word head'] ?></h3>
              <div class="box-tools pull-right">
                <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <div class="box-body">
                  <div class="alert alert-info alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h4><i class="icon fa fa-info"></i> Info!</h4>
                    <p><?php echo $lang_admin_censoring['Add censored word intro']; if ($forum_user['g_id'] == FORUM_ADMIN) printf(' '.$lang_admin_censoring['Add censored word extra'], '<strong><a href="'.forum_link('admin/settings.php?section=features').'">'.$lang_admin_common['Settings'].' » '.$lang_admin_common['Features'].'</a></strong>') ?></p>
                  </div>
			<fieldset class="frm-group frm-hdgroup group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_censoring['Add censored word legend'] ?></span></legend>
<?php ($hook = get_hook('acs_pre_add_word_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?><?php echo ($forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
					<div class="mf-box">
<?php ($hook = get_hook('acs_pre_add_search_for')) ? eval($hook) : null; ?>
						<div class="mf-field mf-field1">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo $lang_admin_censoring['Censored word label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="new_search_for" size="24" maxlength="60" /></span>
						</div>
<?php ($hook = get_hook('acs_pre_add_replace_with')) ? eval($hook) : null; ?>
						<div class="mf-field">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo $lang_admin_censoring['Replacement label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="new_replace_with" size="24" maxlength="60" /></span>
						</div>

					</div>
<?php ($hook = get_hook('acs_pre_add_word_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('acs_add_word_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
            </div><!-- /.box-body -->
<?php ($hook = get_hook('acs_pre_add_submit')) ? eval($hook) : null; ?>
		<div class="box-footer">
			<input class="btn btn-primary" type="submit" name="add_word" value=" <?php echo $lang_admin_censoring['Add word'] ?> " />
		</div>
          </div>
</form>
<?php

if (!empty($forum_censors))
{
	// Reset
	$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
<form class="form-horizontal" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/censoring.php') ?>?action=foo">
	<div class="hidden">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/censoring.php').'?action=foo') ?>" />
	</div>
<div class="box">
	<div class="box-header with-border">
		<h3 class="box-title"><?php echo $lang_admin_censoring['Edit censored word legend'] ?></h3>
		<div class="box-tools pull-right">
			<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
			<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
		</div>
	</div>
	<div class="box-body">
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">	
<?php
	foreach ($forum_censors as $censor_key => $cur_word)
	{
	?>
<?php ($hook = get_hook('acs_pre_edit_word_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set mf-extra set<?php echo ++$forum_page['item_count'] ?><?php echo ($forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
<table id="gc<?php echo ++$forum_page['group_count'] ?>" class="table table-bordered table-hover dataTable" role="grid" aria-describedby="example2_info">
	<thead>
<?php ($hook = get_hook('acs_pre_edit_search_for')) ? eval($hook) : null; ?>
		<tr role="row">
			<th rowspan="1" colspan="1"><?php echo $lang_admin_censoring['Censored word label'] ?></th>
			<th rowspan="1" colspan="1"><?php echo $lang_admin_censoring['Replacement label'] ?></th>
			<th rowspan="1" colspan="1"></th>
			<th rowspan="1" colspan="1"></th>
		</tr>
	</thead>
<?php ($hook = get_hook('acs_pre_edit_replace_with')) ? eval($hook) : null; ?>
	<tbody class="gc1">								
		<tr role="row" class="set1 mf-head">
			<td><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="search_for[<?php echo $cur_word['id'] ?>]" value="<?php echo forum_htmlencode($cur_word['search_for']) ?>" size="24" maxlength="60" class="form-control input-md"></td>
			<td><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="replace_with[<?php echo $cur_word['id'] ?>]" value="<?php echo forum_htmlencode($cur_word['replace_with']) ?>" size="24" maxlength="60" class="form-control input-md"></td>
			<td><button type="submit" name="update[<?php echo $cur_word['id'] ?>]" class="btn btn-success"><?php echo $lang_admin_common['Update'] ?></button></td>
			<td><button type="submit" name="remove[<?php echo $cur_word['id'] ?>]" class="btn btn-danger"><?php echo $lang_admin_common['Delete'] ?></button></td>
		</tr>
	</tbody>
</table>
<?php ($hook = get_hook('acs_pre_edit_word_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php
	}
?>				
<?php ($hook = get_hook('acs_edit_word_fieldset_end')) ? eval($hook) : null; ?>
	</div><!-- /.box-body -->
			</fieldset>
<?php ($hook = get_hook('acs_pre_edit_submit')) ? eval($hook) : null; ?>
		</form>
</div>
<?php
}
else
{
?>
<div class="box ">
	<div class="box-body">
		<p class="lead"><?php echo $lang_admin_censoring['No censored words'] ?></p>
	</div><!-- /.box-body -->
</div>
<?php

}

($hook = get_hook('acs_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<forum_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <forum_main>

require FORUM_ROOT.'admin/footer_adm.php';
