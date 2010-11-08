<?php
/*
Plugin Name: TP - TweetPress
Description: All the tools you need to integrate your wordpress and twitter.
Author: Louy
Version: 1.2
Author URI: http://l0uy.com
Text Domain: tp
Domain Path: /po
*/
/*
if you want to force the plugin to use a consumer key and secret,
add your keys and copy the following 2 lines to your wp-config.php
*/
//define('TWITTER_CONSUMER_KEY', 'EnterYourKeyHere');
//define('TWITTER_CONSUMER_SECRET', 'EnterYourSecretHere');

// Load translations
load_plugin_textdomain( 'tp', false, dirname( plugin_basename( __FILE__ ) ) . '/po/' );

define('TP_VERSION', '1.2');

require_once dirname(__FILE__).'/wp-oauth.php';

/**
 * TweetPress Core:
 */
add_action('init','tp_init');
function tp_init() {

	if (session_id() == '') {
		session_start();
	}
	
	isset($_SESSION['tw-connected']) or 
		$_SESSION['tw-connected'] = false;
	
	if(isset($_GET['oauth_token'])) {
		tp_oauth_confirm();
	}
}

function tp_app_options_defined() {
    return defined('TWITTER_CONSUMER_KEY') && defined('TWITTER_CONSUMER_SECRET');
}

function tp_options($k=false) {
	$options = get_option('tp_options');
        $options = array_merge($options, tp_app_options());
	if( $k ) {
		$options = $options[$k];
	}
	return $options;
}

function tp_app_options() {
    $options = get_site_option('twitter_app_details');
    
    if( tp_app_options_defined() ) {
        $options['consumer_key']    = TWITTER_CONSUMER_KEY   ;
        $options['consumer_secret'] = TWITTER_CONSUMER_SECRET;
    }

    return $options;
}

// require PHP 5
function tp_activate(){
	oauth_activate();
	
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die("Sorry, TweetPress requires PHP 5 or higher. Ask your host how to enable PHP 5 as the default on your servers.");
	}
        
}
register_activation_hook(__FILE__, 'tp_activate');

// register twitter in wp-oauth
function add_twitter_to_oauth_sites($sites){
	$sites[] = 'twitter';
	return $sites;
}
add_filter('oauth_sites', 'add_twitter_to_oauth_sites');

// action links
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'tp_links', 10, 1);
function tp_links($links) {
	$links[] = '<a href="'.admin_url('options-general.php?page=tp').'">'.__('Settings', 'tp').'</a>';
        if( tp_app_options_defined() )
            $links[] = '<a href="'.admin_url('options-general.php?page=tp-app').'">'.__('App Settings', 'tp').'</a>';
	return $links;
}

// add the admin options page
add_action('admin_menu', 'tp_admin_add_page');
function tp_admin_add_page() {
	add_options_page(__('TweetPress', 'tp'), __('TweetPress', 'tp'), 'manage_options', 'tp', 'tp_options_page');
        if( tp_app_options_defined() &&
                ( is_multisite() ? is_super_admin() : true ) )
            add_options_page(__('TweetPress App', 'tp'), __('TweetPress App', 'tp'), 'manage_options', 'tp-app', 'tp_app_options_page');
}

// add the admin settings and such
add_action('admin_init', 'tp_admin_init',9);
function tp_admin_init(){

    add_option('tp_options', array(
        'allow_comments' => false,
        'comm_text' => '',
        'tweetbutton_source' => 'l0uy',
        'tweetbutton_position' => 'manual',
        'tweetbutton_style' => 'vertical',
        'tweetbutton_css' => '',
        'tweetbutton_singleonly' => true,
        'autotweet_flag' => 0,
        'publish_text' => __('%title% %url%[ifauthor] by %author%[/ifauthor]', 'tp'),
        'autotweet_name' => '',
        'autotweet_token' => '',
        'autotweet_secret' => '',
    ));

    add_site_option('twitter_app_details', array(
        'consumer_key' => '',
        'consumer_secret' => '',
    ));

    $options = tp_options();
    
    if (empty($options['consumer_key']) || empty($options['consumer_secret'])) {
            add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".sprintf(__('TweetPress needs to be configured on its <a href="%s">settings</a> page.', 'tp'), admin_url('options-general.php?page=tp'))."</p></div>';" ) );
    }
    wp_enqueue_script('jquery');
    register_setting( 'tp_options', 'tp_options', 'tp_options_validate' );
    
    if ( !tp_app_options_defined() &&
            (is_multisite() ? is_super_admin() : true) ) {
        register_setting( 'tp_app_options', 'tp_app_options', 'tp_app_options_validate' );
        add_settings_section('tp-app', __('TweetPress App Settings', 'tp'), 'tp_section_text', 'tp-app');
        add_settings_field('tp_consumer_key', __('Twitter Consumer Key', 'tp'), 'tp_setting_consumer_key', 'tp-app', 'tp-app');
        add_settings_field('tp_consumer_secret', __('Twitter Consumer Secret', 'tp'), 'tp_setting_consumer_secret', 'tp-app', 'tp-app');
    }
}

// display the admin options page
function tp_options_page() {
?>
	<div class="wrap">
	<h2><?php _e('TweetPress', 'tp'); ?></h2>
	<p><?php _e('Options related to the TweetPress plugin.', 'tp'); ?></p>
	<form method="post" action="options.php">
	<?php settings_fields('tp_options'); ?>
	<table><tr><td>
	<?php do_settings_sections('tp'); ?>
	</td></tr></table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'tp') ?>" />
	</p>
	</form>

	</div>

