<?

// Языковые конструкии используемые в index.php
$lang_error = array(

// Заголовок
'title 400'		=>	'400 - Bad Request',
'title 401'		=>	'401 - Unauthorized',
'title 403'		=>	'403 - Forbidden',
'title 404'		=>	'404 - Not Found',
'title 500'		=>	'500 - Internal Server Error',
// Описание
'desc 400'		=>	'The server cannot or will not process the request due to something that is perceived to be a client error (e.g., malformed request syntax, invalid request message framing, or deceptive request routing)',
'desc 401'		=>	'Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided.',
'desc 403'		=>	'The request was a valid request, but the server is refusing to respond to it. Unlike a 401 Unauthorized response, authenticating will make no difference.',
'desc 404'		=>	'The requested resource could not be found but may be available again in the future. Subsequent requests by the client are permissible.',
'desc 500'		=>	'A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.',

// Код ошибки (eng)
'kod 400'		=>	'400 Bad Request ',
'kod 401'		=>	'401 Unauthorized ',
'kod 403'		=>	'403 Forbidden ',
'kod 404'		=>	'404 Not Found ',
'kod 500'		=>	'500 Internal Server Error ',

// Совет
/*RUS*/'board 400'		=>	'Запрос содержит синтаксическую ошибку и не может быть принят сервером',
/*RUS*/'board 401'		=>	'Запрос требует аутентификации пользователя',
/*RUS*/'board 403'		=>	'По какой либо причине у Вас нет права доступа к этой части сайта',
/*RUS*/'board 404'		=>	'Сервер не нашел ничего, что могло бы соответствовать URL запросу',
/*RUS*/'board 500'		=>	'Сервер столкнулся с непредвиденными обстоятельствами, которые не позволяют ему выполнить запрос',

/*RUS*/'Search'		=>	'Чтобы найти интересующую Вас информацию, воспользуйтесь <a href="%s">поиском</a> или перейдите на <a href="%s">главную страницу</a>',
'Redirect'		=>	'You will be automatically redirected to the home page, after %s sec(s)'

);