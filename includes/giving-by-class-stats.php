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

    public function update($school_year, $class_year, $value) {
        global $hkr_class_years;

        $stats = $this->get_stats_option($school_year);
        $class_years = $hkr_class_years->get_class_years($school_year);

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
        global $hkr_class_years;

        if (!isset($school_year)) {
            return;
        }

        $class_years = $hkr_class_years->get_class_years($school_year);

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
        global $hkr_class_years;

        $stats = array();
        $class_years = $hkr_class_years->get($school_year);

        foreach ($class_years as $class_year => $class_data) {
            $stats[$class_year] = $class_data['gave_percent'];
        }

        return $stats;
    }

    private function update_stats_option($school_year, $stats) {
        global $hkr_class_years;

        $class_years = $hkr_class_years->get($school_year);

        foreach ($class_years as $year => $data) {
            if (isset($stats[$year])) {
                $class_years[$year]['gave_percent'] = $stats[$year];
            }
        }

        return $hkr_class_years->update($school_year, $class_years);
    }

}

$giving_by_class_stats = new GivingByClassStats();
