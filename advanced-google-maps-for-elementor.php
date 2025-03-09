<?php
/**
 * Plugin Name: Advanced Google Maps For Elementor
 * Description: A simple Google Maps integration for Elementor using Google JavaScript API.
 * Version: 1.0.0
 * Author: Kamil Suchocki
 * Author URI: https://www.linkedin.com/in/kamil-suchocki-772862240/
 * Text Domain: advanced-google-maps-for-elementor
 * Domain Path: /languages
 * License: GPLv2 or later
 * 
 * 
 *  * Advanced Google Maps For Elementor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Advanced Google Maps For Elementor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 
 */



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin version
if (!defined('GME_VERSION')) {
    define('GME_VERSION', '1.0.0');
}

// Load plugin text domain for translations
function gme_load_textdomain()
{
    load_plugin_textdomain('advanced-google-maps-for-elementor', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'gme_load_textdomain');


function gme_enqueue_scripts()
{
    // Get the API key from the options
    $api_key = get_option('gme_api_key', 'YOUR_GOOGLE_MAPS_API_KEY');
    $version = GME_VERSION ?? '1.0.0'; // Add plugin version constant

    // Register and enqueue Google Maps API with geocoding library
    wp_register_script('gme-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=places&callback=gmeInitCallback&loading=async', array(), $version, true);
    wp_enqueue_script('gme-google-maps');

    // Register Axios from local file
    wp_register_script('axios', plugins_url('/assets/js/lib/axios.min.js', __FILE__), array(), $version, true);

    // Register and enqueue our map initialization script
    wp_register_script('gme-maps-init', plugins_url('/assets/js/map.js', __FILE__), array('gme-google-maps', 'axios'), $version, true);
    wp_enqueue_script('gme-maps-init');

    // Localize script with translations
    wp_localize_script('gme-maps-init', 'gmeL10n', array(
        'error_loading_map' => __('Error loading map. Please try again later.', 'advanced-google-maps-for-elementor'),
    ));
}
add_action('wp_enqueue_scripts', 'gme_enqueue_scripts');

// Enqueue admin scripts
function gme_enqueue_admin_scripts($hook)
{
    if ('toplevel_page_gme-settings' !== $hook) {
        return;
    }

    $version = GME_VERSION ?? '1.0.0'; // Add plugin version constant
    wp_enqueue_script('gme-admin-js', plugins_url('/assets/js/admin.js', __FILE__), array('jquery'), $version, true);
    wp_localize_script('gme-admin-js', 'gmeAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gme-verify-api-key'),
        'verifying' => __('Verifying...', 'advanced-google-maps-for-elementor'),
        'success' => __('API key is valid!', 'advanced-google-maps-for-elementor'),
        'mapsApiError' => __('Maps JavaScript API error:', 'advanced-google-maps-for-elementor'),
        'geocodingApiError' => __('Geocoding API error:', 'advanced-google-maps-for-elementor'),
    ));
}
add_action('admin_enqueue_scripts', 'gme_enqueue_admin_scripts');

