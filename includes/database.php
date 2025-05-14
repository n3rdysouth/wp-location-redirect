<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Get the table name for location redirects.
 *
 * @return string The full table name for location redirects.
 */
function wp_location_redirect_get_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'location_redirects';
}

/**
 * Create the table `location_redirects` if it does not exist.
 * This is executed during plugin activation.
 */
function wp_location_redirect_create_table() {
    global $wpdb;

    $table_name = wp_location_redirect_get_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        location_name VARCHAR(255) NOT NULL, -- Human-friendly location name (required)
        country VARCHAR(5) NOT NULL,         -- Country code (required)
        state VARCHAR(255) DEFAULT NULL,    -- Optional state or region
        city VARCHAR(255) DEFAULT NULL,     -- Optional city
        url VARCHAR(2083) NOT NULL,         -- Redirect URL
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Save a new location redirect rule to the database.
 *
 * @param string $name Location name (optional)
 * @param string $country Country code.
 * @param string|null $state State name (optional).
 * @param string|null $city City name (optional).
 * @param string $redirect_url The redirection URL.
 *
 * @return int|false The inserted row ID, or false if the insert failed.
 */
function wp_location_redirect_create_location( $data ) {
    global $wpdb;

    // Insert into the table
    $table_name = wp_location_redirect_get_table_name();

    $result = $wpdb->insert(
        $table_name,
        array(
            'country' => sanitize_text_field( $data['country'] ),
            'state' => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : null,
            'city' => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : null,
            'url' => esc_url_raw( $data['url'] )
        ),
        array( '%s', '%s', '%s', '%s' )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * Fetch all location redirect rules from the database.
 *
 * @return array List of location redirect rules as objects.
 */
function wp_location_redirect_get_locations() {
    global $wpdb;

    // Caching result to avoid repeated database queries
    $cache_key = 'wp_location_redirect_get_locations';
    $cached_result = wp_cache_get( $cache_key, 'location_redirects' );

    if ( false !== $cached_result ) {
        return $cached_result; // Return cached result if available
    }

    // Query to fetch all records from the table
    $table_name = wp_location_redirect_get_table_name();
    $result = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC" );

    // Cache the result
    wp_cache_set( $cache_key, $result, 'location_redirects' );

    return $result;
}

/**
 * Fetch a single redirect rule by its ID.
 *
 * @param int $id ID of the rule to fetch.
 *
 * @return object|null The redirect rule object, or null if not found.
 */
function wp_location_redirect_get_rule_by_id( $id ) {
    global $wpdb;

    // Caching result for individual rules
    $cache_key = 'wp_location_redirect_rule_' . $id;
    $cached_result = wp_cache_get( $cache_key, 'location_redirects' );

    if ( false !== $cached_result ) {
        return $cached_result; // Return cached result if available
    }

    // Query to fetch the rule by ID
    $table_name = wp_location_redirect_get_table_name();
    $result = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", intval( $id ) )
    );

    // Cache the result
    wp_cache_set( $cache_key, $result, 'location_redirects' );

    return $result;
}

/**
 * Update a location redirect rule by ID.
 *
 * @param int $id The ID of the rule to update.
 * @param array $data The update data.
 *      [
 *          'country' => 'US',
 *          'state'   => 'CA',
 *          'city'    => 'Los Angeles',
 *          'url'     => 'https://example.com/ca/la/'
 *      ]
 *
 * @return bool True if successful, false otherwise.
 */
function wp_location_redirect_update_rule( $id, $data ) {
    global $wpdb;

    // Update the record in the table
    $table_name = wp_location_redirect_get_table_name();
    $updated = $wpdb->update(
            $table_name,
            array(
                'country' => sanitize_text_field( $data['country'] ),
                'state' => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : null,
                'city' => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : null,
                'url' => esc_url_raw( $data['url'] )
            ),
            array( 'id' => intval( $id ) ), // WHERE condition
            array( '%s', '%s', '%s', '%s' ), // Data format
            array( '%d' ) // WHERE format
        ) !== false;

    // Clear cache for this specific rule and all rules
    if ( $updated ) {
        wp_cache_delete( 'wp_location_redirect_rule_' . $id, 'location_redirects' );
        wp_cache_delete( 'wp_location_redirect_get_locations', 'location_redirects' );
    }

    return $updated;
}

/**
 * Delete a location redirect rule from the database by ID.
 *
 * @param int $id ID of the rule to delete.
 *
 * @return bool True if successful, false otherwise.
 */
function wp_location_redirect_delete_location( $id ) {
    global $wpdb;

    // Delete the record
    $table_name = wp_location_redirect_get_table_name();
    $deleted = $wpdb->delete( $table_name, array( 'id' => intval( $id ) ), array( '%d' ) ) !== false;

    // Clear cache for this specific rule and all rules
    if ( $deleted ) {
        wp_cache_delete( 'wp_location_redirect_rule_' . $id, 'location_redirects' );
        wp_cache_delete( 'wp_location_redirect_get_locations', 'location_redirects' );
    }

    return $deleted;
}