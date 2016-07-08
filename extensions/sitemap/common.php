<?php
function createSitemap()
{
	global $forum_url, $forum_db;
	
	if(!is_writable(FORUM_ROOT.'sitemap.xml'))
		die("<strong>sitemap.xml</strong> on forum root is not writable.");
	
	if(!$handle = @fopen(FORUM_ROOT.'sitemap.xml', 'w'))
		die("Could not open <strong>sitemap.xml</strong> on forum root.");
	
	$atts['xmlns'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	$atts['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
	$atts['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
	
	$array = array();
	
	$array[] = forum_link($forum_url['index']);
	$array[] = forum_link($forum_url['users']);

	$query = array(
		'SELECT'	=> 't.id, t.subject, f.id AS fid, f.forum_name',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id=2)'
			)
		),
		'WHERE'		=> 'fp.read_forum IS NULL OR fp.read_forum=1',
		'ORDER BY'	=> 'f.disp_position ASC, t.id DESC'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	$cur_forum = array();
	
	while ($cur_topic = $forum_db->fetch_assoc($result))
	{
		if($cur_topic['fid'] != $cur_forum)
		{
			$array[] = forum_link($forum_url['forum'], array($cur_topic['fid'], sef_friendly($cur_topic['forum_name'])));
			$cur_forum = $cur_topic['fid'];
		}
		$array[] = forum_link($forum_url['topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject'])));
	}

	$query = array(
		'SELECT'	=> 'f.id, f.forum_name',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'categories AS c',
				'ON'			=> 'c.id=f.cat_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id=2)'
			)
		),
		'WHERE'		=> 'f.num_topics=0 AND (fp.read_forum IS NULL OR fp.read_forum=1)',
		'ORDER BY'	=> 'c.disp_position ASC, f.disp_position ASC'
	);
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	while ($cur_forum = $forum_db->fetch_assoc($result))
		$array[] = forum_link($forum_url['forum'], array($cur_forum['id'], sef_friendly($cur_forum['forum_name'])));
	
	$xml = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<!-- Created by Sitemap extension 0.0.1 for Flazy -->'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	
	foreach($array as $url)
	{
		$xml .= "\t<url>\n";
		$xml .= "\t\t<loc>$url</loc>\n";
		$xml .= "\t</url>\n";
	}
	
	$xml .= "</urlset>";
	
	if(@fwrite($handle, $xml) === false)
		die("Could not write to <strong>sitemap.xml</strong> on forum root.");
	
	fclose($handle);
}
?>