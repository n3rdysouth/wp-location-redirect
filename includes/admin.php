<?php

// Add main menu and submenus
add_action( 'admin_menu', 'wp_location_redirect_add_main_menu' );
function wp_location_redirect_add_main_menu() {
    add_menu_page(
        'WP Location Redirect',
        'Location Redirect',
        'manage_options',
        'location-redirect-wp',
        'wp_location_redirect_settings_page',
        'dashicons-location',
        80
    );

    add_submenu_page(
        'location-redirect-wp',
        'Location Redirect Settings',
        'Settings',
        'manage_options',
        'location-redirect-wp',
        'wp_location_redirect_settings_page'
    );

    add_submenu_page(
        'location-redirect-wp',
        'Manage Locations',
        'Manage Locations',
        'manage_options',
        'manage-locations',
        'wp_location_redirect_manage_locations_page'
    );
}

// SETTINGS PAGE
function wp_location_redirect_settings_page() {
    $geoip_file = WP_LOCATION_REDIRECT_DIR . 'data/GeoLite2-City.mmdb';
    $last_updated = file_exists( $geoip_file )
        ? gmdate( "F d, Y H:i:s", filemtime( $geoip_file ) )
        : 'Never';

    ?>
    <div class="wrap">
        <h1>WP Location Redirect Settings</h1>

        <!-- MMDB File Upload Section -->
        <h2>Upload GeoLite2-City.mmdb File</h2>
        <p>If you have a GeoLite2-City `.mmdb` file, please upload it here. We recommend obtaining it directly from <a href="https://www.maxmind.com/" target="_blank">MaxMind</a>.</p>
        <p>Last Updated: <strong><?php echo esc_html( $last_updated ); ?></strong></p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field( 'wp_location_geoip_upload', '_wpnonce_geoip_upload' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="geoip_file">GeoLite2-City.mmdb file:</label></th>
                    <td><input type="file" name="geoip_file" id="geoip_file" accept=".mmdb" required></td>
                </tr>
            </table>
            <p><input type="hidden" name="action" value="upload_geoip">
                <button type="submit" class="button button-primary">Upload File</button>
            </p>
        </form>

        <?php if ( isset( $_GET['geoip_uploaded'] ) && $_GET['geoip_uploaded'] === 'true' ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>The GeoLite2 database file was successfully uploaded!</p>
            </div>
        <?php elseif ( isset( $_GET['geoip_error'] ) && $_GET['geoip_error'] === 'true' ) : ?>
            <div class="notice notice-error is-dismissible">
                <p>Failed to upload the GeoLite2 database file. Please ensure it is a valid `.mmdb` file and try again.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// HANDLE GEOIP FILE UPLOAD
add_action( 'admin_post_upload_geoip', 'wp_location_redirect_upload_geoip' );
function wp_location_redirect_upload_geoip() {
    if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'wp_location_geoip_upload', '_wpnonce_geoip_upload' ) ) {
        wp_die( 'Unauthorized!', 'Error', array( 'response' => 401 ) );
    }

    // Validate and process the uploaded file
    if ( isset( $_FILES['geoip_file'] ) && ! empty( $_FILES['geoip_file']['tmp_name'] ) ) {
        $uploaded_file = $_FILES['geoip_file'];

        // Check the file type (allow only `.mmdb` extensions)
        $file_name = sanitize_file_name( wp_unslash( $uploaded_file['name'] ) );
        $file_type = wp_check_filetype( $file_name );

        if ( $file_type['ext'] !== 'mmdb' ) {
            wp_redirect( admin_url( 'admin.php?page=location-redirect-wp&geoip_error=true' ) );
            exit;
        }

        global $wp_filesystem;

        // Initialize the WP Filesystem API
        if ( ! WP_Filesystem() ) {
            wp_die( esc_html__( 'Failed to initialize WP_Filesystem', 'location-redirect-wp' ) );
        }

        $upload_dir = WP_LOCATION_REDIRECT_DIR . 'data/';
        if ( ! $wp_filesystem->is_dir( $upload_dir ) ) {
            $wp_filesystem->mkdir( $upload_dir );
        }

        $destination = trailingslashit( $upload_dir ) . 'GeoLite2-City.mmdb';

        if ( ! $wp_filesystem->move( $uploaded_file['tmp_name'], $destination, true ) ) {
            wp_redirect( admin_url( 'admin.php?page=location-redirect-wp&geoip_error=true' ) );
            exit;
        }

        wp_redirect( admin_url( 'admin.php?page=location-redirect-wp&geoip_uploaded=true' ) );
        exit;
    }

    wp_redirect( admin_url( 'admin.php?page=location-redirect-wp&geoip_error=true' ) );
    exit;
}

// MANAGE LOCATIONS PAGE
function wp_location_redirect_manage_locations_page() {
    global $wpdb;
    require_once __DIR__ . '/database.php';

    $table_name = wp_location_redirect_get_table_name();

    // Handle CRUD actions
    if ( isset( $_POST['action'], $_POST['_wpnonce'] ) && sanitize_text_field( $_POST['action'] ) === 'create' ) {
        check_admin_referer( 'create_location', '_wpnonce' );

        $name         = sanitize_text_field( wp_unslash( $_POST['location_name'] ) );
        $country      = sanitize_text_field( wp_unslash( $_POST['country'] ) );
        $state        = sanitize_text_field( wp_unslash( $_POST['state'] ) );
        $city         = sanitize_text_field( wp_unslash( $_POST['city'] ) );
        $redirect_url = esc_url_raw( wp_unslash( $_POST['redirect_url'] ) );

        if ( ! empty( $name ) && ! empty( $country ) && ! empty( $redirect_url ) ) {
            wp_location_redirect_create_location( [
                'location_name' => $name,
                'country'       => $country,
                'state'         => $state,
                'city'          => $city,
                'url'           => $redirect_url,
            ] );
        }
    } elseif ( isset( $_GET['action'], $_GET['_wpnonce'], $_GET['id'] ) && sanitize_text_field( $_GET['action'] ) === 'delete' ) {
        $id = absint( $_GET['id'] );

        if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_location_' . $id ) ) {
            wp_location_redirect_delete_location( $id );
        } else {
            wp_die( esc_html__( 'Nonce verification failed.', 'location-redirect-wp' ) );
        }
    }

    $locations = wp_location_redirect_get_locations();

    ?>
    <div class="wrap">
        <h1>Manage Locations</h1>

        <!-- Add New Location Form -->
        <h2>Add New Location</h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'create_location', '_wpnonce' ); ?>
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
                <th>Actions</th>
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
                        <td><a href="<?php echo esc_url( $location->url ); ?>" target="_blank"><?php echo esc_html( $location->url ); ?></a></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'id' => $location->id ], admin_url( 'admin.php?page=manage-locations' ) ), 'delete_location_' . $location->id ) ); ?>" class="button button-danger" onclick="return confirm('Are you sure you want to delete this location?')">Delete</a>
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
?>