<?php

namespace MarvyElementor\animation;

if (!defined('ABSPATH')) exit;

use Elementor\Controls_Manager;

class MarvyRipplesAnimation
{

    public function __construct()
    {
        add_action('elementor/frontend/section/before_render', array($this, 'before_render'), 1);
        add_action('elementor/element/section/section_layout/after_section_end', array($this, 'register_controls'), 1);
    }

    public function register_controls($element)
    {
        $element->start_controls_section('marvy_ripples_animation_section',
            [
                'label' => __('<div style="float: right"><img src="' . plugin_dir_url(__DIR__) . 'assets/images/logo.png" height="15px" width="15px" style="float:left;" alt=""></div> Ripples Animation', 'marvy-animation-addons-for-elementor-lite'),
                'tab' => Controls_Manager::TAB_LAYOUT
            ]
        );

        $element->add_control('marvy_enable_ripples_animation',
            [
                'label' => esc_html__('Enable Ripples Animation', 'marvy-animation-addons-for-elementor-lite'),
                'type' => Controls_Manager::SWITCHER,
            ]
        );

        $element->add_control(
            'marvy_ripples_animation_circle_color',
            [
                'label' => esc_html__('Circle Color', 'marvy-animation-addons-for-elementor-lite'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2F74C5',
                'condition' => [
                    'marvy_enable_ripples_animation' => 'yes',
                ]
            ]
        );

        $element->add_control(
            'marvy_ripples_animation_circle_size',
            [
                'label' => esc_html__('Size', 'marvy-animation-addons-for-elementor-lite'),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
                'min' => 50,
                'max' => 1000,
                'step' => 5,
                'condition' => [
                    'marvy_enable_ripples_animation' => 'yes',
                ]
            ]
        );

        $element->add_control(
            'marvy_ripples_animation_circle_position',
            [
                'label' => esc_html__('Position', 'marvy-animation-addons-for-elementor-lite'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'marvy-animation-addons-for-elementor-lite'),
                    'top' => esc_html__('Top', 'marvy-animation-addons-for-elementor-lite'),
                    'right' => esc_html__('Right', 'marvy-animation-addons-for-elementor-lite'),
                    'bottom' => esc_html__('Bottom', 'marvy-animation-addons-for-elementor-lite'),
                    'topLeft' => esc_html__('Top Left', 'marvy-animation-addons-for-elementor-lite'),
                    'topRight' => esc_html__('Top Right', 'marvy-animation-addons-for-elementor-lite'),
                    'bottomRight' => esc_html__('Bottom Right', 'marvy-animation-addons-for-elementor-lite'),
                    'bottomLeft' => esc_html__('Bottom Left', 'marvy-animation-addons-for-elementor-lite')
                ],
                'condition' => [
                    'marvy_enable_ripples_animation' => 'yes'
                ]
            ]
        );

        $element->end_controls_section();

    }

    public function before_render($element)
    {
        $settings = $element->get_settings();

        if ($settings['marvy_enable_ripples_animation'] === 'yes') {
            $element->add_render_attribute(
                '_wrapper',
                [
                    'data-marvy_enable_ripples_animation' => 'true',
                    'data-marvy_ripples_animation_circle_color' => $settings['marvy_ripples_animation_circle_color'],
                    'data-marvy_ripples_animation_circle_position' => $settings['marvy_ripples_animation_circle_position'],
                    'data-marvy_ripples_animation_circle_size' => $settings['marvy_ripples_animation_circle_size']
                ]
            );
        } else {
            $element->add_render_attribute('_wrapper', 'data-marvy_enable_ripples_animation', 'false');
        }

    }
}
