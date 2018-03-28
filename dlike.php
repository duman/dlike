<?php
/**
 * Plugin Name: DumanLIKE
 * Plugin URI: https://github.com/tkduman/dlike
 * Description: Wordpress like extension, it's not bounded to any social network. Works locally.
 * Version: 1.0.0
 * Author: Tuğberk Kaan Duman
 * Author URI: https://dumanstudios.com
 */
?>

<?php
ini_set('display_errors', 1);
$the_theme = wp_get_theme(); // this will make extension work with any theme
$TextDomain = $the_theme->get('TextDomain');

add_action('wp_enqueue_scripts', 'dumanlike_enqueue_script');
function dumanlike_enqueue_script()
{
	wp_register_style('dumanlike-public',  plugin_dir_url( __FILE__ ) . '/css/dumanlike-public.css');
	wp_enqueue_style('dumanlike-public');
	wp_register_script('dumanlike-js', plugin_dir_url( __FILE__ ) . '/js/dumanlike.js', array('jquery'), '0.5', false);
	wp_enqueue_script('dumanlike-js');
	wp_localize_script('dumanlike-js', 'dumanlikeloc', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'like' => 'Like',
		'unlike' => 'Unlike'
	)); 
}

/**
Hooks a function on to a specific action.

Learnt something from https://wordpress.stackexchange.com/questions/121072/using-wp-ajax-and-wp-ajax-nopriv-hooks
Leaving it here, may help later.
*/
add_action('wp_ajax_nopriv_process_dumanlike', 'process_dumanlike');
add_action('wp_ajax_process_dumanlike', 'process_dumanlike');
function process_dumanlike()
{
	// Nonce is used for security purposes to protect against unexpected or duplicate requests that could cause undesired permanent or irreversible changes to the web site and particularly to its database.
	$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : 0;
	if (!wp_verify_nonce($nonce, 'dumanlike-nonce'))
	{
		exit(__('Not permitted', $TextDomain));
	}
	/*
	Testing if the javascript is not enabled.
	wp_redirect(get_permalink(get_the_ID())); is applied otherwise.
	*/
	$disabled = (isset($_REQUEST['disabled']) && $_REQUEST['disabled'] == true) ? true : false;
	/*
	Trying to find out if it's a comment or not.
	*/
	$is_comment = (isset($_REQUEST['is_comment']) && $_REQUEST['is_comment'] == 1) ? 1 : 0;

	/*
	Base variables.
	If post_id is set and numberic sets post_id with $_REQUEST['post_id'].
	Else sets it empty.
	*/
	$post_id = (isset($_REQUEST['post_id']) && is_numeric($_REQUEST['post_id'])) ? $_REQUEST['post_id'] : '';
	$result = array();
	$post_users = NULL;
	$like_count = 0;
	/*
	If post_id is set empty above
	following process is done.
	*/
	if ($post_id != '')
	{
		/*
		Since likes are getting saved inside comment meta / post meta we also do retrieve them from there.
		If it's a comment get_comment_meta if it's not get_post_meta will do the job.
		*/
		$count = ($is_comment == 1) ? get_comment_meta($post_id, "_comment_like_count", true) : get_post_meta($post_id, "_post_like_count", true);
		$count = (isset($count) && is_numeric($count)) ? $count : 0;
		if ($is_refresh)
		{
			$response['count'] = get_like_count($count);
			$response['icon'] = get_unliked_icon();
		}
		if (!post_is_liked_earlier($post_id, $is_comment))
		{ // If button not clicked earlier,
			if (is_user_logged_in()) // and is a user which is logged in we save the like with the user_id instead of ip.
			{
				$user_id = get_current_user_id();
				$post_users = post_user_likes($user_id, $post_id, $is_comment);
				if ($is_comment == 1)
				{
					// Updating the user and the comment
					$user_like_count = get_user_option("_comment_like_count", $user_id);
					$user_like_count =  (isset($user_like_count) && is_numeric($user_like_count)) ? $user_like_count : 0;
					update_user_option($user_id, "_comment_like_count", ++$user_like_count);
					if ($post_users)
					{
						update_comment_meta($post_id, "_user_comment_liked", $post_users);
					}
				}
				else
				{
					// Updating the user and the post
					$user_like_count = get_user_option("_user_like_count", $user_id);
					$user_like_count =  (isset($user_like_count) && is_numeric($user_like_count)) ? $user_like_count : 0;
					update_user_option($user_id, "_user_like_count", ++$user_like_count);
					if ($post_users)
					{
						update_post_meta($post_id, "_user_liked", $post_users);
					}
				}
			}
			/*
			User is unknown. Probably a visitor.
			We'll work with their IP address here.
			*/
			else
			{
				$user_ip = dumanlike_get_ip();
				$post_users = visitor_ip_like($user_ip, $post_id, $is_comment);
				// Updating the comment or the post
				if ($post_users)
				{
					if ($is_comment == 1)
					{
						update_comment_meta($post_id, "_user_comment_IP", $post_users);
					}
					else
					{ 
						update_post_meta($post_id, "_user_IP", $post_users);
					}
				}
			}
			$like_count = ++$count;
			$response['status'] = "liked";
			$response['icon'] = draw_full_heart();
		}
		else
		{ // If button is clicked earlier,
			if (is_user_logged_in())
			{ // and is a user which is logged in we save the like with the user_id instead of ip.
				$user_id = get_current_user_id();
				$post_users = post_user_likes($user_id, $post_id, $is_comment);
				// Updating the user
				if ($is_comment == 1)
				{
					$user_like_count = get_user_option("_comment_like_count", $user_id);
					$user_like_count =  (isset($user_like_count) && is_numeric($user_like_count)) ? $user_like_count : 0;
					if ($user_like_count > 0)
					{
						update_user_option($user_id, "_comment_like_count", --$user_like_count);
					}
				}
				else
				{
					$user_like_count = get_user_option("_user_like_count", $user_id);
					$user_like_count =  (isset($user_like_count) && is_numeric($user_like_count)) ? $user_like_count : 0;
					if ($user_like_count > 0)
					{
						update_user_option($user_id, '_user_like_count', --$user_like_count);
					}
				}
				// Updating the post
				if ($post_users)
				{	
					$uid_key = array_search($user_id, $post_users);
					unset($post_users[$uid_key]);
					if ($is_comment == 1)
					{
						update_comment_meta($post_id, "_user_comment_liked", $post_users);
					}
					else
					{ 
						update_post_meta($post_id, "_user_liked", $post_users);
					}
				}
			}
			/*
			User is unknown. Probably a visitor.
			We'll work with their IP address here.
			*/
			else
			{
				$user_ip = dumanlike_get_ip();
				$post_users = visitor_ip_like($user_ip, $post_id, $is_comment);
				// Updating the post
				if ($post_users)
				{
					$uip_key = array_search($user_ip, $post_users);
					unset($post_users[$uip_key]);
					if ($is_comment == 1)
					{
						update_comment_meta($post_id, "_user_comment_IP", $post_users);
					}
					else
					{ 
						update_post_meta($post_id, "_user_IP", $post_users);
					}
				}
			}
			$like_count = ($count > 0) ? --$count : 0; // preventing negative like count
			$response['status'] = "unliked";
			$response['icon'] = draw_empty_heart();
		}
		if ($is_comment == 1)
		{
			update_comment_meta($post_id, "_comment_like_count", $like_count);
			update_comment_meta($post_id, "_comment_like_modified", date('Y-m-d H:i:s'));
		}
		else
		{ 
			update_post_meta($post_id, "_post_like_count", $like_count);
			update_post_meta($post_id, "_post_like_modified", date('Y-m-d H:i:s'));
		}
		$response['count'] = get_like_count($like_count);
		$response['testing'] = $is_comment;
		if ($disabled == true)
		{
			if ($is_comment == 1)
			{
				wp_redirect(get_permalink(get_the_ID()));
				exit();
			}
			else
			{
				wp_redirect(get_permalink($post_id));
				exit();
			}
		}
		else
		{
			wp_send_json($response);
		}
	}
}

