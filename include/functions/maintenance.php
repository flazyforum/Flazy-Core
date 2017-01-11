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
 * Показывает сообщение когда форум находится в режиме техобслуживания.
 */
function maintenance_message()
{
	global $forum_db, $forum_config, $lang_common, $forum_user, $base_url;

	$return = ($hook = get_hook('fn_maintenance_message_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\t\t", '  ', '  ');
	$replace = array('&#160; &#160; ', '&#160; ', ' &#160;');
	$message = str_replace($pattern, $replace, $forum_config['o_maintenance_message']);

	// Send the Content-type header in case the web server is setup to send something else
	header('Content-type: text/html; charset=utf-8');

	// Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
	header('HTTP/1.1 503 Service Temporarily Unavailable');

	// Load the maintenance template
	$tpl_path = check_tpl('maintenance');

	($hook = get_hook('fn_maintenance_message_pre_template_loaded')) ? eval($hook) : null;

	$tpl_main = forum_trim(file_get_contents($tpl_path));

	($hook = get_hook('fn_maintenance_message_template_loaded')) ? eval($hook) : null;

	// START SUBST - <forum_local>
	$tpl_main = str_replace('<forum_local>', 'xml:lang="'.$lang_common['lang_identifier'].'" lang="'.$lang_common['lang_identifier'].'" dir="'.$lang_common['lang_direction'].'"', $tpl_main);
	// END SUBST - <forum_local>


	// START SUBST - <forum_head>
	define('FORUM_PAGE', 'maintenance');
	ob_start();

?>
<title><?php echo $lang_common['Maintenance mode'].$lang_common['Title separator'].forum_htmlencode($forum_config['o_board_title']) ?></title>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $base_url ?>'/favicon.ico" />
<?php

	require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_head>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_head>

	// START SUBST - <forum_html_top>


	if ($forum_config['o_html_top'])
		$tpl_main = str_replace('<forum_html_top>', $forum_config['o_html_top_message'], $tpl_main);

	// END SUBST - <forum_html_top>

	// START SUBST - <forum_maint_main>
	ob_start();

?>
<div id="brd-main" class="main basic">

	<div class="main-head">
		<h1 class="hn"><span><?php echo $lang_common['Maintenance mode'] ?></span></h1>
	</div>
	<div class="main-content main-message">
		<div class="ct-box user-box">
			<?php echo $message."\n" ?>
		</div>
	</div>

</div>
<?php

	$tpl_temp = "\t".forum_trim(ob_get_contents());
	$tpl_main = str_replace('<forum_maint_main>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_maint_main>


	// End the transaction
	$forum_db->end_transaction();


	// START SUBST - <!forum_include "*">
	while (preg_match('#<forum_include "([^/\\\\]*?)">#', $tpl_main, $cur_include))
	{
		if (!file_exists(FORUM_ROOT.'include/user/'.$cur_include[1]))
			error('Unable to process user include &lt;!-- forum_include "'.forum_htmlencode($cur_include[1]).'" --&gt; from template maintenance.tpl. There is no such file in folder /include/user/.');

		ob_start();
		include FORUM_ROOT.'include/user/'.$cur_include[1];
		$tpl_temp = ob_get_contents();
		$tpl_maint = str_replace($cur_include[0], $tpl_temp, $tpl_main);
		ob_end_clean();
	}
	// END SUBST - <forum_include "*">

	// START SUBST - <forum_html_bottom>
	ob_start();

	if ($forum_config['o_html_bottom'])
		$tpl_main = str_replace('<forum_html_bottom>', $forum_config['o_html_bottom_message'], $tpl_main);

	$tpl_temp = ob_get_contents();
	$tpl_main = str_replace('<forum_html_bottom>', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <forum_html_bottom>


	// Close the db connection (and free up any result data)
	$forum_db->close();

	die($tpl_main);
}

define('FORUM_FUNCTIONS_MAINTENANCE', 1);
