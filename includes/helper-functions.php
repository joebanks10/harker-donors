<?php

/**
 * Takes an array of meta keys and saves the POST variables if they exist
 *
 * @param int $post_id
 * @param array $meta_keys
 * @param int|string|array $default
 */
function hkr_save_custom_fields( $post_id, $meta_keys, $default = '' ) {
    foreach( $meta_keys as $meta_key ) {
        if ( !empty( $_POST[$meta_key] ) ) {
            if ( is_array($_POST[$meta_key]) ) {
                delete_post_meta($post_id, $meta_key);
                foreach( $_POST[$meta_key] as $meta_value ) {
                    add_post_meta( $post_id, $meta_key, $meta_value);
                }
            }
            else {
                update_post_meta( $post_id, $meta_key, $_POST[$meta_key] );
            }
        }
        else {
            update_post_meta( $post_id, $meta_key, $default );
        }
    }
}

/**
 * Takes an array of meta keys and returns their values in an array
 *
 * @param int $post_id
 * @param array $meta_keys
 * @param int|string|array $default
 * @return array meta_key -> meta_value pairs
 */
function hkr_get_custom_fields( $post_id, $meta_keys, $default = '' ) {
    $custom = get_post_custom( $post_id );
    $fields = array();
    foreach( $meta_keys as $meta_key ) {
        if ( isset( $custom[$meta_key] ) ) {
            if ( count($custom[$meta_key]) > 1 ) {
                $fields[$meta_key] = $custom[$meta_key];
            }
            else {
                $fields[$meta_key] = $custom[$meta_key][0];
            }
        }
        else {
            $fields[$meta_key] = $default;
        }
    }
    return $fields;
}

/**
 * Gets the current school year
 *
 * @return string the current school year
 */
function hkr_get_current_school_year() {
    $current_year = (int) date('Y');
    $current_month = (int) date('n');

    // if it's before August, get the last year
    $current_year = ( $current_month < 8 ) ? $current_year - 1 : $current_year;

    return $current_year . '-' . substr( (string) $current_year + 1, -2);
}

function hkr_get_end_school_year( $school_year ) {
    if ( preg_match( '/^\d\d\d\d/', $school_year, $matches ) ) {
        return intval( $matches[0] ) + 1;
    }
    else {
        return 0;
    }
}

function hkr_add_term( $term_name, $taxonomy, $parent_id = 0 ) {
    if ( empty($term_name) ) {
        return 0;
    }

    if ( $term = term_exists( $term_name, $taxonomy, $parent_id )) {
        $term_id = intval($term['term_id']);
    }
    else {
        $term = wp_insert_term( $term_name, $taxonomy, array( 'parent' => $parent_id ) );
        if ( !empty($term) && !is_wp_error($term) ) {
            $term_id = intval($term['term_id']);
        }
        else {
            $term_id = $term;
        }
    }
    return $term_id;
}

function hkr_get_school_year( $post_id ) {
    $root_page = hkr_get_root_page( $post_id );
    if ( preg_match('/\d\d\d\d-\d\d/', $root_page->post_name, $matches ) ) {
        return $matches[0];
    }
    else {
        return 0;
    }
}

function hkr_get_root_page( $post_id ) {
    $ancestors = get_post_ancestors( $post_id ); // returns array of ancestors
    $root_id = ( $ancestors ) ? $ancestors[count($ancestors)-1] : $post_id; // root is last array element
    $root = get_page( $root_id ); // get root page's data

    return $root;
}

?>
