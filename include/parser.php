<?php
/**
 * Загружает различные функции, используемые для парсинга сообщений.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Список смайлов
if (!defined('FORUM_SMILIES_LOADED'))
	require FORUM_ROOT.'include/smilies.php';

($hook = get_hook('ps_start')) ? eval($hook) : null;


// Make sure all BBCodes are lower case and do a little cleanup
function preparse_bbcode($text, &$errors, $is_signature = false)
{
	global $forum_config;

	$return = ($hook = get_hook('ps_preparse_bbcode_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($is_signature)
	{
		global $lang_profile;

		if (preg_match('#\[quote(=(&quot;|"|\'|)(.*)\\1)?\]|\[/quote\]|\[code\]|\[/code\]|\[list(=([1a\*]))?\]|\[/list\]#i', $text))
			$errors[] = $lang_profile['Signature quote/code/list'];
	}

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]', $errors);
		$text = implode("\xc1", $outside);
	}

	// Tidy up lists
	$pattern = array('%\[list(?:=([1a*]))?+\]((?:(?>.*?(?=\[list(?:=[1a*])?+\]|\[/list\]))|(?R))*)\[/list\]%ise');
	$replace = array('preparse_list_tag(\'$2\', \'$1\', $errors)');
	$text = preg_replace($pattern, $replace, $text);

	$text = str_replace('*'."\0".']', '*]', $text);

	if ($forum_config['o_make_links'])
		$text = do_clickable($text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\xc1", $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
				$text .= '[code]'.$inside[$i].'[/code]';
		}
	}

	$temp_text = false;
	if (empty($errors))
		$temp_text = preparse_tags($text, $errors, $is_signature);

	if ($temp_text !== false)
		$text = $temp_text;

	// Remove empty tags
	while ($new_text = preg_replace('/\[(b|u|i|h|colou?r|quote|code|spoiler|hide|img|url|email|list)(?:\=[^\]]*)?\]\[\/\1\]/', '', $text))
	{
		if ($new_text != $text)
			$text = $new_text;
		else
			break;
	}

	$return = ($hook = get_hook('ps_preparse_bbcode_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return forum_trim($text);
}


// Check the structure of bbcode tags and fix simple mistakes where possible
function preparse_tags($text, &$errors, $is_signature = false)
{
	global $lang_common, $forum_config;

	// Start off by making some arrays of bbcode tags and what we need to do with each one

	// List of all the tags
	$tags = array('quote', 'code', 'b', 'i', 'u', 's', 'color', 'colour', 'url', 'email', 'spoiler', 'hide', 'img', 'video', 'wiki', 'list', '*', 'h');
	// List of tags that we need to check are open (You could not put b,i,u in here then illegal nesting like [b][i][/b][/i] would be allowed)
	$tags_opened = $tags;
	// and tags we need to check are closed (the same as above, added it just in case)
	$tags_closed = $tags;
	// Tags we can nest and the depth they can be nested to (only quotes )
	$tags_nested = array('quote' => $forum_config['o_quote_depth'], 'list' => 5, '*' => 5);
	// Tags to ignore the contents of completely (just code)
	$tags_ignore = array('code');
	// Block tags, block tags can only go within another block tag, they cannot be in a normal tag
	$tags_block = array('quote', 'code', 'list', 'spoiler', 'hide', 'h', '*');
	// Inline tags, we do not allow new lines in these
	$tags_inline = array('b', 'i', 'u', 's', 'color', 'colour', 'wiki', 'h');
	// Tags we trim interior space
	$tags_trim = array('img');
	// Tags we remove quotes from the argument
	$tags_quotes = array('url', 'email', 'img');
	// Tags we limit bbcode in
	$tags_limit_bbcode = array(
		'*' 	=> array('b', 'i', 'u', 's', 'color', 'colour', 'url', 'email', 'list', 'img', 'wiki'),
		'list' 	=> array('*'),
		'url' 	=> array('b', 'i', 'u', 's', 'color', 'colour', 'img', 'wiki'),
		'email' => array('b', 'i', 'u', 's', 'color', 'colour', 'img', 'wiki'),
		'img' 	=> array()
	);
	// Tags we can automatically fix bad nesting
	$tags_fix = array('quote', 'b', 'i', 'u', 's', 'color', 'colour', 'url', 'email', 'wiki', 'h');

	$return = ($hook = get_hook('ps_preparse_tags_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$split_text = preg_split("/(\[[\*a-zA-Z0-9-\/]*?(?:=.*?)?\])/", $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

	$open_tags = array('post');
	$open_args = array('');
	$opened_tag = 0;
	$new_text = '';
	$current_ignore = '';
	$current_nest = '';
	$current_depth = array();
	$limit_bbcode = $tags;

	foreach ($split_text as $current)
	{
		if ($current == '')
			continue;

		// Are we dealing with a tag?
		if (substr($current, 0, 1) != '[' || substr($current, -1, 1) != ']')
		{
			// Its not a bbcode tag so we put it on the end and continue

			// If we are nested too deeply don't add to the end
			if ($current_nest)
				continue;

			$current = str_replace("\r\n", "\n", $current);
			$current = str_replace("\r", "\n", $current);
			if (in_array($open_tags[$opened_tag], $tags_inline) && strpos($current, "\n") !== false)
			{
				// Deal with new lines
				$split_current = preg_split("/(\n\n+)/", $current, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$current = '';
				
				if (!forum_trim($split_current[0], "\n")) // the first part is a linebreak so we need to handle any open tags first
					array_unshift($split_current, '');

				$split_current_c = count($split_current);
				for ($i = 1; $i < $split_current_c; $i += 2)
				{
					$temp_opened = array();
					$temp_opened_arg = array();
					$temp = $split_current[$i - 1];
					while (!empty($open_tags))
					{
						$temp_tag = array_pop($open_tags);
						$temp_arg = array_pop($open_args);

						if (in_array($temp_tag , $tags_inline))
						{
							array_push($temp_opened, $temp_tag);
							array_push($temp_opened_arg, $temp_arg);
							$temp .= '[/'.$temp_tag.']';
						}
						else
						{
							array_push($open_tags, $temp_tag);
							array_push($open_args, $temp_arg);
							break;
						}
					}
					$current .= $temp.$split_current[$i];
					$temp = '';
					while (!empty($temp_opened))
					{
						$temp_tag = array_pop($temp_opened);
						$temp_arg = array_pop($temp_opened_arg);
						if (empty($temp_arg))
							$temp .= '['.$temp_tag.']';
						else
							$temp .= '['.$temp_tag.'='.$temp_arg.']';
						array_push($open_tags, $temp_tag);
						array_push($open_args, $temp_arg);
					}
					$current .= $temp;
				}
				
				if (array_key_exists($i - 1, $split_current))
					$current .= $split_current[$i - 1];
			}

			if (in_array($open_tags[$opened_tag], $tags_trim))
				$new_text .= forum_trim($current);
			else
				$new_text .= $current;

			continue;
		}

		// Get the name of the tag
		$current_arg = '';
		if (strpos($current, '/') === 1)
			$current_tag = substr($current, 2, -1);
		else if (strpos($current, '=') === false)
			$current_tag = substr($current, 1, -1);
		else
		{
			$current_tag = substr($current, 1, strpos($current, '=')-1);
			$current_arg = substr($current, strpos($current, '=')+1, -1);
		}
		$current_tag = strtolower($current_tag);

		// Is the tag defined?
		if (!in_array($current_tag, $tags))
		{
			// Its not a bbcode tag so we put it on the end and continue
			if (!$current_nest)
				$new_text .= $current;

			continue;
		}

		// We definitely have a bbcode tag.

		// Make the tag string lower case
		if ($equalpos = strpos($current,'='))
		{
			// We have an argument for the tag which we don't want to make lowercase
			if (strlen(substr($current, $equalpos)) == 2)
			{
				// Empty tag argument
				$errors[] = sprintf($lang_common['BBCode error 6'], $current_tag);
				return false;
			}
			$current = strtolower(substr($current, 0, $equalpos)).substr($current, $equalpos);
		}
		else
			$current = strtolower($current);

		//This is if we are currently in a tag which escapes other bbcode such as code
		if ($current_ignore)
		{
			if ('[/'.$current_ignore.']' == $current)
			{
				// We've finished the ignored section
				$current = '[/'.$current_tag.']';
				$current_ignore = '';
			}

			$new_text .= $current;

			continue;
		}

		if ($current_nest)
		{
			// We are currently too deeply nested so lets see if we are closing the tag or not.
			if ($current_tag != $current_nest)
				continue;

			if (substr($current, 1, 1) == '/')
				$current_depth[$current_nest]--;
			else
				$current_depth[$current_nest]++;

			if ($current_depth[$current_nest] <= $tags_nested[$current_nest])
				$current_nest = '';

			continue;
		}

		// Check the current tag is allowed here
		if (!in_array($current_tag, $limit_bbcode) && $current_tag != $open_tags[$opened_tag])
		{
			$errors[] = sprintf($lang_common['BBCode error 3'], $current_tag, $open_tags[$opened_tag]);
			return false;
		}

		if (substr($current, 1, 1) == '/')
		{
			//This is if we are closing a tag

			if ($opened_tag == 0 || !in_array($current_tag, $open_tags))
			{
				//We tried to close a tag which is not open
				if (in_array($current_tag, $tags_opened))
				{
					$errors[] = sprintf($lang_common['BBCode error 1'], $current_tag);
					return false;
				}
			}
			else
			{
				// Check nesting
				while (true)
				{
					// Nesting is ok
					if ($open_tags[$opened_tag] == $current_tag)
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
						break;
					}

					// Nesting isn't ok, try to fix it
					if (in_array($open_tags[$opened_tag], $tags_closed) && in_array($current_tag, $tags_closed))
					{
						if (in_array($current_tag, $open_tags))
						{
							$temp_opened = array();
							$temp_opened_arg = array();
							$temp = '';
							while (!empty($open_tags))
							{
								$temp_tag = array_pop($open_tags);
								$temp_arg = array_pop($open_args);

								if (!in_array($temp_tag, $tags_fix))
								{
									// We couldn't fix nesting
									$errors[] = sprintf($lang_common['BBCode error 5'], array_pop($temp_opened));
									return false;
								}
								array_push($temp_opened, $temp_tag);
								array_push($temp_opened_arg, $temp_arg);

								if ($temp_tag == $current_tag)
									break;
								else
									$temp .= '[/'.$temp_tag.']';
							}
							$current = $temp.$current;
							$temp = '';
							array_pop($temp_opened);
							array_pop($temp_opened_arg);

							while (!empty($temp_opened))
							{
								$temp_tag = array_pop($temp_opened);
								$temp_arg = array_pop($temp_opened_arg);
								if (empty($temp_arg))
									$temp .= '['.$temp_tag.']';
								else
									$temp .= '['.$temp_tag.'='.$temp_arg.']';
								array_push($open_tags, $temp_tag);
								array_push($open_args, $temp_arg);
							}
							$current .= $temp;
							$opened_tag--;
							break;
						}
						else
						{
							// We couldn't fix nesting
							$errors[] = sprintf($lang_common['BBCode error 1'], $current_tag);
							return false;
						}
					}
					else if (in_array($open_tags[$opened_tag], $tags_closed))
						break;
					else
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
					}
				}
			}

			if (in_array($current_tag, array_keys($tags_nested)))
			{
				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]--;
			}

			if (in_array($open_tags[$opened_tag], array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$open_tags[$opened_tag]];
			else
				$limit_bbcode = $tags;

			$new_text .= $current;

			continue;
		}
		else
		{
			// We are opening a tag
			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];
			else
				$limit_bbcode = $tags;

			if (in_array($current_tag, $tags_block) && !in_array($open_tags[$opened_tag], $tags_block) && $opened_tag != 0)
			{
				// We tried to open a block tag within a non-block tag
				$errors[] = sprintf($lang_common['BBCode error 3'], $current_tag, $open_tags[$opened_tag]);
				return false;
			}

			if (in_array($current_tag, $tags_ignore))
			{
				// Its an ignore tag so we don't need to worry about whats inside it,
				$current_ignore = $current_tag;
				$new_text .= $current;
				continue;
			}

			// Deal with nested tags
			if (in_array($current_tag, $open_tags) && !in_array($current_tag, array_keys($tags_nested)))
			{
				// We nested a tag we shouldn't
				$errors[] = sprintf($lang_common['BBCode error 4'], $current_tag);
				return false;
			}
			else if (in_array($current_tag, array_keys($tags_nested)))
			{
				// We are allowed to nest this tag

				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]++;
				else
					$current_depth[$current_tag] = 1;

				// See if we are nested too deep
				if ($current_depth[$current_tag] > $tags_nested[$current_tag])
				{
					$current_nest = $current_tag;
					continue;
				}
			}

			// Remove quotes from arguments for certain tags
			if (strpos($current, '=') !== false && in_array($current_tag, $tags_quotes))
			{
				$current = preg_replace('#\['.$current_tag.'=("|\'|)(.*?)\\1\]\s*#i', '['.$current_tag.'=$2]', $current);
			}

			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];

			$open_tags[] = $current_tag;
			$open_args[] = $current_arg;
			$opened_tag++;
			$new_text .= $current;
			continue;
		}
	}

	// Check we closed all the tags we needed to
	foreach ($tags_closed as $check)
	{
		if (in_array($check, $open_tags))
		{
			// We left an important tag open
			$errors[] = sprintf($lang_common['BBCode error 5'], $check);
			return false;
		}
	}

	if ($current_ignore)
	{
		// We left an ignore tag open
		$errors[] = sprintf($lang_common['BBCode error 5'], $current_ignore);
		return false;
	}

	$return = ($hook = get_hook('ps_preparse_tags_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $new_text;
}


// Preparse the contents of [list] bbcode
function preparse_list_tag($content, $type = '*', &$errors)
{
	global $lang_common;

	if (strlen($type) != 1)
		$type = '*';
	
	if (strpos($content,'[list') !== false)
	{
		$pattern = array('%\[list(?:=([1a*]))?+\]((?:(?>.*?(?=\[list(?:=[1a*])?+\]|\[/list\]))|(?R))*)\[/list\]%ise');
		$replace = array('preparse_list_tag(\'$2\', \'$1\', $errors)');
		$content = preg_replace($pattern, $replace, $content);
	}

	$items = explode('[*]', str_replace('\"', '"', $content));

	$content = '';
	foreach ($items as $item)
	{
		if (forum_trim($item) != '')
			$content .= '[*'."\0".']'.str_replace('[/*]', '', forum_trim($item)).'[/*'."\0".']'."\n";
	}

	return '[list='.$type.']'."\n".$content.'[/list]';
}


// Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
function split_text($text, $start, $end, &$errors, $retab = true)
{
	global $forum_config, $lang_common;

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i)
	{
		$temp = explode($end, $tokens[$i]);

		if (count($temp) != 2)
		{
			$errors[] = $lang_common['BBCode code problem'];
			return array(null, array($text));
		}
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}

	if ($forum_config['o_indent_num_spaces'] != 8 && $retab)
	{
		$spaces = str_repeat(' ', $forum_config['o_indent_num_spaces']);
		$inside = str_replace("\t", $spaces, $inside);
	}

	return array($inside, $outside);
}


// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
function handle_url_tag($url, $link = '', $bbcode = false)
{
	global $base_url;
	$return = ($hook = get_hook('ps_handle_url_tag_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
	if (strpos($url, 'www.') === 0) // If it starts with www, we add http://
		$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0) // Else if it starts with ftp, we add ftp://
		$full_url = 'ftp://'.$full_url;
	else if (!preg_match('#^([a-z0-9]{3,6})://#', $url)) // Else if it doesn't start with abcdef://, we add http://
		$full_url = 'http://'.$full_url;

	// Ok, not very pretty :-)
	if (!$bbcode)
		$link = ($link == '' || $link == $url) ? ((utf8_strlen($url) > 55) ? utf8_substr($url, 0 , 39).' … '.utf8_substr($url, -10) : $url) : stripslashes($link);


	$return = ($hook = get_hook('ps_handle_url_tag_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($bbcode)
	{
		if ($full_url == $link)
			return '[url]'.$link.'[/url]';
		else
			return '[url='.$full_url.']'.$link.'[/url]';
	}
	else
	{
		$site_url = (dirname($_SERVER['REQUEST_URI']) != '/') ? $_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']) : $_SERVER['SERVER_NAME'];

		$site_base = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $site_url);
		if (strpos($site_url, 'www.') === 0) // If it starts with www, we add http://
			$site_base = 'http://'.$site_base;
		else if (strpos($site_url, 'ftp.') === 0) // Else if it starts with ftp, we add ftp://
			$site_base = 'ftp://'.$site_base;
		else if (!preg_match('#^([a-z0-9]{3,6})://#', $site_url)) // Else if it doesn't start with abcdef://, we add http://
			$site_base = 'http://'.$site_base;

		if (preg_match('#^'.preg_quote(str_replace('www.', '', $site_base), '#').'#i', str_replace('www.', '', $full_url)))
			return '<a href="'.$full_url.'">'.$link.'</a>';
		else
			return '<a href="'.forum_link('click.php').'?'.$full_url.'" onclick="window.open(this.href); return false" rel="nofollow">'.$link.'</a>';
	}
}


// Turns an URL from the [img] tag into an <img> tag or a <a href...> tag
function handle_img_tag($url, $is_signature = false, $alt = null)
{
	global $lang_common, $forum_user;

	$return = ($hook = get_hook('ps_handle_img_tag_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$alt = forum_htmlencode($alt);
	if ($alt == '')
	{
		$alt = $url;
		$title = '';
	}
	else
		$title = ' class="popup" title="'.$lang_common['Description'].' - '.$alt.'"';

	$img_tag = '<a href="'.$url.'">&lt;'.$lang_common['Image link'].'&gt;</a>';

	if ($is_signature && $forum_user['show_img_sig'])
		$img_tag = '<img class="sigimage" src="'.$url.'" alt="'.$alt.'"'.$title.'/>';
	else if (!$is_signature && $forum_user['show_img'])
		$img_tag = '<span class="postimg"><img src="'.$url.'" alt="'.$alt.'"'.$title.'/></span>';

	$return = ($hook = get_hook('ps_handle_img_tag_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $img_tag;
}


// Функция [video]
function video($url)
{
	$return = ($hook = get_hook('ps_fl_video_bbcode_parser')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if (preg_match('#http://.*youtube\.com/watch\?v=([0-9a-zA-Z_-]+)#s', $url, $matches))
		return '<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/'.forum_htmlencode($matches['1']).'" /><param name="wmode" value="transparent" /><embed src="http://www.youtube.com/v/'.forum_htmlencode($matches['1']).'" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>';
	else if (preg_match('#http://www\.veoh\.com/videos/(.*)#', $url, $matches))
		return '<embed src="http://www.veoh.com/videodetails2.swf?permalinkId='.forum_htmlencode($matches['1']).'&amp;id=anonymous&amp;player=videodetailsembedded&amp;videoAutoPlay=0" allowFullScreen="true" width="540" height="438" bgcolor="#FFFFFF" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>';
	else if (preg_match('#http://tinypic\.com/player\.php\?v=(.*)#s', $url, $matches))
		return '<embed width="440" height="380" type="application/x-shockwave-flash" src="http://v3.tinypic.com/player.swf?file='.forum_htmlencode($matches['1']).'"></embed>';
	else if (preg_match('#http://www\.metacafe\.com/watch/(\d+)/(.*)/#', $url, $matches))
 		return '<embed src="http://www.metacafe.com/fplayer/'.forum_htmlencode($matches['1']).'/'.forum_htmlencode($matches['2']).'.swf" width="400" height="345" wmode="transparent" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>';
	else if (preg_match('#http://www\.videovat\.com/videos/(\d+)/.*#', $url, $matches))
		return '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="424" height="373" id="videovatPlayer"><param name="allowScriptAccess" value="always" /><param name="movie" value="http://www.videovat.com/videoPlayer.swf" /><param name="quality" value="high" /><param name="flashvars" value="videoId='.forum_htmlencode($matches['1']).'" /><param name="allowFullscreen" value="true" /><param name="wmode" value="transparent" /><embed src="http://www.videovat.com/videoPlayer.swf" quality="high" wmode="transparent" flashvars="videoId=16816" width="424" height="373" name="videovatPlayer" align="middle" allowScriptAccess="always" allowFullscreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></embed></object>';
	else if (preg_match('#http://www\.gametrailers\.com/player/(\d+)\.html#s', $url, $matches))
 		return '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"  codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" id="gtembed" width="480" height="392"><param name="allowScriptAccess" value="sameDomain" /><param name="allowFullScreen" value="true" /> <param name="movie" value="http://www.gametrailers.com/remote_wrap.php?mid='.forum_htmlencode($matches['1']).'"/> <param name="quality" value="high" /> <embed src="http://www.gametrailers.com/remote_wrap.php?mid='.forum_htmlencode($matches['1']).'" swLiveConnect="true" name="gtembed" align="middle" allowScriptAccess="sameDomain" allowFullScreen="true" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="480" height="392"></embed> </object>';
	else if (preg_match('#http://video.yahoo.com/watch/(\d+)/(\d+)#', $url, $matches))
		return '<object width="512" height="323"><param name="movie" value="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.2" /><param name="allowFullScreen" value="true" /><param name="flashVars" value="id='.forum_htmlencode($matches['2']).'&amp;vid='.forum_htmlencode($matches['1']).'&amp;lang=en-us&amp;intl=us&amp;embed=1" /><embed src="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.2" type="application/x-shockwave-flash" width="512" height="323" allowFullScreen="true" flashVars="id='.forum_htmlencode($matches['2']).'&amp;vid='.forum_htmlencode($matches['1']).'&amp;lang=en-us&amp;intl=us&amp;embed=1" ></embed></object>';
	else if (preg_match('#http://v\.youku\.com/v_show/id_ca00XMj([a-zA-Z0-9]+)=\.html#s', $url, $matches))
		return '<embed src="http://player.youku.com/player.php/sid/XMj'.forum_htmlencode($matches['1']).'=/v.swf" quality="high" width="480" height="400" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash"></embed>';
	else if (preg_match('#http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual\&amp;VideoID=(\d+)#s', $url, $matches))
		return '<embed pluginspage="http://www.macromedia.com/go/getflashplayer" src="http://lads.myspace.com/videos/vplayer.swf" width="430" height="346" type=application/x-shockwave-flash allownetworking="internal" allowscriptaccess="never" flashvars="m='.forum_htmlencode($matches['1']).'&amp;v=2&amp;type=video" wmode="opaque">';
	else if (preg_match('#http://video\.google\.com/videoplay\?docid=(-?\d+)(.*)?#s', $url, $matches))
		return '<embed id="VideoPlayback" style="width:400px;height:326px" flashvars="" src="http://video.google.com/googleplayer.swf?docid='.forum_htmlencode($matches['1']).'&amp;hl=en" type="application/x-shockwave-flash"></embed>';
	else if (preg_match('#http://www\.dailymotion\.com/video/(.*?)_#', $url, $matches))
		return '<object width="520" height="406" align="top" data="http://www.dailymotion.com/swf/'.forum_htmlencode($matches['1']).'.swf" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="http://www.dailymotion.com/swf/'.forum_htmlencode($matches[1]).'.swf" /><param name="quality" value="best" /><embed src="http://www.dailymotion.com/swf/'.forum_htmlencode($matches['1']).'" width="520" height="406" quality="best" align="top" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></embed></object>';
	else if (preg_match('#http://www\.collegehumor\.com/video:([0-9]+)#', $url, $matches))
		return '<object id="video_1820056" type="application/x-shockwave-flash" data="http://www.collegehumor.com/moogaloop/moogaloop.internal.swf?clip_id='.forum_htmlencode($matches['1']).'&amp;autostart=true&amp;fullscreen=1" width="480" height="360"><param name="allowfullscreen" value="true" /><param name="movie" quality="best" value="http://www.collegehumor.com/moogaloop/moogaloop.internal.swf?clip_id='.forum_htmlencode($matches['1']).'&amp;autostart=true&amp;fullscreen=1" /></object>';
	else if (preg_match('#http://www\.vimeo\.com/([0-9]+)#', $url, $matches))
		return '<object class="swf_holder" type="application/x-shockwave-flash" width="506" height="382" data="http://www.vimeo.com/moogaloop_local.swf?clip_id='.forum_htmlencode($matches['1']).'&amp;server=www.vimeo.com&amp;autoplay=0&amp;fullscreen=1&amp;show_portrait=0&amp;show_title=0&amp;show_byline=0&amp;md5=&amp;color=&amp;context=&amp;context_id=&amp;hd_off=0"><param name="quality" value="high" /><param name="allowfullscreen" value="true" /><param name="AllowScriptAccess" value="always" /><param name="scale" value="showAll" /><param name="movie" value="http://www.vimeo.com/moogaloop_local.swf?clip_id='.forum_htmlencode($matches['1']).'&amp;server=www.vimeo.com&amp;autoplay=0&amp;fullscreen=1&amp;show_portrait=0&amp;show_title=0&amp;show_byline=0&amp;md5=&amp;color=&amp;context=&amp;context_id=&amp;hd_off=0" /></object>';
 	else if (preg_match("#http:\/\/rutube\.ru\/tracks\/([0-9]+)\.html\?v=(\w+)#",$url, $matches))
		return '<object width="470" height="353"><param name="movie" value="http://video.rutube.ru/'.forum_htmlencode($matches['2']).'"></param><param name="wmode" value="window"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="never" /><embed allowscriptaccess="never" src="http://video.rutube.ru/'.forum_htmlencode($matches['2']).'" type="application/x-shockwave-flash" wmode="window" width="470" height="353" allowFullScreen="true" ></embed></object>';
	else
		return forum_htmlencode($url);

	$return = ($hook = get_hook('ps_fl_video_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;
}


// Parse the contents of [list] bbcode
function handle_list_tag($content, $type = '*')
{
	if (strlen($type) != 1)
		$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$pattern = array('%\[list(?:=([1a*]))?+\]((?:(?>.*?(?=\[list(?:=[1a*])?+\]|\[/list\]))|(?R))*)\[/list\]%ise');
		$replace = array('handle_list_tag(\'$2\', \'$1\')');
		$content = preg_replace($pattern, $replace, $content);
	}

	$content = preg_replace('#\s*\[\*\](.*?)\[/\*\]\s*#s', '<li><p>$1</p></li>', forum_trim($content));

	if ($type == '*')
		$content = '<ul>'.$content.'</ul>';
	else
	{
		if ($type == 'a')
			$content = '<ol class="alpha">'.$content.'</ol>';
		else
			$content = '<ol class="decimal">'.$content.'</ol>';
	}

	return '</p>'.$content.'<p>';
}


// Convert BBCodes to their HTML equivalent
function do_bbcode($text, $is_signature = false)
{
	global $lang_common, $forum_user, $forum_url, $forum_config, $base_url;

	$return = ($hook = get_hook('ps_do_bbcode_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if (strpos($text, '[quote') !== false)
	{
		$text = preg_replace('#\[quote=(&quot;|"|\'|)(.*?)\\1\]#se', '"</p><div class=\"quotebox\"><cite>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." ".$lang_common[\'wrote\'].":</cite><blockquote><div><p>"', $text);
		$text = preg_replace('#\[quote\]\s*#', '</p><div class="quotebox"><blockquote><div><p>', $text);
		$text = preg_replace('#\s*\[\/quote\]#S', '</p></div></blockquote></div><p>', $text);
	}

	if (strpos($text, '[hide') !== false)
	{
		$text = preg_replace('#\[hide\](.*?)\[\/hide\]#si', '</p><div class="hide-wrap"><span class="hide-head" onclick="$(this).toggleClass(\'show\');"><span>'.$lang_common['Hidden show text'].'</span></span><div class="hide-text">$1</div></div><p>', $text);

		if ($forum_user['is_guest'])
			$text = preg_replace('#\[hide=([0-9]*)](.*?)\[/hide\]#si', '<strong>['.sprintf($lang_common['Hidden text guest'], '<a href="'.forum_link($forum_url['login']).'">'.$lang_common['login'].'</a>', '<a href="'.forum_link($forum_url['register']).'">'.$lang_common['register'].'</a>').']</strong>', $text);
		else
		{
			$num_hide = preg_match_all("#\[hide\=.+?\](.+?)\[/hide\]#si", $text, $temp);
			for($i = 0; $i < $num_hide; $i++)
			{
				preg_match("#\[hide\=(.+?)\].+?\[/hide\]#s", $temp[0][$i], $hide_count);
				if($forum_user['is_admmod'] || $forum_user['num_posts'] >= $hide_count[1])
					$text_hide = preg_replace('#\[hide=([0-9]*)](.*?)\[/hide\]#s', '</p><div class="hide-wrap"><span class="hide-head" onclick="$(this).toggleClass(\'show\');"><span>'.$lang_common['Hidden show text'].'</span></span><div class="hide-text">$2</div></div><p>', $temp[0][$i]);
				else
					$text_hide = preg_replace("#\[hide=([0-9]*)](.*?)\[/hide\]#s", '<strong>'.sprintf($lang_common['Hidden count text'], $hide_count['1']).'</strong>', $temp[0][$i]);

				if (isset($text_hide))
					$text = str_replace($temp[0][$i], $text_hide, $text);
			}
		}
	}

	if (!$is_signature)
	{
		$pattern[] = '%\[list(?:=([1a*]))?+\]((?:(?>.*?(?=\[list(?:=[1a*])?+\]|\[/list\]))|(?R))*)\[/list\]%ise';
		$replace[] = 'handle_list_tag(\'$2\', \'$1\')';
	}

	$pattern[] = '#\[b\](.*?)\[/b\]#ms';
	$pattern[] = '#\[i\](.*?)\[/i\]#ms';
	$pattern[] = '#\[u\](.*?)\[/u\]#ms';
	$pattern[] = '#\[s\](.*?)\[/s\]#ms';
	$pattern[] = '#\[colou?r=([a-zA-Z]{3,20}|\#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})](.*?)\[/colou?r\]#ms';
	$pattern[] = '#\[h\](.*?)\[/h\]#ms';
	$pattern[] = '#\[center\](.*?)\[/center\]#ms';
	$pattern[] = '#\[right\](.*?)\[/right\]#ms';
	$pattern[] = '#\[left\](.*?)\[/left\]#ms';
	$pattern[] = '#\[font=(.*?)](.*?)\[/font\]#ms';
	$pattern[] = '#\[size=([0-9]*)](.*?)\[/size\]#ms';
	$pattern[] = '#\[wiki=(\w{2})\](.*?)\[/wiki\]#s';
	$pattern[] = '#\[hr\]#s';

	$replace[] = '<strong>$1</strong>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span class="bbu">$1</span>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<span style="color: $1">$2</span>';
	$replace[] = '</p><h5>$1</h5><p>';
	$replace[] = '</p><p style="text-align:center">$1</p><p>';
	$replace[] = '</p><p style="text-align:right">$1</p><p>';
	$replace[] = '</p><p style="text-align:left">$1</p><p>';
	$replace[] = '<span style="font-family: $1">$2</span>';
	$replace[] = '<span style="font-size: $1px">$2</span>';
	$replace[] = '<a href="'.forum_link('click.php').'?http://$1.wikipedia.org/wiki/$2" class="wiki" onclick="window.open(this.href); return false" rel="nofollow">$2</a>';
	$replace[] = '<hr />';

	if (($is_signature && $forum_config['p_sig_img_tag']) || (!$is_signature && $forum_config['p_message_img_tag']))
	{
		$pattern[] = '#\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
		$pattern[] = '#\[img=([^\[]*?)\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e';
		if ($is_signature)
		{
			$replace[] = 'handle_img_tag(\'$1$3\', true)';
			$replace[] = 'handle_img_tag(\'$2$4\', true, \'$1\')';
		}
		else
		{
			$replace[] = 'handle_img_tag(\'$1$3\', false)';
			$replace[] = 'handle_img_tag(\'$2$4\', false, \'$1\')';
		}
	}

	$pattern[] = '#\[video\](.*?)\[/video\]#e';
	$pattern[] = '#\[url\]([^\[]*?)\[/url\]#e';
	$pattern[] = '#\[url=([^\[]+?)\](.*?)\[/url\]#e';
	$pattern[] = '#\[email\]([^\[]*?)\[/email\]#';
	$pattern[] = '#\[email=([^\[]+?)\](.*?)\[/email\]#';

	$replace[] = 'video(\'$1\')';
	$replace[] = 'handle_url_tag(\'$1\')';
	$replace[] = 'handle_url_tag(\'$1\', \'$2\')';
	$replace[] = '<a href="mailto:$1">$1</a>';
	$replace[] = '<a href="mailto:$1">$2</a>';

	$return = ($hook = get_hook('ps_do_bbcode_replace')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);

	$return = ($hook = get_hook('ps_do_bbcode_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $text;
}


// Make hyperlinks clickable
function do_clickable($text)
{
	$text = ' '.$text;

	$text = preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#ie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5://$6\', \'$5://$6\', true).stripslashes(\'$4$10$11$12\')', $text);
	$text = preg_replace('#(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\s\[]*[^\s.,?!\[;:-])?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])#ie', 'stripslashes(\'$1$2$3$4\').handle_url_tag(\'$5.$6\', \'$5.$6\', true).stripslashes(\'$4$10$11$12\')', $text);

	return substr($text, 1);
}


// Convert a series of smilies to images
function do_smilies($text)
{
	global $forum_config, $base_url, $smilies;

	$return = ($hook = get_hook('ps_do_smilies_start')) ? eval($hook) : null; 
	if ($return != null)
		return $return;

	$text = ' '.$text.' ';

	foreach ($smilies as $smiley_text => $smiley_img)
	{
		if (strpos($text, $smiley_text) !== false)
			$text = preg_replace("#(?<=[>\s])".preg_quote($smiley_text, '#')."(?=\W)#m", '<img src="'.$base_url.'/img/smilies/'.$smiley_img.'" alt="'.substr($smiley_img, 0, strrpos($smiley_img, '.')).'" />', $text);
	}

	$return = ($hook = get_hook('ps_do_smilies_end')) ? eval($hook) : null;

	return substr($text, 1, -1);
}


// Parse message text
function parse_message($text, $hide_smilies)
{
	global $forum_config, $lang_common, $forum_user;

	$return = ($hook = get_hook('ps_parse_message_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($forum_config['o_censoring'])
		$text = censor_words($text);

	$return = ($hook = get_hook('ps_parse_message_post_censor')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Convert applicable characters to HTML entities
	$text = forum_htmlencode($text);

	$return = ($hook = get_hook('ps_parse_message_pre_split')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]', $errors);
		$text = implode("\xc1", $outside);
	}

	$return = ($hook = get_hook('ps_parse_message_post_split')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($forum_config['p_message_bbcode'] && strpos($text, '[') !== false && strpos($text, ']') !== false)
		$text = do_bbcode($text);

	if ($forum_config['o_smilies'] && $forum_user['show_smilies'] && !$hide_smilies)
		$text = do_smilies($text);

	$return = ($hook = get_hook('ps_parse_message_bbcode')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$text = str_replace($pattern, $replace, $text);

	$return = ($hook = get_hook('ps_parse_message_pre_merge')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\xc1", $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
				$text .= '</p><div class="codebox"><pre><code>'.forum_trim($inside[$i], "\n\r").'</code></pre></div><p>';
		}
	}

	$return = ($hook = get_hook('ps_parse_message_post_merge')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Add paragraph tag around post, but make sure there are no empty paragraphs
	$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
	$text = str_replace('<p><br />', '<p>', $text);
	$text = str_replace('<p></p>', '', '<p>'.$text.'</p>');

	$return = ($hook = get_hook('ps_parse_message_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $text;
}


// Parse signature text
function parse_signature($text)
{
	global $forum_config, $lang_common, $forum_user;

	$return = ($hook = get_hook('ps_parse_signature_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($forum_config['o_censoring'])
		$text = censor_words($text);

	$return = ($hook = get_hook('ps_parse_signature_post_censor')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Convert applicable characters to HTML entities
	$text = forum_htmlencode($text);

	$return = ($hook = get_hook('ps_parse_signature_pre_bbcode')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($forum_config['p_sig_bbcode'] && strpos($text, '[') !== false && strpos($text, ']') !== false)
		$text = do_bbcode($text, true);

	if ($forum_config['o_smilies_sig'] && $forum_user['show_smilies'])
		$text = do_smilies($text);

	$return = ($hook = get_hook('ps_parse_signature_post_bbcode')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$text = str_replace($pattern, $replace, $text);

	// Add paragraph tag around post, but make sure there are no empty paragraphs
	$text = preg_replace('#<br />\s*?<br />((\s*<br />)*)#i', "</p>$1<p>", $text);
	$text = str_replace('<p><br />', '<p>', $text);
	$text = str_replace('<p></p>', '', '<p>'.$text.'</p>'); 

	$return = ($hook = get_hook('ps_parse_signature_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $text;
}

define('FORUM_PARSER_LOADED', 1);
