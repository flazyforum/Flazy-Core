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
 * Удаление личных сообщения.
 * @param array Массив, содержащий int ID сообщений которые надо удалить.
 * @param string inbox или outbox - входящие или исходящие сообщения.
 */
function delete_pm($ids, $section)
{
	global $forum_db, $db_type, $forum_user;

	$return = ($hook = get_hook('fn_fl_delete_pm_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'DELETE'	=> 'pm',
		'WHERE'		=> 'id IN('.implode(',', $ids).')',
	);

	if ($section == 'inbox')
		$query['WHERE'] .= ' AND receiver_id='.$forum_user['id'].' AND deleted_by_sender=1';
	else
		$query['WHERE'] .= ' AND sender_id='.$forum_user['id'].' AND (status=\'draft\' OR status=\'sent\' OR deleted_by_receiver=1)';

	($hook = get_hook('fn_fl_delete_pm_qr_delete')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'pm',
		'WHERE'		=> 'id IN('.implode(',', $ids).')',
	);

	if ($section == 'inbox')
	{
		$query['SET'] = 'status=\'read\', deleted_by_receiver=1';
		$query['WHERE'] .= ' AND receiver_id='.$forum_user['id'];
	}
	else
	{
		$query['SET'] = 'deleted_by_sender=1';
		$query['WHERE'] .= ' AND sender_id='.$forum_user['id'];
	}

	($hook = get_hook('fn_fl_delete_pm_qr_update')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'SELECT'	=> 'COUNT(m.id)',
		'FROM'		=> 'pm AS m',
	);

	if ($section == 'inbox')
		$query['WHERE'] = 'm.receiver_id='.$forum_user['id'].' AND m.deleted_by_receiver=0 AND (m.status=\'read\' OR m.status=\'delivered\')';
	else
		$query['WHERE'] = 'm.sender_id='.$forum_user['id'].' AND m.deleted_by_sender=0';

	($hook = get_hook('fn_fl_delete_pm_qr_message_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$pm_count = $forum_db->result($result);

	if ($section == 'inbox')
	{
		$query = array(
			'SELECT'	=> 'COUNT(m.id)',
			'FROM'		=> 'pm AS m',
			'WHERE'		=> 'm.receiver_id='.$forum_user['id'].' AND (m.status=\'delivered\' OR m.status=\'sent\') AND m.deleted_by_receiver=0'
		);

		($hook = get_hook('fn_fl_delete_pm_qr_message_count_new')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$pm_count_new = $forum_db->result($result);
	}

	$query = array(
		'UPDATE'	=> 'users',
		'WHERE'		=> 'id='.$forum_user['id'],
	);

	if ($section == 'inbox')
		$query['SET'] = 'pm_inbox='.$pm_count.', pm_new='.$pm_count_new;
	else
		$query['SET'] = 'pm_outbox='.$pm_count;

	($hook = get_hook('fn_fl_delete_pm_qr_increment_message')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

define('FORUM_FUNCTIONS_DELETE_POST', 1);
