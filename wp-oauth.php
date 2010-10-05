<?php
/**
 * OAuth script for WordPress
 * @author Louy Alakkad <louy08@gmail.com>
 * @website http://l0uy.com/
 */
global $oauth_activate;
if( !isset($oauth_activate) )
	$oauth_activate = false;

/**
 * Don't forget to set $oauth_activate to true when you activate your plugin.
 *  just to make sure rewrite rules will be flushed.
 */

add_action('init', 'oauth_init');
function oauth_init() {
	global $oauth_activate, $wp;
	
	add_rewrite_rule('oauth/(.+?)/?', 'index.php?oauth=$matches[1]',1);
	add_rewrite_rule('oauth/?', 'index.php?oauth=null',1);
	
	if( $oauth_activate ) {
		flush_rewrite_rules();
	}
	
	$wp->add_query_var('oauth');
	
}

add_action('template_redirect', 'oauth_template_redirect');
function oauth_template_redirect() {
	if( get_query_var('oauth') ) {
		$oauth_sites = apply_filters('oauth_sites', array());
		if( !in_array(get_query_var('oauth'), $oauth_sites))
			die( __('OAuth site not recognized!') );
		do_action('oauth_start_'.get_query_var('oauth'));
		die();
	}
}
