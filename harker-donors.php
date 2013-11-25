<?php
/*
Plugin Name: Harker Donors
Description: A tool to manage donors and their gifts
Version: 1.0
Author: Joe Banks
*/

define('HKR_DNRS_PATH', plugin_dir_path(__FILE__) );
define('HKR_DNRS_URL', plugin_dir_url(__FILE__) );

require(HKR_DNRS_PATH . 'includes/helper-functions.php');
require(HKR_DNRS_PATH . 'shortcodes.php');

add_action('admin_print_styles', 'hkr_dnrs_admin_styles');

function hkr_dnrs_admin_styles() {
    wp_enqueue_style('hkr_dnrs_admin_styles', HKR_DNRS_URL . 'css/admin.css');
}


add_action('admin_enqueue_scripts', 'hkr_dnrs_admin_scripts');

function hkr_dnrs_admin_scripts() {
    global $post;
    if ( !isset($post) )
        return;

    if ( $post->post_type === 'constituent' ) {
        wp_enqueue_script('hkr_dnrs_constituent_script', HKR_DNRS_URL . 'js/edit-constituent.js');
    }
}


add_action( 'wp_loaded', 'hkr_dnrs_connections' );

function hkr_dnrs_connections() {
    if ( !function_exists( 'p2p_register_connection_type' ) )
        return;

    p2p_register_connection_type( array(
        'name' => 'spouses',
        'from' => 'constituent',
        'to' => 'constituent',
        'reciprocal' => false,
        'cardinality' => 'one-to-one',
        'title' => array( 'from' => 'Spouse', 'to' => 'Spouse (record owner)'),
        'admin_column' => false,
        'admin_box' => array(
            'show' => 'any',
            'context' => 'advanced'
        )
    ) );


    p2p_register_connection_type( array(
        'name' => 'parent_to_child',
        'from' => 'constituent',
        'to' => 'constituent',
        'title' => array( 'from' => 'Children & Grandchildren', 'to' => 'Parents & Grandparents'),
        'admin_column' => false,
        'admin_box' => array(
              'show' => 'any',
              'context' => 'advanced'
        ),
        'fields' => array(
                'relationship' => array(
                        'title' => 'Relationship',
                        'type' => 'select',
                        'values' => array( 'Parent-Child', 'Grandparent-Grandchild' ),
                        'default' => 'Parent-Child'
                )
        )
    ) );



    p2p_register_connection_type( array(
        'name' => 'constituents_to_records',
        'from' => 'constituent',
        'to' => 'record',
        'title' => array( 'from' => 'Records', 'to' => 'Constituents'),
        'admin_column' => 'to',
        'admin_box' => array(
              'show' => 'any',
              'context' => 'advanced'
        ),
        'fields' => array(
                'role' => array(
                        'title' => 'Constituent\'s Role',
                        'type' => 'select',
                        'values' => array( 'Record Owner', 'Spouse', 'Child' )
                )
        )
    ));
}


/******************************************************************************/
/*                          CONSTITUENT                                       */
/******************************************************************************/

add_action( 'init', 'hkr_dnrs_register_constituent' );
/**
 * Add constituent post type
 */
function hkr_dnrs_register_constituent() {

  $labels = array(
    'name' => _x('Constituents', 'post type general name'),
    'singular_name' => _x('Constituent', 'post type singular name'),
    'add_new' => _x('Add New', 'constituent'),
    'add_new_item' => __('Add New Constituent'),
    'edit_item' => __('Edit Constituent'),
    'new_item' => __('New Constituent'),
    'all_items' => __('All Constituents'),
    'view_item' => __('View Constituent'),
    'search_items' => __('Search Constituents'),
    'not_found' =>  __('No constituents found'),
    'not_found_in_trash' => __('No constituents found in Trash'),
    'parent_item_colon' => '',
    'menu_name' => 'Constituents'
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
    'register_meta_box_cb' => 'hkr_dnrs_register_constituent_fields',
    'supports' => array('')
  );

  register_post_type('constituent', $args);

}


/*
 * Registers custom metaboxes
 */
