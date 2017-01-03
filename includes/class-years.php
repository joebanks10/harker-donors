<?php 

class ClassYears {

    private $default_class_year = array(
        'year' => '',
        'student_count' => 0,
        'gave_count' => 0
    );

    public function __construct() {
        add_action( 'wp_ajax_hkr_dnrs_get_class_years', array($this, 'get_ajax') );
        // add_action( 'wp_ajax_hkr_dnrs_update_class_years', array($this, 'update_ajax') );
    }

    public function get($school_year) {
        $school_year = sanitize_title($school_year);

        $report = get_posts(array(
            'name'        => $school_year,
            'post_type'   => 'report',
            'numberposts' => 1,
        ));

        $data = $report ? get_post_meta($report[0]->ID, 'class_years', true) : get_transient($this->get_transient_key($school_year));

        $data = empty($data) ? $this->get_default_option_value($school_year) : $data;

        return $data;
    }

    public function get_ajax() {
        $school_year = (isset($_GET['school_year'])) ? $_GET['school_year'] : false;
        $refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : false;

        if (!$school_year) {
            exit();
        }

        if ($refresh) {
            $this->generate_stats($school_year);
        }

        $data = $this->get($school_year);
        $output = array();

        foreach ($data as $class_data) {
            $output[] = $class_data;
        }

        header('Content-Type: application/json');

        echo json_encode($output); 

        exit();
    }

    public function get_class_count($school_year, $class_year) {
        global $hkr_annual_settings;

        $classes = $this->get($school_year);

        if (isset($classes[$class_year]) && !empty($classes[$class_year]['student_count'])) {
            return $classes[$class_year]['student_count'];
        } else {
            return $hkr_annual_settings->get_class_total($class_year, $school_year);
        }
    }

    public function update($school_year, $data) {
        $school_year = sanitize_title($school_year);

        $report = get_posts(array(
            'name'        => $school_year,
            'post_type'   => 'report',
            'numberposts' => 1,
        ));

        $prev_classes = $report ? get_post_meta($report[0]->ID, 'class_years', true) : get_transient($this->get_transient_key($school_year));
        
        $new_classes = $this->sanitize_classes($school_year, $data, $prev_classes);

        if ($report) {
            $success = update_post_meta($report[0]->ID, 'class_years', $new_classes);
        } else {
            // save temporarily for 30 minutes
            $success = set_transient($this->get_transient_key($school_year), $new_classes, 60*30); 
        }

        return $success ? $new_classes : false;
    }

    public function update_by_post_id($post_id, $school_year, $data) {
        $prev_classes = get_post_meta($post_id, 'class_years', true);
        $new_classes = $this->sanitize_classes($school_year, $data, $prev_classes);

        $success = update_post_meta($post_id, 'class_years', $new_classes);

        return $success ? $new_classes : false;
    }

    public function sanitize_classes($school_year, $classes, $prev_classes = array()) {
        $new_classes = array();
        $prev_classes = empty($prev_classes) ? array() : $prev_classes;
        $default_classes = $this->get_default_option_value($school_year);

        foreach ($default_classes as $class_year => $defaults) {
            $prev_class = isset($prev_classes[$class_year]) ? $prev_classes[$class_year] : array();

            $temp = isset($classes[$class_year]) ? 
                array_merge($defaults, $prev_class, $classes[$class_year]) : 
                array_merge($defaults, $prev_class);

            // only copy fields needed
            $new_classes[$class_year] = array(
                'year' => $temp['year'],
                'student_count' => $temp['student_count'],
                'gave_count' => $temp['gave_count']
            );
        }

        return $new_classes;
    }

    public function update_ajax() {
        $school_year = (isset($_GET['school_year'])) ? $_GET['school_year'] : false;
        $new_data = (isset($_GET['new_data'])) ? $_GET['new_data'] : false;

        if (!$school_year) {
            exit();
        }

        $new_classes = $this->update($school_year, $new_data);

        header('Content-Type: application/json');

        echo json_encode($new_classes); 

        exit();
    }

    public function generate_stats($school_year, $class_year = null) {
        if (!isset($school_year)) {
            return;
        }

        $class_years = $this->get_class_years($school_year);

        if (isset($class_year) && in_array($class_year, $class_years)) {
            $this->generate_class_stats($school_year, $class_year);
        } else {
            foreach ($class_years as $c) {
                $this->generate_class_stats($school_year, $c);
            }
        }
    }

    public function generate_class_stats($school_year, $class_year) {
        $shortcode = sprintf('[dnrs_class_year school_year="%s" class_year="%s"]', $school_year, $class_year);
        
        // running shortcode updates the stats
        do_shortcode($shortcode);
    }

    private function get_default_option_value($school_year) {
        if (!isset($school_year)) {
            return array();
        }

        $default_value = array();
        $class_years = $this->get_class_years($school_year);

        foreach ($class_years as $c) {
            $default_value[$c] = array_merge($this->default_class_year, array('year' => $c));
        }

        return $default_value;
    }

    private function get_transient_key($school_year) {
        return sanitize_key("${school_year}_class_years");
    }

    public function get_class_years($school_year) {
        $class_years = array();

        $oldest_class = hkr_get_end_school_year($school_year);
        $youngest_class = $oldest_class + 15; // 16 classes: Age 3, Age 4, TK, K-12

        for($i = $youngest_class; $i >= $oldest_class; $i--) {
            $class_years[] = $i;
        }

        return $class_years;
    }

}

$hkr_class_years = new ClassYears();