// AJAX handler for API key verification
function gme_verify_api_key()
{
    check_ajax_referer('gme-verify-api-key', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'advanced-google-maps-for-elementor'));
        return;
    }

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

    if (empty($api_key)) {
        wp_send_json_error(__('API key cannot be empty.', 'advanced-google-maps-for-elementor'));
        return;
    }

    // Check Maps JavaScript API
    $maps_api_url = 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&callback=Function.prototype';
    $maps_response = wp_remote_get($maps_api_url);

    // Check Geocoding API
    $geocoding_test_address = 'New York, NY';
    $geocoding_api_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($geocoding_test_address) . '&key=' . $api_key;
    $geocoding_response = wp_remote_get($geocoding_api_url);

    $result = array(
        'maps_api' => array(
            'status' => 'error',
            'message' => ''
        ),
        'geocoding_api' => array(
            'status' => 'error',
            'message' => ''
        )
    );

    // Check Maps JavaScript API response
    if (!is_wp_error($maps_response)) {
        $status_code = wp_remote_retrieve_response_code($maps_response);
        $body = wp_remote_retrieve_body($maps_response);

        if ($status_code === 200 && !strpos($body, 'InvalidKeyMapError') && !strpos($body, 'RefererNotAllowedMapError')) {
            $result['maps_api']['status'] = 'success';
            $result['maps_api']['message'] = __('Maps JavaScript API is working correctly.', 'advanced-google-maps-for-elementor');
        } else {
            $result['maps_api']['message'] = __('Invalid API key or domain not authorized.', 'advanced-google-maps-for-elementor');
        }
    } else {
        $result['maps_api']['message'] = $maps_response->get_error_message();
    }

    // Check Geocoding API response
    if (!is_wp_error($geocoding_response)) {
        $status_code = wp_remote_retrieve_response_code($geocoding_response);
        $body = wp_remote_retrieve_body($geocoding_response);
        $data = json_decode($body, true);

        if ($status_code === 200 && isset($data['status'])) {
            if ($data['status'] === 'OK') {
                $result['geocoding_api']['status'] = 'success';
                $result['geocoding_api']['message'] = __('Geocoding API is working correctly.', 'advanced-google-maps-for-elementor');
            } else {
                // translators: %s: The error status returned by the Geocoding API
                $result['geocoding_api']['message'] = sprintf(__('Geocoding API error: %s', 'advanced-google-maps-for-elementor'), $data['status']);
            }
        } else {
            $result['geocoding_api']['message'] = __('Invalid response from Geocoding API.', 'advanced-google-maps-for-elementor');
        }
    } else {
        $result['geocoding_api']['message'] = $geocoding_response->get_error_message();
    }

    if ($result['maps_api']['status'] === 'success' && $result['geocoding_api']['status'] === 'success') {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_gme_verify_api_key', 'gme_verify_api_key');

// Add a new Elementor widget category
function gme_add_elementor_widget_categories($elements_manager)
{
    $elements_manager->add_category(
        'advanced-google-maps-for-elementor',
        [
            'title' => __('Google Maps', 'advanced-google-maps-for-elementor'),
            'icon' => 'eicon-google-maps',
        ]
    );
}
add_action('elementor/elements/categories_registered', 'gme_add_elementor_widget_categories');

// Elementor integration - register widget
function gme_register_elementor_widget()
{
    // Check if Elementor is active
    if (defined('ELEMENTOR_PATH') && class_exists('\Elementor\Plugin')) {
        require_once(plugin_dir_path(__FILE__) . 'widgets/google-maps-widget.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Google_Maps_For_Elementor\Google_Maps_Widget());
    }
}
add_action('elementor/widgets/widgets_registered', 'gme_register_elementor_widget');

// Admin Menu: Add new tab for API key settings
function gme_register_admin_menu()
{
    add_menu_page(
        __('Google Maps Settings', 'advanced-google-maps-for-elementor'),
        __('Google Maps Settings', 'advanced-google-maps-for-elementor'),
        'manage_options',
        'gme-settings',
        'gme_settings_page',
        'dashicons-admin-site',
        81
    );
}
add_action('admin_menu', 'gme_register_admin_menu');

function gme_settings_page()
{
    // Process form submission
    if (isset($_POST['gme_api_key_submit'])) {
        if (check_admin_referer('gme_update_api_key')) {
            if (isset($_POST['gme_api_key'])) {
                update_option('gme_api_key', sanitize_text_field(wp_unslash($_POST['gme_api_key'])));
                echo '<div class="updated"><p>' . esc_html__('API Key updated successfully.', 'advanced-google-maps-for-elementor') . '</p></div>';
            }
        }
    }

    $api_key = get_option('gme_api_key', '');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Google Maps API Key', 'advanced-google-maps-for-elementor'); ?></h1>
        <div class="notice notice-info">
            <p><?php esc_html_e('Important: Make sure to enable the following APIs in your Google Cloud Console:', 'advanced-google-maps-for-elementor'); ?>
            </p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><?php esc_html_e('Maps JavaScript API', 'advanced-google-maps-for-elementor'); ?></li>
                <li><?php esc_html_e('Geocoding API - Required for address to coordinates conversion', 'advanced-google-maps-for-elementor'); ?>
                </li>
            </ul>
            <p><?php printf(
                /* translators: %s: Google Cloud Console URL */
                esc_html__('Visit %s to enable these APIs for your project.', 'advanced-google-maps-for-elementor'),
                '<a href="https://console.cloud.google.com/apis/library" target="_blank">Google Cloud Console</a>'
            ); ?></p>
        </div>
        <form method="post" action="">
            <?php wp_nonce_field('gme_update_api_key'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('API Key', 'advanced-google-maps-for-elementor'); ?></th>
                    <td>
                        <input type="text" id="gme_api_key" name="gme_api_key" value="<?php echo esc_attr($api_key); ?>"
                            size="50" />
                        <p class="description">
                            <?php esc_html_e('Enter your Google Maps API key. Both Maps JavaScript API and Geocoding API must be enabled.', 'advanced-google-maps-for-elementor'); ?>
                        </p>
                        <button type="button" id="gme_verify_api_key" class="button button-secondary">
                            <?php esc_html_e('Verify API Key', 'advanced-google-maps-for-elementor'); ?>
                        </button>
                        <div id="gme_api_validation_results" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save API Key', 'advanced-google-maps-for-elementor'), 'primary', 'gme_api_key_submit'); ?>
        </form>
    </div>
    <?php
}
?>