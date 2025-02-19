<?php
/**
 * Plugin Name: Google Maps For Elementor
 * Description: A simple Google Maps integration for Elementor using Google JavaScript API.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function gme_enqueue_scripts()
{
    // Get the API key from the options
    $api_key = get_option('gme_api_key', 'YOUR_GOOGLE_MAPS_API_KEY');

    // Enqueue Google Maps API with your API key
    wp_register_script('gme-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key), array(), null, true);
    wp_enqueue_script('gme-google-maps');

    // Enqueue custom maps script that depends on Google Maps API
    // wp_register_script('gme-maps', plugins_url('assets/js/maps.js', __FILE__), array('gme-google-maps'), '1.0.0', true);
    // wp_enqueue_script('gme-maps');
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
        'Google Maps Settings',
        'Google Maps Settings',
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
            echo '<div class="updated"><p>API Key updated successfully.</p></div>';
        }
    }

    $api_key = get_option('gme_api_key', '');
    ?>
    <div class="wrap">
        <h1>Google Maps API Key</h1>
        <form method="post" action="">
            <?php wp_nonce_field('gme_update_api_key'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="gme_api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                </tr>
            </table>
            <?php submit_button('Save API Key', 'primary', 'gme_api_key_submit'); ?>
        </form>
    </div>
    <?php
}
?>