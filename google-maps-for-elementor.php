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

    // Remove the old Google Maps API registration
    // wp_register_script('gme-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key), array(), null, true);
    // wp_enqueue_script('gme-google-maps');

    // Register loader script and add inline module import code
    wp_register_script('gme-google-maps-loader', '', array(), null, true);
    $module_script = '
(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: "' . esc_attr($api_key) . '",
    v: "weekly",
    libraries: ["maps, maker"]
  });
';
    wp_add_inline_script('gme-google-maps-loader', $module_script);
    wp_enqueue_script('gme-google-maps-loader');
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
        <form method="post" action="">
            <?php wp_nonce_field('gme_update_api_key'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('API Key', 'google-maps-for-elementor'); ?></th>
                    <td><input type="text" name="gme_api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                </tr>
            </table>
            <?php submit_button(__('Save API Key', 'google-maps-for-elementor'), 'primary', 'gme_api_key_submit'); ?>
        </form>
    </div>
    <?php
}
?>