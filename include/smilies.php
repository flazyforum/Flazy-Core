<?php
/**
 * Список смайлов форума.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

($hook = get_hook('ps_fl_smilies_start')) ? eval($hook) : null;

// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
$smilies = array(
	':)'		=> 'smile.gif',
	'=)'		=> 'smile.gif',
	':('		=> 'sad.gif',
	'=('		=> 'sad.gif',
	':D'		=> 'biggrin.gif',
	'=D'		=> 'biggrin.gif',
	':o'		=> 'shok.gif',
	':O'		=> 'shok.gif',
	'=-O'		=> 'shok.gif',
	';)'		=> 'wink.gif',
	';-)'		=> 'wink.gif',
	':/'		=> 'nea.gif',
	':-/'		=> 'nea.gif',
	':cry:'		=> 'cry.gif', // :'(
	':p'		=> 'blum.gif',
	':P'		=> 'blum.gif',
	':-['		=> 'blush.gif',
	'%)'		=> 'wacko.gif',
	':crazy:'	=> 'crazy.gif',
	':lol:'		=> 'lol.gif',
	':rofl:'	=> 'rofl.gif',
	':mad:'		=> 'mad.gif',
	':ireful:'	=> 'ireful.gif',
	':good:'	=> 'good.gif',
	':negative:'	=> 'negative.gif',
	':bad:'		=> 'bad.gif',
	':cool:'	=> 'cool.gif',
	':shout:'	=> 'shout.gif',
	':yahoo:'	=> 'yahoo.gif',
	'O:-)'		=> 'angel.gif',
	'O=)'		=> 'angel.gif',
	':pardon:'	=> 'pardon.gif',
	':sorry:'	=> 'sorry.gif',
	':yes:'		=> 'yes.gif',
	':music:'	=> 'music.gif',
	':dance:'	=> 'dance.gif',
	':hi:'		=> 'hi.gif',
	':bye:'		=> 'bye.gif',
	':gamer:'	=> 'gamer.gif',
	']:->'		=> 'diablo.gif',
	':fool:'	=> 'fool.gif',
	':secret:'	=> 'secret.gif',
	':bomb:'	=> 'bomb.gif',
	':timeout:'	=> 'timeout.gif',
	':kiss:'	=> 'kiss.gif',
	':rose:'	=> 'rose.gif', // @}->--
	':in love:'	=> 'in_love.gif',
	':wall:'	=> 'wall.gif',
	':mail:'	=> 'mail.gif',
	':emo:'		=> 'emo.gif',
	':heart:'	=> 'heart.gif'
	);

($hook = get_hook('ps_fl_smilies_end')) ? eval($hook) : null;

define('FORUM_SMILIES_LOADED', 1);
