<?php
/**
 * Plugin Name: Google Maps For Elementor
 * Description: A simple Google Maps integration for Elementor using Google JavaScript API.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: google-maps-for-elementor
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin text domain for translations
function gme_load_textdomain()
{
    load_plugin_textdomain('google-maps-for-elementor', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'gme_load_textdomain');

function gme_enqueue_scripts()
{
    // Get the API key from the options
    $api_key = get_option('gme_api_key', 'YOUR_GOOGLE_MAPS_API_KEY');

    // Register and enqueue Google Maps API with geocoding library
    wp_register_script('gme-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=geocoding', array(), null, true);
    wp_enqueue_script('gme-google-maps');

    // Register and enqueue our map initialization script
    wp_register_script('gme-maps-init', plugins_url('/assets/js/map.js', __FILE__), array('gme-google-maps', 'axios'), null, true);
    wp_enqueue_script('gme-maps-init');
}
add_action('wp_enqueue_scripts', 'gme_enqueue_scripts');

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
        __('Google Maps Settings', 'google-maps-for-elementor'),
        __('Google Maps Settings', 'google-maps-for-elementor'),
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
            update_option('gme_api_key', sanitize_text_field($_POST['gme_api_key']));
            echo '<div class="updated"><p>' . esc_html__('API Key updated successfully.', 'google-maps-for-elementor') . '</p></div>';
        }
    }

    $api_key = get_option('gme_api_key', '');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Google Maps API Key', 'google-maps-for-elementor'); ?></h1>
        <div class="notice notice-info">
            <p><?php esc_html_e('Important: Make sure to enable the following APIs in your Google Cloud Console:', 'google-maps-for-elementor'); ?>
            </p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><?php esc_html_e('Maps JavaScript API', 'google-maps-for-elementor'); ?></li>
                <li><?php esc_html_e('Geocoding API - Required for address to coordinates conversion', 'google-maps-for-elementor'); ?>
                </li>
            </ul>
            <p><?php printf(
                /* translators: %s: Google Cloud Console URL */
                esc_html__('Visit %s to enable these APIs for your project.', 'google-maps-for-elementor'),
                '<a href="https://console.cloud.google.com/apis/library" target="_blank">Google Cloud Console</a>'
            ); ?></p>
        </div>
        <form method="post" action="">
            <?php wp_nonce_field('gme_update_api_key'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('API Key', 'google-maps-for-elementor'); ?></th>
                    <td>
                        <input type="text" name="gme_api_key" value="<?php echo esc_attr($api_key); ?>" size="50" />
                        <p class="description">
                            <?php esc_html_e('Enter your Google Maps API key. Both Maps JavaScript API and Geocoding API must be enabled.', 'google-maps-for-elementor'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save API Key', 'google-maps-for-elementor'), 'primary', 'gme_api_key_submit'); ?>
        </form>
    </div>
    <?php
}
?>