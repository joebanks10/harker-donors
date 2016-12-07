<?php 

class ClassYears {

    private $default_class_year = array(
        'year' => '',
        'student_count' => 0,
        'gave_count' => 0,
        'gave_percent' => 0
    );

    public function __construct() {
        add_action( 'wp_ajax_hkr_dnrs_class_years', array($this, 'ajax_get') );
    }

    public function get($school_year) {
        $data = get_option($this->get_option_key($school_year));
        $data = empty($data) ? $this->get_default_option_value($school_year) : $data;

        return $data;
    }

    public function ajax_get() {
        $school_year = (isset($_GET['school_year'])) ? $_GET['school_year'] : '';
        $refresh = (isset($_GET['refresh'])) ? $_GET['refresh'] : false;

        if ($refresh) {
            $this->generate_stats($school_year);
        }

        $data = $this->get($school_year);
        $output = [];

        foreach ($data as $class_data) {
            $output[] = $class_data;
        }

        header('Content-Type: application/json');

        echo json_encode($output); 

        exit();
    }

    public function update($school_year, $data) {
        $option_key = $this->get_option_key($school_year);
        $option_value = get_option($option_key);

        if (!$option_value) {
            $option_value = $this->get_default_option_value($school_year);
        }

        foreach ($data as $class_year => $class_data) {
            if (isset($option_value[$class_year])) {
                $option_value[$class_year] = array_merge($option_value[$class_year], $class_data);
            }
        }

        return update_option($option_key, $option_value);
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

    private function get_option_key($school_year) {
        return sanitize_key("hkr-class-years-$school_year");
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
