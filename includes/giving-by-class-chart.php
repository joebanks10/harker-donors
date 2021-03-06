<?php

class GivingByClassChart {

    public function __construct() {
        add_shortcode( 'giving_by_class_chart', array($this, 'shortcode') );
        add_action( 'wp_ajax_hkr_dnrs_giving_by_class_data', array($this, 'data') );
        add_action( 'wp_ajax_nopriv_hkr_dnrs_giving_by_class_data', array($this, 'data') );
        add_action( 'wp_enqueue_scripts', array($this, 'styles') );
    }

    public function shortcode($atts) {
        global $post;

        $atts = shortcode_atts(array(
            'width' => 718,
            'height' => 500,
            'school_year' => hkr_get_school_year($post->ID)
        ), $atts);

        wp_enqueue_script('d3-core', 'https://d3js.org/d3.v4.min.js', array(), false, true);
        wp_enqueue_script('d3-tip', HKR_DNRS_URL . 'js/d3-tip.js', array('d3-core'), false, true);
        wp_enqueue_script('giving-by-class-chart', HKR_DNRS_URL . 'js/giving-by-class-chart.js', array('d3-core', 'd3-tip'), '1.0', true);

        wp_localize_script('giving-by-class-chart', 'hkr_dnrs', array(
            'school_year' => $atts['school_year'],
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ));

        $output = '<div class="chart-container"><svg width="' . $atts['width'] . '" height="' . $atts['height'] . '" class="giving-by-class-chart" viewBox="0 0 ' . $atts['width'] . ' ' . $atts['height'] . '"
  preserveAspectRatio="xMinYMid"></svg></div>';

        return $output;
    }

    public function styles() {
        wp_enqueue_style('hkr_dnrs_chart_styles', HKR_DNRS_URL . 'css/chart.css');
    }

    public function data() {
        global $hkr_class_years;

        $school_year = (isset($_GET['school_year'])) ? $_GET['school_year'] : '';

        $class_years = $hkr_class_years->get($school_year);
        $output = array();

        foreach($class_years as $year => $data) {
            $output[] = array(
                'class' => $year,
                'student_count' => $data['student_count'],
                'gave_count' => $data['gave_count'],
                'gave_percent' => round($data['gave_count']/$data['student_count'], 2),
            );
        }

        header('Content-Type: application/json');

        echo json_encode($output); // file_get_contents(HKR_DNRS_PATH . 'tmp/data.tsv');

        exit();
    }

}

new GivingByClassChart();
