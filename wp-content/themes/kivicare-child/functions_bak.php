<?php
/**
 * Theme functions and definitions.
 */
add_action( 'wp_enqueue_scripts', 'kivicare_enqueue_styles' ,99);

function kivicare_enqueue_styles() {

wp_enqueue_style( 'parent-style', get_stylesheet_directory_uri() . '/style.css'); 
wp_enqueue_style( 'child-style',get_stylesheet_directory_uri() . '/style.css');
}

/**
 * Set up My Child Theme's textdomain.
*
* Declare textdomain for this child theme.
* Translations can be added to the /languages/ directory.
*/
function kivicare_child_theme_setup() {
load_child_theme_textdomain( 'streamit', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'kivicare_child_theme_setup' );

add_filter( 'woocommerce_prevent_admin_access', 'kivicare_agent_admin_access', 20, 1 );

function kivicare_agent_admin_access( $prevent_access ) {
    if ( current_user_can('read') ) {
        $prevent_access = false;
    }
    return $prevent_access;
}