function hkr_dnrs_register_constituent_fields() {
    add_meta_box(
        'hrk_dnrs_constituent_info',
        __( 'Constituent Information', 'constituent information' ),
        'hkr_dnrs_print_constituent_fields',
        'constituent',
        'normal',
        'core'
    );
}

/*
 * Print custom metaboxes
 */
function hkr_dnrs_print_constituent_fields() {
    global $post;

    // check if data is being saved
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post->ID;

    // get custom field values
    $fields = array( 'fname', 'lname', 'org_name', 'import_id', 'cc_rec' );
    $custom = hkr_get_custom_fields( $post->ID, $fields );
    extract( $custom );

    // get class year term
    $class_year = hkr_dnrs_get_class_year( $post->ID );
    if ( !$class_year ) {
        $class_year = '';
    }

    // get years
    $current_year = date('Y');
    $max_year = $current_year + 20;
    $min_year = 1893;

    // create nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'hkr_dnrs_constituent_fields_nonce' );

    ?>
    <table class="form-table">
    <tbody>
        <tr>
            <th scope="row"><label for="fname">First Name</label>
            <td><input value="<?php echo $fname; ?>" type="text" id="fname" name="fname" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="lname">Last Name</label>
            <td><input value="<?php echo $lname; ?>" type="text" id="lname" name="lname" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="class_year">Class Year</label>
            <td>
                <input value="<?php echo $class_year; ?>" type="number" max="<?php echo $max_year; ?>" min="<?php echo $min_year; ?>" id="class_year" name="class_year" />
                <p class="description">For student or alumni constituents.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="org_name">Organization</label>
            <td><input value="<?php echo $org_name; ?>" type="text" id="org_name" name="org_name" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="cc_rec">Capital Campaign Recognition</label></th>
            <td><input type="text" value="<?php echo $cc_rec; ?>" id="cc_rec" name="cc_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="import_id">Constituent ID</label>
            <td>
                <input value="<?php echo $import_id; ?>" type="text" id="import_id" name="import_id" />
                <p class="description">Unique constituent ID from external database (used for imports).</p>
            </td>
        </tr>
    </tbody>
    </table>
    <?php
}


add_action('save_post', 'hkr_dnrs_save_constituent');

