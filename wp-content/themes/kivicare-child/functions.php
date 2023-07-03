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
/**
 *
 *  @author     Christopher Davies, WP Davies
 *  @link       https://wpdavies.dev/
 *  @link       https://wpdavies.dev/remove-checkout-fields-for-downloadable-products-woocommerce/
 *  @snippet    Remove checkout fields for virtual/downloadable products
 *
 */
add_filter( 'woocommerce_checkout_fields' , 'wpd_virtual_checkout_fields', 100 );
function wpd_virtual_checkout_fields( $fields ) {
     
    $only_virtual = true;
 
    // Check if there are non-virtual products
    foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
 
        if ( ! $cart_item['data']->is_virtual() ) $only_virtual = false;   
 
    }
 
    // If there are only virtual products in the cart
    if ( $only_virtual ) {
 
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_postcode']);
        // unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_state']);
        add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
        add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
 
        // Set email as first field to help capture cart abandonment
        $fields['billing']['billing_email']['priority'] = -100; // must be first
 
    }
 
    return $fields;
 
}
add_action('woocommerce_thankyou', 'tenabook_autocomplete_virtual_orders', 10, 1 );
function tenabook_autocomplete_virtual_orders( $order_id ) {
 
    if( ! $order_id ) return;
 
    // Get order
    $order = wc_get_order( $order_id );
 
    // get order items = each product in the order
    $items = $order->get_items();
 
    // Set variable
    $only_virtual = true;
 
    foreach ( $items as $item ) {
         
        // Get product id
        $product = wc_get_product( $item['product_id'] );
 
        // Is virtual
        $is_virtual = $product->is_virtual();
 
        // Is_downloadable
        $is_downloadable = $product->is_downloadable();
 
        if ( ! $is_virtual && ! $is_downloadable  ) {
 
            $only_virtual = false;
 
        }
 
    }
 
    // true
    if ( $only_virtual ) {
 
        $order->update_status( 'completed' );
 
    }
}
/**
 *
 *  Let the user checkout as guest even if they have an account
 *  @todo tie the order to an email
 *
 */
add_filter( 'woocommerce_checkout_posted_data', 'ftm_filter_checkout_posted_data', 10, 1 );
function ftm_filter_checkout_posted_data( $data ) {
 
    $email = $data['billing_email'];
 
    if ( email_exists( $email ) ) $data['createaccount'] = 0;
 
    return $data;
 
}
add_action('woocommerce_cart_calculate_fees', function() {
if (is_admin() && !defined('DOING_AJAX')) {
return;
}
WC()->cart->add_fee(__('Booking Charge', 'txtdomain'), 1.5);
});
add_action( 'admin_footer', function(){

    ?>

    <style>

        div.custom-control.custom-radio.custom-control-inline label[for='other']{

display: none !important;

}

    </style>

<?php

});

add_action( 'rest_api_init', function () {

            register_rest_route( 'kivicare/api/v1/appointment/', 'get_search', array(
                'methods'             => 'GET',
                'callback'            => 'search_service',
                'permission_callback' => '__return_true',
            ));

            register_rest_route( 'kivicare/api/v1/appointment/', 'top_services', array(
                'methods'             => 'GET',
                'callback'            => 'top_services',
                'permission_callback' => '__return_true',
            ));
            
});


function search_service($param){
        global $wpdb;
        $response = array();
        $search = sanitize_text_field($param['search']);
        
        //$get_data = $wpdb->get_results("SELECT ID, user_email, display_name FROM `wp_users` WHERE `display_name` LIKE '$search%'");
        $get_doctor = $wpdb->get_results("SELECT wp_users.ID, wp_users.user_email, wp_users.display_name, (select wp_usermeta.meta_value from wp_usermeta where wp_usermeta.user_id = wp_users.id and wp_usermeta.meta_key = 'first_name') as first_name, (select wp_usermeta.meta_value from wp_usermeta where wp_usermeta.user_id = wp_users.id and wp_usermeta.meta_key = 'last_name') as last_name FROM wp_users INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id WHERE (wp_usermeta.meta_key = 'wp_capabilities' AND wp_usermeta.meta_value LIKE '%kiviCare_doctor%') AND wp_users.display_name LIKE '%$search%'");

        $get_clinic = $wpdb->get_results("SELECT wp_users.ID, wp_users.user_email, wp_users.display_name, (select wp_usermeta.meta_value from wp_usermeta where wp_usermeta.user_id = wp_users.id and wp_usermeta.meta_key = 'first_name') as first_name, (select wp_usermeta.meta_value from wp_usermeta where wp_usermeta.user_id = wp_users.id and wp_usermeta.meta_key = 'last_name') as last_name FROM wp_users INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id WHERE (wp_usermeta.meta_key = 'wp_capabilities' AND wp_usermeta.meta_value LIKE '%kiviCare_clinic_admin%') AND wp_users.display_name LIKE '%$search%'");

        
        if(count($get_doctor) > 0 || count($get_clinic) > 0){
            $response['doctor'] = $get_doctor;
            $response['clinic'] = $get_clinic;
            $status_code = 200;
            $response['message'] = "Fetch Successfully";
        }else{
            $status_code = 402;
            $response['success'] = false;
            $response['message'] = "Invalid User";
        }
        
        return new WP_REST_Response($response,$status_code);
}

function top_services($param){
        global $wpdb;
        $response = array();
        $service_name = sanitize_text_field($param['service_name']);
        
        $status_code = 402;
        $response['success'] = false;
        $response['message'] = "Invalid User";
        return new WP_REST_Response($response,$status_code);
}


function execute_on_register_new_user_event($user_id, $notify){

     update_user_meta($user_id, 'longitudess', 'Deepak');

}
add_action( "register_new_user", "execute_on_register_new_user_event" , 10, 2);
