<?php
/**
 * Plugin Name: WP Proxy Updater
 * Description: Configures a proxy server to allow installing and updating plugins/themes from WordPress.org, even when blocked by certain hosting providers.
 * Version: 1.1
 * Author: Globerunner
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Function to create the settings page
function wp_proxy_updater_menu() {
    add_options_page(
        'WP Proxy Updater', 
        'WP Proxy Updater', 
        'manage_options', 
        'wp-proxy-updater', 
        'wp_proxy_updater_settings_page'
    );
}
add_action('admin_menu', 'wp_proxy_updater_menu');

// Display the settings page
function wp_proxy_updater_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP Proxy Updater Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_proxy_updater_settings');
            do_settings_sections('wp-proxy-updater');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function wp_proxy_updater_register_settings() {
    register_setting('wp_proxy_updater_settings', 'wp_proxy_host');
    register_setting('wp_proxy_updater_settings', 'wp_proxy_port');
    register_setting('wp_proxy_updater_settings', 'wp_proxy_username');
    register_setting('wp_proxy_updater_settings', 'wp_proxy_password');
    
    add_settings_section('wp_proxy_updater_section', '', null, 'wp-proxy-updater');
    
    add_settings_field(
        'wp_proxy_host', 
        'Proxy Host', 
        'wp_proxy_updater_host_callback', 
        'wp-proxy-updater', 
        'wp_proxy_updater_section'
    );
    
    add_settings_field(
        'wp_proxy_port', 
        'Proxy Port', 
        'wp_proxy_updater_port_callback', 
        'wp-proxy-updater', 
        'wp_proxy_updater_section'
    );
    
    add_settings_field(
        'wp_proxy_username', 
        'Proxy Username', 
        'wp_proxy_updater_username_callback', 
        'wp-proxy-updater', 
        'wp_proxy_updater_section'
    );
    
    add_settings_field(
        'wp_proxy_password', 
        'Proxy Password', 
        'wp_proxy_updater_password_callback', 
        'wp-proxy-updater', 
        'wp_proxy_updater_section'
    );
}
add_action('admin_init', 'wp_proxy_updater_register_settings');

// Callbacks for each field
function wp_proxy_updater_host_callback() {
    $host = get_option('wp_proxy_host');
    echo '<input type="text" name="wp_proxy_host" value="' . esc_attr($host) . '" />';
}

function wp_proxy_updater_port_callback() {
    $port = get_option('wp_proxy_port');
    echo '<input type="text" name="wp_proxy_port" value="' . esc_attr($port) . '" />';
}

function wp_proxy_updater_username_callback() {
    $username = get_option('wp_proxy_username');
    echo '<input type="text" name="wp_proxy_username" value="' . esc_attr($username) . '" />';
}

function wp_proxy_updater_password_callback() {
    $password = get_option('wp_proxy_password');
    echo '<input type="password" name="wp_proxy_password" value="' . esc_attr($password) . '" />';
}

// Apply proxy settings to HTTP requests
add_action('init', function() {
    $proxy_host = get_option('wp_proxy_host');
    $proxy_port = get_option('wp_proxy_port');
    $proxy_username = get_option('wp_proxy_username');
    $proxy_password = get_option('wp_proxy_password');

    if ($proxy_host && $proxy_port) {
        // Set proxy server for HTTP requests
        add_filter('pre_http_send_through_proxy', function($send, $uri) use ($proxy_host, $proxy_port, $proxy_username, $proxy_password) {
            // Apply proxy only for requests to wordpress.org
            if (strpos($uri, 'wordpress.org') !== false) {
                return true;
            }
            return false;
        }, 10, 2);
        
        // Configure proxy details
        add_filter('http_request_args', function($args) use ($proxy_host, $proxy_port, $proxy_username, $proxy_password) {
            if ($proxy_username && $proxy_password) {
                // If proxy username and password are set, include them
                $args['proxy'] = array(
                    'host' => $proxy_host,
                    'port' => $proxy_port,
                    'username' => $proxy_username,
                    'password' => $proxy_password,
                );
            } else {
                // Without authentication
                $args['proxy'] = array(
                    'host' => $proxy_host,
                    'port' => $proxy_port,
                );
            }
            return $args;
        }, 10, 1);
    }
});