<?php
}
function tp_app_options_page() {
?>
	<div class="wrap">
	<h2><?php _e('TweetPress', 'tp'); ?></h2>
	<p><?php _e('Twitter App Options.', 'tp'); ?></p>
	<form method="post" action="options.php">
	<?php settings_fields('tp_app_options'); ?>
	<table><tr><td>
	<?php do_settings_sections('tp-app'); ?>
	</td></tr></table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'tp') ?>" />
	</p>
	</form>

	</div>

<?php
}

// start wp-oauth
function tp_oauth_start() {
	$options = tp_options();
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) return false;
	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret']);
	$tok = $to->getRequestToken();

	$token = $tok['oauth_token'];
	$_SESSION['tp_req_token'] = $token;
	$_SESSION['tp_req_secret'] = $tok['oauth_token_secret'];

	$_SESSION['tp_callback'] = $_GET['loc'];
	$_SESSION['tp_callback_action'] = $_GET['tpaction'];
	
	if ($_GET['type'] == 'authorize') $url=$to->getAuthorizeURL($token);
	else $url=$to->getAuthenticateURL($token);

	wp_redirect($url);
	exit;
}
add_action('oauth_start_twitter', 'tp_oauth_start');

function tp_oauth_confirm() {
	$options = tp_options();
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) return false;
	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $_SESSION['tp_req_token'], $_SESSION['tp_req_secret']);

	$tok = $to->getAccessToken();

	$_SESSION['tp_acc_token'] = $tok['oauth_token'];
	$_SESSION['tp_acc_secret'] = $tok['oauth_token_secret'];

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $tok['oauth_token'], $tok['oauth_token_secret']);
	
	$_SESSION['tw-connected'] = true;
	
	// this lets us do things actions on the return from twitter and such
	if ($_SESSION['tp_callback_action']) {
		do_action('tp_'.$_SESSION['tp_callback_action']);
		$_SESSION['tp_callback_action'] = ''; // clear the action
	}
	
	wp_redirect(remove_query_arg('reauth', $_SESSION['tp_callback']));
	exit;
}

// get the user credentials from twitter
function tp_get_credentials($force_check = false) {
	
	if(!$force_check && !$_SESSION['tw-connected']) return false;
	
	// cache the results in the session so we don't do this over and over
	if (!$force_check && $_SESSION['tp_credentials']) return $_SESSION['tp_credentials']; 
	
	$_SESSION['tp_credentials'] = tp_do_request('http://twitter.com/account/verify_credentials');
	
	return $_SESSION['tp_credentials'];
}

// json is assumed for this, so don't add .xml or .json to the request URL
function tp_do_request($url, $args = array(), $type = NULL) {
	
	if (isset($args['acc_token'])) {
		$acc_token = $args['acc_token'];
		unset($args['acc_token']);
	} else {
		$acc_token = isset($_SESSION['tp_acc_token']) ? $_SESSION['tp_acc_token'] : false;
	}
	
	if (isset($args['acc_secret'])) {
		$acc_secret = $args['acc_secret'];
		unset($args['acc_secret']);
	} else {
		$acc_secret = isset($_SESSION['tp_acc_secret']) ? $_SESSION['tp_acc_secret'] : false;
	}
	
	$options = tp_options();
	if (empty($options['consumer_key']) || empty($options['consumer_secret']) ||
		empty($acc_token) || empty($acc_secret) ) return false;

	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $acc_token, $acc_secret);
	$json = $to->OAuthRequest($url.'.json', $args, $type);

	return json_decode($json);
}

function tp_section_text() {
	$options = tp_options();
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) {
?>
<p><?php _e('To connect your site to Twitter, you will need a Twitter Application. If you have already created one, please insert your Consumer Key and Consumer Secret below.', 'tp'); ?></p>
<p><strong><?php _e('Can&#39;t find your key?', 'tp'); ?></strong></p>
<ol>
<li><?php _e('Get a list of your applications from here: <a target="_blank" href="http://dev.twitter.com/apps">Twitter Application List</a>', 'tp'); ?></li>
<li><?php _e('Select the application you want, then copy and paste the Consumer Key and Consumer Secret from there.', 'tp'); ?></li>
</ol>

<p><?php _e('<strong>Haven&#39;t created an application yet?</strong> Don&#39;t worry, it&#39;s easy!', 'tp'); ?></p>
<ol>
<li><?php _e('Go to this link to create your application: <a target="_blank" href="http://dev.twitter.com/apps/new">Twitter: Register an Application</a>', 'tp'); ?></li>
<li><?php _e('Important Settings:', 'tp'); ?><ol>
<li><?php _e('Application Type must be set to "Browser".', 'tp'); ?></li>
<li><?php printf(__('Callback URL must be set to "%s".', 'tp'), get_bloginfo('url')); ?></li>
<li><?php _e('Default Access type must be set to "Read and Write".', 'tp'); ?></li>
</ol>
</li>
<li><?php _e('The other application fields can be set up any way you like.', 'tp'); ?></li>
<li><?php _e('After creating the application, copy and paste the Consumer Key and Consumer Secret from the Application Details page.', 'tp'); ?></li>
</ol>
<?php
	}
}

