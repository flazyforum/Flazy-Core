<?php
/**
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2015 Flazy.us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


($hook = get_hook('bb_fl_start')) ? eval($hook) : null;

if ($forum_config['p_enable_bb_panel'] && $forum_user['show_bb_panel'])
{
	// Load the bb.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/bb.php';
	$forum_js->file($base_url.'/resources/editor/js/bb.js');
	$url_bl = $base_url.'/resources/editor/images/b_bl.gif';

	// li id => (js onclick , lang_bb)
	$bbcode = array(
		'bold'		=> 	array('bbcode(\'[b]\',\'[/b]\')', 'Bold'),
		'italic'	=> 	array('bbcode(\'[i]\',\'[/i]\')', 'Italic'),
		'strike'	=> 	array('bbcode(\'[s]\',\'[/s]\')', 'Strike'),
		'left'		=> 	array('bbcode(\'[left]\',\'[/left]\')', 'Left'),
		'center'	=> 	array('bbcode(\'[center]\',\'[/center]\')', 'Center'),
		'right'		=> 	array('bbcode(\'[right]\',\'[/right]\')', 'Right'),
		'image'		=> 	array('tag(\'[img]\',\'[/img]\', tag_image)', 'Image'),
		'quote'		=> 	array('bbcode(\'[quote]\',\'[/quote]\')', 'Quote'),
	);

	($hook = get_hook('bb_fl_pre_bb_panel')) ? eval($hook) : null;

	foreach ($bbcode as $bb_type => $bb_text)
		$forum_page['bb_code'][] = '<li id="bt-'.$bb_type.'"><span><img onclick="'.$bb_text['0'].'" src="'.$url_bl.'" title="'.$lang_bb[$bb_text['1']].'" alt="" /></span></li>';

	if (!defined('FORUM_SMILIES_LOADED'))
		require FORUM_ROOT.'include/smilies.php';

	$smiley_groups = array();
	foreach ($smilies as $smiley_text => $smiley_img)
		$smiley_groups[$smiley_img][] = $smiley_text;

	// Ограничим количество смайлов
	$smiley_groups = array_slice($smiley_groups, 0, $forum_config['p_bb_panel_smilies']);
	foreach ($smiley_groups as $smiley_img => $smiley_texts)
		$forum_page['bb_smiley'][] = '<img onclick="smile(\''.$smiley_texts['0'].'\')" src="'.$base_url.'/resources/editor/emoticons/'.$smiley_img.'" alt="'.$smiley_texts['0'].'" title="'.$smiley_texts['0'].'"/>';

	($hook = get_hook('bb_fl_pre_bb_list')) ? eval($hook) : null;

?>
					<div id="smiley-box">
						<strong>Smilies</strong>
							<br>
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['bb_smiley'])."\n"; ?>
						<p><a href="<?php echo forum_link($forum_url['smilies']) ?>" onclick="return smile_pop(this.href);"><span><?php echo $lang_bb['All'] ?></span></a></p>
					</div>
					<div id="format-buttons">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['bb_code'])."\n"; ?>
					</div>
<?php

}

($hook = get_hook('bb_fl_end')) ? eval($hook) : null;
