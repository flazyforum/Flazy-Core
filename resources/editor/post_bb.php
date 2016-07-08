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
		'font'		=> 	array('visibility(\'font-area\')', 'Font'),
		'size'		=> 	array('visibility(\'size-area\')', 'Size'),
		'bold'		=> 	array('bbcode(\'[b]\',\'[/b]\')', 'Bold'),
		'italic'	=> 	array('bbcode(\'[i]\',\'[/i]\')', 'Italic'),
		'underline'	=> 	array('bbcode(\'[u]\',\'[/u]\')', 'Underline'),
		'strike'	=> 	array('bbcode(\'[s]\',\'[/s]\')', 'Strike'),
		'left'		=> 	array('bbcode(\'[left]\',\'[/left]\')', 'Left'),
		'center'	=> 	array('bbcode(\'[center]\',\'[/center]\')', 'Center'),
		'right'		=> 	array('bbcode(\'[right]\',\'[/right]\')', 'Right'),
		'link'		=> 	array('tag(\'[url]\',\'[/url]\', tag_url)', 'Link'),
		'email'		=> 	array('tag(\'[email]\',\'[/email]\', tag_email)', 'Email'),
		'image'		=> 	array('tag(\'[img]\',\'[/img]\', tag_image)', 'Image'),
		'video'		=> 	array('tag(\'[video]\',\'[/video]\', tag_video)', 'Video'),
		'hide'		=> 	array('tag_hide()', 'Hide'),
		'quote'		=> 	array('bbcode(\'[quote]\',\'[/quote]\')', 'Quote'),
		'code'		=> 	array('bbcode(\'[code]\',\'[/code]\')', 'Code'),
		'color'		=> 	array('visibility(\'color-area\')', 'Color'),
		'smile'		=> 	array('visibility(\'smilies-area\')', 'Smile'),
		'speller'	=> 	array('spellCheck()', 'Speller'),
	);
	
	$font_list = array('Arial', 'Arial Black', 'Arial Narrow', 'Book Antiqua', 'Century Gothic', 'Comic Sans Ms', 'Courier New', 'Fixedsys', 'Franklin Gothic Medium', 'Garamond', 'Georgia', 'Impact', 'Lucida Console', 'Microsoft Sans Serif', 'Palatino Linotype', 'System', 'Tahoma', 'Times New Roman', 'Trebuchet Ms', 'Verdana');
	$size_list = array(
	'50'	=> array('Tiny font'), 
	'85'	=> array('Small font'), 
	'150'	=> array('Large font'),
	'200'	=> array('Huge font'),
	);
	$color_list = array('black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple', 'fuchsia', 'green', 'lime', 'olive', 'yellow', 'navy', 'blue', 'teal', 'aqua');

	($hook = get_hook('bb_fl_pre_bb_panel')) ? eval($hook) : null;

	foreach ($bbcode as $bb_type => $bb_text)
		$forum_page['bb_code'][] = '<li id="bt-'.$bb_type.'"><span><img onclick="'.$bb_text['0'].'" src="'.$url_bl.'" title="'.$lang_bb[$bb_text['1']].'" alt="" /></span></li>';
	foreach ($font_list as $font)
		$forum_page['bb_font'][] = '<div style="font-family:'.$font.'"><span>'.$font.'</span><img onclick="bbcode(\'[font='.$font.']\',\'[/font]\')" src="'.$url_bl.'" /></div>';
	foreach ($size_list as $size => $size_lang)
		$forum_page['bb_size'][] = '<div style="font-size:'.$size.'%"><span>'.$size_lang['0'].'</span><img onclick="bbcode(\'[size='.$size.']\',\'[/size]\')" src="'.$url_bl.'" /></div>';
	foreach ($color_list as $color)
		$forum_page['bb_color'][] = '<td style="background-color:'.$color.'"><img onclick="bbcode(\'[color='.$color.']\',\'[/color]\')" src="'.$url_bl.'" /></td>';

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
					<div class="bbm" id="font-area" style="display:none" onclick="visibility('font-area')">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['bb_font'])."\n"; ?>
					</div>
					<div class="bbm" id="size-area" style="display:none" onclick="visibility('size-area')">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['bb_size'])."\n"; ?>
					</div>
					<div class="bbm" id="color-area" style="display:none" onclick="visibility('color-area')">
					<table cellspacing="0" cellpadding="0">
						<tr>
							<?php echo implode("\n\t\t\t\t\t\t\t", $forum_page['bb_color'])."\n"; ?>
						</tr>
					</table>
					</div>
					<div class="bbm" id="smilies-area" style="display:none" onclick="visibility('smilies-area')">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['bb_smiley'])."\n"; ?>
						<p><a href="<?php echo forum_link($forum_url['smilies']) ?>" onclick="return smile_pop(this.href);"><span><?php echo $lang_bb['All'] ?></span></a></p>
					</div>
<?php

}

($hook = get_hook('bb_fl_end')) ? eval($hook) : null;
