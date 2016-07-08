<?php
/**
 * Список смайлов форума.
 *
 * @copyright Copyright (C) 2008-2015 PunBB, partially based on code copyright (C) 2008-2015 FluxBB.org
 * @modified Copyright (C) 2013-2015 Flazy.Us
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

($hook = get_hook('ps_fl_smilies_start')) ? eval($hook) : null;

// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
$smilies = array(
	':)'		=> 'smile.png',
	'=)'		=> 'smile.png',
	':('		=> 'sad.png',
	'=('		=> 'sad.png',
	':D'		=> 'grin.png',
	'=D'		=> 'grin.png',
	':o'		=> 'shocked.png',
	':O'		=> 'shocked.png',
	'=-O'		=> 'shocked.png',
	';)'		=> 'wink.png',
	';-)'		=> 'wink.png',
	':/'		=> 'nea.png',
	':-/'		=> 'nea.png',
	':cry:'		=> 'cwy.png', // :'(
	':p'		=> 'tongue.png',
	':P'		=> 'tongue.png',
	':-['		=> 'blush.png',
	'%)'		=> 'w00t.png',
	':crazy:'	=> 'crazy.png',
	':lol:'		=> 'lol.png',
	':rofl:'	=> 'laughing.png',
	':mad:'		=> 'angry.png',

	);

($hook = get_hook('ps_fl_smilies_end')) ? eval($hook) : null;

define('FORUM_SMILIES_LOADED', 1);
