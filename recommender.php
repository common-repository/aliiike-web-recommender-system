<?php
/*
* Plugin Name: Aliiike Recommender System for Wordpress
* Plugin URI: http://aliiike.com/wordpress/
* Description: Web recommender system for posts and pages
* Version: 0.1.5
* Author: Peter Ljubic, Joore d.o.o.
* Author URI: http://perro.si/
* */

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
 
# guess the location
$plugin_path = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)).'/';

function recommender_content($content) 
{
	# add div only to posts or pages (not home page, categories, search, etc.)
	$display_position = get_option('recommender_display_position');

	if(
		($display_position == 'both') ||
		($display_position == 'posts' && is_single()) || 
		($display_position == 'pages' && is_page())
	)
		return $content . '<div><div id="__recommendations__"></div></div>';
	else
		return $content;
}
add_filter('the_content', 'recommender_content');

function recommender_wp_footer()
{
	global $plugin_path, $user_ID, $id;
	$account_id = get_option('recommender_account_id');
	$ajax_path = get_option('recommender_ajax_path');
	$tagline = get_option('recommender_tagline');
	$track_logged = get_option('recommender_track_logged');

	# track all users that:
	$track = !is_user_logged_in() ||					# 1. are not logged in
		$track_logged ||								# 2. are logged in and you specified you want to log all users
		(												# 3. are logged in and:
			!current_user_can('level_10') &&			#    a) are not administrators,
			($user_ID != get_post($id)->post_author)	#    b) AND are not watching his/her own post 
		);	

	# only output recommendations to posts and pages
	if(is_single() || is_page())
	{
		echo "<!-- START: aliiike code -->\n";
		echo "<script type=\"text/javascript\">\n";
		echo "document.write(unescape('%3Cscript src=\"http://log.aliiike.com/s/js/track.js\" type=\"text/javascript\"%3E%3C/script%3E'));\n";
		echo "</script>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "try {\n";
		echo "\t__ato__ = _get_aliiike_tracker('" . $account_id . "');\n";
		if($account_id != '')
			echo "\t__ato__.log();\n";
		echo "\t__ato__.recommend('" . $ajax_path . "?id=" . $id . "');\n";
		echo "} catch(e) {}\n";
		echo "</script>\n";
		echo "<!-- END: aliiike code -->\n";
	}
}

add_action('wp_footer', 'recommender_wp_footer');


function recommender_restore_or_init_config()
{
	update_option('recommender_account_id',			'');
	update_option('recommender_secret_key',			'');
	update_option('recommender_type',				'cb');
	update_option('recommender_track_logged', 		false);
	update_option('recommender_display_position',	'posts');
	update_option('recommender_tagline',			'<strong>Related content</strong>');
	update_option('recommender_list_size',			3);
	update_option('recommender_display_percentage',	100);
	update_option('recommender_index_error',		'');

	$baseurl = parse_url(get_option('home'));
	$ajaxpath = $baseurl['path'] . '/wp-content/plugins/aliiike-web-recommender-system/ajax_rec.php';
	$ajaxpath = str_replace('//', '/', $ajaxpath);
	update_option('recommender_ajax_path',			$ajaxpath);
}

function config_page()
{
	switch(true)
	{
		# user pressed restore built-in defaults button
		case isset($_POST['restore']):
			recommender_restore_or_init_config();
			break;

		# user pressed save changes button
		case isset($_POST['save']):
			update_option('recommender_account_id',			$_POST['account_identifier']);
			update_option('recommender_secret_key',			$_POST['secret_key']);
			update_option('recommender_type',				$_POST['recommender_type']);
			update_option('recommender_ajax_path',			$_POST['ajax_path']);
			update_option('recommender_display_position',	$_POST['display_position']);
			update_option('recommender_tagline',			$_POST['tagline']);
			update_option('recommender_list_size',			$_POST['list_size']);
			update_option('recommender_display_type',		$_POST['display_type']);
			update_option('recommender_display_percentage',	$_POST['display_percentage']);
			break;

		# create index (if it does not exist)
		case isset($_POST['create_index']):
			if(!recommender_index_exists())
				recommender_create_index();
			break;
	}

	# get all options in variables
	$account_id 		= get_option('recommender_account_id');
	$secret_key 		= get_option('recommender_secret_key');
	$recommender_type	= get_option('recommender_type');
	$ajax_path			= get_option('recommender_ajax_path');
	$display_position	= get_option('recommender_display_position');
	$tagline			= get_option('recommender_tagline');
	$list_size			= get_option('recommender_list_size');
	$display_type		= get_option('recommender_display_type');
	$display_percentage	= get_option('recommender_display_percentage');
	$index_error		= get_option('recommender_index_error');

	# initialize if none initialized
	if(!$ajax_path && !$display_position && !$tagline && !$list_size)
		recommender_restore_or_init_config();

	# and display form with variable values
?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
	<div class="wrap">
		<h2>Aliiike Recommender Settings</h2>
		<table class="form-table">
<?php if(!recommender_index_exists()) { ?>
			<tr>
				<td colspan="2" bgcolor="#faa">
					<p class="submit" style="padding-top: 0px; margin-top: 0px; border-top: 0px; ">
						<b>Plugin will not work because there is no required index on the database.
						You can try to create index by pressing Create Index button.</b><br /><br />
						<input type="submit" value="Create Index" name="create_index" />
						<?php echo $index_error; ?>
					<p>
				</td>
			</tr>
<?php } ?>
			<tr>
				<th valign="top">Recommender type:</th>
				<td>
					<select name="recommender_type">
						<option value="cb"<?php if($recommender_type == "cb") echo ' selected="selected"' ?>>Content based (simpler, recommended for a start :)</option>
						<option value="cf"<?php if($recommender_type == "cf") echo ' selected="selected"' ?>>Based on collaborative filtering (requires account from aliiike.com)</option>
					</select>
					<br><?php _e("<b>Content based</b> recommendations are easier to use, standalone (does not require any extra accounts), and work straight from the start. If post A is similar to post B (i.e., A and B share a lot of words) then post B is being recommended at seeining the post A.<br><br>Recommendations based on <b>collaborative filtering</b> normally offer better performance while they require few more steps at installation time. This type takes into account users' browsing behaviours that are logged in the first phase. System then learns from logs what posts (or pages) are being viewed by the same users. This type of recommendations require that you <a href='http://aliiike.com/user/register/'>register with aliiike.com</a> and create an account for your site. Then you have to wait until enough visit data is being gathered in the logging process. Only then the system can calculate and serve recommendations.<br><br>Suggested scenario is that you use <b>content based</b> type at the beginning and create an aliiike.com account. After enough data is collected, you switch the recommendation type to collaborative filtering, and compare site's performance in both cases using Google Analytics.", 'recommender'); ?><br/>
				</td>
			</tr>

			<tr>
				<th valign="top">Tagline:</th>
				<td>
					<input type="text" size="50" maxchars="200" name="tagline" value="<?php echo $tagline; ?>" />
					<br><?php _e("The tagline above the recommendation list. HTML tags are allowed here.", 'recommender'); ?><br/>
				</td>
			</tr>
			<tr>
				<th valign="top">List size:</th>
				<td>
					<select name="list_size">
						<?php for($i=1; $i<10; $i++) { ?>
						<option value="<?php echo $i; ?>"<?php if($i == $list_size) echo ' selected="selected"'?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
					<br><?php _e("The size of the recommendation list.", 'recommender'); ?><br/>
				</td>
			</tr>
			<tr>
				<th valign="top">Display type:</th>
				<td>
					<select name="display_type">
						<option value="ul"<?php if($display_type == 'ul') echo ' selected="selected"'?>>Unordered list</option>
						<option value="ol"<?php if($display_type == 'ol') echo ' selected="selected"'?>>Ordered list</option>
						<option value="tb"<?php if($display_type == 'tb') echo ' selected="selected"'?>>As table</option>
						<option value="tm"<?php if($display_type == 'tm') echo ' selected="selected"'?>>As table with similarity bar</option>
						<option value="br"<?php if($display_type == 'br') echo ' selected="selected"'?>>Using breaks</option>
					</select>
					<br><?php _e("The way the list is displayed. Defaults to <b>unordered list</b>.", 'recommender'); ?><br/>
				</td>
			</tr>
			<tr>
				<th valign="top">Display Position:</th>
				<td>
					<input type="radio" name="display_position" value="posts"<?php echo ($display_position == 'posts') ? ' checked="checked"' : ''; ?> /> Posts only<br/>
					<input type="radio" name="display_position" value="pages"<?php echo ($display_position == 'pages') ? ' checked="checked"' : ''; ?> /> Pages only<br/>
					<input type="radio" name="display_position" value="both"<?php echo ($display_position == 'both') ? ' checked="checked"' : ''; ?> /> Both
					<br><?php _e("Select where do you want to display recommendations. Default is to display list on <b>posts only</b>.", 'recommender'); ?>
				</td>
			</tr>
			<tr>
				<th valign="top">Display percentage:</th>
				<td>
					<select name="display_percentage">
						<?php
							$perc = Array(100, 90, 80, 70, 60, 50, 45, 40, 35, 30, 25, 20, 17.5, 15, 12.5, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0.5, 0.4, 0.3, 0.2, 0.1, 0.05, 0.04, 0.03, 0.02, 0.01 );
							foreach($perc as $i) { ?>
							<option value="<?php echo $i; ?>"<?php if($i == $display_percentage) echo ' selected="selected"'?>><?php echo $i; ?>%</option>
						<?php } ?>
					</select>
					<br><?php _e("Percentage of visitors that will get recommendations. This is useful for testing recommendations on subgroup of visitors. Combining this option with Google Analytics can reveal the influence of recommendation lists. Defaults to <b>100%</b>.", 'recommender'); ?><br/>
				</td>
			</tr>
			<tr>
				<th valign="top">Ajax path:</th>
				<td>
					<input type="text" size="60" name="ajax_path" value="<?php echo $ajax_path; ?>" />
					<br><?php _e("Path to file that receives the ajax request for recommendations.", 'recommender'); ?>
				</td>
			</tr>
			<tr>
				<th valign="top">Account identifier:</th>
				<td>
					<input type="text" size="10" maxchars="10" name="account_identifier" value="<?php echo $account_id; ?>" />
					<br><?php _e("Id of the form Txxxxxxxxx of your account from aliiike.com server. Not used for content based recommending.", 'recommender'); ?><br/>
				</td>
			</tr>
			<tr>
				<th valign="top">Secret key:</th>
				<td>
					<input type="text" size="32" maxchars="32" name="secret_key" value="<?php echo $secret_key; ?>" />
					<br><?php _e("The 32 characters key from your account from aliiike.com server. Not used for content based recommending.", 'recommender'); ?><br/>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input name="save" value="<?php _e("Save Changes", 'recommender'); ?>" type="submit" />
			<input name="restore" value="<?php _e("Restore Built-in Defaults", 'recommender'); ?>" type="submit"/>
		</p>
	</div>
</form>
<?php
}

function add_config_page()
{
	add_submenu_page('plugins.php', 'Recommender for WordPress Configuration', 'Aliiike Recommender', 10, basename(__FILE__), 'config_page');
}

add_action('admin_menu', 'add_config_page');

function recommender_index_exists()
{
	global $table_prefix;

	@mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(@mysql_select_db('information_schema'))
	{
		$query = @sprintf("SELECT count(*) AS idxnum FROM statistics WHERE index_schema='%s' AND index_name='comparator' AND index_type='FULLTEXT';", DB_NAME);
		$result = @mysql_query($query);
		if($result)
		{
			$row = @mysql_fetch_assoc($result);
			if($row)
			{
				if($row['idxnum'] == 2)
				{
					@mysql_close();
					return true;
				}
			}
		}
	}
	@mysql_close();

	return false;
}

function recommender_create_index()
{
	global $table_prefix;

	@mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(@mysql_select_db(DB_NAME))
	{
		$query = sprintf("CREATE FULLTEXT INDEX comparator ON %sposts(post_title, post_content)", $table_prefix);
		$result = @mysql_query($query);
		if(!$result)
			update_option('recommender_index_error', mysql_error());
	}
	@mysql_close();
}

function recommender_activate()
{
	global $table_prefix;

	recommender_restore_or_init_config();
	if(recommender_index_exists())
		return;

	recommender_create_index();
}

register_activation_hook( __FILE__, 'recommender_activate' );

?>
