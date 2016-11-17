<?php

class GivingByClassChart {

    public function __construct() {
        add_shortcode( 'giving_by_class_chart', array($this, 'shortcode') );
        add_action( 'wp_ajax_hkr_dnrs_giving_by_class_data', array($this, 'data') );
        add_action( 'wp_enqueue_scripts', array($this, 'styles') );
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'width' => 718,
            'height' => 500
        ), $atts);

        wp_enqueue_script('d3-library', 'https://d3js.org/d3.v4.min.js', array(), false, true);
        wp_enqueue_script('d3-tip', HKR_DNRS_URL . 'js/d3-tip.js', array('d3-library'), false, true);
        wp_enqueue_script('giving-by-class-chart', HKR_DNRS_URL . 'js/giving-by-class-chart.js', array('d3', 'd3-tip'), '1.0', true);

        $output = '<div class="chart-container"><svg width="' . $atts['width'] . '" height="' . $atts['height'] . '" class="giving-by-class-chart"></svg></div>';

        return $output;
    }

    public function styles() {
        wp_enqueue_style('hkr_dnrs_chart_styles', HKR_DNRS_URL . 'css/chart.css');
    }

    public function data() {
        header('Content-Type: text/tab-separated-values');

        $output = file_get_contents(HKR_DNRS_PATH . 'tmp/data.tsv');

        echo $output;

        exit();
    }

}

new GivingByClassChart();
