<?php

use GeoIp2\Database\Reader;

/**
 * Add a WordPress action to handle redirects based on user location.
 */
add_action( 'template_redirect', 'wp_location_redirect_check_user_location' );

/**
 * Retrieve the client IP address, considering CDN/Proxy headers.
 *
 * @return string The client's IP address.
 */
function get_client_ip() {
    $ip = '';

    // Verify and sanitize each superglobal key/value before usage
    if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }

    if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // Get the first IP from the comma-separated forwarded IP list
        $ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        $ip = trim( $ip_list[0] );
    }

    return $ip;
}

/**
 * Perform location-based redirect using the GeoLite2 database and stored rules.
 */
function wp_location_redirect_check_user_location() {
    // Define the GeoLite2 database file path
    $geoip_file = WP_LOCATION_REDIRECT_DIR . 'data/GeoLite2-City.mmdb';

    // Check if the GeoLite2 database exists
    if ( ! file_exists( $geoip_file ) ) {
        return; // No database present, no redirects should occur
    }

    // Create MaxMind GeoIP2 Reader instance
    $reader = new Reader( $geoip_file );

    // Get the client IP address
    $user_ip = get_client_ip();

    try {
        // Get location data for the client's IP address
        $geoData = $reader->city( $user_ip );

        if ( ! $geoData ) {
            return; // No location data found for the IP address
        }

        // Extract country, state, and city from the GeoData
        $country = $geoData->country->isoCode; // e.g., 'US'
        $state   = $geoData->mostSpecificSubdivision->isoCode; // e.g., 'CA'
        $city    = $geoData->city->name; // e.g., 'Los Angeles'

        // Fetch location-based redirects from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'location_redirects';

        // Use a prepared statement to safely query the database
        $redirects = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE country = %s", $country ) // Safely prepare query
        );

        // Process the redirect rules
        foreach ( $redirects as $redirect ) {
            if (
                ( empty( $redirect->state ) || $redirect->state === $state ) &&
                ( empty( $redirect->city ) || $redirect->city === $city )
            ) {
                // Perform the redirect if a match is found
                wp_redirect( esc_url( $redirect->url ) );
                exit;
            }
        }
    } catch ( Exception $e ) {
        // Use trigger_error instead of error_log for proper error handling
        trigger_error( 'GeoIP2 Error: ' . esc_html( $e->getMessage() ), E_USER_WARNING );
    }
}