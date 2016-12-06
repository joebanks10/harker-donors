<?php
/*
Plugin Name: Harker Donors
Description: A tool to manage donors and their gifts
Version: 1.0
Author: Joe Banks
*/

define('HKR_DNRS_PATH', plugin_dir_path(__FILE__) );
define('HKR_DNRS_URL', plugin_dir_url(__FILE__) );

require(HKR_DNRS_PATH . 'includes/settings.php');
require(HKR_DNRS_PATH . 'includes/helper-functions.php');
require(HKR_DNRS_PATH . 'includes/class-years.php');
require(HKR_DNRS_PATH . 'includes/giving-by-class-stats.php');
require(HKR_DNRS_PATH . 'includes/giving-by-class-chart.php');
require(HKR_DNRS_PATH . 'includes/shortcodes.php');
require(HKR_DNRS_PATH . 'includes/connections.php');
require(HKR_DNRS_PATH . 'includes/constituent.php');
require(HKR_DNRS_PATH . 'includes/record.php');
require(HKR_DNRS_PATH . 'includes/annual-report.php');

add_action('admin_print_styles', 'hkr_dnrs_admin_styles');

function hkr_dnrs_admin_styles() {
    wp_enqueue_style('hkr_dnrs_admin_styles', HKR_DNRS_URL . 'css/admin.css');
}
