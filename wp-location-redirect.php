<?php
/*
Plugin Name: WP Location Redirect
Description: Redirect users based on their location, such as country, state, city by using the GeoLite2-City database.
Version: 1.0.0
Author: Brandon DuBois
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'WP_LOCATION_REDIRECT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_LOCATION_REDIRECT_URL', plugin_dir_url( __FILE__ ) );

include_once WP_LOCATION_REDIRECT_DIR . 'vendor/autoload.php';

// Include necessary files
include_once WP_LOCATION_REDIRECT_DIR . 'includes/admin.php';
include_once WP_LOCATION_REDIRECT_DIR . 'includes/redirects.php';
include_once WP_LOCATION_REDIRECT_DIR . 'includes/geoip.php';
include_once WP_LOCATION_REDIRECT_DIR . 'includes/database.php';

// Activation hook
register_activation_hook( __FILE__, 'wp_location_redirect_activate' );
function wp_location_redirect_activate() {
    wp_location_redirect_create_table(); // Create database table for redirects
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'wp_location_redirect_deactivate' );
function wp_location_redirect_deactivate() {
    // Code for cleanup if needed
}