function tp_get_connect_button($action='', $type='authenticate', $image ='Sign-in-with-Twitter-darker') {
	$image = apply_filters('tp_connect_button_image', $image, $action, $type);
	$imgsrc = apply_filters('tp_connect_button_image_src', plugins_url('/images/'.$image.'.png', __FILE__), $image, $action, $type);
	return apply_filters('tp_get_connect_button', 
		'<a href="' . oauth_link('twitter', array(
				'tpaction' => $action,
				'loc' => tp_get_current_url(), 
				'type' => $type) ) . '" title="'.__('Sign in with Twitter', 'tp').'">'.
			'<img src="'.$imgsrc.'" alt="'.__('Sign in with Twitter', 'tp').'" style="border:none;" />'.
		'</a>', $action, $type, $image);
}

function tp_get_current_url() {
	// build the URL in the address bar
	$requested_url  = ( !empty($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
	$requested_url .= $_SERVER['HTTP_HOST'];
	$requested_url .= $_SERVER['REQUEST_URI'];
	return $requested_url;
}

function tp_setting_consumer_key() {
	if (defined('TWITTER_CONSUMER_KEY')) return;
	$options = tp_options();
	echo "<input type='text' id='tpconsumerkey' name='tp_options[consumer_key]' value='{$options['consumer_key']}' size='40' /> " . __('(required)', 'tp');	
}

function tp_setting_consumer_secret() {
	if (defined('TWITTER_CONSUMER_SECRET')) return;
	$options = tp_options();
	echo "<input type='text' id='tpconsumersecret' name='tp_options[consumer_secret]' value='{$options['consumer_secret']}' size='40' /> " . __('(required)', 'tp');
}

// validate our options
function tp_options_validate($input) {
        unset($input['consumer_key'], $input['consumer_secret']);
	$input = apply_filters('tp_validate_options',$input);
	return $input;
}
function tp_app_options_validate($input) {
	if (!defined('TWITTER_CONSUMER_KEY') && !defined('TWITTER_CONSUMER_SECRET')) {
            if( isset($input['consumer_key']) && isset($input['consumer_secret']) &&
                    (is_multisite() ? is_super_admin() : 1) ) {

		$input['consumer_key'] = trim($input['consumer_key']);
		if(! preg_match('/^[A-Za-z0-9]+$/i', $input['consumer_key'])) {
		  $input['consumer_key'] = '';
		}

		$input['consumer_secret'] = trim($input['consumer_secret']);
		if(! preg_match('/^[A-Za-z0-9]+$/i', $input['consumer_secret'])) {
		  $input['consumer_secret'] = '';
		}

                $app_options = array(
                    'consumer_key'    => $input['consumer_key'],
                    'consumer_secret' => $input['consumer_secret'],
                );

                update_site_option('twitter_app_details', $app_options);

            }
	}

	$input = apply_filters('tp_validate_app_options',$input);
	return $input;
}


// load the @anywhere script 
add_action('wp_enqueue_scripts','anywhereloader');
add_action('admin_enqueue_scripts','anywhereloader');
function anywhereloader() {
	$options = tp_options();
	
	if (!empty($options['consumer_key'])) {		
		wp_enqueue_script( 'twitter-anywhere', "http://platform.twitter.com/anywhere.js?id={$options['consumer_key']}&v=1", array(), '1', false);
	}
}

/**
 * TweetPress Comments:
 */
add_action('admin_init','tp_comm_error_check');
function tp_comm_error_check() {
	if ( get_option( 'comment_registration' ) && tp_options('allow_comments') ) {
		add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".__('TweetPress Comment function doesn\'t work with sites that require registration to comment.', 'tp')."</p></div>';" ) );
	}
}

add_action('admin_init', 'tp_comm_admin_init');
function tp_comm_admin_init() {
	add_settings_section('tp_comm', __('Comment Settings', 'tp'), 'tp_comm_section_callback', 'tp');
	add_settings_field('tp_allow_comments', __('Allow Twitter users to comment?', 'tp'), 'tp_setting_allow_comments', 'tp', 'tp_comm');
	add_settings_field('tp_comm_text', __('Comment Tweet Text', 'tp'), 'tp_comm_text', 'tp', 'tp_comm');
}

function tp_comm_section_callback() {
	echo '<p>'.__('Allow twitter users to comment and set the tweet style, use % for shortlink.', 'tp').'</p>';
	if (!function_exists('get_shortlink') && !function_exists('wp_get_shortlink')) {
		echo '<p>'.__('Warning: No URL Shortener plugin detected. Links used will be full permalinks.', 'tp').'</p>';
	}
}

function tp_setting_allow_comments() {
	$options = tp_options();
	echo "<input type='checkbox' id='tpallowcomment' name='tp_options[allow_comment]' value='yes' ".checked($options['allow_comment'],true,false)." />";	
}

function tp_comm_text() {
	$options = tp_options();
	echo "<input type='text' name='tp_options[comment_text]' value='{$options['comment_text']}' size='40' />";	
}

add_action('tp_validate_options', 'tp_comm_validate_options');
function tp_comm_validate_options($input) {
	if( isset($input['allow_comment']) && $input['allow_comment'] == 'yes' ) {
		$input['allow_comment'] = true;
	} else {
		$input['allow_comment'] = false;
	}
	$input['comment_text'] = trim($input['comment_text']);
	return $input;
}

// set a variable to know when we are showing comments (no point in adding js to other pages)
function tp_comm_comments_enable() {
	global $tp_comm_comments_form;
	$tp_comm_comments_form = true;
}

// add placeholder for sending comment to twitter checkbox
function tp_comm_send_place() {
?><p id="tp_comm_send"></p><?php
}

// hook to the footer to add our scripting
function tp_comm_footer_script() {
	global $tp_comm_comments_form;
	if ($tp_comm_comments_form != true) return; // nothing to do, not showing comments

	if ( is_user_logged_in() ) return; // don't bother with this stuff for logged in users
	
	?>
<script type="text/javascript">
	jQuery(function() {
		var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
		var data = { action: 'tp_comm_get_display' }
		jQuery.post(ajax_url, data, function(response) {
			if (response != '0' && response != 0) {
				jQuery('#alt-comment-login').hide();
				jQuery('#comment-user-details').hide().after(response);
				
				<?php 
				$options = tp_options();
				if (!empty($options['comment_text'])) {  // dont do this if disabled 
				?>
				jQuery('#tp_comm_send').html('<input style="width: auto;" type="checkbox" name="tp_comm_send" value="send"/><label for="tp_comm_send"><?php _e('Send Comment to Twitter', 'tp'); ?></label>');
				
				<?php } ?>
			}
		});
	});
</script>
	<?php
}

function tp_comm_get_display() {
	$tw = tp_get_credentials();
	if ($tw) {
		echo '<div id="tw-user">'.
			 '<img src="http://api.twitter.com/1/users/profile_image/'.$tw->screen_name.'?size=bigger" width="96" height="96" id="tw-avatar" class="avatar" />'.
			 '<h3 id="tw-msg">Hi '.$tw->name.'!</h3>'.
			 '<p>'.__('You are connected with your Twitter account.', 'tp').'</p>'.
			 apply_filters('tp_user_logout','<a href="?twitter-logout=1" id="tw-logout">'.__('Logout', 'tp').'</a>').
			 '</div>';
		exit;
	}
	
	echo 0;
	exit;
}

// check for logout request
function tp_comm_logout() {
	if ($_GET['twitter-logout']) { 
		session_unset();
		$page = tp_get_current_url();
		if (strpos($page, "?") !== false) $page = reset(explode("?", $page));
		wp_redirect($page);
		exit; 
	}
}


function tp_comm_send_to_twitter() {
	$options = tp_options();
	
	if (!$options['comment_text']) return;

	$postid = (int) $_POST['comment_post_ID'];
	if (!$postid) return;
	
	// send the comment to twitter
	if (isset($_POST['tp_comm_send']) && $_POST['tp_comm_send'] == 'send') {
	
		// args to send to twitter
		$args=array();
	
		if (function_exists('wp_get_shortlink')) {
			// use the shortlink if it's available
			$link = wp_get_shortlink($postid);
		} else if (function_exists('get_shortlink')) {
			// use the shortlink if it's available
			$link = get_shortlink($postid);
		} else {
			// use the full permalink (twitter will shorten for you)
			$link = get_permalink($postid);
		}
		
		$args['status'] = str_replace('%',$link, $options['comment_text']);
		
		$resp = tp_do_request('http://api.twitter.com/1/statuses/update',$args);
	}
}

function tp_comm_login_button() {
	echo '<p id="tw-connect">'.tp_get_connect_button('comment').'</p>';
}

if( !function_exists('alt_comment_login') ) {
	
	function alt_comment_login() {
		echo '<div id="alt-comment-login">';
		do_action('alt_comment_login');
		echo '</div>';
	}
	
	function comment_user_details_begin() { echo '<div id="comment-user-details">'; }
	
	function comment_user_details_end() { echo '</div>'; }
}

// generate avatar code for Twitter user comments
add_filter('get_avatar','tp_comm_avatar', 10, 5);
function tp_comm_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	// check to be sure this is for a comment
	if ( !is_object($id_or_email) || !isset($id_or_email->comment_ID) || $id_or_email->user_id) 
		 return $avatar;
		 
	// check for twuid comment meta
	$twuid = get_comment_meta($id_or_email->comment_ID, 'twuid', true);
	if ($twuid) {
		// return the avatar code
		$avatar = "<img class='avatar avatar-{$size} twitter-avatar' src='http://api.twitter.com/1/users/profile_image/{$twuid}?size=bigger' width='{$size}' height='{$size}' />";
	}
		
	return $avatar;
}

// store the Twitter screen_name as comment meta data ('twuid')
function tp_comm_add_meta($comment_id) {
	$tw = tp_get_credentials();
	if ($tw) {
		update_comment_meta($comment_id, 'twuid', $tw->screen_name);
	}
}

// Add user fields for FB commenters
function tp_comm_fill_in_fields($comment_post_ID) {
	if (is_user_logged_in()) return; // do nothing to WP users
	
	$tw = tp_get_credentials();
	if ($tw) {	
		$_POST['author'] = $tw->name;
		$_POST['url'] = 'http://twitter.com/'.$tw->screen_name;
		
		// use an @twitter email address. This shows it's a twitter name, and email to it won't work.
		$_POST['email'] = $tw->screen_name.'@fake.twitter.com'; 
	}
}

if( tp_options('allow_comment') ) {
	add_action('comment_form','tp_comm_comments_enable');
	add_action('comment_form','tp_comm_send_place');
	add_action('wp_footer','tp_comm_footer_script',30);
	add_action('wp_ajax_nopriv_tp_comm_get_display', 'tp_comm_get_display');
	add_action('init','tp_comm_logout');
	add_action('comment_post','tp_comm_send_to_twitter');
	add_action('comment_form_before_fields', 'alt_comment_login',1,0);
	add_action('alt_comment_login', 'tp_comm_login_button');
	add_action('comment_form_before_fields', 'comment_user_details_begin',2,0);
	add_action('comment_form_after_fields', 'comment_user_details_end',20,0);
	add_action('comment_post','tp_comm_add_meta', 10, 1);
	add_filter('pre_comment_on_post','tp_comm_fill_in_fields');
}

/**
 * TweetPress Login:
 */
// add the section on the user profile page
add_action('profile_personal_options','tp_login_profile_page');


function tp_login_profile_page($profile) {
	$options = tp_options();
?>
	<table class="form-table">
		<tr>
			<th><label><?php _e('Twitter Connect', 'tp'); ?></label></th>
<?php
	$twuid = get_user_meta($profile->ID, 'twuid', true);
	if (empty($twuid)) { 
		?>
			<td><p><?php echo tp_get_connect_button('login_connect'); ?></p></td>
		</tr>
	</table>
	<?php	
	} else { ?>
		<td><p><?php _e('Connected as ', 'tp'); ?>
		<img src='http://api.twitter.com/1/users/profile_image/<?php echo $twuid; ?>?size=bigger' width='32' height='32' />
		<a href='http://twitter.com/<?php echo $twuid; ?>'><?php echo $twuid; ?></a>
		<input type="button" class="button-primary" value="<?php _e('Disconnect', 'tp'); ?>" onclick="tp_login_disconnect(); return false;" />
		<script type="text/javascript">
		function tp_login_disconnect() {
			var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
			var data = {
				action: 'disconnect_twuid',
				twuid: '<?php echo $twuid; ?>'
			}
			jQuery.post(ajax_url, data, function(response) {
				if (response == '1') {
					location.reload(true);
				}
			});
		}
		</script>
</p></td>
	<?php } ?>
	</tr>
	</table>
	<?php
}

add_action('wp_ajax_disconnect_twuid', 'tp_login_disconnect_twuid');
function tp_login_disconnect_twuid() {
	$user = wp_get_current_user();
	
	$twuid = get_user_meta($user->ID, 'twuid', true);
	if ($twuid == $_POST['twuid']) {
		delete_usermeta($user->ID, 'twuid');
	}
	
	echo 1;
	exit();
}

add_action('tp_login_connect','tp_login_connect');
function tp_login_connect() {
	if (!is_user_logged_in()) return; // this only works for logged in users
	$user = wp_get_current_user();
	
	$tw = tp_get_credentials();
	if ($tw) {
		// we have a user, update the user meta
		update_usermeta($user->ID, 'twuid', $tw->screen_name);
	}
}
	
add_action('login_form','tp_login_add_login_button');
function tp_login_add_login_button() {
	global $action;
	$style = apply_filters('tp_login_button_style', ' style="text-align: center;"');
	if ($action == 'login') echo '<p id="tw-login"'.$style.'>'.tp_get_connect_button('login').'</p><br />';
}

add_filter('authenticate','tp_login_check');
function tp_login_check($user) {
	if ( is_a($user, 'WP_User') ) { return $user; } // check if user is already logged in, skip

	$tw = tp_get_credentials();
	if ($tw) {
		global $wpdb;
		$twuid = $tw->screen_name;
		$user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'twuid' AND meta_value = '%s'", $twuid) );

		if ($user_id) {
			$user = new WP_User($user_id);
		} else {
			do_action('tp_login_new_tw_user',$tw); // hook for creating new users if desired
			global $error;
			$error = __('<strong>Error</strong>: Twitter user not recognized.', 'tp');
		}
	}
	return $user;
}

add_action('wp_logout','tp_login_logout');
function tp_login_logout() {
	session_unset();	
}

/**
 * TweetPress Tweet Button:
 */
global $tweetbutton_defaults, $tweetbutton_is_displayed;
$tweetbutton_defaults = array(
	'id'=>0,
);
$tweetbutton_is_displayed = false;

/**
 * Simple tweet button
 *
 * @param string $source Source that the RT will appear to be from.
 * @param int $post_id An optional post ID.
 */
function get_tweetbutton($args='') {
	global $tweetbutton_defaults, $tweetbutton_is_displayed;
	$tweetbutton_is_displayed = true;
	$args = wp_parse_args($tweetbutton_defaults, $args);
	extract($args);
	
	$options = tp_options();
	if ($options['tweetbutton_source']) $source = $options['tweetbutton_source'];
	if ($options['tweetbutton_style']) $style = $options['tweetbutton_style'];
	//if ($options['tweetbutton_related']) $related = $options['tweetbutton_related'];
	/*
	if( get_the_author_meta('twuid') ) {
		$source = get_the_author_meta('twuid');
	}
	*/
	$related = $source;
	if( get_the_author_meta('twuid') ) {
		$related .= ':'.get_the_author_meta('twuid');
	}
	
	$url = esc_attr(get_permalink($id));
	$post = get_post($id);
	$text = esc_attr(strip_tags($post->post_title));;
	
	if (!empty($related)) $related = " data-related='{$related}'";
	
	$out = "<a href='http://twitter.com/share' class='twitter-share-button' data-text='{$text}' data-url='{$url}' data-count='{$style}' data-via='{$source}'{$related}>Tweet</a>";
	return $out;
}

function tweetbutton($args) {
	echo get_tweetbutton($args);
}

// we need this script in the footer to make the tweet buttons show up
add_action('wp_footer','tweetbutton_footer');
function tweetbutton_footer() {
	global $tweetbutton_is_displayed;
	if(!$tweetbutton_is_displayed) return;
	?><script type='text/javascript' src='http://platform.twitter.com/widgets.js'></script><?php
}

/**
 * Simple tweetbutton button as a shortcode
 *
 * Example use: [tweetbutton source="l0uy"] or [tweetbutton id="123"]
 */
function tweetbutton_shortcode($atts) {
	global $tweetbutton_defaults;
	$args = shortcode_atts($tweetbutton_defaults, $atts);
	return get_tweetbutton_button($args);
}
add_shortcode('tweetbutton', 'tweetbutton_shortcode');

function tweetbutton_automatic($content) {
	$options = tp_options();
	$button = get_tweetbutton();
	if( $options['tweetbutton_singleonly'] && !is_single() ) return $content;
	switch ($options['tweetbutton_position']) {
		case "before":
			$content = $button . $content;
			break;
		case "after":
			$content = $content . $button;
			break;
		case "both":
			$content = $button . $content . $button;
			break;
		case "manual":
		default:
			break;
	}
	return $content;
}
add_filter('the_content', 'tweetbutton_automatic', 30);

// add the admin sections to the tp page
add_action('admin_init', 'tweetbutton_admin_init');
function tweetbutton_admin_init() {
	add_settings_section('tweetbutton', __('Tweet Button Settings', 'tp'), 'tweetbutton_section_callback', 'tp');
	add_settings_field('tweetbutton_source', __('Tweet Source', 'tp'), 'tweetbutton_source', 'tp', 'tweetbutton');
	add_settings_field('tweetbutton_position', __('Tweet Button Position', 'tp'), 'tweetbutton_position', 'tp', 'tweetbutton');
	add_settings_field('tweetbutton_style', __('Tweet Button Style', 'tp'), 'tweetbutton_style', 'tp', 'tweetbutton');
	//add_settings_field('tweetbutton_related', __('Tweet Button related', 'tp'), 'tweetbutton_related', 'tp', 'tweetbutton');
	add_settings_field('tweetbutton_css', __('Tweet Button CSS', 'tp'), 'tweetbutton_css', 'tp', 'tweetbutton');
	add_settings_field('tweetbutton_singleonly', __('Tweet Button Single Pages Only', 'tp'), 'tweetbutton_singleonly', 'tp', 'tweetbutton');
}

function tweetbutton_section_callback() {
	echo '<p>'.__('Choose where you want the Tweetbutton button to add the button in your content.', 'tp').'</p>';
}

function tweetbutton_source() {
	$options = tp_options();
	if (!$options['tweetbutton_source']) $options['tweetbutton_source'] = 'l0uy';
	echo "<input type='text' id='tweetbutton-source' name='tp_options[tweetbutton_source]' value='{$options['tweetbutton_source']}' size='40' /> " . __('(Username that appears to be RT&#39;d)', 'tp');
}

function tweetbutton_position() {
	$options = tp_options();
	if (!$options['tweetbutton_position']) $options['tweetbutton_position'] = 'manual';
	?>
	<p><label><input type="radio" name="tp_options[tweetbutton_position]" value="before" <?php checked('before', $options['tweetbutton_position']); ?> /> <?php _e('Before the content of your post', 'tp'); ?></label></p>
	<p><label><input type="radio" name="tp_options[tweetbutton_position]" value="after" <?php checked('after', $options['tweetbutton_position']); ?> /> <?php _e('After the content of your post', 'tp'); ?></label></p>
	<p><label><input type="radio" name="tp_options[tweetbutton_position]" value="both" <?php checked('both', $options['tweetbutton_position']); ?> /> <?php _e('Before AND After the content of your post', 'tp'); ?></label></p>
	<p><label><input type="radio" name="tp_options[tweetbutton_position]" value="manual" <?php checked('manual', $options['tweetbutton_position']); ?> /> <?php _e('Manually add the button to your theme or posts (use the tweetbutton_button function in your theme, or the [tweetbutton] shortcode in your posts)', 'tp'); ?></label></p>
<?php 
}

function tweetbutton_style() {
	$options = tp_options();
	if (!$options['tweetbutton_style']) $options['tweetbutton_style'] = 'manual';
	?>
	<select name="tp_options[tweetbutton_style]" id="select_tweetbutton_position">
	<option value="none" <?php selected('none', $options['tweetbutton_style']); ?>><?php _e('None', 'tp'); ?></option>
	<option value="horizontal" <?php selected('horizontal', $options['tweetbutton_style']); ?>><?php _e('Horizonal', 'tp'); ?></option>
	<option value="vertical" <?php selected('vertical', $options['tweetbutton_style']); ?>><?php _e('Vertical', 'tp'); ?></option>
	</select>
<?php
}
/*
function tweetbutton_related() {
	$options = tp_options();
	if (!$options['tweetbutton_related']) $options['tweetbutton_related'] = '';
	echo "<input type='text' id='tweetbutton-related' name='tp_options[tweetbutton_related]' value='{$options['tweetbutton_related']}' size='40' /> Users that the person will be suggested to follow. Max 2, separate with colon. Example l0uy:ardroid";

}
*/
function tweetbutton_css() {
	$options = tp_options();
	if (!$options['tweetbutton_css']) $options['tweetbutton_css'] = '';
	echo "<input type='text' id='tweetbutton-css' name='tp_options[tweetbutton_css]' value='{$options['tweetbutton_css']}' size='40' /> " . __('the css style of the tweet button.', 'tp');
}

function tweetbutton_singleonly() {
	$options = tp_options();
	if (!$options['tweetbutton_singleonly']) $options['tweetbutton_singleonly'] = true;
	echo "<input type='checkbox' id='tweetbutton-singleonly' name='tp_options[tweetbutton_singleonly]' value='yes'".checked($options['tweetbutton_singleonly'],true,false)." /> " . __('show tweet button only on single pages?.', 'tp');
}

add_filter('tp_validate_options','tweetbutton_validate_options');
function tweetbutton_validate_options($input) {
	if(!in_array($input['tweetbutton_position'], array('before', 'after', 'both', 'manual'))) {
			$input['tweetbutton_position'] = 'manual';
	}

	if (!in_array($input['tweetbutton_style'], array('none', 'horizontal', 'vertical'))) {
			$input['tweetbutton_style'] = 'none';
	}
	
	if (!$input['tweetbutton_source']) $input['tweetbutton_source'] = 'l0uy';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetbutton_source'] = preg_replace('/[^a-zA-Z0-9_\s]/', '', $input['tweetbutton_source']);
	}
/*
	if (!$input['tweetbutton_related']) $input['tweetbutton_related'] = '';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetbutton_related'] = preg_replace('/[^a-zA-Z0-9_\s:]/', '', $input['tweetbutton_related']);
	}
*/
	if (!$input['tweetbutton_css']) $input['tweetbutton_css'] = '';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetbutton_css'] = esc_attr($input['tweetbutton_css']);
	}

	if (isset($input['tweetbutton_singleonly']) && $input['tweetbutton_singleonly'] == 'yes') $input['tweetbutton_singleonly'] = true;
	else {
		$input['tweetbutton_singleonly'] = false;
	}

	return $input;
}

