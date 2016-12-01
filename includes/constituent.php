<?php

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

    // get capital campaign levels
    $cc_levels = get_terms('cc_level', array(
        'hide_empty' => false
    ));
    $cons_cc_levels = wp_get_object_terms($post->ID, 'cc_level');
    $cons_cc_level_id = (isset($cons_cc_levels[0])) ? $cons_cc_levels[0]->term_id : 0;

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
        <?php if(!empty($cc_levels)): ?>
        <tr>
            <th scope="row"><label for="cc_level">Capital Campaign Level</label></th>
            <td>
                <select name="cc_level" id="cc_level">
                    <option value="0" <?php selected( 0, $cons_cc_level_id) ?> ><?php echo 'None'; ?></option>
                    <?php foreach($cc_levels as $level): ?>
                        <option value="<?php echo $level->term_id; ?>" <?php selected( $level->term_id, $cons_cc_level_id) ?> ><?php echo $level->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php endif; ?>
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

    if ( !empty($_POST['cc_level']) )
        wp_set_post_terms($post->ID, array(intval($_POST['cc_level'])), 'cc_level' );
    else
        wp_set_post_terms($post->ID, null, 'cc_level' );
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

    // Capital Giving Level
    $labels = array(
        'name' => _x( 'CC Levels', 'taxonomy general name' ),
        'singular_name' => _x( 'CC Level', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Levels' ),
        'all_items' => __( 'All Levels' ),
        'edit_item' => __( 'Edit Level' ),
        'update_item' => __( 'Update Level' ),
        'add_new_item' => __( 'Add New Level' ),
        'new_item_name' => __( 'New Level Name' ),
        'menu_name' => __( 'CC Levels' ),
    );

    register_taxonomy( 'cc_level', array('constituent'), array(
        'labels' => $labels,
        'hierarchical' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'cc_level' )
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

add_action( 'admin_menu' , 'hkr_dnrs_remove_cc_level_meta' );

function hkr_dnrs_remove_cc_level_meta() {
    remove_meta_box( 'cc_leveldiv', 'constituent', 'side' );
}