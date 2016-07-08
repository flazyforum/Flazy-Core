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

$return = ($hook = get_hook('cl_fl_start')) ? eval($hook) : null;
if ($return != null)
	return;

if (isset($_SERVER['QUERY_STRING']))
{
	if (preg_match('#^(http|ftp|https|news|file)://(\S+)$#i', $_SERVER['QUERY_STRING']))
	{
		header('HTTP/1.1 303 See Other');
		header('Location: '.$_SERVER['QUERY_STRING']);

		($hook = get_hook('cl_preg')) ? eval($hook) : null;
	}
	else
	{
		header('Location: ./'); 
		die;

		($hook = get_hook('cl_fl_die')) ? eval($hook) : null;
	}
}

($hook = get_hook('cl_fl_end')) ? eval($hook) : null;
