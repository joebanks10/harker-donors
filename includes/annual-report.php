<?php

class AnnualReport {

    public function __construct() {
        add_action( 'init', array($this, 'register') );
        add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
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
            'Student Class Years',
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
        global $giving_by_class_stats;



        wp_nonce_field(basename(__FILE__), 'hkr_dnrs_class_years');

        ?>
        <style>
            #postcustomstuff table input {
                width: 93%;
            }
            .refresh-stats {
                margin-top: 10px;
            }
        </style>
        <div id="postcustomstuff">
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
            <div class="refresh-stats">
                <input type="submit" id="refresh-2030" class="button button-small refresh-stats-button" value="Refresh Gave/Pledge Stats">
            </div>
        </div>
        <script id="class-row-template" type="text/template">
            <tr>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-year">Class Year</label>
                <input name="classes[][year]" id="class-of-{{year}}-year" type="text" class="large-text" value="{{year}}" readonly>
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-count">Student Count</label>
                <input name="classes[][count]" id="class-of-{{year}}-count" type="text" class="large-text" value="{{count}}">
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-count">Gave/Pledged Count</label>
                <input name="classes[][gave_count]" id="class-of-{{year}}-gave-count" type="text" class="large-text" value="{{gave_count}}">
              </td>
              <td>
                <label class="screen-reader-text" for="class-of-{{year}}-gave-percent">Gave/Pledged Percent</label>
                <input name="classes[][gave_count]" id="class-of-{{year}}-gave-percent" type="text" class="large-text" value="{{gave_percent}}">
              </td>
            </tr>
        </script>
        <script>
            (function($){
                $(function() {
                    _.templateSettings = {
                        interpolate: /\{\{(.+?)\}\}/g
                    };

                    var template = _.template($('#class-row-template').html());
                    
                    // when school year is selected
                    // get class years data with ajax
                    // loop through data and render template
                    // add to dom
                    // $('#class-rows').html(template({
                        // year: "2030",
                        // count: 100,
                        // gave_count: 39,
                        // gave_percent: 39
                    // }));

                    var classRows = getClassYears('2016-17').map(function(classYear) {
                        return template({ 
                            year: classYear,
                            count: '',
                            gave_count: '',
                            gave_percent: ''
                        });
                    });

                    $('#class-rows').html(classRows.join(' '));

                    function getClassYears(schoolYear) {
                        var classYears = [],
                            oldestClass = +getEndYear(schoolYear),
                            youngestClass = oldestClass + 15; // 16 classes: Age 3, Age 4, TK, K-12

                        for(var i = youngestClass; i >= oldestClass; i--) {
                            classYears.push(i);
                        }

                        return classYears;
                    }

                    // expects schoolYear to be formatted as YYYY-YY (e.g. 2016-17)
                    function getEndYear(schoolYear) {
                        var classYears = [],
                            re = /^(\d\d\d\d)\-\d\d$/,
                            match = schoolYear.match(re);

                        if (match) {
                            return (+match[1] + 1).toString();
                        } else {
                            return "";
                        }
                    }
                });
            })(jQuery);
        </script>

        <?php
    }

}

new AnnualReport();
