<?php
namespace Google_Maps_For_Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Google_Maps_Widget extends Widget_Base
{
    public function get_name()
    {
        return 'google_maps_widget';
    }

    public function get_title()
    {
        return __('Advanced Google Maps', 'advanced-google-maps-for-elementor');
    }

    public function get_icon()
    {
        return 'eicon-google-maps';
    }

    public function get_categories()
    {
        return ['advanced-google-maps-for-elementor'];
    }

    public function get_style_depends()
    {
        return ['gme-widget-style'];
    }

    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        $version = GME_VERSION ?? '1.0.0';
        // Register Axios
        wp_register_script('axios', plugins_url('/assets/js/lib/axios.min.js', __FILE__), array(), $version, true);

        // Register widget styles with version
        wp_register_style(
            'gme-widget-style',
            false,
            array(),
            $version
        );

        wp_add_inline_style('gme-widget-style', '
            .gme-map-wrapper {
                position: relative;
                width: 100%;
            }
            .gme-map-placeholder {
                background-color: #f7f7f7;
                border: 1px dashed #d5dadf;
                border-radius: 3px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .gme-map-info {
                text-align: center;
                padding: 20px;
                color: #6d7882;
            }
            .gme-map-info .eicon-google-maps {
                font-size: 32px;
                margin-bottom: 10px;
            }
            .gme-map-notice {
                margin-bottom: 5px;
            }
            .gme-map-locations {
                font-size: 12px;
                font-style: italic;
            }
        ');
    }

    protected function _register_controls()
    {
        // Advanced Options: Locations Tab
        $this->start_controls_section(
            'section_locations',
            [
                'label' => __('Locations', 'advanced-google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT, // changed from TAB_LAYOUT to TAB_CONTENT
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'location_name',
            [
                'label' => __('Location Name', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => __('New Location', 'advanced-google-maps-for-elementor'),
            ]
        );

        $repeater->add_control(
            'position_type',
            [
                'label' => __('Position Type', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'coordinates',
                'options' => [
                    'coordinates' => __('Coordinates', 'advanced-google-maps-for-elementor'),
                    'address' => __('Address', 'advanced-google-maps-for-elementor'),
                ],
            ]
        );

        $repeater->add_control(
            'address',
            [
                'label' => __('Address', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
                'description' => __('Enter a full address to automatically get coordinates', 'advanced-google-maps-for-elementor'),
                'condition' => [
                    'position_type' => 'address',
                ],
            ]
        );

        $repeater->add_control(
            'lat',
            [
                'label' => __('Latitude', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'position_type' => 'coordinates',
                ],
            ]
        );

        $repeater->add_control(
            'lng',
            [
                'label' => __('Longitude', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'dynamic' => [
                    'active' => true,
                ],
                'condition' => [
                    'position_type' => 'coordinates',
                ],
            ]
        );

        $this->add_control(
            'locations',
            [
                'label' => __('Locations', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ location_name }}}',
            ]
        );

        $this->end_controls_section(); // End of Locations section

        // New Content Tab: Center Options
        $this->start_controls_section(
            'section_center',
            [
                'label' => __('Center Options', 'advanced-google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'center_type',
            [
                'label' => __('Center Type', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => __('Auto', 'advanced-google-maps-for-elementor'),
                    'manual' => __('Manual', 'advanced-google-maps-for-elementor'),
                ],
            ]
        );

        $this->add_control(
            'center_lat',
            [
                'label' => __('Center Latitude', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => ['center_type' => 'manual'],
            ]
        );

        $this->add_control(
            'center_lng',
            [
                'label' => __('Center Longitude', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => ['center_type' => 'manual'],
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label' => __('Zoom', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
            ]
        );

        $this->end_controls_section();

        // New Advanced Configuration Tab
        $this->start_controls_section(
            'section_advanced_config',
            [
                'label' => __('Advanced Configuration', 'advanced-google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Add documentation link
        $this->add_control(
            'advanced_options_docs',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<small><a href="https://developers.google.com/maps/documentation/javascript/reference/map#MapOptions" target="_blank">' . esc_html__('View Google Maps Options Documentation »', 'advanced-google-maps-for-elementor') . '</a></small>',
                'separator' => 'after',
            ]
        );

        $this->add_control(
            'rendering_type',
            [
                'label' => __('Rendering Type', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'RASTER',
                'options' => [
                    'RASTER' => __('Raster', 'advanced-google-maps-for-elementor'),
                    'UNSPECIFIED' => __('Unspecified', 'advanced-google-maps-for-elementor'),
                    'VECTOR' => __('Vector', 'advanced-google-maps-for-elementor'),
                ],
                'description' => __('Choose how the map should be rendered.', 'advanced-google-maps-for-elementor'),
            ]
        );

        $this->add_control(
            'tilt',
            [
                'label' => __('Tilt', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 45,
                'description' => __('Controls the angle of 45° imagery, value from 0 to 45.', 'advanced-google-maps-for-elementor'),
            ]
        );

        $this->add_control(
            'tilt_interaction_enabled',
            [
                'label' => __('Enable Tilt Interaction', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Allow users to change the tilt.', 'advanced-google-maps-for-elementor'),
            ]
        );

        $this->add_control(
            'restriction_enabled',
            [
                'label' => __('Enable Map Restrictions', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'restriction_lat_north',
            [
                'label' => __('North Latitude Bound', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 85,
                'condition' => ['restriction_enabled' => 'yes'],
            ]
        );

        $this->add_control(
            'restriction_lat_south',
            [
                'label' => __('South Latitude Bound', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => -85,
                'condition' => ['restriction_enabled' => 'yes'],
            ]
        );

        $this->add_control(
            'restriction_lng_east',
            [
                'label' => __('East Longitude Bound', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 180,
                'condition' => ['restriction_enabled' => 'yes'],
            ]
        );

        $this->add_control(
            'restriction_lng_west',
            [
                'label' => __('West Longitude Bound', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => -180,
                'condition' => ['restriction_enabled' => 'yes'],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'description' => __('Set a custom background color for the map. Leave empty for default.', 'advanced-google-maps-for-elementor'),
            ]
        );

        $this->add_control(
            'disable_default_ui',
            [
                'label' => __('Disable Default UI', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'camera_control',
            [
                'label' => __('Camera Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'fullscreen_control',
            [
                'label' => __('Fullscreen Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'clickable_icons',
            [
                'label' => __('Clickable Icons', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'color_scheme',
            [
                'label' => __('Color Scheme', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'light',
                'options' => [
                    'light' => __('Light', 'advanced-google-maps-for-elementor'),
                    'dark' => __('Dark', 'advanced-google-maps-for-elementor'),
                ],
            ]
        );

        $this->add_control(
            'disable_double_click_zoom',
            [
                'label' => __('Disable Double Click Zoom', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'gesture_handling',
            [
                'label' => __('Gesture Handling', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => __('Auto', 'advanced-google-maps-for-elementor'),
                    'none' => __('None', 'advanced-google-maps-for-elementor'),
                    'cooperative' => __('Cooperative', 'advanced-google-maps-for-elementor'),
                    'greedy' => __('Greedy', 'advanced-google-maps-for-elementor'),
                ],
            ]
        );

        $this->add_control(
            'keyboard_shortcuts',
            [
                'label' => __('Keyboard Shortcuts', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'max_zoom',
            [
                'label' => __('Maximum Zoom Level', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'min' => 1,
                'max' => 20,
            ]
        );

        $this->add_control(
            'min_zoom',
            [
                'label' => __('Minimum Zoom Level', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 20,
            ]
        );

        $this->add_control(
            'rotate_control',
            [
                'label' => __('Rotate Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'scale_control',
            [
                'label' => __('Scale Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'scrollwheel',
            [
                'label' => __('Scroll Wheel Zoom', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'zoom_control',
            [
                'label' => __('Zoom Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'street_view',
            [
                'label' => __('Street View', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'street_view_control',
            [
                'label' => __('Street View Control', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // New Style Tab: Map Style
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Map Style', 'advanced-google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'map_type',
            [
                'label' => __('Map Type', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'roadmap',
                'options' => [
                    'roadmap' => __('Roadmap', 'advanced-google-maps-for-elementor'),
                    'satellite' => __('Satellite', 'advanced-google-maps-for-elementor'),
                    'hybrid' => __('Hybrid', 'advanced-google-maps-for-elementor'),
                    'terrain' => __('Terrain', 'advanced-google-maps-for-elementor'),
                ],
            ]
        );

        $this->add_responsive_control(
            'map_width',
            [
                'label' => __('Map Width', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '100%',
                'selectors' => [
                    '{{WRAPPER}} #gme-map' => 'width: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'map_height',
            [
                'label' => __('Map Height', 'advanced-google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '400px',
                'selectors' => [
                    '{{WRAPPER}} #gme-map' => 'height: {{VALUE}};',
                ],
            ]
        );

        // Default Elementor Border group control remains
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'map_border',
                'label' => __('Border', 'advanced-google-maps-for-elementor'),
                'selector' => '{{WRAPPER}} #gme-map',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $map_id = 'gme-map-' . $this->get_id();

        // Enqueue Axios
        wp_enqueue_script('axios');

        // Calculate center based on center_type
        if ('manual' === $settings['center_type'] && !empty($settings['center_lat']) && !empty($settings['center_lng'])) {
            $center_lat = $settings['center_lat'];
            $center_lng = $settings['center_lng'];
        } else {
            $locations = $settings['locations'];
            if (!empty($locations)) {
                $total_lat = 0;
                $total_lng = 0;
                $count = 0;
                foreach ($locations as $location) {
                    if ($location['position_type'] === 'coordinates' && !empty($location['lat']) && !empty($location['lng'])) {
                        $total_lat += floatval($location['lat']);
                        $total_lng += floatval($location['lng']);
                        $count++;
                    } elseif ($location['position_type'] === 'address' && !empty($location['address'])) {
                        // For addresses, we'll set a temporary center that will be updated by JavaScript
                        // This ensures the map has an initial center point
                        if ($count === 0) {
                            $center_lat = -34.397;
                            $center_lng = 150.644;
                        }
                    }
                }
                if ($count > 0) {
                    $center_lat = $total_lat / $count;
                    $center_lng = $total_lng / $count;
                } elseif (!isset($center_lat)) {
                    // Default center if no valid locations found
                    $center_lat = -34.397;
                    $center_lng = 150.644;
                }
            } else {
                $center_lat = -34.397;
                $center_lng = 150.644;
            }
        }

        $map_data = [
            'mapId' => $map_id,
            'center' => [
                'lat' => floatval($center_lat),
                'lng' => floatval($center_lng)
            ],
            'zoom' => intval($settings['zoom']),
            'locations' => array_map(function ($location) {
                return [
                    'name' => $location['location_name'],
                    'position_type' => $location['position_type'],
                    'address' => $location['address'],
                    'lat' => floatval($location['lat']),
                    'lng' => floatval($location['lng'])
                ];
            }, $settings['locations']),
            'backgroundColor' => !empty($settings['background_color']) ? $settings['background_color'] : null,
            'disableDefaultUI' => $settings['disable_default_ui'] === 'yes',
            'cameraControl' => $settings['camera_control'] === 'yes',
            'fullscreenControl' => $settings['fullscreen_control'] === 'yes',
            'clickableIcons' => $settings['clickable_icons'] === 'yes',
            'colorScheme' => $settings['color_scheme'],
            'disableDoubleClickZoom' => $settings['disable_double_click_zoom'] === 'yes',
            'gestureHandling' => $settings['gesture_handling'],
            'keyboardShortcuts' => $settings['keyboard_shortcuts'] === 'yes',
            'maxZoom' => intval($settings['max_zoom']),
            'minZoom' => intval($settings['min_zoom']),
            'rotateControl' => $settings['rotate_control'] === 'yes',
            'scaleControl' => $settings['scale_control'] === 'yes',
            'scrollwheel' => $settings['scrollwheel'] === 'yes',
            'zoomControl' => $settings['zoom_control'] === 'yes',
            'streetView' => $settings['street_view'] === 'yes',
            'streetViewControl' => $settings['street_view_control'] === 'yes',
            // Add new configuration options
            'renderingType' => $settings['rendering_type'],
            'mapTypeId' => $settings['map_type'],
            'tilt' => intval($settings['tilt']),
            'tiltInteractionEnabled' => $settings['tilt_interaction_enabled'] === 'yes',
            'restriction' => $settings['restriction_enabled'] === 'yes' ? [
                'latLngBounds' => [
                    'north' => floatval($settings['restriction_lat_north']),
                    'south' => floatval($settings['restriction_lat_south']),
                    'east' => floatval($settings['restriction_lng_east']),
                    'west' => floatval($settings['restriction_lng_west'])
                ],
                'strictBounds' => true
            ] : null
        ];

        $style = 'width: ' . $settings['map_width'] . '; height: ' . $settings['map_height'] . ';';

        // Get API key for geocoding
        $api_key = get_option('gme_api_key', '');
        ?>
        <script>
            window.gmeApiKey = '<?php echo esc_js($api_key); ?>';
        </script>
        <div class="gme-map-wrapper">
            <div id="<?php echo esc_attr($map_id); ?>" class="gme-map" style="<?php echo esc_attr($style); ?>"
                data-map-settings="<?php echo esc_attr(json_encode($map_data)); ?>">
                <div class="gme-map-placeholder" style="<?php echo esc_attr($style); ?>">
                    <div class="gme-map-info">
                        <i class="eicon-google-maps" aria-hidden="true"></i>
                        <div class="gme-map-notice">
                            <?php esc_html_e('Google Map will be displayed here', 'advanced-google-maps-for-elementor'); ?>
                        </div>
                        <?php if (!empty($settings['locations'])): ?>
                            <div class="gme-map-locations">
                                <?php
                                echo esc_html(sprintf(
                                    /* translators: %d: Number of locations added to the map */
                                    __('Locations added: %d', 'advanced-google-maps-for-elementor'),
                                    count($settings['locations'])
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php

        wp_enqueue_script('gme-maps-init', plugins_url('../assets/js/map.js', __FILE__), array('gme-google-maps-loader'), GME_VERSION, true);
    }

    protected function _content_template()
    {
        ?>
        <# var mapId='elementor-preview-' + view.getID(); var style='width: ' + settings.map_width + '; height: ' +
            settings.map_height + ';' ; #>
            <div class="gme-map-wrapper">
                <div id="{{ mapId }}" class="gme-map" style="{{ style }}">
                    <div class="gme-map-placeholder" style="{{ style }}">
                        <div class="gme-map-info">
                            <i class="eicon-google-maps" aria-hidden="true"></i>
                            <div class="gme-map-notice">
                                <?php esc_html_e('Google Map will be displayed here', 'advanced-google-maps-for-elementor'); ?>
                            </div>
                            <# if (settings.locations && settings.locations.length> 0) { #>
                                <div class="gme-map-locations">
                                    <?php
                                    /* translators: %d: Number of locations added to the map */
                                    echo esc_html__('Locations added: %d', 'advanced-google-maps-for-elementor'); ?>
                                    {{
                                    settings.locations.length }}
                                </div>
                                <# } #>
                        </div>
                    </div>
                </div>
            </div>
            <?php
    }
}
