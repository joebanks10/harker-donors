<?php 

class ClassYears {

    private $default_class_year = array(
        'year' => '',
        'student_count' => 0,
        'gave_count' => 0,
        'gave_percent' => 0
    );

    public function get($school_year) {
        $data = get_option($this->get_option_key($school_year));
        $data = empty($data) ? $this->get_default_option_value() : $data;

        return $data;
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

    public function update_class($school_year, $class_year, $class_data) {
        $option_key = $this->get_option_key($school_year);
        $option_value = get_option($option_key);

        if (!$option_value) {
            $option_value = $this->get_default_option_value($school_year);
        }

        if (!isset($option_value[$class_year])) {
            return false;
        }

        $option_value[$class_year] = array_map($option_value[$class_year], $class_data);

        return update_option($option_key, $option_value);
    }

    private function get_default_option_value($school_year) {
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