/**
 * TweetPress Publish:
 */
// add the meta boxes
add_action('admin_menu', 'tp_publish_meta_box_add');
function tp_publish_meta_box_add() {
	add_meta_box('tp-publish-div', __('Twitter Publisher', 'tp'), 'tp_publish_meta_box', 'post', 'side');
}

// add the admin sections to the tp page
add_action('admin_init', 'tp_publish_admin_init');
function tp_publish_admin_init() {
	add_settings_section('tp_publish', __('Publish Settings', 'tp'), 'tp_publish_section_callback', 'tp');
	add_settings_field('tp_publish_flags', __('Automatic Publishing', 'tp'), 'tp_publish_auto_callback', 'tp', 'tp_publish');
	add_settings_field('tp_publish_text', __('Publish Tweet Text', 'tp'), 'tp_publish_text', 'tp', 'tp_publish');
	wp_enqueue_script('jquery');
}

function tp_publish_section_callback() {
	echo '<p>' . __('Settings for the Publish function. The manual Twitter Publishing buttons can be found on the Edit Post screen, after you publish a post. If you can&#39;t find them, try scrolling down or seeing if you have the box disabled in the Options dropdown.', 'tp') . '</p>';
}

function tp_publish_auto_callback() {
	$options = tp_options();
	if (!$options['autotweet_flag']) $options['autotweet_flag'] = false;
	?>
	<p><label><?php _e('Automatically Tweet on Publish:', 'tp'); ?> <input type="checkbox" name="tp_options[autotweet_flag]" value="1" <?php checked('1', $options['autotweet_flag']); ?> /></label></p>
	<?php 
	$tw = tp_get_credentials(true);
	if (isset($tw->screen_name)) echo "<p>" . sprintf(__('Currently logged in as: <strong>%s</strong>', 'tp'), $tw->screen_name) . "</p>";
	
	if ($options['autotweet_name']) {
		echo "<p>" . sprintf(__('Autotweet set to Twitter User: <strong>%s</strong>', 'tp'), $options['autotweet_name']) . "</p>";
	} else {
		echo "<p>" . __('Autotweet not set to a Twitter user.', 'tp') . "</p>";
	}
	echo '<p>' . __('To auto-publish new posts to any Twitter account, click this button and then log into that account to give the plugin access.', 'tp') . '</p><p>' . sprintf(__('Authenticate for auto-tweeting: %s', 'tp'), tp_get_connect_button('publish_preauth', 'authorize')) . '</p>';
	echo '<p>' . __('Afterwards, you can use this button to log back into your own normal account, if you are posting to a different account than your normal one.', 'tp') . '</p><p>' . sprintf(__('Normal authentication: %s', 'tp'), tp_get_connect_button('', 'authorize')) . '</p>';
}

