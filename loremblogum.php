<?php
/**
 * @package LoremBlogum
 */
/*
Plugin Name: LoremBlogum
Plugin URI: http://LoremBlogum.org/
Description:  Imports Articles for WordPress Lorem Ipsum content tests
Version: 1.0.0
Author: Lewis A. Sellers
Author URI: http://lewisasellers.com/
License: GPL2
*/

/*

@date: 8/2015
@author: Lewis A. Sellers lasellers@gmail.com

*/

if ( !defined('ABSPATH') ) {
	echo 'This WordPress plugin can not be run directly.';
	exit;
}

define( 'LOREMBLOGUM_VERSION', '1.0.0' );
define( 'LOREMBLOGUM_MINIMUM_WP_VERSION', '4.2' );
define( 'LOREMBLOGUM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOREMBLOGUM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOREMBLOGUM_DATA', 'loremblogum_data');
define( 'LOREMBLOGUM_FEEDS', 'loremblogum_feeds');
define( 'LOREMBLOGUM_PREDEFINES', 'loremblogum_predefines');
define( 'LOREMBLOGUM_ID', 'loremblogum');
//define( 'LOREMBLOGUM_MAIL_DOMAIN', 'loremblogum.com');

define( 'LOREMBLOGUM_META_ID', 'loremblogum_feed_id');
define( 'LOREMBLOGUM_META_URL', 'loremblogum_url');

require_once( LOREMBLOGUM_PLUGIN_DIR . 'functions.php' );
require_once( LOREMBLOGUM_PLUGIN_DIR . "SmartDOMDocument.class.php");
require_once( LOREMBLOGUM_PLUGIN_DIR . 'model.feeds.php' );
require_once( LOREMBLOGUM_PLUGIN_DIR . 'model.feeditems.php' );
require_once( LOREMBLOGUM_PLUGIN_DIR . 'model.predefines.php' );
require_once( LOREMBLOGUM_PLUGIN_DIR . 'model.loremblogum.php' );
require_once( LOREMBLOGUM_PLUGIN_DIR . "api.php");

if(is_admin())
{
	require_once( LOREMBLOGUM_PLUGIN_DIR . "admin/ajax.php");
	require_once( LOREMBLOGUM_PLUGIN_DIR . "admin/settings.php");
}

/* */
function loremblogum_activation()
{
	global $wpdb;

	$pluginOptions = get_option(LOREMBLOGUM_DATA);

	if ( false === $pluginOptions ) {
        // Install plugin
		$pluginOptions['version']=LOREMBLOGUM_VERSION;
		update_option(LOREMBLOGUM_DATA, $pluginOptions);
	} else if ( !isset($pluginOptions['version']) ) {
        // init plugin
		$pluginOptions['version']=LOREMBLOGUM_VERSION;
		update_option(LOREMBLOGUM_DATA, $pluginOptions);
	} else if ( LOREMBLOGUM_VERSION != $pluginOptions['version'] ) {
        // Upgrade plugin
	}

}

/* 

*/
function loremblogum_deactivation() {
	$pluginOptions = get_option(LOREMBLOGUM_DATA);
	if ( true === $pluginOptions['uninstall'] ) {
		/*delete_option(LOREMBLOGUM_DATA);*/
	}
}

/* */
function loremblogum_uninstall()
{
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	check_admin_referer( 'bulk-plugins' );

    // Important: Check if the file is the one
    // that was registered during the uninstall hook.
	if ( __FILE__ != WP_UNINSTALL_PLUGIN )
		return;

   # Uncomment the following line to see the function in action
    # exit( var_dump( $_GET ) );
}

register_activation_hook( __FILE__,'loremblogum_activation');
register_deactivation_hook( __FILE__,'loremblogum_deactivation');
register_uninstall_hook( __FILE__,'loremblogum_uninstall');

/* load the plugin style.css */
add_action( 'wp_enqueue_scripts', function() {

} );

/* */
if(is_admin())
{

	/* load the admin js */
	add_action( 'admin_enqueue_scripts', function ( $hook )
	{
		wp_enqueue_script(
			'bootstrap_js', 
			'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js',
			['jquery'],
			'',
			true );
		wp_enqueue_script( 
        'loremblogum_settings_script',                         // Handle
        plugins_url( 'admin/settings.js', __FILE__ ),  // Path to file
        ['jquery' ]                             // Dependancies
        );
	}, 2000 );


	/* css */
	add_action('admin_enqueue_scripts', function () 
	{
		wp_enqueue_style( 
			'fontawesome_css',
			'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'
			);
		wp_enqueue_style( 
			'bootstrap_css',
			'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css'
			);

		wp_enqueue_style( 'admin_main', plugins_url( '/admin/style.css', __FILE__ ) );

	});

}

/*
 adds 120x120 size for media uploads so images will have a properly resized option.
*/
 add_theme_support( 'post-thumbnails' );
 add_image_size( LOREMBLOGUM_ID.'-image', 120, 120,true );

/* 
Ads query variables that can be used to the master list.
These are used as options for the shortcodes.
*/
add_filter( 'query_vars', function( $vars ){
	$vars[] = "id";
	$vars[]="feed_id";
	$vars[]="predefine_id";
	$vars[]="action";
	$vars[]="act";
	return $vars;
});


/* Add custom post type */
add_action('init', function () {

	$labels = array(
		'name'=>_x('loremblogums', 'post type general name'),
		'singular_name'=>_x('loremblogum', 'post type singular name'),

		);

	$args = array(
		'labels'=>$labels,
		'public'=>true,
		'publicly_queryable'=>true,
		'show_ui'=>true,
		'query_var'=>true,
		//'menu_icon'=>get_stylesheet_directory_uri() . '/icon.png',
		'rewrite'=>true,
		'capability_type'=>'post',
		'hierarchical'=>false,
		'menu_position'=>null,
		'supports'=>array('title','editor','thumbnail')
		); 

	register_post_type( 'loremblogum' , $args );
}
);

?>