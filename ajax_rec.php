<?php
	require_once('../../../wp-config.php');

	# get recommender settings
	$tagline = @get_option('recommender_tagline');
	$list_size = @get_option('recommender_list_size');
	$display_type = @get_option('recommender_display_type');
	$recommender_type = @get_option('recommender_type');
	$display_position = @get_option('recommender_display_position');
	$display_percentage = @get_option('recommender_display_percentage');

	# first check if user should receive recommendations at all because of percentage settings
	$u = explode(".", $_COOKIE['uid']);
	if($u[0]%10000 > $display_percentage*100)
		die();

	# check the selected recommender type
	if($recommender_type == 'cb')
	{
		# content based recommendations

		# get home path to build url with /home/path/somewhere/else/?p=ID
		$baseurl = parse_url(get_option('home'));
		$homepath = $baseurl['path'];

		# get the most similar posts from the database	
		$link = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		if($link)
		{
			if(@mysql_select_db(DB_NAME, $link))
			{
				$query = @sprintf("SELECT post_title, post_content FROM %sposts WHERE ID=%d", $table_prefix, $_GET['id']);
				$result = @mysql_query($query, $link);
				if($result)
				{
					$row = @mysql_fetch_assoc($result);
					if($row)
						$searchtext = @strip_tags(@mysql_escape_string($row['post_title'] . " ". $row['post_content']));
				}

				if(!$searchtext)
					return;

				$query = @sprintf("
					SELECT ID, post_title, MATCH (post_title, post_content) AGAINST ('%s') AS n 
					FROM %sposts
					WHERE post_status='publish' AND post_type like '%s' AND MATCH (post_title, post_content) AGAINST ('%s') AND ID != %d 
					ORDER BY n DESC
					LIMIT %d", $searchtext, $table_prefix, $display_position=='both'?'%':@substr($display_position, 0, 4), $searchtext, $_GET['id'], $list_size);

				$result = @mysql_query($query, $link);
				if($result)
				{
					while($row = @mysql_fetch_assoc($result))
						$items[] = array('url' => $homepath . '/?p='.$row['ID'], 'title' => $row['post_title'], 'match' => $row['n']);
				}
			}	
		}
		@mysql_close($link);
	}
	else
	{
		# collaborative filtering approach
		# get recommendations from server
		$secret_key = @get_option('recommender_secret_key');
		$list_size = @get_option('recommender_list_size');
		$response = @file_get_contents(sprintf("http://rec.aliiike.com/?account=%s&pk=%s&url=%s&size=%s&uid=%s&vid=%s", $_GET['account'], $secret_key, $_GET['url'], $list_size, $_COOKIE['uid'], $_COOKIE['vid']));

		# decode response into array
		$items = @json_decode($response, true);
	}

	# do the output
	# if nothing was returned, echo nothing	
	if(!@is_array($items))
		return;

	# apply list size from wp settings
	$items = array_slice($items, 0, $list_size);	

	# prepare elements for output
	$elements['ul'] = Array('open' => '<ul>', 'close' => '</ul>', 'line_open' => '<li>', 'line_close' => '</li>', 'poweredby' => '<li style="list-style-type: none; list-style-image: none; background: none;">');
	$elements['ol'] = Array('open' => '<ol>', 'close' => '</ol>', 'line_open' => '<li>', 'line_close' => '</li>', 'poweredby' => '<li style="list-style-type: none; list-style-image: none; background: none;">');
	$elements['tb'] = Array('open' => '<table>', 'close' => '</table>', 'line_open' => '<tr><td>', 'line_close' => '</td></tr>', 'poweredby' => '<tr><td>');
	$elements['br'] = Array('open' => '<br />', 'close' => '', 'line_open' => '', 'line_close' => '<br/>', 'poweredby' => '');
	$elements['tm'] = Array('open' => '<table>', 'close' => '</table>', 'line_open' => '<tr><td style="width: 50px; vertical-align: middle;"><div style="float: right; height:12px; width: %gpx; background: url(/wp-content/plugins/aliiike-web-recommender-system/stripesvert.gif); background-position: 100%% 0%%;">&nbsp;</div></td><td>&nbsp;&nbsp;</td><td>', 'line_close' => '</td></tr>', 'poweredby' => '<tr><td colspan="3">');

	# if display type not selected then the default display type is unordered list
	if(!@is_array($elements[$display_type]))
		$display_type = 'ul';

	$max_match= $items[0]['match'];

	# do the output
	echo $tagline;
	echo $elements[$display_type]['open']; 
	foreach($items as $item)
		echo @sprintf('%s<a href="%s">%s</a>%s', @sprintf($elements[$display_type]['line_open'], $item['match']*50/$max_match), $item['url'], $item['title'], $elements[$display_type]['line_close']);
	echo @sprintf('%s<a href="%s" style="border: none; background: none;">%s</a>%s', $elements[$display_type]['poweredby'], 'http://aliiike.com/wordpress/', '<img src="http://log.aliiike.com/s/img/poweredby.0.1.4.png" style="background: none; border: none;">', $elements[$display_type]['line_close']);
	echo $elements[$display_type]['close'];

?>
