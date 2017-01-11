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
 * Функция ДЕБАГ, показать использованые запросы (если включено).
 */
function get_saved_queries()
{
	global $forum_db, $lang_common;

	// Get the queries so that we can print them out
	$saved_queries = $forum_db->get_saved_queries();
	
	ob_start();

?>
<div id="brd-debug" class="main">

	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_common['Debug table'] ?></span></h2>
	</div>
	<div class="main-content debug">
		<table cellspacing="0" summary="<?php echo $lang_common['Debug summary'] ?>">
			<thead>
				<tr>
					<th class="tcl" id="tcl-debug" scope="col"><?php echo $lang_common['Query times'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Query'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

	$query_time_total = 0.0;
	foreach ($saved_queries as $cur_query)
	{
		$query_time_total += $cur_query[1];

?>
				<tr>
					<td class="tcl"><?php echo (($cur_query[1] != 0) ? forum_number_format($cur_query[1], 5) : '&#160;') ?></td>
					<td class="tcr"><?php echo forum_htmlencode($cur_query[0]) ?></td>
				</tr>
<?php

	}

?>
				<tr class="totals">
					<td class="tcl"><em><?php echo forum_number_format($query_time_total, 5) ?></em></td>
					<td class="tcr"><em><?php echo $lang_common['Total query time'] ?></em></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<?php

	return ob_get_clean();
}

define('FORUM_FUNCTIONS_GET_SAVED_QUERIES', 1);
