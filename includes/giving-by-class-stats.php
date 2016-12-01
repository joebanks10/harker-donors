<?php

class GivingByClassStats {

    /**
     * Returns percentage of class that have gave/pledged
     * 
     * @param  string $school_year 
     * @param  string $class_year  
     * @return int|array Returns an int if $class_year is defined; 
     * else it returns an associative array of class year => percentage 
     * pairs for the enrolled class years of $school_year 
     */
    public function get($school_year, $class_year = null) {
        $stats = $this->get_stats_option($school_year);

        if (isset($class_year) && $stats && isset($stats[$class_year])) {
            return $stats[$class_year];
        } else {
            return $stats;
        }
    }

    public function set($school_year, $class_year, $value) {
        $stats = $this->get_stats_option($school_year);
        $class_years = $this->get_class_years($school_year);

        if (!in_array($class_year, $class_years)) {
            return false; // invalid year
        }

        if (!$stats) {
            // stats do not exist, so iniitialize new stats array
            $stats = array();

            foreach ($class_years as $c) {
                $stats[$c] = false;
            }
        }

        // set value
        $stats[$class_year] = $value;

        // update database
        return $this->update_stats_option($school_year, $stats);
    }

    public function refresh($school_year, $class_year = null) {
        $class_years = $this->get_class_years($school_year);

        if (isset($class_year) && in_array($class_year, $class_years)) {
            $this->refresh_class_year($school_year, $c);
        } else {
            foreach ($class_years as $c) {
                $this->refresh_class_year($school_year, $c);
            }
        }
    }

    public function refresh_class_year($school_year, $class_year) {
        $shortcode = sprintf('[dnrs_class_year school_year="%s" class_year="%s"]', $school_year, $class_year);
        
        // running shortcode updates the stats
        do_shortcode($shortcode);
    }

    private function get_stats_option($school_year) {
        return get_option($this->get_option_key($school_year));
    }

    private function update_stats_option($school_year, $value) {
        return update_option($this->get_option_key($school_year), $value);
    }

    private function get_option_key($school_year) {
        return sanitize_key("giving-by-class-stats-$school_year");
    }

    private function get_class_years($school_year) {
        $class_years = array();

        $oldest_class = hkr_get_end_school_year($school_year);
        $youngest_class = $oldest_class + 15; // 16 classes: Age 3, Age 4, TK, K-12

        for($i = $youngest_class; $i >= $oldest_class; $i--) {
            $class_years[] = $i;
        }

        return $class_years;
    }

}
