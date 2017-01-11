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
 * Поля для создания и редактирования опроса.
 */
function form_poll($question, $answers, $options_count, $days, $votes)
{
	global $base_url, $forum_user, $lang_post, $forum_config, $read_unvote, $revote, $forum_js, $js;

	$return = ($hook = get_hook('fn_fl_form_poll_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$forum_js->file('jquery');

	if ($question == '')
	{
		$forum_js->code('$(document).ready(function() {
			$(\'#form-poll\').hide();
			$("a#add").click(function () {
			$(\'#poll\').hide();
			$(\'#form-poll\').show(600);});
		});');
	}
	else
	{
		$forum_js->code('$(document).ready(function() {
			$(\'#poll\').hide();
		});');
	}

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset id="poll" class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Опрос</span></label>
						<span class="fld-input"><p><a id="add" href="javascript:void(0)">Добавить опрос</a></p></span>
					</div>
				</div>
				
			</fieldset>
<?php $forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0; ?>
			<div id="form-poll">
				<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Poll question'] ?></span><small><?php echo $lang_post['Poll question info'] ?></small></label>
							<span class="fld-input"><input type="text" id="quest" name="question" size="80" maxlength="150" value="<?php echo forum_htmlencode(forum_trim($question)); ?>" /></span>
						</div>
					</div>
<?php

	//Validate of answers
	if ($answers != '')
	{
		foreach ($answers as $ans_num => $ans)
			$answers[$ans_num] = forum_trim($answers[$ans_num]);
	}

	for ($opt_num = 0; $opt_num < $options_count; $opt_num++)
	{

?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Voting answer'] ?></span></label>
							<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="answer[]" size="80" maxlength="70" value="<?php echo ($answers != '' && isset($answers[$opt_num]) ? forum_htmlencode(forum_trim($answers[$opt_num])) : '') ?>" /></span>
						</div>
					</div>
<?php

	}

?>
				</fieldset>
				<fieldset class="frm-group frm-hdgroup group<?php echo ++$forum_page['group_count'] ?>">
<?php ($hook = get_hook('fn_fl_pre_poll_count')) ? eval($hook) : null; ?>
					<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?> mf-head">
						<legend><span><?php echo $lang_post['Summary count'] ?></span></legend>
						<div class="mf-box">
							<div class="mf-field mf-field1">
								<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo $lang_post['Count'] ?></span></label>
								<span class="fld-input"><input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="text" name="ans_count" size="5" maxlength="5" value="<?php echo $options_count ?>" /></span>
							</div>
							<div class="mf-field">
								<span class="submit"><input type="submit" name="update_poll" value="<?php echo $lang_post['Button note'] ?>" /></span>
							</div>
						</div>
					</fieldset>
				</fieldset>
<?php $forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0; ?>
				<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
<?php ($hook = get_hook('fn_fl_pre_poll_enable_read')) ? eval($hook) : null; if ($forum_config['p_poll_enable_read']): ?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" value="1" name="read_unvote"<?php echo isset($_POST['read_unvote']) || $read_unvote ? ' checked' : '' ?> /></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Show poll'] ?></span><?php echo $lang_post['Show poll info'] ?></label>
						</div>
					</div>
<?php

	endif;

	($hook = get_hook('fn_fl_pre_poll_enable_revote')) ? eval($hook) : null;

	if ($forum_config['p_poll_enable_revote']):

?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" value="1" name="revote"<?php echo isset($_POST['revouting']) || $revote ? ' checked' : '' ?>/></span>
							<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Allow revote'] ?></span><?php echo $lang_post['Allow revote info'] ?></label>
						</div>
					</div>
<?php endif; ($hook = get_hook('fn_fl_pre_poll_allow_day')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Allow days'] ?></span><small><?php echo $lang_post['Allow days info']; ?></small></label>
							<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="days" size="5" maxlength="5" value="<?php echo ($days != '') ? $days : '0'; ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('fn_fl_pre_poll_max_votes')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Maximum votes'] ?></span><small><?php echo $lang_post['Maximum votes info'] ?></small></label>
							<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="votes" size="5" maxlength="5" value="<?php echo ($votes != '') ? $votes : '0'; ?>" /></span>
						</div>
					</div>
				</fieldset>
			</div>
<?php

}
