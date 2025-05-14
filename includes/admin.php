<?php

// Add main menu and submenus
add_action( 'admin_menu', 'wp_location_redirect_add_main_menu' );
function wp_location_redirect_add_main_menu() {
    // Add main menu (parent)
    add_menu_page(
        'WP Location Redirect',       // Page title
        'Location Redirect',          // Menu title
        'manage_options',             // Capability
        'wp-location-redirect',       // Menu slug
        'wp_location_redirect_settings_page', // Callback for main menu (redirects to Settings)
        'dashicons-location',         // Icon
        80                            // Position
    );

    // Add submenu for Settings
    add_submenu_page(
        'wp-location-redirect',       // Parent slug
        'Location Redirect Settings', // Page title
        'Settings',                   // Submenu title
        'manage_options',             // Capability
        'wp-location-redirect',       // Menu slug (same as main menu for focusing settings)
        'wp_location_redirect_settings_page' // Callback for Settings page
    );

    // Add submenu for managing locations
    add_submenu_page(
        'wp-location-redirect',       // Parent slug
        'Manage Locations',           // Page title
        'Manage Locations',           // Submenu title
        'manage_options',             // Capability
        'manage-locations',           // Menu slug
        'wp_location_redirect_manage_locations_page' // Callback for Manage Locations page
    );
}

// Settings page HTML
function wp_location_redirect_settings_page() {
    // File path for the GeoLite2 database
    $geoip_file = WP_LOCATION_REDIRECT_DIR . 'data/GeoLite2-City.mmdb';

    // Display last update time
    $last_updated = file_exists( $geoip_file )
        ? date( "F d, Y H:i:s", filemtime( $geoip_file ) )
        : '<span style="color: red;">Never</span>';

    ?>
    <div class="wrap">
        <h1>WP Location Redirect Settings</h1>
        <p><?php echo esc_html( $geoip_file ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'wp_location_geoip_download', '_wpnonce_geoip' ); ?>
            <h2>GeoLite2 Database</h2>
            <p>Last Updated: <strong><?php echo $last_updated; ?></strong></p>
            <input type="hidden" name="action" value="download_geoip">
            <button type="submit" name="download_geoip" class="button button-primary">
                Download GeoLite2 Database
            </button>
        </form>
        <?php if ( isset( $_GET['geoip_downloaded'] ) && $_GET['geoip_downloaded'] === 'true' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>GeoLite2 database downloaded and updated successfully!</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Handle GeoLite2 database download
add_action( 'admin_post_download_geoip', 'wp_location_redirect_download_geoip' );
function wp_location_redirect_download_geoip() {
    if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'wp_location_geoip_download', '_wpnonce_geoip' ) ) {
        wp_die( 'Unauthorized!', 'Error', array( 'response' => 401 ) );
    }

    // Example download logic
    // ...
}

// Manage Locations Page
function wp_location_redirect_manage_locations_page() {
    global $wpdb;
    require_once __DIR__ . '/database.php';

    $table_name = wp_location_redirect_get_table_name();

    // Handle CRUD actions
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'create' ) {
        $name         = sanitize_text_field( $_POST['location_name'] );
        $country      = sanitize_text_field( $_POST['country'] );
        $state        = sanitize_text_field( $_POST['state'] ); // Optional state
        $city         = sanitize_text_field( $_POST['city'] );  // Optional city
        $redirect_url = esc_url_raw( $_POST['redirect_url'] );

        if ( ! empty( $name ) && ! empty( $country ) && ! empty( $redirect_url ) ) {
            wp_location_redirect_create_location( [
                'location_name' => $name,
                'country'       => $country,
                'state'         => $state,
                'city'          => $city,
                'url'           => $redirect_url,
            ] );
        }
    } elseif ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
        $id = intval( $_GET['id'] );
        wp_location_redirect_delete_location( $id );
    }

    $locations = wp_location_redirect_get_locations();

    ?>
    <div class="wrap">
        <h1>Manage Locations</h1>

        <!-- Add New Location Form -->
        <h2>Add New Location</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="create">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="name">Location Name</label></th>
                    <td><input name="location_name" type="text" id="location_name" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="country">Country Code</label></th>
                    <td><input name="country" type="text" id="country" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="state">State</label></th>
                    <td><input name="state" type="text" id="state"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="city">City</label></th>
                    <td><input name="city" type="text" id="city"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="redirect_url">Redirect URL</label></th>
                    <td><input name="redirect_url" type="url" id="redirect_url" required></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary">Add Location</button></p>
        </form>

        <!-- Display Existing Locations -->
        <h2>Existing Locations</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Location Name</th>
                <th>Country Code</th>
                <th>State</th>
                <th>City</th>
                <th>Redirect URL</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $locations ) ) : ?>
                <?php foreach ( $locations as $location ) : ?>
                    <tr>
                        <td><?php echo esc_html( $location->id ); ?></td>
                        <td><?php echo esc_html( $location->location_name ); ?></td>
                        <td><?php echo esc_html( $location->country ); ?></td>
                        <td><?php echo esc_html( $location->state ); ?></td>
                        <td><?php echo esc_html( $location->city ); ?></td>
                        <td><a href="<?php echo esc_url( $location->url ); ?>" target="_blank"><?php echo esc_url( $location->url ); ?></a></td>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'delete', 'id' => $location->id ] ) ); ?>" class="button button-danger" onclick="return confirm('Are you sure you want to delete this location?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7">No locations found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}