<?php
namespace MarvyElementor\animation;

if( !defined( 'ABSPATH' ) ) exit;
use Elementor\Controls_Manager;

class MarvyCloudAnimation {

    public function __construct(){
        add_action('elementor/frontend/section/before_render', array($this, 'before_render'), 1);
        add_action('elementor/element/section/section_layout/after_section_end',array($this,'register_controls'), 1 );
    }

    public function register_controls($element)
    {
        $element->start_controls_section('marvy_cloud_animation_section',
        [
            'label' => __('<div style="float: right"><img src="'.plugin_dir_url(__DIR__).'assets/images/logo.png" height="15px" width="15px" style="float:left;" alt=""></div> Cloud Animation', 'marvy-animation-addons-for-elementor-lite'),
            'tab' => Controls_Manager::TAB_LAYOUT
        ]
        );

        $element->add_control('marvy_enable_cloud_animation',
        [
            'label' => esc_html__('Enable Cloud Animation', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::SWITCHER,
        ]
        );

        $element->add_control('marvy_cloud_background_color',
        [
            'label' => esc_html__('Background', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'condition' => [
              'marvy_enable_cloud_animation' => 'yes'
            ]
        ]
        );

        $element->add_control('marvy_cloud_sky_color',
        [
            'label' => esc_html__('Sky Color', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::COLOR,
            'default' => '#77D5F0',
            'condition' => [
              'marvy_enable_cloud_animation' => 'yes'
            ]
        ]
        );

        $element->add_control('marvy_cloud_cloud_color',
        [
            'label' => esc_html__('Cloud Color', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::COLOR,
            'default' => '#CFC9C9',
            'condition' => [
              'marvy_enable_cloud_animation' => 'yes'
            ]
        ]
        );

        $element->add_control('marvy_cloud_light_color',
        [
            'label' => esc_html__('Light Color', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::COLOR,
            'default' => '#FCFCFC',
            'condition' => [
              'marvy_enable_cloud_animation' => 'yes'
            ]
        ]
        );

        $element->add_control('marvy_cloud_cloud_speed',
        [
            'label' => esc_html__('Cloud Speed', 'marvy-animation-addons-for-elementor-lite'),
            'type' => Controls_Manager::NUMBER,
            'default' => 1,
            'min' => 0,
            'max' => 5,
            'step' => 0.1,
            'dynamic' => [
              'active' => true,
            ],
            'condition' => [
              'marvy_enable_cloud_animation' => 'yes'
            ]
        ]
        );

        $element->end_controls_section();
    }

    public function before_render($element) {
        $settings = $element->get_settings();
    
        if ($settings['marvy_enable_cloud_animation'] === 'yes') {
          $element->add_render_attribute(
            '_wrapper',
            [
              'data-marvy_enable_cloud_animation' => 'true',
              'data-marvy_cloud_background_color' => $settings['marvy_cloud_background_color'],
              'data-marvy_cloud_sky_color' => $settings['marvy_cloud_sky_color'],
              'data-marvy_cloud_cloud_color' => $settings['marvy_cloud_cloud_color'],
              'data-marvy_cloud_light_color' => $settings['marvy_cloud_light_color'],
              'data-marvy_cloud_cloud_speed' => $settings['marvy_cloud_cloud_speed'],
            ]
          );
        } else {
          $element->add_render_attribute('_wrapper', 'data-marvy_enable_cloud_animation', 'false');
        }
    }

}