function tp_publish_text() {
	$options = tp_options();
	if (!$options['publish_text']) $options['publish_text'] = __('%title% %url%[ifauthor] by %author%[/ifauthor]', 'tp');

	echo "<input type='text' name='tp_options[publish_text]' value='{$options['publish_text']}' size='40' /><br />";
	echo '<p>' . __('Use %title% for the post title.', 'tp') . '</p>';
	echo '<p>' . __('Use %url% for the post link (or shortlink).', 'tp') . '</p>';
	echo '<p>' . __('Use %author% for the author twitter username (@l0uy for example).', 'tp') . '</p>';
	echo '<p>' . __('Use [ifauthor][/ifauthor] to check if the author have a twitter account.', 'tp') . '</p>';
}

add_action('tp_publish_preauth','tp_publish_preauth');
function tp_publish_preauth() {
	if ( ! current_user_can('manage_options') )
		wp_die(__('You do not have sufficient permissions to manage options for this blog.', 'tp'));

	$tw = tp_get_credentials(true);

	$options = tp_options();
	
	// save the special settings
	if ($tw->screen_name) {
		$options['autotweet_name'] = $tw->screen_name;
		$options['autotweet_token'] = $_SESSION['tp_acc_token'];
		$options['autotweet_secret'] = $_SESSION['tp_acc_secret'];
	}
	
	update_option('tp_options', $options);
}

