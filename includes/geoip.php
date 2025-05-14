<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use GeoIp2\Database\Reader;

/**
 * Retrieve GeoIP information for a given IP address.
 *
 * @param string|null $ip The IP address to look up. Defaults to the client IP.
 *
 * @return array|false The location data array if successful, or false if there's an error.
 */
function wp_location_redirect_get_geoip_data( $ip = null ) {
    // Use the client IP if no IP is provided
    if ( is_null( $ip ) ) {
        $ip = wp_location_redirect_get_client_ip();
    }

    // Define the GeoLite2 database file path
    $geoip_file = WP_LOCATION_REDIRECT_DIR . 'data/GeoLite2-City.mmdb';

    // Check if the GeoLite2 database exists
    if ( ! file_exists( $geoip_file ) ) {
        trigger_error( 'GeoLite2 database not found at: ' . esc_html( $geoip_file ), E_USER_WARNING );
        return false;
    }

    try {
        // Include the Composer autoloader to load the MaxMind GeoIP2 library
        require_once WP_LOCATION_REDIRECT_DIR . '/vendor/autoload.php';

        // Create the GeoIP2 reader instance
        $reader = new Reader( $geoip_file );

        // Fetch GeoIP data for the provided IP
        $record = $reader->city( $ip );

        // Return structured location data
        return array(
            'ip'       => sanitize_text_field( $ip ),
            'country'  => $record->country->isoCode ?? '', // Example: 'US'
            'state'    => $record->mostSpecificSubdivision->isoCode ?? '', // Example: 'CA'
            'city'     => $record->city->name ?? '', // Example: 'Los Angeles'
            'latitude' => $record->location->latitude ?? '', // Example: 34.0522
            'longitude'=> $record->location->longitude ?? '' // Example: -118.2437
        );

    } catch ( Exception $e ) {
        // Log any errors for debugging
        trigger_error( 'GeoIP2 Error: ' . esc_html( $e->getMessage() ), E_USER_WARNING );
        return false;
    }
}

/**
 * Retrieve the client's IP address, respecting proxy headers where applicable.
 *
 * @return string The client IP address.
 */
function wp_location_redirect_get_client_ip() {
    $ip = '';

    // Verify and sanitize each superglobal before use
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