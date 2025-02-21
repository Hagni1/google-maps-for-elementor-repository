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
        return __('Advanced Google Maps', 'google-maps-for-elementor');
    }

    public function get_icon()
    {
        return 'eicon-google-maps';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function _register_controls()
    {
        // ...existing basic controls... 

        // Advanced Options: Locations Tab
        $this->start_controls_section(
            'section_locations',
            [
                'label' => __('Locations', 'google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT, // changed from TAB_LAYOUT to TAB_CONTENT
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'location_name',
            [
                'label' => __('Location Name', 'google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => __('New Location', 'google-maps-for-elementor'),
            ]
        );

        $repeater->add_control(
            'lat',
            [
                'label' => __('Latitude', 'google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $repeater->add_control(
            'lng',
            [
                'label' => __('Longitude', 'google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'locations',
            [
                'label' => __('Locations', 'google-maps-for-elementor'),
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
                'label' => __('Center Options', 'google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'center_type',
            [
                'label' => __('Center Type', 'google-maps-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => __('Auto', 'google-maps-for-elementor'),
                    'manual' => __('Manual', 'google-maps-for-elementor'),
                ],
            ]
        );

        $this->add_control(
            'center_lat',
            [
                'label' => __('Center Latitude', 'google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => ['center_type' => 'manual'],
            ]
        );

        $this->add_control(
            'center_lng',
            [
                'label' => __('Center Longitude', 'google-maps-for-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => ['center_type' => 'manual'],
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label' => __('Zoom', 'google-maps-for-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
            ]
        );

        $this->end_controls_section();

        // New Style Tab: Map Style
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Map Style', 'google-maps-for-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'map_width',
            [
                'label' => __('Map Width', 'google-maps-for-elementor'),
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
                'label' => __('Map Height', 'google-maps-for-elementor'),
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
                'label' => __('Border', 'google-maps-for-elementor'),
                'selector' => '{{WRAPPER}} #gme-map',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $map_id = 'gme-map-' . $this->get_id();

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
                    if (!empty($location['lat']) && !empty($location['lng'])) {
                        $total_lat += floatval($location['lat']);
                        $total_lng += floatval($location['lng']);
                        $count++;
                    }
                }
                if ($count > 0) {
                    $center_lat = $total_lat / $count;
                    $center_lng = $total_lng / $count;
                } else {
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
                    'lat' => floatval($location['lat']),
                    'lng' => floatval($location['lng'])
                ];
            }, $settings['locations'])
        ];

        $style = 'width: ' . $settings['map_width'] . '; height: ' . $settings['map_height'] . ';';
        echo '<div id="' . esc_attr($map_id) . '" class="gme-map" style="' . esc_attr($style) . '" data-map-settings="' . esc_attr(json_encode($map_data)) . '"></div>';

        wp_enqueue_script('gme-maps-init', plugins_url('../assets/js/map.js', __FILE__), array('gme-google-maps-loader'), null, true);
    }

    protected function _content_template()
    {
        ?>
        <# var style = 'width: ' + settings.map_width + '; height: ' + settings.map_height + ';';
            #>
            <div id="gme-map" style="{{ style }}"
              > Here will be map </div>
            <?php
    }
}
