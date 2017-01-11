<?php
/**
 * Загружает сохраненные последовательности используемые для преобразования проблемных строк в URL.
 * Они подбираются в отношении всей строки после всех других преобразований.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


$reserved_strings = array(
''		=>	'view',
	
'newpost'		=>	'view',
'newposts'		=>	'view',
'new-post'		=>	'view',
'new-posts'		=>	'view',
	
'lastpost'		=>	'view',
'lastposts'		=>	'view',
'last-post'		=>	'view',
'last-posts'	=>	'view'

);