function tp_publish_meta_box( $post ) {
	$options = tp_options();
	
	if ($post->post_status == 'private') {
		echo '<p>'.__('Why would you put private posts on Twitter, for all to see?', 'tp').'</p>';
		return;
	}
	
	if ($post->post_status !== 'publish') {
		echo '<p>'.__('After publishing the post, you can send it to Twitter from here.', 'tp').'</p>';
		return;
	}
	
?><div id="tp-publish-buttons">
<div id="tp-manual-tweetbox" style="width:auto; padding-right:10px;"></div>
<script type="text/javascript">
  var tbox=new Array();
  tbox['height'] = 100;
  tbox['width'] = jQuery('#tp-manual-tweetbox').width();
  tbox['defaultContent'] = <?php echo json_encode(tp_get_default_tweet($post->ID)); ?>;
  tbox['label'] = '<?php __('Tweet this:', 'tp'); ?>';
  twttr.anywhere(function (T) {
    T("#tp-manual-tweetbox").tweetBox(tbox);
  });
</script>
</div>
<?php
}

// this new function prevents edits to existing posts from auto-posting
add_action('transition_post_status','tp_publish_auto_check',10,3);
function tp_publish_auto_check($new, $old, $post) {
	if ($new == 'publish' && $old != 'publish') {
		if ($post->post_type == 'post' || $post->post_type == 'page') 
			tp_publish_automatic($post->ID, $post);
	}
}

