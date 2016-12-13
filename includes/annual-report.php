<?php

class AnnualReport {

    public function __construct() {
        add_action( 'init', array($this, 'register') );
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
        add_action( 'save_post_report', array($this, 'save_report') );
    }

    public function admin_scripts() {
        global $post;

        if ($post->post_type === 'report') {
            wp_enqueue_script('annual-report-admin', HKR_DNRS_URL . 'js/annual-report-admin.js', array('jquery'), false, true);
            wp_enqueue_style('annual-report-admin-css', HKR_DNRS_URL . 'css/annual-report-admin.css');
        }
    }

    public function register() {

        $labels = array(
            'name' => _x('Annual Reports', 'post type general name'),
            'singular_name' => _x('Annual Report', 'post type singular name'),
            'add_new' => _x('Add New', 'annual report'),
            'add_new_item' => __('Add New Annual Report Settings'),
            'edit_item' => __('Edit Annual Report Settings'),
            'new_item' => __('New Annual Report'),
            'all_items' => __('All Reports'),
            'view_item' => __('View Settings'),
            'search_items' => __('Search Reports'),
            'not_found' =>  __('No reports found'),
            'not_found_in_trash' => __('No reports found in Trash'),
            'parent_item_colon' => '',
            'menu_name' => 'Annual Reports'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => true,
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-book',
            'supports' => array('')
        );

        register_post_type('report', $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'hkr_dnrs_annual_report_general',
            'General Settings',
            array($this, 'general_settings_form'),
            'report',
            'normal',
            'core'
        );
        add_meta_box(
            'hkr_dnrs_class_years',
            'Student Class Years <span id="class-years-spinner" class="spinner"></span>',
            array($this, 'class_years_form'),
            'report',
            'normal',
            'core'
        );
    }

    public function general_settings_form() {
        global $post;

        $school_year = $post->post_name;
        $excluded_years = array();

        wp_nonce_field(basename(__FILE__), 'hkr_dnrs_ar_general');

        ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="campaign_year">Campaign Year</label>
                    </th>
                    <td>
                        <select id="campaign_year" name="campaign_year">
                            <?php
                                $school_year = ( empty($school_year) ) ? hkr_get_current_school_year() : $school_year;
                                $year_index = date('Y') - 10;
                                for ( $i = 0; $i < 20; $i++ ) {
                                    $this_year = $year_index . '-' . substr( (string) $year_index + 1, -2);
                                    $selected = '';
                                    $disabled = '';

                                    if ( $this_year == $school_year ) {
                                        $selected = 'selected="selected"';
                                    } else {
                                        if ( in_array($this_year, $excluded_years) ) {
                                            $disabled = 'disabled';
                                        }
                                    }

                                    echo "<option value='$this_year' $selected>$this_year</option>";
                                    $year_index++;
                                }
                            ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php
    }

    public function class_years_form() {
        global $post;

        wp_nonce_field(basename(__FILE__), 'hkr_dnrs_ar_classes');

        ?>
        <div id="postcustomstuff">
            <div class="refresh-stats">
                <input type="submit" id="refresh-stats-button" class="button button-small" value="Generate Gave/Pledge Stats">
            </div>
            <table id="list-table">
              <thead>
                <tr>
                  <th>Class Year</th>
                  <th>Student Count</th>
                  <th>Gave/Pledge Count</th>
                  <th>Gave/Pledge Percent</th>
                </tr>
              </thead>
              <tbody id="class-rows">
              </tbody>
            </table>
        </div>
        <script id="class-row-template" type="text/template">
            <tr>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-year">Class Year</label>
                <input name="classes[{{year}}][year]" id="class-of-{{year}}-year" type="text" class="large-text class-year" value="{{year}}" readonly>
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-count">Student Count</label>
                <input name="classes[{{year}}][student_count]" id="class-of-{{year}}-count" type="text" class="large-text class-count" value="{{count}}">
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-count">Gave/Pledged Count</label>
                <input name="classes[{{year}}][gave_count]" id="class-of-{{year}}-gave-count" type="text" class="large-text class-gave-count" value="{{gave_count}}" readonly>
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-percent">Gave/Pledged Percent</label>
                <input name="classes[{{year}}][gave_percent]" id="class-of-{{year}}-gave-percent" type="text" class="large-text class-gave-percent" value="{{gave_percent}}" readonly>
              </td>
            </tr>
        </script>

        <?php
    }

    public function save_report($post_id) {
        // If this is a revision, get real post ID
        if ( $parent_id = wp_is_post_revision( $post_id ) ) {
            $post_id = $parent_id;
        }

        $this->save_report_general($post_id);        
        $this->save_report_classes($post_id);
    }

    private function save_report_general($post_id) {
        check_admin_referer(basename(__FILE__), 'hkr_dnrs_ar_general');

        if (!isset($_POST['campaign_year'])) {
            return;
        }

        $campaign_year = $_POST['campaign_year'];

        // unhook this function so it doesn't loop infinitely
        remove_action( 'save_post_report', array($this, 'save_report') );

        // update the post, which calls save_post again
        wp_update_post( array( 
            'ID' => $post_id, 
            'post_title' => $campaign_year,
            'post_name' => $campaign_year
        ) );

        // re-hook this function
        add_action( 'save_post_report', array($this, 'save_report') );
    }

    private function save_report_classes($post_id) {
        // echo "<pre>"; print_r($_POST['classes']); echo "</pre>"; exit();
        check_admin_referer(basename(__FILE__), 'hkr_dnrs_ar_classes');

        $data = (isset($_POST['classes'])) ? $_POST['classes'] : null;

        update_post_meta($post_id, 'class_years', $data);
    }

}

new AnnualReport();
