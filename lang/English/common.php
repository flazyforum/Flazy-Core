<?php
/**
 * Русский языковой пакет.
 * @package Flazy_Russian
 */

/** Языковые конструкции часто используемых строк */
$lang_common = array(

// Ориентировка текста и кодировки
'lang_direction'			=>	'ltr', // ltr (слева направо) or rtl (справа налево)
'lang_identifier'			=>	'en',

// Количество форматирования
'lang_decimal_point'		=>	'.',
'lang_thousands_sep'		=>	',',

// Уведомления
'Bad request'				=>	'Bad request. The link you followed is incorrect or outdated.',
'No view'					=>	'You do not have permission to view these forums.',
'No permission'				=>	'You do not have permission to access this page.',
'CSRF token mismatch'		=>	'Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted a form or clicked a link. If that is the case and you would like to continue with your action, please click the Confirm button. Otherwise, you should click the Cancel button to return to where you were.',
'No cookie'					=>	'You appear to have logged in successfully, however a cookie has not been set. Please check your settings and if applicable, enable cookies for this website.',

// Miscellaneous
'Forum index'				=>	'Forum index',
'Submit'					=>	'Submit',	// "name" of submit buttons
'Cancel'					=>	'Cancel', // "name" of cancel buttons
'Preview'					=>	'Preview',	// submit button to preview message
'Delete'					=>	'Delete',
'Split'						=>	'Split',
'Ban message'				=>	'You are banned from this forum.',
'Ban message 2'				=>	'The ban expires at the end of %s.',
'Ban message 3'				=>	'The administrator or moderator that banned you left the following message:',
'Ban message 4'				=>	'Please direct any inquiries to the forum administrator at %s.',
'Never'						=>	'Never',
'Today'						=>	'Today',
'Yesterday'					=>	'Yesterday',
'Back'						=>	'ago',
'After'						=>	'по-късно',
'Years'						=>	'няколко години',
'More year'					=>	'повече от една година',
'Week'						=>	'week',
'Weeks'						=>	'weeks',
'Weeks2'					=>	'weeks',
'Day'						=>	'day',
'Days'						=>	'days',
'Days2'						=>	'days',
'Hour'						=>	'hour',
'Hours'						=>	'hours',
'Hours2'					=>	'hours',
'Hours2'					=>	'hours',
'Minute'					=>	'minute',
'Minutes'					=>	'minutes',
'Minutes2'					=>	'minutes',
'Second'					=>	'second',
'Seconds'					=>	'seconds',
'Seconds2'					=>	'seconds',
'Forum message'				=>	'Forum message',
'Maintenance warning'		=>	'<strong>WARNING! %s Enabled.</strong> DO NOT LOGOUT as you will be unable to login again.',
'Maintenance mode'			=>	'Maintenance Mode',
'Redirecting'				=>	' Redirecting…', // With space!
'Forwarding info'			=>	'You should automatically be forwarded to a new page in %s %s.',
'second'					=>	'second',	// singular
'seconds'					=>	'seconds',	// plural
'Click redirect'			=>	'Click here if you do not want to wait any longer (or if your browser does not automatically forward you)',
'Invalid e-mail'			=>	'The email address you entered is invalid.',
'New posts'					=>	'New posts',	// the link that leads to the first new post
'New posts title'			=>	'Find topics containing posts made since your last visit.',	// the popup text for new posts links
'Active topics'				=>	'Active topics',
'Active topics title'		=>	'Find topics which contain recent posts.',
'Unanswered topics'			=>	'Unanswered topics',
'Unanswered topics title'	=>	'Find topics which have not been replied to.',
'My posts'					=>	'My posts',
'My posts title'			=>	'Find topics containing posts made since your last visit',
'PM new'					=>	'New PM ',
'PM full'					=>	'PM is full(!)',
'Username'					=>	'Username',
'Registered'				=>	'Registered',
'Write message'				=>	'Write message:',
'Forum'						=>	'Forum',
'Posts'						=>	'Posts',
'Pages'						=>	'Pages',
'Page'						=>	'Page',
'BBCode'					=>	'BBCode', // You probably shouldn't change this
'Smilies'					=>	'Smilies',
'Images'					=>	'Image',
'You may use'				=>	'You may use: %s',
'and'						=>	' and ',
'Description'				=>	'Description', // для изображения
'Image link'				=>	'image',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'						=>	'wrote',	// For [quote]'s (e.g., User wrote:)
'Code'						=>	'Code',		// For [code]'s
'Forum mailer'				=>	'%s Mailer',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Write message legend'		=>	'Compose your post',
'Required information'		=>	'Required information',
'Reqmark'					=>	'*',
'Required'					=>	'(*)',
'Required warn'				=>	'All fields with bold label must be completed before the form is submitted.',
'Crumb separator'			=>	' &rarr;&#160;', // The character or text that separates links in breadcrumbs
'Title separator'			=>	' — ',
'Page separator'			=>	'', //The character or text that separates page numbers
'Spacer'					=>	'…', // Ellipsis for paginate
'Paging separator'			=>	' ', //The character or text that separates page numbers for page navigation generally
'Previous'					=>	'Previous',
'Next'						=>	'Next',
'Guests none'				=>	'guest',
'Guests single'				=>	'guest',
'Guests plural'				=>	'guests',
'Cancel redirect'			=>	'Operation cancelled.',
'No confirm redirect'		=>	'No confirmation provided. Operation cancelled.',
'Please confirm'			=>	'Please confirm:',
'Help page'					=>	'Help with: %s',
'Re'						=>	'Re:',
'Page info'					=>	'(Page %1$s of %2$s)',
'Item info single'			=>	'%s [ %s ]',
'Item info plural'			=>	'%s [ %s to %s of %s ]', // e.g. Topics [ 10 to 20 of 30 ]
'Info separator'			=>	' ', // e.g. 1 Page | 10 Topics
'Powered by'				=>	'Powered by <strong>%s</strong>',

// Формы CSRF
'Confirm'					=>	'Confirm',	// Button
'Confirm action'			=>	'Confirm action',
'Confirm action head'		=>	'Please confirm or cancel your last action',

// Статус
'Title'						=>	'Title',
'Member'					=>	'Member',	// Default title
'Moderator'					=>	'Moderator',
'Administrator'				=>	'Administrator',
'Banned'					=>	'Banned',
'Guest'						=>	'Guest',

// Конструкции для for include/parser.php
'BBCode error 1'			=>	'[/%1$s] was found without a matching [%1$s]',
'BBCode error 2'			=>	'[%s] tag is empty',
'BBCode error 3'			=>	'[%1$s] was opened within [%2$s], this is not allowed',
'BBCode error 4'			=>	'[%s] was opened within itself, this is not allowed',
'BBCode error 5'			=>	'[%1$s] was found without a matching [/%1$s]',
'BBCode error 6'			=>	'[%s] tag had an empty attribute section',
'BBCode nested list'		=>	'[list] tags cannot be nested',
'BBCode code problem'		=>	'There is a problem with your [code] tags',
/*BGN*/'Hidden text guest'			=>	'Трабва да %1$s или да се %2$s за да видите скрития текст',
'Hidden show text'			=>	'Show hidden text',
'Hidden text'				=>	'Hidden text',
/*BGN*/'Hidden count text'			=>	'Нуждаете се от %s или повече съобщения, за да видите този текст',

// Конструкции меню (вверху каждой страницы)
'Index'						=>	'Home',
'User list'					=>	'Members',
'Rules'						=>  'Rules',
'Search'					=>  'Search',
'Register'					=>  'Register',
'register'					=>	'register',
'Login'						=>  'Login',
'login'						=>	'login',
'To website' 				=>  'Site',
'Not logged in'				=>  'You are not logged in.',
'Profile'					=>	'Profile',
'Logout'					=>	'Logout',
'Logged in as'				=>	'Logged in as %s.',
'Admin'						=>	'Admin CP',
'Last visit'				=>	'Last login %s',
'Mark all as read'			=>	'Mark all topics as read',
'Login nag'					=>	'Please <a href="%s">login</a> or <a href="%s">register</a>.',
'New reports'				=>	'New reports',
'Private messages'			=>	'Private messages',

// Предупреждения
'New alerts'				=>	'Warning!',
'New user notification'		=>	'Warning — New registration',
/*RUS*/'Banned email notification'	=>	'Warning — Обнаружен заблокированый e-mail',
/*RUS*/'Duplicate email notification'	=>	'Warning — Обнаружены однинаковые e-mail\'ы',

// Конструкции для меню переходов
'Go'						=>	'Go',		// submit button in forum jump
'Jump to'					=>	'Jump to forum:',

// Для extern.php RSS feed
'RSS description'			=>	'The most recent topics at %s.',
'RSS description topic'		=>	'The most recent posts in %s.',
'RSS reply'					=>	'Re: ',	// The topic subject will be appended to this string (to signify a reply)

// Доступность
'Skip to content'			=>	'Skip to forum content',

//Footer
'About us' 					=> 'About us',
'Useful links'				=> 'Useful links',
'Contact us' 				=> 'Contact us',
'Social links' 				=> 'Find us',
'Board options'				=>	'Board options',

// Отладочная онформация
'Querytime'					=>	'Generated in %1$s seconds, %2$s queries executed',
'Debug table'				=>	'Debug information',
'Debug summary'				=>	'Database query performance information',
'Query times'				=>	'Time (s)',
'Query'						=>	'Query',
'Total query time'			=>	'Total query time',

);