function tp_publish_automatic($id, $post) {
	
	// check to make sure post is published
	if ($post->post_status !== 'publish') return;
	
	// check options to see if we need to send to FB at all
	$options = tp_options();
	if (!$options['autotweet_flag'] || !$options['autotweet_token'] || !$options['autotweet_secret'] || !$options['publish_text']) 
		return;
	
	// args to send to twitter
	$args=array();

	$args['status'] = tp_get_default_tweet($id);

	$args['acc_token'] = $options['autotweet_token'];
	$args['acc_secret'] = $options['autotweet_secret'];
	
	$resp = tp_do_request('http://api.twitter.com/1/statuses/update',$args);
}

function tp_get_default_tweet($id) {
	$options = tp_options();
	if (function_exists('wp_get_shortlink')) {
		// use the shortlink if it's available
		$link = wp_get_shortlink($id);
	} else if (function_exists('get_shortlink')) {
		// use the shortlink if it's available
		$link = get_shortlink($id);
	} else {
		// use the full permalink (twitter will shorten for you)
		$link = get_permalink($id);
	}

	$output = $options['publish_text'];
	$output = str_replace('%title%', get_the_title($id), $output );
	$output = str_replace('%url%', $link, $output );
	
	$post = get_post($id);
	$authortw = get_the_author_meta('twuid',$post->post_author);
	if( $authortw ) {
		$output = str_replace('[ifauthor]', '', str_replace('[/ifauthor]', '', $output ) );
		$output = str_replace('%author%', '@'.$authortw ,$output );
	} else {
		$output = preg_replace('/\[ifauthor\].*\[\/ifauthor\]/', '', $output);
	}

	return $output;
}

