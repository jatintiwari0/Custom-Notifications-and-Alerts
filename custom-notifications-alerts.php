<?php
/*
Plugin Name: Custom Notifications and Alerts
Description: A plugin to create custom notifications and alerts for users.
Version: 1.0
Author: Getallscripts
*/

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook to add a menu item in the admin dashboard
add_action( 'admin_menu', 'cna_add_admin_menu' );

// Function to add a menu item
function cna_add_admin_menu() {
    add_menu_page(
        'Custom Notifications and Alerts',  // Page title
        'Notifications & Alerts',           // Menu title
        'manage_options',                   // Capability
        'custom-notifications-alerts',      // Menu slug
        'cna_settings_page'                 // Function to display the settings page
    );
}

// Function to display the settings page
function cna_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Notifications and Alerts</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cna_settings_group' );
            do_settings_sections( 'custom-notifications-alerts' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Hook to initialize settings
add_action( 'admin_init', 'cna_settings_init' );

// Function to initialize settings
function cna_settings_init() {
    register_setting( 'cna_settings_group', 'cna_notification_message' );
    register_setting( 'cna_settings_group', 'cna_notification_type' );
    register_setting( 'cna_settings_group', 'cna_user_roles' );
    register_setting( 'cna_settings_group', 'cna_show_to_guests' );

    add_settings_section(
        'cna_settings_section',
        'Notification Settings',
        'cna_settings_section_callback',
        'custom-notifications-alerts'
    );

    add_settings_field(
        'cna_notification_message',
        'Notification Message',
        'cna_notification_message_render',
        'custom-notifications-alerts',
        'cna_settings_section'
    );

    add_settings_field(
        'cna_notification_type',
        'Notification Type',
        'cna_notification_type_render',
        'custom-notifications-alerts',
        'cna_settings_section'
    );

    add_settings_field(
        'cna_user_roles',
        'Target User Roles',
        'cna_user_roles_render',
        'custom-notifications-alerts',
        'cna_settings_section'
    );

    add_settings_field(
        'cna_show_to_guests',
        'Show to Guests',
        'cna_show_to_guests_render',
        'custom-notifications-alerts',
        'cna_settings_section'
    );
}

// Callback function for settings section
function cna_settings_section_callback() {
    echo 'Configure your custom notification settings below:';
}

// Function to render the notification message input field
function cna_notification_message_render() {
    $message = get_option( 'cna_notification_message' );
    ?>
    <textarea name="cna_notification_message" rows="5" cols="50"><?php echo esc_textarea( $message ); ?></textarea>
    <?php
}

// Function to render the notification type dropdown
function cna_notification_type_render() {
    $type = get_option( 'cna_notification_type', 'info' );
    ?>
    <select name="cna_notification_type">
        <option value="info" <?php selected( $type, 'info' ); ?>>Info</option>
        <option value="warning" <?php selected( $type, 'warning' ); ?>>Warning</option>
        <option value="error" <?php selected( $type, 'error' ); ?>>Error</option>
    </select>
    <?php
}

// Function to render the user roles checkboxes
function cna_user_roles_render() {
    $roles = get_option( 'cna_user_roles', array() );
    $all_roles = wp_roles()->roles;
    foreach ( $all_roles as $role_key => $role ) {
        ?>
        <label>
            <input type="checkbox" name="cna_user_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $roles ) ); ?>>
            <?php echo esc_html( $role['name'] ); ?>
        </label><br>
        <?php
    }
}

// Function to render the show to guests checkbox
function cna_show_to_guests_render() {
    $show_to_guests = get_option( 'cna_show_to_guests', false );
    ?>
    <label>
        <input type="checkbox" name="cna_show_to_guests" value="1" <?php checked( $show_to_guests, 1 ); ?>>
        Show notification to guest users (not logged in)
    </label>
    <?php
}

// Hook to display notification on the front end
add_action( 'wp_footer', 'cna_display_notification' );

// Function to display notification on the front end
function cna_display_notification() {
    $message = get_option( 'cna_notification_message' );
    $type = get_option( 'cna_notification_type', 'info' );
    $roles = get_option( 'cna_user_roles', array() );
    $show_to_guests = get_option( 'cna_show_to_guests', false );

    if ( ! empty( $message ) ) {
        $display_notification = false;

        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            foreach ( $user->roles as $user_role ) {
                if ( in_array( $user_role, $roles ) ) {
                    $display_notification = true;
                    break;
                }
            }
        } elseif ( $show_to_guests ) {
            $display_notification = true;
        }

        if ( $display_notification ) {
            echo '<div id="cna-notification" class="cna-notification-' . esc_attr( $type ) . '">';
            echo '<span>' . esc_html( $message ) . '</span>';
            echo '<button id="cna-dismiss" style="margin-left: 10px;">Dismiss</button>';
            echo '</div>';
        }
    }
}

// Hook to enqueue custom styles and scripts
add_action( 'wp_enqueue_scripts', 'cna_enqueue_assets' );

// Function to enqueue custom styles and scripts
function cna_enqueue_assets() {
    wp_enqueue_style( 'cna-custom-styles', plugin_dir_url( __FILE__ ) . 'styles.css' );
    wp_enqueue_script( 'cna-custom-scripts', plugin_dir_url( __FILE__ ) . 'scripts.js', array('jquery'), null, true );
}

// Hook for plugin activation
register_activation_hook( __FILE__, 'cna_activate_plugin' );

// Function to run on activation
function cna_activate_plugin() {
    if ( ! current_user_can( 'activate_plugins' ) ) return;

    // Default notification message and settings
    add_option( 'cna_notification_message', 'This is a default notification message.' );
    add_option( 'cna_notification_type', 'info' );
    add_option( 'cna_user_roles', array() );
    add_option( 'cna_show_to_guests', false );
}

// Hook for plugin deactivation
register_deactivation_hook( __FILE__, 'cna_deactivate_plugin' );

// Function to run on deactivation
function cna_deactivate_plugin() {
    if ( ! current_user_can( 'activate_plugins' ) ) return;

    // Optionally, you can delete the option on deactivation
    // delete_option( 'cna_notification_message' );
    // delete_option( 'cna_notification_type' );
    // delete_option( 'cna_user_roles' );
    // delete_option( 'cna_show_to_guests' );
}
?>