/**
To automatically add like button to all posts via the shortcode.
*/
add_action('the_content', 'add_to_content');
function add_to_content($content)
{
    return $content .= '<p style="position: inherit; bottom: 0; right: 0;">[dlike]</p>';
}

/**
function for the previous if (!post_is_liked_earlier($post_id, $is_comment)) part.
Whenever this function gets called it checks for comment and post metas
for either an IP address or a user_id based on the situation.
*/
function post_is_liked_earlier($post_id, $is_comment)
{
	$post_users = NULL;
	$user_id = NULL;
	if (is_user_logged_in()) // User is a member and is logged in
	{
		$user_id = get_current_user_id();
		$post_meta_users = ($is_comment == 1) ? get_comment_meta($post_id, "_user_comment_liked") : get_post_meta($post_id, "_user_liked");
		if (count($post_meta_users) != 0)
		{
			$post_users = $post_meta_users[0];
		}
	}
	else // User is a visitor or not logged in
	{
		$user_id = dumanlike_get_ip();
		$post_meta_users = ($is_comment == 1) ? get_comment_meta($post_id, "_user_comment_IP") : get_post_meta($post_id, "_user_IP"); 
		if (count($post_meta_users) != 0) // If meta does exists, sets the value
		{
			$post_users = $post_meta_users[0];
		}
	}
	if (is_array($post_users) && in_array($user_id, $post_users))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
defaults is_comment to null.
You need to provide at least a post_id.

This will bring you likes and will output it.
See $output in the bottom.
*/
function get_dumanlikes_button($post_id, $is_comment = NULL)
{
	$is_comment = (NULL == $is_comment) ? 0 : 1; // checks if button is for a comment or not
	$output = ''; // initialized, built up later on.
	$nonce = wp_create_nonce('dumanlike-nonce'); // protection from unexpected or duplicate requests
	if ($is_comment == 1) // if it's a comment
	{
		$post_id_class = esc_attr(' dumanlike-comment-button-' . $post_id); // appending post id to comment button
		$comment_class = esc_attr(' dumanlike-comment');
		$like_count = get_comment_meta($post_id, "_comment_like_count", true);
		$like_count = (isset($like_count) && is_numeric($like_count)) ? $like_count : 0;
	}
	else // if it's a post
	{
		$post_id_class = esc_attr(' dumanlike-button-' . $post_id); // appends post id to button
		$comment_class = esc_attr(''); // no need to escape comment_class because it's a post
		$like_count = get_post_meta($post_id, "_post_like_count", true);
		$like_count = (isset($like_count) && is_numeric($like_count)) ? $like_count : 0;
	}
	$count = get_like_count($like_count);
	$icon_empty = draw_empty_heart();
	$icon_full = draw_full_heart();
	$loader = '<span id="dumanlike-loader"></span>'; // var loader = allbuttons.next('#dumanlike-loader');
	/*
	Set status of the button.
	*/
	if (post_is_liked_earlier($post_id, $is_comment))
	{
		$class = esc_attr(' liked');
		$title = __('Unlike', $TextDomain);
		$icon = $icon_full;
	}
	else
	{
		$class = '';
		$title = __('Like', $TextDomain);
		$icon = $icon_empty;
	}
	$output = '<span class="dumanlike-wrapper"><a style="outline: none;" href="' . admin_url('admin-ajax.php?action=process_dumanlike' . '&post_id=' . $post_id . '&nonce=' . $nonce . '&is_comment=' . $is_comment . '&disabled=true') . '" class="dumanlike-button' . $post_id_class . $class . $comment_class . '" data-nonce="' . $nonce . '" data-post-id="' . $post_id . '" data-iscomment="' . $is_comment . '" title="' . $title . '">' . $icon . $count . '</a>' . $loader . '</span>';
	return $output;
}

/**
You can manually add a like button. Short codes are awesome!
Wherever you type [dlike] a button will appear.
*/
add_shortcode('dlike', 'dumanlike_shortcode');
function dumanlike_shortcode()
{
	return get_dumanlikes_button(get_the_ID(), 0); // Retrieve the ID of the current item in the WordPress Loop.
}

/**
Retrieves post meta field for a post
then adds current user id to the retrieved array.
*/
function post_user_likes($user_id, $post_id, $is_comment)
{
	$post_users = '';
	$post_meta_users = ($is_comment == 1) ? get_comment_meta($post_id, "_user_comment_liked") : get_post_meta($post_id, "_user_liked");
	if (count($post_meta_users) != 0)
	{
		$post_users = $post_meta_users[0];
	}
	if (!is_array($post_users))
	{
		$post_users = array();
	}
	if (!in_array($user_id, $post_users))
	{
		$post_users['user-' . $user_id] = $user_id;
	}
	return $post_users;
}

/**
If you don't know who the user is it's still okay,
you can retrieve their IP adderss and store it just like
the user method above.

So that even if a user likes a post we will know who it is,
plus they'll not be able to abuse like button.

Of course they can change their IP but I couldn't
find a better solution than this.
*/
function visitor_ip_like($user_ip, $post_id, $is_comment)
{
	$post_users = '';
	$post_meta_users = ($is_comment == 1) ? get_comment_meta($post_id, "_user_comment_IP") : get_post_meta($post_id, "_user_IP");

	if (count($post_meta_users) != 0)
	{
		$post_users = $post_meta_users[0];
	}
	if (!is_array($post_users))
	{
		$post_users = array();
	}
	if (!in_array($user_ip, $post_users))
	{
		$post_users['ip-' . $user_ip] = $user_ip;
	}
	return $post_users;
}

/**
I've applied same technique I did in ip.dumanstudios.com also added some extras from
https://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
This way I've stored the likes IP based, thus client can't like something multiple times.
*/
function dumanlike_get_ip()
{
	if (isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP']))
	{
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
	$ip = filter_var($ip, FILTER_VALIDATE_IP);
	$ip = ($ip === false) ? '0.0.0.0' : $ip;
	return $ip;
}

/**
&#9829; is the black heart emoji. Drew it with SVG so that
it'll look pretty no matter which screen you're looking it from.
*/
function draw_full_heart()
{
	/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart"></i> */
	$icon = '<span class="dumanlike-icon"><svg role="img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0" y="0" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path id="heart-full" d="M124 20.4C111.5-7 73.7-4.8 64 19 54.3-4.9 16.5-7 4 20.4c-14.7 32.3 19.4 63 60 107.1C104.6 83.4 138.7 52.7 124 20.4z"/>&#9829;</svg></span>';
	return $icon;
}

/**
Same with the like button above but inside of it is empty.
So that users can easily distinguish what's up.
*/
function draw_empty_heart()
{
	/* If already using Font Awesome with your theme, replace svg with: <i class="fa fa-heart-o"></i> */
	$icon = '<span class="dumanlike-icon"><svg role="img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0" y="0" viewBox="0 0 128 128" enable-background="new 0 0 128 128" xml:space="preserve"><path id="heart" d="M64 127.5C17.1 79.9 3.9 62.3 1 44.4c-3.5-22 12.2-43.9 36.7-43.9 10.5 0 20 4.2 26.4 11.2 6.3-7 15.9-11.2 26.4-11.2 24.3 0 40.2 21.8 36.7 43.9C124.2 62 111.9 78.9 64 127.5zM37.6 13.4c-9.9 0-18.2 5.2-22.3 13.8C5 49.5 28.4 72 64 109.2c35.7-37.3 59-59.8 48.6-82 -4.1-8.7-12.4-13.8-22.3-13.8 -15.9 0-22.7 13-26.4 19.2C60.6 26.8 54.4 13.4 37.6 13.4z"/>&#9829;</svg></span>';
	return $icon;
}

// WIDGET START

add_action( 'widgets_init', function()
{
	register_widget( 'dlike_widget' );
});

class dlike_widget extends WP_Widget {
	// class constructor
	public function __construct()
	{
		$widget_ops = array(
			'classname' => 'dlike_widget',
			'description' => 'Top 10 list for the dlike widget',
		);
		parent::__construct( 'dlike_widget', 'DLIKE', $widget_ops);
	}
	
	// output the widget content on the front-end
	public function widget($args, $instance)
	{
		most_popular_year($instance['title']);
	}

	// output the option form field in admin Widgets screen
	public function form($instance)
	{
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Title', 'text_domain' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
		<?php esc_attr_e( 'Title:', 'text_domain' ); ?>
		</label> 
		
		<input 
			class="widefat" 
			id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
			name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
			type="text" 
			value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	// save options
	public function update($new_instance, $old_instance)
	{
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
			
		$selected_posts = ( ! empty ( $new_instance['selected_posts'] ) ) ? (array) $new_instance['selected_posts'] : array();
		$instance['selected_posts'] = array_map( 'sanitize_text_field', $selected_posts );

		return $instance;
	}
}

function most_popular_year($title)
{
	global $post;
	$year = date('Y');
	$args = array(
		'year' => $year,
		'post_type' => array( 'post', 'posts' ),
		'meta_key' => '_post_like_count',
		'orderby' => '_post_like_count',
		'order' => 'DESC',
		'posts_per_page' => 10
	);

	$pop_posts = new WP_Query($args);
	$counter = 1;

	if ($pop_posts->have_posts())
	{
		echo "<section id=\"dlike-1\" class=\"widget widget_recent_entries\">\n";
		echo "<h2 class=\"widget-title\">$title</h2>\n";
		echo "<ul id=\"ordered\">\n";
		while ($pop_posts->have_posts())
		{
			$pop_posts->the_post();
			$value = get_post_meta($post->ID,'_post_like_count',true);
			echo "<li><a href='" . get_permalink($post->ID) . "'><b>" . $counter . '.</b> ' . get_the_title() . "</a> ( " . dumanlike_format_count($value) . " &#9829; )</li>\n";
			$counter++;
		}
		echo "</ul>\n";
		echo "</section>\n";
	}
	wp_reset_postdata();
}

// WIDGET END

/**
Reads the number of times post got liked.
If the post is liked between a thousand and a million
you don't display the total likes in raw format.

It's easier to read if it's simplified.

Let's say that the post has received 998635 likes.
It gets replaced with 998K likes.

Million is realistic up until a point but I don't expect
a button to be clicked up to a billion.
However it's also implemented.
*/
function dumanlike_format_count($post_like_amount)
{
	$precision = 2;

	if ($post_like_amount >= 1000 && $post_like_amount < 1000000)
	{
		$formatted = number_format($post_like_amount/1000, $precision).'K';
	}
	else if ($post_like_amount >= 1000000 && $post_like_amount < 1000000000)
	{
		$formatted = number_format($post_like_amount/1000000, $precision).'M';
	}
	else if ($post_like_amount >= 1000000000)
	{
		$formatted = number_format($post_like_amount/1000000000, $precision).'B';
	}
	else
	{
		$formatted = $post_like_amount; // returns raw like amount (ex: 934)
	}
	$formatted = str_replace('.00', '', $formatted); // cleanup
	return $formatted;
}

/**
Checks the format like type and the options for it.
*/
function get_like_count($like_count)
{
	$like_text = __('Like', $TextDomain);
	if (is_numeric($like_count) && $like_count > 0)
	{ 
		$number = dumanlike_format_count($like_count);
	}
	else
	{
		$number = $like_text;
	}
	$count = '<span class="dumanlike-count">' . $number . '</span>'; // sets text color to gray, some styling
	return $count;
}

/**
Displaying likes of a user.
*/
add_action('show_user_profile', 'show_user_likes');
add_action('edit_user_profile', 'show_user_likes');
function show_user_likes($user)
{
	?>        
	<table class="form-table">
		<tr>
			<th><label for="user_likes"><?php _e('You\'ve liked:', $TextDomain); ?></label></th>
			<td>
			<?php
			$types = get_post_types(array('public' => true));
			$args = array(
			  'numberposts' => -1,
			  'post_type' => $types,
			  'meta_query' => array (
				array (
				  'key' => '_user_liked',
				  'value' => $user->ID,
				  'compare' => 'LIKE'
				)
			 ));		
			$sep = '';
			$like_query = new WP_Query($args);
			if ($like_query->have_posts()) : ?>
			<p>
			<?php while ($like_query->have_posts()) : $like_query->the_post(); 
			echo $sep; ?><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
			<?php
			$sep = ' &middot; ';
			endwhile; 
			?>
			</p>
			<?php else : ?>
			<p><?php _e('You didn\'t like anything yet.', $TextDomain); ?></p>
			<?php 
			endif; 
			wp_reset_postdata(); 
			?>
			</td>
		</tr>
	</table>
<?php
}