add_filter('tp_validate_options','tp_publish_validate_options');
function tp_publish_validate_options($input) {
	$options = tp_options();
	if ($input['autotweet_flag'] != 1) $input['autotweet_flag'] = 0;
	
	$input['publish_text'] = trim($input['publish_text']);
	
	// preserve existing vars which are not inputs
	$input['autotweet_name'] = $options['autotweet_name'];
	$input['autotweet_token'] = $options['autotweet_token'];
	$input['autotweet_secret'] = $options['autotweet_secret'];
	
	return $input;
}

/**
 * TweetPress Follow Button:
 */
function get_tp_follow_button($user) {
	$ret = "<div id='twitter-follow-{$user}'></div>\n"
	. '<script type="text/javascript">' ."\n"
	. "	twttr.anywhere(function (twitter) {\n"
	. " twitter('#twitter-follow-{$user}').followButton('{$user}')\n"
	. "});\n"
	.'</script>';
	return $ret;
}

// output the button
function tp_follow_button($user) {
	echo get_tp_follow_button($user);
}

/**
 * Twitter follow as a shortcode
 *
 * Example use: [twitterfollow user="l0uy"]
 */
function tp_follow_shortcode($atts) {
	extract(shortcode_atts(array(
		'user' => '',
	), $atts));
	return get_tp_follow_button($user);
}
add_shortcode('twitterfollow','tp_follow_shortcode');

class TP_Follow_Widget extends WP_Widget {
	function TP_Follow_Widget() {
		$widget_ops = array('classname' => 'widget_tp-follow', 'description' => __('Twitter Follow Button'));
		$this->WP_Widget('tp-follow', __('Twitter Follow Button'), $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php tp_follow_button($instance['user']); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		$options = tp_options();
		if (empty($options['autotweet_name'])) $defaultuser = '';
		else $defaultuser = $options['autotweet_name'];
		
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'user' => $defaultuser) );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['user'] = strip_tags($new_instance['user']);
		return $instance;
	}

	function form($instance) {
		$options = get_option('tp_options');
		if (empty($options['autotweet_name'])) $defaultuser = ''; 
		else $defaultuser = $options['autotweet_name'];
		
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'user' => $defaultuser) );
		$title = strip_tags($instance['title']);
		$user = strip_tags($instance['user']);
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Username:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo $user; ?>" />
</label></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("TP_Follow_Widget");'));
