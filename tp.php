<?php
/*
Plugin Name: TP - TweetPress
Description: All the tools you need to integrate your wordpress and twitter.
Author: Louy
Version: 2.0
Author URI: http://l0uy.com/
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

define( 'TP_VERSION', '2.0' );
define( 'TP_PHP_VERSION_REQUIRED', '5.4.0' );

function tp_activate(){
	// require PHP 5
	if( version_compare(PHP_VERSION, TP_PHP_VERSION_REQUIRED, '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die( sprintf( __("Sorry, FacePress requires PHP %1$s or higher. Ask your host how to enable PHP %1$s as the default on your servers.", 'tp'), TP_PHP_VERSION_REQUIRED ) );
	}
}
register_activation_hook(__FILE__, 'tp_activate');

if( version_compare(PHP_VERSION, TP_PHP_VERSION_REQUIRED, '>=') ) {
	require_once dirname(__FILE__) . '/tp-social.php';
	global $tp;
	$tp = TP_Social::get_instance(__FILE__);
}
