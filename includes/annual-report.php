<?php

class AnnualReport {

    public function __construct() {
        add_action( 'init', array($this, 'register') );
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts') );
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
            'hkr_dnrs_annual_report_basic',
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
        ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="school_year">Campaign Year</label>
                    </th>
                    <td>
                        <select id="school_year" name="school_year">
                            <?php
                                $school_year = ( empty($school_year) ) ? hkr_get_current_school_year() : $school_year;
                                $year_index = date('Y') - 20;
                                for ( $i = 0; $i < 50; $i++ ) {
                                    $this_year = $year_index . '-' . substr( (string) $year_index + 1, -2);
                                    $selected = '';
                                    if ( $this_year == $school_year ) {
                                        $selected = 'selected="selected"';
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

        wp_nonce_field(basename(__FILE__), 'hkr_dnrs_class_years');

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
                <input name="classes[][year]" id="class-of-{{year}}-year" type="text" class="large-text class-year" value="{{year}}" readonly>
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-count">Student Count</label>
                <input name="classes[][count]" id="class-of-{{year}}-count" type="text" class="large-text class-count" value="{{count}}">
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-count">Gave/Pledged Count</label>
                <input name="classes[][gave_count]" id="class-of-{{year}}-gave-count" type="text" class="large-text class-gave-count" value="{{gave_count}}" readonly>
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-percent">Gave/Pledged Percent</label>
                <input name="classes[][gave_count]" id="class-of-{{year}}-gave-percent" type="text" class="large-text class-gave-percent" value="{{gave_percent}}" readonly>
              </td>
            </tr>
        </script>

        <?php
    }

}

new AnnualReport();
