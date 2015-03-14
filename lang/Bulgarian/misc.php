<?php
/**
 * Русский языковой пакет.
 * @package Flazy_Russian
 */

/** Языковые конструкии используемые в различный скриптах */
$lang_misc = array(

'Mark read redirect'		=>	'Всички теми и форуми бяха отбелязани като прочетени. Пренасочване …',
'Mark forum read redirect'	=>	'Всички теми в посочения форум бяха отбелязани като прочетени. Пренасочване …',
'Report off'				=>	'Забранено е изпращането на жалби.',

// Send e-mail
'Form e-mail disabled'		=>	'Потребителя е деактивирал e-mail функцията.',
'Form e-mail errors'		=>	'<strong>Внимание!</strong> Следните грешки трябва да бъдат коригирани преди да изпратите съобщението:',
'No e-mail subject'			=>	'Трябва да въведете заглавие.',
'No e-mail message'			=>	'Трябва да въведете съобщение.',
'Too long e-mail message'	=>	'Вашето съобщение е с дължина %s bytes. Това надвишава лимита от %s bytes.',
'Email flood'				=>	'Най-малко %s секунди трябва да преминат за изпращане на др. имейл. Моля, изчакайте малко и опитайте да изпратите отново.',
'E-mail sent redirect'		=>	'Вашият E-mail е изпратен. Пренасочване …',
'E-mail subject'			=>	'Заглавие',
'E-mail message'			=>	'Съобщение',
'E-mail disclosure note'	=>	'<strong>Важно!</strong> Когато изпращате e-mail чрез тази форма, вашият адрес ще бъде видим за получателя.',
'Write e-mail'				=>	'Напиши e-mail',
'Send forum e-mail'			=>	'Изпрати e-mail до %s чрез форума',

// Report
'No reason'					=>	'Трябва да въведете причина.',
'Report flood'				=>	'Най-малко %s секунди трябва да минат между докладите. Изчакайте известно времи и опитайте да изпратите отново.',
'Report redirect'			=>	'Съобщението е изпратено. Пренасочване …',
'Report post'				=>	'Съобщи за нередност',
'Reason'					=>	'Причина',
'Reason help'				=>	'Напишете причината поради която смятате това мнение за нередно.',

// Subscriptions
'Already subscribed'		=>	'Вече сте абонирани за тази тема.',
'Subscribe redirect'		=>	'Абонаментът ви е добавен. Пренасочване …',
'Not subscribed'			=>	'Не сте абонирани за тази тема.',
'Unsubscribe redirect'		=>	'Абонаментът ви е премахнат. Пренасочване …',

// General forum and topic moderation
'Moderate forum'			=>	'Модерирай форум',
'Select'					=>	'Маркирай',	// the header of a column of checkboxes
'Move'						=>	'Премести',
'Merge'						=>	'Съедини',
'Open'						=>  'Отвори',
'Close'						=>  'Затвори',
'Select all'				=>	'Избери всичко',


// Hostname lookup
'Hostname lookup'			=>	'IP адрес: %1$s<br />Име на хостa: %2$s<br /><br />%3$s',
'Show more users'			=>	'Вижте всички участници с такива IP',

// Moderate forum
'Moderate forum head'		=>	'Модерирай : %s',
'Topics'					=>	'Теми',
'Move topic'				=>	'Премести тема',
'Move topics'				=>	'Премести теми',
'Merge topics'				=>	'Смеси теми',
'Delete topics'				=>	'Изтрий теми',
'Delete topic'				=>	'Изтрий тема',
'To new forum'				=>	'към нов форум',
'Move to'					=>	'към форум',
'Redirect topic'			=>	'Пренасочване',
'Nowhere to move'			=>	'Няма форуми, в които можете да преместите темата.',
'Leave redirect'			=>	'Остави връзка в стария форум от който е преместена темата.',
'Leave redirects'			=>	'Остави връзки в стария форум от който са преместени темите.',
'Leave merge redirects'		=>	'Остави връзки за пренасочване на съединените теми.',
'Move topic redirect'		=>	'Темата е преместена. Пренасочване …',
'Move topics redirect'		=>	'Темите са преместени. Пренасочване …',
'Merge topics redirect'		=>	'Темите са съединени. Пренасочване …',
'Delete topic comply'		=>	'Сигурни ли сте че желаете да премахнете тази тема?',
'Delete topics comply'		=>	'Сигурни ли сте че желаете да премахнете тези теми?',
'Delete topic redirect'		=>	'Темата е изтрита. Пренасочване …',
'Delete topics redirect'	=>	'Темите са изтрити. Пренасочване …',
'Open topic redirect'		=>	'Темата е отворена. Пренасочване …',
'Open topics redirect'		=>	'Темите са отворени. Пренасочване …',
'Close topic redirect'		=>	'Темата е затворена. Пренасочване …',
'Close topics redirect'		=>	'Темите са затворени. Пренасочване …',
'No topics selected'		=>	'Трябва да изберете поне една тема.',
'Min topics selected'		=>	'Трябва да изберете най-малко две теми, за да извърши това действие.',
'Merge poll error'			=>	'Вие не можете да съедините, като 2 или повече теми включват гласуване. Премахнете гласуването преди да ги съедините.',
'Stick topic redirect'		=>	'Темата е отбелязана като Важна. Пренасочване …',
'Unstick topic redirect'	=>	'Темата вече не е отбелязана като Важна. Пренасочване … …',
'Merge error'				=>	'Трябва да изберете повече от 1 теми за да ги съедините.',

// Moderate topic
'Posts'						=>	'Мнения',
'Delete posts'				=>	'Изтрий изпраните мнения',
'Split posts'				=>	'Раздели избраните мнения',
'Delete whole topic'		=>	'Изтрий цялата тема',
'Moderate topic head'		=>	'Модерирай тема : %s',
'New subject'				=>	'Ново заглавие на темата:',
'Select post'				=>	'Избери мнение', // Label for checkbox
'Confirm post delete'		=>	'Потвърди изтриването на всички избрани мнения',
'Confirm topic delete'		=>	'Потвърди изтриването на всички избрани теми',
'Delete posts redirect'		=>	'Мненията са изтрити. Пренасочване …',
'Split posts redirect'		=>	'Мненията са разделени в нова тема. Пренасочване …',
'No posts selected'			=>	'Трябва да изберете поне едно мнение.',

// Smilies
'Smilies'					=>	'Емоции',
'Click smilies'				=> 	'Кликнете върху усмивка, и тя ще се появи в вашето съобщение',
'Closed'					=> 	'Затваряне на прозореца',

// Change database engine
'Change database engine'	=>	'Промяна на ниско ниво подсистема MySQL',
'Perform conversion'		=>	'Конвертиране на БД',
'Perform conversion label'	=>	'Извършване на преобразуване на подсистемата на ниско ниво %1$s за %2$s.',
'Conversion successful'		=>	'Подсистема е преобразувана с %1$s за %2$s. Пренасочване…',
'Conversion not supported'	=>	'Част от нея не го позволява подкрепя избран подсистемата.',
'Already converted'			=>	'Базата данни вече се използва препоръчано от подсистемата.'

);