function hkr_dnrs_save_constituent() {
    // check if data is being saved
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // check nonce
    if ( !isset($_POST['hkr_dnrs_constituent_fields_nonce']) || !wp_verify_nonce( $_POST['hkr_dnrs_constituent_fields_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    global $post;
    $fields = array( 'fname', 'lname', 'org_name', 'import_id', 'cc_rec' );

    hkr_save_custom_fields( $post->ID, $fields );

    if ( !empty($_POST['class_year']) )
        wp_set_post_terms($post->ID, intval($_POST['class_year']), 'class_year' );
    else
        wp_set_post_terms($post->ID, null, 'class_year' );
}

add_action('save_post', 'hkr_dnrs_constituent_sync_roles', 15);

function hkr_dnrs_constituent_sync_roles() {

    // check if data is being saved
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // check nonce
    if ( !isset($_POST['hkr_dnrs_constituent_fields_nonce']) || !wp_verify_nonce( $_POST['hkr_dnrs_constituent_fields_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    global $post;

    $records = get_posts( array(
      'connected_type' => 'constituents_to_records',
      'connected_items' => $post
    ) );

    foreach( $records as $record ) {
        $p2p_role = p2p_get_meta( $record->p2p_id, 'role', true );
        if ( !empty($p2p_role) ) {
            $p2p_role = trim( str_replace( ' ', '-', strtolower( $p2p_role ) ) );
            $record_years = wp_get_object_terms( $record->ID, 'school_year', array('fields' => 'names') );
            $record_year = $record_years[0];

            $role_term = $record_year . '-' . $p2p_role;
            if ( term_exists( $role_term, 'role') ) {
                wp_set_object_terms( $post->ID, $role_term, 'role', true );
            }
        }
    }
}


add_filter( 'wp_insert_post_data', 'hkr_dnrs_constituent_insert_title', 10, 2 );

function hkr_dnrs_constituent_insert_title( $data, $post_args ) {

    if ( $data['post_type'] != 'constituent' )
        return $data;

    // check if data is being saved
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $data;

    // check nonce
    if ( !isset($_POST['hkr_dnrs_constituent_fields_nonce']) || !wp_verify_nonce( $_POST['hkr_dnrs_constituent_fields_nonce'], plugin_basename( __FILE__ ) ) )
        return $data;

    $fname = ( isset($_POST['fname']) ) ? trim($_POST['fname']) : '';
    $lname = ( isset($_POST['lname']) ) ? trim($_POST['lname']) : '';
    $class_year = ( isset($_POST['class_year']) ) ? trim($_POST['class_year']) : '';
    $org_name = ( isset($_POST['org_name']) ) ? trim($_POST['org_name']) : '';

    if ( !empty($fname) || !empty($lname) ) {
        $title = trim("$fname $lname");
        if ( !empty($class_year) ) {
            $class_year = "'" . substr( $class_year, -2);
            $title .= " $class_year";
        }
        $data['post_title'] = $title;
        return $data;
    }

    if ( !empty($org_name) ) {
        $data['post_title'] = $org_name;
        return $data;
    }

    $data['post_title'] = 'Anonymous';
    return $data;
}


add_filter('enter_title_here', 'hkr_dnrs_constituent_title_label');
/*
 * Change 'Enter title here' text
 */
function hkr_dnrs_constituent_title_label() {
    global $post;
    if( $post->post_type === 'constituent' ) {
        return __( 'Enter preferred name here' );
    }
    else {
        return __( 'Enter title here' );
    }
}


add_filter( 'the_title', 'hkr_dnrs_constituent_title', 10, 2 );

function hkr_dnrs_constituent_title( $title, $post_id ) {
    $post = get_post( $post_id );

    if ( $post->post_type != 'constituent' )
        return $title;

    $fname = get_post_meta( $post_id, 'fname', true );
    $lname = get_post_meta( $post_id, 'lname', true );
    $class_year = hkr_dnrs_get_class_year( $post_id );

    if ( !empty($fname) || !empty($lname) ) {
        $title = trim("$fname $lname");
        if ( !empty($class_year) ) {
            $class_year = "'" . substr( $class_year, -2);
            $title .= " $class_year";
        }
        return $title;
    }

    $org_name = get_post_meta( $post_id, 'org_name', true );

    if ( !empty($org_name) ) {
        return $org_name;
    }

    return 'Anonymous';
}


add_action( 'init', 'hkr_dnrs_register_constituent_tax');

function hkr_dnrs_register_constituent_tax() {

    // Role
    $labels = array(
        'name' => _x( 'Roles', 'taxonomy general name' ),
        'singular_name' => _x( 'Role', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Roles' ),
        'all_items' => __( 'All Roles' ),
        'edit_item' => __( 'Edit Role' ),
        'update_item' => __( 'Update Role' ),
        'add_new_item' => __( 'Add New Role' ),
        'new_item_name' => __( 'New Role Name' ),
        'menu_name' => __( 'Roles' ),
    );

    register_taxonomy( 'role', array('constituent'), array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'role' )
    ));

    // Class Years
    $labels = array(
        'name' => _x( 'Class Years', 'taxonomy general name' ),
        'singular_name' => _x( 'Class Year', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Class Years' ),
        'all_items' => __( 'All Class Years' ),
        'edit_item' => __( 'Edit Class Year' ),
        'update_item' => __( 'Update Class Year' ),
        'add_new_item' => __( 'Add New Class Year' ),
        'new_item_name' => __( 'New Class Yeare' ),
        'menu_name' => __( 'Class Years' ),
    );

    register_taxonomy( 'class_year', array('constituent'), array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => false,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'class_year' )
    ));

}

/******************************************************************************/
/*                          RECORD                                            */
/******************************************************************************/

add_action( 'init', 'hkr_dnrs_register_record' );
/**
 * Adds record post type
 */
function hkr_dnrs_register_record() {

  $labels = array(
    'name' => _x('Annual Records', 'post type general name'),
    'singular_name' => _x('Annual Record', 'post type singular name'),
    'add_new' => _x('Add New', 'record'),
    'add_new_item' => __('Add New Record'),
    'edit_item' => __('Edit Record'),
    'new_item' => __('New Record'),
    'all_items' => __('All Records'),
    'view_item' => __('View Record'),
    'search_items' => __('Search Records'),
    'not_found' =>  __('No records found'),
    'not_found_in_trash' => __('No records found in Trash'),
    'parent_item_colon' => '',
    'menu_name' => 'Annual Records'
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
    'register_meta_box_cb' => 'hkr_dnrs_register_record_fields',
    'supports' => array('')
  );

  register_post_type('record', $args);

}

/**
 * Registers custom fields for record post type
 */
function hkr_dnrs_register_record_fields() {
    add_meta_box(
        'hrk_dnrs_record_info',
        __( 'Record Information', 'record information' ),
        'hkr_dnrs_print_record_fields',
        'record',
        'normal',
        'high'
    );
    add_meta_box(
        'hrk_dnrs_recogntion',
        __( 'Recognition', 'recogntion' ),
        'hkr_dnrs_print_recognition_fields',
        'record',
        'normal'
    );
    add_meta_box(
        'hrk_dnrs_donation_amounts',
        __( 'Donation Amounts', 'donation amounts' ),
        'hkr_dnrs_print_donation_amounts_fields',
        'record',
        'normal'
    );
    /*
    add_meta_box(
        'hrk_dnrs_imo_iho',
        __( 'Honorary & Memorial Dedications', 'dedications' ),
        'hkr_dnrs_print_imo_iho_fields',
        'record',
        'normal'
    );
    */
}

function hkr_dnrs_print_record_fields() {
    global $post;

    // check if data is being saved
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post->ID;
    
    // get custom field values
    $fields = array( 'import_id' );
    $custom = hkr_get_custom_fields( $post->ID, $fields );
    $school_year = wp_get_object_terms( $post->ID, 'school_year', array( 'fields' => 'names' ) );
    $school_year = $school_year[0];
    extract( $custom );

    // create nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'hkr_dnrs_record_fields_nonce' );
    
    ?>
    <table class="form-table">
    <tbody>
        <tr>
            <th scope="row"><label for="school_year">Campaign Year</label>
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
                <p class="description">The school year that this record represents.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="import_id">Record ID</label>
            <td><input value="<?php echo $import_id; ?>" type="text" id="import_id" name="import_id" class="widefat" /></td>
        </tr>
    </tbody>
    </table>
    <?php
}

function hkr_dnrs_print_recognition_fields() {
    global $post;

    // check if data is being saved
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post->ID;

    // get custom field values
    $fields = array( 'ag_rec', 'picnic_rec', 'fs_rec', 'cc_rec', 'end_rec', 'inf_addr', 'alumni_rec', 'planned_giving_rec' );
    $custom = hkr_get_custom_fields( $post->ID, $fields );
    extract( $custom );

    ?>
    <table class="form-table">
    <tbody>
        <tr>
            <th scope="row"><label for="inf_addr">Informal Addressee</label>
            <td><input value="<?php echo $inf_addr; ?>" type="text" id="inf_addr" name="inf_addr" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="ag_rec">Annual Giving Recognition</label>
            <td><input value="<?php echo $ag_rec; ?>" type="text" id="ag_rec" name="ag_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="picnic_rec">Picnic Recognition</label>
            <td><input value="<?php echo $picnic_rec; ?>" type="text" id="picnic_rec" name="picnic_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="fs_rec">Fashion Show Recognition</label>
            <td><input value="<?php echo $fs_rec; ?>" type="text" id="fs_rec" name="fs_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="planned_giving_rec">Planned Giving Recognition</label>
            <td><input value="<?php echo $planned_giving_rec; ?>" type="text" id="planned_giving_rec" name="planned_giving_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="cc_rec">Capital Campaign Recognition</label>
            <td><input value="<?php echo $cc_rec; ?>" type="text" id="cc_rec" name="cc_rec" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="alumni_rec">Alumni Recognition</label>
            <td><input value="<?php echo $alumni_rec; ?>" type="text" id="alumni_rec" name="alumni_rec" class="widefat" /></td>
        </tr>
    </tbody>
    </table>
    <?php
}

function hkr_dnrs_print_donation_amounts_fields() {
    global $post;

    // check if data is being saved
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post->ID;

    // get custom field values
    $fields_num = array( 'ag_amount', 'picnic_amount', 'fs_amount');
    $custom = hkr_get_custom_fields( $post->ID, $fields_num, 0 );
    extract( $custom );

    ?>
    <table class="form-table">
    <tbody>
    <tr>
        <th scope="row"><label for="ag_amount">Annual Giving Gift Amount</label></th>
        <td>
            $<input type="number" min="0" value="<?php echo $ag_amount; ?>" id="ag_amount" name="ag_amount" />
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="picnic_amount">Picnic Sponsor Gift Amount</label></th>
        <td>
            $<input type="number" min="0" value="<?php echo $picnic_amount; ?>" id="picnic_amount" name="picnic_amount" />
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="fs_amount">Fashion Show Sponsor Gift Amount</label></th>
        <td>
            $<input type="number" min="0" value="<?php echo $fs_amount; ?>" id="fs_amount" name="fs_amount" />
        </td>
    </tr>
    </tbody>
    </table>

    <?php
}

function hkr_dnrs_print_imo_iho_fields() {
    global $post;

    // check if data is being saved
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
        return $post->ID;

    // get custom field values
    $fields = array( 'imo', 'iho');
    $custom = hkr_get_custom_fields( $post->ID, $fields );
    extract( $custom );

    ?>
    <table class="form-table">
    <tbody>
        <tr>
            <th scope="row"><label for="imo_1">In memory of...</label>
            <td><input value="<?php if ( isset( $imo[0] )) echo $imo[0]; ?>" type="text" id="imo_1" name="imo[]" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="imo_2">In memory of...</label>
            <td><input value="<?php if ( isset( $imo[1] )) echo $imo[1]; ?>" type="text" id="imo_2" name="imo[]" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="iho_1">In honor of...</label>
            <td><input value="<?php if ( isset( $iho[0] )) echo $iho[0]; ?>" type="text" id="iho_1" name="iho[]" class="widefat" /></td>
        </tr>
        <tr>
            <th scope="row"><label for="iho_2">In honor of...</label>
            <td><input value="<?php if ( isset( $iho[1] )) echo $iho[1]; ?>" type="text" id="iho_2" name="iho[]" class="widefat" /></td>
        </tr>
    </tbody>
    </table>
    <?php
}

add_action('save_post', 'hkr_dnrs_save_record');

function hkr_dnrs_save_record() {
    // check if data is being saved
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // check nonce
    if ( !isset($_POST['hkr_dnrs_record_fields_nonce']) || !wp_verify_nonce( $_POST['hkr_dnrs_record_fields_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    global $post;
    $fields_str = array( 'ag_rec', 'picnic_rec', 'fs_rec', 'cc_rec', 'end_rec', 'inf_addr', 'planned_giving_rec', 'alumni_rec', 'import_id' );
    $fields_num = array( 'ag_amount', 'picnic_amount', 'fs_amount' );
    $school_year = ( !empty($_POST['school_year']) ) ? $_POST['school_year'] : null;
    $cached_year = str_replace('-', '_', $school_year);

    hkr_save_custom_fields( $post->ID, $fields_str );
    hkr_save_custom_fields( $post->ID, $fields_num, 0 );
    wp_set_object_terms( $post->ID, $school_year, 'school_year' );

    // delete transient of ag levels shortcode
    delete_transient($cached_year . '_ag_levels_cached');
}


add_action('save_post', 'hkr_dnrs_record_sync_roles', 15);

function hkr_dnrs_record_sync_roles() {

    // check if data is being saved
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // check nonce
    if ( !isset($_POST['hkr_dnrs_record_fields_nonce']) || !wp_verify_nonce( $_POST['hkr_dnrs_record_fields_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    global $post;

    $constituents = get_posts( array(
      'connected_type' => 'constituents_to_records',
      'connected_items' => $post
    ) );

    foreach( $constituents as $constituent ) {
        $p2p_role = p2p_get_meta( $constituent->p2p_id, 'role', true );
        if ( !empty($p2p_role) ) {
            $p2p_role = trim( str_replace( ' ', '-', strtolower( $p2p_role ) ) );
            $record_years = wp_get_object_terms( $post->ID, 'school_year', array('fields' => 'names') );
            $record_year = $record_years[0];

            $role_term = $record_year . '-' . $p2p_role;
            if ( term_exists( $role_term, 'role') ) {
                wp_set_object_terms( $constituent->ID, $role_term, 'role', true );
            }
        }
    }
}


add_filter( 'the_title', 'hkr_dnrs_record_title', 10, 2 );

function hkr_dnrs_record_title( $title, $post_id ) {
    $post = get_post( $post_id );

    if ( $post->post_type != 'record' )
        return $title;

    $title = '';
    $year = wp_get_object_terms( $post_id, 'school_year', array( 'fields' => 'names' ) );
    $year = $year[0];
    $inf_addr = get_post_meta( $post_id, 'inf_addr', true );

    if ( !empty($year) )
        $title = $year . ': ';

    if ( !empty($inf_addr) ) {
        $title .= $inf_addr;
    }
    else {
        $title .= 'Unknown Informal Addressee';
    }

    return $title;
}

add_action( 'init', 'hkr_dnrs_register_record_tax');

function hkr_dnrs_register_record_tax() {

    // Gift
    $labels = array(
        'name' => _x( 'Gifts', 'taxonomy general name' ),
        'singular_name' => _x( 'Gift', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Gifts' ),
        'all_items' => __( 'All Gifts' ),
        'edit_item' => __( 'Edit Gift' ),
        'update_item' => __( 'Update Gift' ),
        'add_new_item' => __( 'Add New Gift' ),
        'new_item_name' => __( 'New Gift Name' ),
        'menu_name' => __( 'Gifts' ),
    );

    register_taxonomy( 'gift', array('record'), array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'gift' )
    ));


    // Honorary Dedications
    $labels = array(
        'name' => _x( 'Honorary Dedications', 'taxonomy general name' ),
        'singular_name' => _x( 'Honorary Dedication', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Dedications' ),
        'all_items' => __( 'All Dedications' ),
        'edit_item' => __( 'Edit Dedication' ),
        'update_item' => __( 'Update Dedication' ),
        'add_new_item' => __( 'Add New Dedication' ),
        'new_item_name' => __( 'New Dedication Name' ),
        'menu_name' => __( 'Honorary Dedications' ),
    );

    register_taxonomy( 'iho', array('record'), array(
        'labels' => $labels,
        'hierarchical' => false,
        'query_var' => true,
        'show_in_nav_menus' => false,
        'rewrite' => array( 'slug' => 'honorary' )
    ));


    // Memorial Dedications
    $labels = array(
        'name' => _x( 'Memorial Dedications', 'taxonomy general name' ),
        'singular_name' => _x( 'Memorial Dedication', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Dedications' ),
        'all_items' => __( 'All Dedications' ),
        'edit_item' => __( 'Edit Dedication' ),
        'update_item' => __( 'Update Dedication' ),
        'add_new_item' => __( 'Add New Dedication' ),
        'new_item_name' => __( 'New Dedication Name' ),
        'menu_name' => __( 'Memorial Dedications' ),
    );

    register_taxonomy( 'imo', array('record'), array(
        'labels' => $labels,
        'hierarchical' => false,
        'query_var' => true,
        'show_in_nav_menus' => false,
        'rewrite' => array( 'slug' => 'memorial' )
    ));


     // School Years
    $labels = array(
        'name' => _x( 'School Years', 'taxonomy general name' ),
        'singular_name' => _x( 'School Year', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search School Years' ),
        'all_items' => __( 'All School Years' ),
        'edit_item' => __( 'Edit School Year' ),
        'update_item' => __( 'Update School Year' ),
        'add_new_item' => __( 'Add New School Year' ),
        'new_item_name' => __( 'New School Year' ),
        'menu_name' => __( 'School Years' ),
    );

    register_taxonomy( 'school_year', array('record'), array(
        'labels' => $labels,
        'hierarchical' => false,
        'query_var' => true,
        'show_ui' => true,
        'rewrite' => array( 'slug' => 'school_year' )
    ));


}

// add custom columns to constituents
add_filter('manage_edit-constituent_columns', 'hkr_dnrs_constituent_col_heads', 10);
add_action('manage_constituent_posts_custom_column', 'hkr_dnrs_constituent_col_data', 10, 2);

function hkr_dnrs_constituent_col_heads($defaults) {
        // $defaults['modified'] = 'Modified';
        $defaults['fname'] = 'First Name';
        $defaults['lname'] = 'Last Name';
        $defaults['class_year'] = 'Class Year';
        $defaults['role'] = 'Roles';
        // unset( $defaults['date'] );
	return $defaults;
}

function hkr_dnrs_constituent_col_data($column_name, $post_ID) {
	if ('modified' == $column_name) {
            echo get_post_field('post_modified', $post_ID);
        }
        if ($column_name == 'fname') {
            $fname = get_post_meta( $post_ID, 'fname', true);
            if ( $fname )
                echo $fname;
	}
        if ($column_name == 'lname') {
            $lname = get_post_meta( $post_ID, 'lname', true);
            if ( $lname )
                echo $lname;
	}
        if ( $column_name == 'class_year' ) {
            $class_year = hkr_dnrs_get_class_year( $post_ID );
            if ( $class_year )
                echo $class_year;
        }
        if ( $column_name == 'role' ) {
            $roles = wp_get_post_terms( $post_ID, 'role', array("fields" => "names"));
            foreach ( $roles as $role ) {
                echo $role . '</br >';
            }
        }

}


add_filter( 'manage_edit-constituent_sortable_columns', 'hkr_dnrs_constituent_col_register_sortable' );

function hkr_dnrs_constituent_col_register_sortable( $defaults ) {
	// $defaults['modified'] = 'Modified';
    $defaults['fname'] = 'First Name';
    $defaults['lname'] = 'Last Name';

	return $defaults;
}


add_filter( 'request', 'custom_cons_column_orderby' );

function custom_cons_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'Modified' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'orderby' => 'modified'
		) );
	}
        if ( isset( $vars['orderby'] ) && 'FirstName' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'fname',
			'orderby' => 'meta_value'
		) );
	}
        if ( isset( $vars['orderby'] ) && 'LastName' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'lname',
			'orderby' => 'meta_value'
		) );
	}

	return $vars;
}


// add custom columns to records
add_filter('manage_edit-record_columns', 'hkr_dnrs_record_col_heads', 10);
add_action('manage_record_posts_custom_column', 'hkr_dnrs_record_col_data', 10, 2);

function hkr_dnrs_record_col_heads($defaults) {
        $defaults['year'] = 'Campaign Year';
        $defaults['ag_amount'] = 'Annual Giving Donation';
        // unset( $defaults['date'] );
	return $defaults;
}

function hkr_dnrs_record_col_data($column_name, $post_ID) {
	if ($column_name == 'year') {
            $year = wp_get_object_terms( $post_ID, 'school_year', array( 'fields' => 'names' ) );
            $year = $year[0];
            if ( $year )
                echo $year;
	}
        if ($column_name == 'ag_amount') {
            $ag_amount = get_post_meta( $post_ID, 'ag_amount', true);
            if ( $ag_amount )
                echo '$'. $ag_amount;
	}
}

add_filter( 'manage_edit-record_sortable_columns', 'hkr_dnrs_record_col_register_sortable' );

function hkr_dnrs_record_col_register_sortable( $defaults ) {
	$defaults['year'] = 'Year';
        $defaults['ag_amount'] = 'Annual Giving Donation';

	return $defaults;
}


add_filter( 'request', 'custom_record_column_orderby' );

function custom_record_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'Year' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'year',
			'orderby' => 'meta_value_num'
		) );
	}
    if ( isset( $vars['orderby'] ) && 'AnnualGivingDonation' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'ag_amount',
			'orderby' => 'meta_value_num'
		) );
	}

	return $vars;
}

/* Helper Functions
------------------------------------------------------------------------ */

function hkr_dnrs_get_class_year( $post_id ) {
    $class_years = wp_get_object_terms( $post_id, 'class_year', array('fields' => 'names') );
    if ( count($class_years) == 1 ) {
        return $class_years[0];
    } 
    else {
        return false;
    }
}

?>
