<?php

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
