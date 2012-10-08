<?php

/* Annual Giving Class Years */
add_shortcode( 'dnrs_class_year', 'hkr_dnrs_class_year_shortcode' );

function hkr_dnrs_class_year_shortcode( $atts ) {

    extract($atts = shortcode_atts( array(
        'class_year' => 0,
    ), $atts ));

    if ( !isset($class_year) )
        return;

    $school_year = '2011-12';

    $class_totals = array(
        '2024' => 81,
        '2023' => 84,
        '2022' => 89,
        '2021' => 104,
        '2020' => 118,
        '2019' => 131,
        '2018' => 164,
        '2017' => 168,
        '2016' => 167,
        '2015' => 191,
        '2014' => 183,
        '2013' => 186,
        '2012' => 179,
    ); // TODO: Use configuration from Annual Report post

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => array( "$school_year-student", "$school_year-alumni" )
                ),
                array(
                        'taxonomy' => 'class_year',
                        'field' => 'slug',
                        'terms' => $class_year
                ),
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Child' ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ));

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Child' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    p2p_type( 'parent_to_child' )->each_connected( $query, array(
        'tax_query' => array(
                    array(
                            'taxonomy' => 'role',
                            'field' => 'slug',
                            'terms' => "$school_year-parent"
                    )
        )
    ), 'parents' );

    $content = '';
    if ( $query->have_posts() ) {

        $list = '';
        $class_count = 0;
        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );
            $child = hkr_dnrs_get_title_by_cons( $cons_custom );

            foreach( $post->records as $record ) {

                $record_custom = get_post_custom( $record->ID );
                $gift_terms = wp_get_object_terms( $record->ID , 'gift', array( 'fields' => 'slugs' ) );

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'inf_addr', $post->parents );

                $icon = ' ';
                if ( in_array('senior-brick', $gift_terms ) )
                    $icon .= '<i class="icon-tint"></i>';
                if ( in_array('senior-parent-appreciation-gift', $gift_terms ) )
                    $icon .= '<i class="icon-star"></i>';

                $list .= '<li class="' . implode(' ', get_post_class( $gift_terms, $record->ID ) ) . '">' . $title . '<br />' . $child . $icon . '</li>';
            }
            $class_count++;
        }

        $percent = round( $class_count/$class_totals[$class_year] * 100 );
        $stat = "<h2>$percent% ($class_count out of {$class_totals[$class_year]}) gave.</h2>";

        $content .= $stat;
        $content .= '<ul class="ar-list">' . $list . '</ul>';
    }

    wp_reset_postdata();
    return $content;
}



/* Annual Giving Levels */
add_shortcode( 'annual_giving', 'hkr_dnrs_ag_shortcode' );

function hkr_dnrs_ag_shortcode() {

    $cached_content = get_transient('ag_levels_cached');
    if( $cached_content ) {
        return $cached_content;
    }

    $school_year = '2011-12';
    set_time_limit(60);

    $levels = array(
        array(
            'label' => 'Leadership Circle',
            'slug' => 'leadership-circle',
            'min' => 25000,
            'max' => 999999999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Eagles\' Circle',
            'slug' => 'eagles-circle',
            'min' => 10000,
            'max' => 24999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Trustees\' Circle',
            'slug' => 'trustees-circle',
            'min' => 7500,
            'max' => 9999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Benefactors\' Circle',
            'slug' => 'benefactors-circle',
            'min' => 5000,
            'max' => 7499,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Head of School\'s Circle',
            'slug' => 'head-of-schools-circle',
            'min' => 2500,
            'max' => 4999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Ambassadors\' Circle',
            'slug' => 'ambassadors-circle',
            'min' => 1000,
            'max' => 2499,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Friends',
            'slug' => 'friends',
            'min' => 1,
            'max' => 999,
            'list' => '',
            'anonymous' => 0
        ),
    );

    $level_count = count($levels);

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'tax_query' => array(
                array(
                        'taxonomy' => 'gift',
                        'field' => 'slug',
                        'terms' => 'annual-giving'
                ),
                array(
                        'taxonomy' => 'school_year',
                        'field' => 'slug',
                        'terms' => $school_year
                )
        )
    ), 'records' );

    $content = '';
    while ( $query->have_posts() ) {
        $query->the_post();
        global $post;
        $cons_custom = get_post_custom( $post->ID );

        foreach( $post->records as $record ) {

            $record_custom = get_post_custom( $record->ID );
            $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

            for( $i = 0; $i < $level_count; $i++ ) {
                if ( $record_custom['ag_amount'][0] < $levels[$i]['min'] || $record_custom['ag_amount'][0] > $levels[$i]['max'] ) {
                    continue;
                }
                
                if ( $title == 'Anonymous' ) {
                    $levels[$i]['anonymous']++;
                    continue;
                }

                $levels[$i]['list'] .= '<li>' . $title . '</li>';
            }

        }
    }

    foreach( $levels as $level ) {
        if ( $level['anonymous'] )
            $level['list'] .= "<li>Anonymous ({$level['anonymous']})</li>";

        if ( empty($level['list']) )
            continue;

        $content .= '<a name="' . $level['slug'] . '"></a>';
        $content .= '<h2>' . $level['label'] . '</h2>';
        $content .= '<ul class="ar-list">' . $level['list'] . '</ul>';
        $content .= '<p><a href="#top">Back to top</a></p>';
    }

    wp_reset_postdata();
    set_transient( 'ag_levels_cached', $content, 60 * 60 * 24 );
    return $content;
}



/* Alumni Leaders & Pacesetters */
add_shortcode( 'alumni_lp', 'hkr_dnrs_alumni_lp_shortcode' );

function hkr_dnrs_alumni_lp_shortcode() {

    $school_year = '2011-12';

    $groups = array(
        'leaders' => array(
            'title' => 'Alumni Leaders',
            'list' => '',
            'anonymous' => 0
        ),
        'pacesetters' => array(
            'title' => 'Alumni Pacesetters',
            'list' => '',
            'anonymous' => 0
        )
    );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
		relation => 'AND',
		array(
			'taxonomy' => 'role',
			'field' => 'slug',
			'terms' => array( "$school_year-alumni" )
		),
                array(
			'taxonomy' => 'role',
			'field' => 'slug',
			'terms' => array( "$school_year-record-owner", "$school_year-spouse" )
		)
	),
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array(
                array(
			'key' => 'role',
			'value' => array( 'Record Owner', 'Spouse' )
		)
        ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array(
                array(
			'key' => 'role',
			'value' => array( 'Record Owner', 'Spouse' )
		)
        ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $end_school_year = hkr_get_end_school_year( $school_year );

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            $cons_custom = get_post_custom( $post->ID );
            $class_year = ( !empty($cons_custom['class_year'][0]) ) ? intval($cons_custom['class_year'][0]) : false;
            $title = hkr_dnrs_get_title_by_cons( $cons_custom );

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $ag_amount = intval($record_custom['ag_amount'][0]);
                $alumni_amount = ($end_school_year - $class_year) * 5;

                if ( $ag_amount > $alumni_amount ) {
                    if ( $title == 'Anonymous' ) {
                        $groups['leaders']['anonymous']++;
                    }
                    else {
                        $groups['leaders']['list'] .= '<li>' . $title . '</li>';
                    }
                    break;
                }
                else if ( $ag_amount == $alumni_amount ) {
                    if ( $title == 'Anonymous' ) {
                        $groups['pacesetters']['anonymous']++;
                    }
                    else {
                        $groups['pacesetters']['list'] .= '<li>' . $title . '</li>';
                    }
                    break;
                }
            }
        }

        foreach( $groups as $group ) {
            $content .= '<h2>'. $group['title'] . '</h2>';
            $content .= '<ul class="ar-list">' . $group['list'] . '</ul>';
        }

    }

    wp_reset_postdata();
    return $content;
}



/* Organizations */
add_shortcode( 'organizations', 'hkr_dnrs_orgs_shortcode' );

function hkr_dnrs_orgs_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-organization"
                )
        ),
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
                'tax_query' => array(
                        array(
                                'taxonomy' => 'gift',
                                'field' => 'slug',
                                'terms' => 'annual-giving'
                        ),
                        array(
                                'taxonomy' => 'school_year',
                                'field' => 'slug',
                                'terms' => $school_year
                        )
                )
        ),
        'meta_key' => 'org_name',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    $content = '';
    if ( $query->have_posts() ) {
        
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            $title = get_the_title();

            if ( $title == 'Anonymous' ) {
                $anonymous++;
                continue;
            }

            $content .= '<li>' . $title . '</li>';
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Faculty & Staff */
add_shortcode( 'faculty_staff', 'hkr_dnrs_fac_staff_shortcode' );

function hkr_dnrs_fac_staff_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-faculty-staff"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
                'tax_query' => array(
                        array(
                                'taxonomy' => 'gift',
                                'field' => 'slug',
                                'terms' => 'annual-giving'
                        ),
                        array(
                                'taxonomy' => 'school_year',
                                'field' => 'slug',
                                'terms' => $school_year
                        )
                )
        )
    ) );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            $title = get_the_title();

            if ( $title == 'Anonymous' ) {
                $anonymous++;
                continue;
            }

            $content .= '<li>' . $title . '</li>';
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Grandparents */
add_shortcode( 'grandparents', 'hkr_dnrs_gp_shortcode' );

function hkr_dnrs_gp_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => array( "$school_year-grandparent", "$school_year-record-owner" ),
                        'operator' => 'AND'
                )
        ),
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    p2p_type( 'parent_to_child' )->each_connected( $query, array(
        'connected_meta' => array( 'relationship' => 'Grandparent-Grandchild' ),
    ), 'grandchildren' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Grandparents</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );

            $children = '';
            foreach( $post->grandchildren as $child ) {
                $child_custom = get_post_custom( $child->ID );
                $children .= '<br />';
                $children .= hkr_dnrs_get_title_by_cons( $child_custom );
            }

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . $children . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Friends */
add_shortcode( 'friends', 'hkr_dnrs_friends' );

function hkr_dnrs_friends() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => array( "$school_year-friend", "$school_year-record-owner" ),
                        'operator' => 'AND'
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            )
        )
    ));

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Friends</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Honorary Gifts */
add_shortcode( 'honorary_gifts', 'hkr_dnrs_iho_shortcode' );

function hkr_dnrs_iho_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                array(
                        'taxonomy' => 'gift',
                        'field' => 'slug',
                        'terms' => 'honorary'
                ),
                array(
                        'taxonomy' => 'school_year',
                        'field' => 'slug',
                        'terms' => $school_year
                )
            )
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'honorary'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Honorary Gifts</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $dedications = wp_get_object_terms( $record->ID, 'iho', array( 'fields' => 'names' ) );

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $names = '';
                foreach( $dedications as $name ) {
                    $names .= '<br />' .  $name;
                }

                $content .= '<li>' . $title . $names . '</li>';

            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Memorial Gifts */
add_shortcode( 'memorial_gifts', 'hkr_dnrs_imo_shortcode' );

function hkr_dnrs_imo_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                array(
                        'taxonomy' => 'gift',
                        'field' => 'slug',
                        'terms' => 'memorial'
                ),
                array(
                        'taxonomy' => 'school_year',
                        'field' => 'slug',
                        'terms' => $school_year
                )
            )
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'memorial'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Memorial Gifts</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $dedications = wp_get_object_terms( $record->ID, 'imo', array( 'fields' => 'names' ) );

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $names = '';
                foreach( $dedications as $name ) {
                    $names .= '<br />' .  $name;
                }

                $content .= '<li>' . $title . $names . '</li>';

            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Eagle Clubs */
add_shortcode( 'eagle_clubs', 'hkr_dnrs_eagle_clubs' );

function hkr_dnrs_eagle_clubs() {

    $school_year = '2011-12';

    $clubs = array(
        array(
            'title' => 'Financial Aid',
            'slug' => 'financial-aid'
        ),
        array(
            'title' => 'Athletic Boosters',
            'slug' => 'athletic-boosters'
        ),
        array(
            'title' => 'Friends of Debate',
            'slug' => 'friends-of-debate'
        ),
        array(
            'title' => 'Friends of the Library',
            'slug' => 'friends-of-the-library'
        ),
        array(
            'title' => 'Patrons of the Arts',
            'slug' => 'patrons-of-the-arts'
        ),
        array(
            'title' => 'Friends of Robotics',
            'slug' => 'friends-of-robotics'
        ),
        array(
            'title' => 'Friends of Journalism',
            'slug' => 'friends-of-journalism'
        )
    );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'eagle-clubs'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            )
        )
    ));

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'eagle-clubs'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {
        foreach( $clubs as $club ) {

            $content .= '<h2>' . $club['title'] . '</h2>';
            $content .= '<ul class="ar-list">';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach ( $post->records as $record ) {
                    if ( !has_term( $club['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    $content .= '<li>' . $title . '</li>';
                }
            }

            $content .= "<li>Anonymous ($anonymous)</li>";
            $content .= '</ul>';
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return $content;
}

function hkr_dnrs_eagle_clubs_by_record() {

    $query = new WP_Query( array(
        'post_type' => 'record',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'gift',
                        'field' => 'slug',
                        'terms' => 'eagle-clubs'
                ),
                array(
                        'taxonomy' => 'school_year',
                        'field' => 'slug',
                        'terms' => '2011-12'
                )
        ),
        'meta_key' => 'ag_rec',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query );

    $content = '';
    if ( $query->have_posts() ) {

        $clubs = array(
            array(
                'title' => 'Financial Aid',
                'slug' => 'financial-aid'
            ),
            array(
                'title' => 'Athletic Boosters',
                'slug' => 'athletic-boosters'
            ),
            array(
                'title' => 'Friends of Debate',
                'slug' => 'friends-of-debate'
            ),
            array(
                'title' => 'Friends of the Library',
                'slug' => 'friends-of-the-library'
            ),
            array(
                'title' => 'Patrons of the Arts',
                'slug' => 'patrons-of-the-arts'
            ),
            array(
                'title' => 'Friends of Robotics',
                'slug' => 'friends-of-robotics'
            ),
            array(
                'title' => 'Friends of Journalism',
                'slug' => 'friends-of-journalism'
            )
        );

        foreach( $clubs as $club ) {

            $content .= '<h2>' . $club['title'] . '</h2>';
            $content .= '<ul class="ar-list">';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                if ( !has_term( $club['slug'], 'gift', $post->ID ) ) {
                    continue;
                }

                $record_custom = get_post_custom( $post->ID );

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', $post->connected );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }
                
                $content .= '<li>' . $title . '</li>';
            }

            $content .= "<li>Anonymous ($anonymous)</li>";
            $content .= '</ul>';
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return $content;
}


/* Eagle's Nest Club */
add_shortcode( 'ag_eagles_club', 'hkr_dnrs_ag_eagles_club' );

function hkr_dnrs_ag_eagles_club() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'eagles-nest-club'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            )
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'eagles-nest-club'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Annual Giving Eagle\'s Nest Club</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                if ( has_term( 'eagles-nest-bold', 'gift', $record->ID ) ) {
                    $content .= '<li><strong>' . $title . '</strong></li>';
                }
                else {
                    $content .= '<li>' . $title . '</li>';
                }
            }
        }
        $content .= "<li>Anonymous ($anonymous)</li>";
        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}

function hkr_dnrs_ag_eagles_club_by_record() {

    $query = new WP_Query( array(
        'post_type' => 'record',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'gift',
                        'field' => 'slug',
                        'terms' => 'eagles-nest-club'
                ),
                array(
                        'taxonomy' => 'school_year',
                        'field' => 'slug',
                        'terms' => '2011-12'
                )
        ),
        'meta_key' => 'ag_rec',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Annual Giving Eagle\'s Nest Club</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $record_custom = get_post_custom( $post->ID );

            $title = hkr_dnrs_get_title_by_record( $record_custom, 'ag_rec', $post->connected );

            if ( $title == 'Anonymous' ) {
                $anonymous++;
                continue;
            }

            if ( has_term( 'eagles-nest-bold', 'gift' ) ) {
                $content .= '<li><strong>' . $title . '</strong></li>';
            }
            else {
                $content .= '<li>' . $title . '</li>';
            }

        }
        $content .= "<li>Anonymous ($anonymous)</li>";
        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Picnic */
add_shortcode( 'picnic', 'hkr_dnrs_picnic_shortcode' );

function hkr_dnrs_picnic_shortcode() {

    $school_year = '2011-12';

    $sponsor_levels = array(
        array(
            'title' => 'Towering Top Hats',
            'desc' => '($5,000+)',
            'min' => 5000,
            'max' => 999999
        ),
        array(
            'title' => 'Stately Stetsons',
            'desc' => '($2,500-$4,999)',
            'min' => 2500,
            'max' => 4999
        ),
        array(
            'title' => 'Fancy Fedoras',
            'desc' => '($1,500-$2,499)',
            'min' => 1500,
            'max' => 2499
        ),
        array(
            'title' => 'Dashing Derbies',
            'desc' => '($1,000-$1,499)',
            'min' => 1000,
            'max' => 1499
        ),
        array(
            'title' => 'Beautiful Bonnets',
            'desc' => '($500-$999)',
            'min' => 500,
            'max' => 999
        ),
        array(
            'title' => 'Teenie Beanies',
            'desc' => '($250-$499)',
            'min' => 250,
            'max' => 499
        ),
    );

    $sponsors = array(
        'title' => 'Picnic Sponsors',
        'slug' => 'picnic-sponsor',
        'levels' => $sponsor_levels
    );

    $groups = array(
        array(
            'title' => 'Picnic In-Kind Sponsors',
            'slug' => 'picnic-in-kind-sponsor'
        ),
        array(
            'title' => 'Picnic Cash Donors',
            'slug' => 'picnic-cash-donor'
        ),
        array(
            'title' => 'Picnic Teacher Packages',
            'slug' => 'picnic-teacher-pack'
        )
    );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'picnic'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'picnic'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>' . $sponsors['title'] . '</h2>';
        foreach( $sponsors['levels'] as $level ) {
            $ul = '';
            $anonymous = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach( $post->records as $record ) {
                    if ( !has_term( $sponsors['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    if ( $record_custom['picnic_amount'][0] < $level['min'] || $record_custom['picnic_amount'][0] > $level['max'] ) {
                        continue;
                    }

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'picnic_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    $ul .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $ul .= "<li>Anonymous ($anonymous)</li>";

            if ( empty($ul) )
                continue;

            $content .= '<h3>' . $level['title'] . ' '. $level['desc'] . '</h3>';
            $content .= '<ul class="ar-list">' . $ul . '</ul>';
            $query->rewind_posts();
        }

        foreach( $groups as $group ) {

            $content .= '<h2>' . $group['title'] . '</h2>';
            $content .= '<ul class="ar-list">';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach( $post->records as $record ) {
                    if ( !has_term( $group['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'picnic_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    $content .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $content .= "<li>Anonymous ($anonymous)</li>";

            $content .= '</ul>';
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return $content;
}



/* Fashion Show */
add_shortcode( 'fashion_show', 'hkr_dnrs_fs_shortcode' );

function hkr_dnrs_fs_shortcode() {

    $school_year = '2011-12';

    $sponsor_levels = array(
        array(
            'title' => 'Diamond',
            'desc' => '($15,000+)',
            'min' => 15000,
            'max' => 999999
        ),
        array(
            'title' => 'Gold',
            'desc' => '($10,000+)',
            'min' => 10000,
            'max' => 14999
        ),
        array(
            'title' => 'Silver',
            'desc' => '($5,000+)',
            'min' => 5000,
            'max' => 9999
        ),
        array(
            'title' => 'Bronze',
            'desc' => '($2,500+)',
            'min' => 2500,
            'max' => 4999
        )
    );

    $sponsors = array(
        'title' => 'Fashion Show Sponsors',
        'slug' => 'fashion-show-sponsor',
        'levels' => $sponsor_levels
    );

    $groups = array(
        array(
            'title' => 'Fashion Show In-Kind Sponsors',
            'slug' => 'fashion-show-in-kind-sponsor'
        ),
        array(
            'title' => 'Fashion Show In-Kind Donors',
            'slug' => 'fashion-show-in-kind-donor'
        ),
        array(
            'title' => 'Fashion Show Cash Donors',
            'slug' => 'fashion-show-cash-donors'
        )
    );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'fashion-show'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'fashion-show'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>' . $sponsors['title'] . '</h2>';
        foreach( $sponsors['levels'] as $level ) {
            $ul = '';
            $anonymous = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach( $post->records as $record ) {
                    if ( !has_term( $sponsors['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    if ( $record_custom['fs_amount'][0] < $level['min'] || $record_custom['fs_amount'][0] > $level['max'] ) {
                        continue;
                    }

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'fs_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    $ul .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $ul .= "<li>Anonymous ($anonymous)</li>";

            if ( empty($ul) )
                continue;

            $content .= '<h3>' . $level['title'] . ' '. $level['desc'] . '</h3>';
            $content .= '<ul class="ar-list">' . $ul . '</ul>';
            $query->rewind_posts();
        }

        foreach( $groups as $group ) {

            $content .= '<h2>' . $group['title'] . '</h2>';
            $content .= '<ul class="ar-list">';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach( $post->records as $record ) {
                    if ( !has_term( $group['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'fs_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    $content .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $content .= "<li>Anonymous ($anonymous)</li>";

            $content .= '</ul>';
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return $content;
}



/* Alumni */
add_shortcode( 'alumni', 'hkr_dnrs_alumni_shortcode' );

function hkr_dnrs_alumni_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                relation => 'AND',
		array(
			'taxonomy' => 'role',
			'field' => 'slug',
			'terms' => array( "$school_year-alumni" )
		),
                array(
			'taxonomy' => 'role',
			'field' => 'slug',
			'terms' => array( "$school_year-record-owner", "$school_year-spouse" )
		)
	),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_meta' => array( 
                array(
			'key' => 'role',
			'value' => array( 'Record Owner', 'Spouse' )
		) ),
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Alumni</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $title = get_the_title();

            if ( $title == 'Anonymous' ) {
                $anonymous++;
                continue;
            }

            $content .= '<li>' . $title . '</li>';
        }
        $content .= '</ul>';

    }

    wp_reset_postdata();
    return $content;
}



/* Alumni Parents */
add_shortcode( 'alumni_parents', 'hkr_dnrs_alparents_shortcode' );

function hkr_dnrs_alparents_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => array( "$school_year-alumni-parent", "$school_year-record-owner" ),
                        'operator' => 'AND'
                )
        ),
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    p2p_type( 'parent_to_child' )->each_connected( $query, array(
        'tax_query' => array(
            array(
                    'taxonomy' => 'role',
                    'field' => 'slug',
                    'terms' => array( "$school_year-alumni" )
            )
        )
    ), 'children' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Alumni Parents</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $cons_custom = get_post_custom( $post->ID );

            $children = '';
            foreach( $post->children as $child ) {
                $child_custom = get_post_custom( $child->ID );
                $children .= '<br />';
                $children .= hkr_dnrs_get_title_by_cons( $child_custom );
            }

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'inf_addr', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . $children . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Senior Class Gift */
add_shortcode( 'senior_class_gift', 'hkr_dnrs_senior_class_gift_shortcode' );

function hkr_dnrs_senior_class_gift_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'class_year',
                        'field' => 'slug',
                        'terms' => '2012'
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'senior-class-gift'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Senior Class Gift</h2>';
        $content .= '<ul class="ar-list">';

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            $content .= '<li>' . get_the_title() . '</li>';
        }
        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Senior Parent Appreciation Gift */
add_shortcode( 'spag', 'hkr_dnrs_spag_shortcode' );

function hkr_dnrs_spag_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => array( "$school_year-parent", "$school_year-record-owner" ),
                        'operator' => 'AND'
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'senior-parent-appreciation-gift'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'annual-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Senior Parent Appreciation Gift</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'inf_addr', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* John Near Excellence in History Education Endowment */
add_shortcode( 'john_near_end', 'hkr_dnrs_john_near_shortcode' );

function hkr_dnrs_john_near_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'john-near-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'john-near-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>John Near Excellence in History Education Endowment</h2>';
        $content .= '<ul class="ar-list nocol">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'end_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Sharron Mittelstet Endowment */
add_shortcode( 'sharron_end', 'hkr_dnrs_sharronm_shortcode' );

function hkr_dnrs_sharronm_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'sharron-mittelstet-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'sharron-mittelstet-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>Endowed Scholarship Fund in Memory of Sharron Mittelstet</h2>';
        $content .= '<ul class="ar-list">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'end_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Nichols Planned Giving */
add_shortcode( 'nichols_planned_giving', 'hkr_dnrs_nichols_planned_giving_shortcode' );

function hkr_dnrs_nichols_planned_giving_shortcode() {

    $school_year = '2011-12';

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'nichols-planned-giving-society'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'nichols-planned-giving-society'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        $content .= '<h2>The Nichols Planned Giving Society</h2>';
        $content .= '<ul class="ar-list nocol">';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {
                $record_custom = get_post_custom( $record->ID );
                $title = hkr_dnrs_get_title_by_record( $record_custom, 'planned_giving_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $content .= '<li>' . $title . '</li>';
            }
        }

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';
    }
    wp_reset_postdata();
    return $content;

}



/* Capital Giving */
add_shortcode( 'capital_giving', 'hkr_dnrs_cc_shortcode' );

function hkr_dnrs_cc_shortcode() {

    $school_year = '2011-12';

    $levels = array(
        array(
            'title' => 'Founding Visionaries',
            'desc' => '($5,000,000+)',
            'slug' => 'founding-visionaries'
        ),
        array(
            'title' => 'Visionaries',
            'desc' => '($1,000,000+)',
            'slug' => 'visionaries'
        ),
        array(
            'title' => 'Harker Group Gold',
            'desc' => '($500,000+)',
            'slug' => 'harker-group-gold'
        ),
        array(
            'title' => 'Harker Group Silver',
            'desc' => '($250,000+)',
            'slug' => 'harker-group-silver'
        ),
        array(
            'title' => 'Harker Group',
            'desc' => '($100,000+)',
            'slug' => 'harker-group'
        ),
        array(
            'title' => 'Leadership Group Platinum',
            'desc' => '($75,000+)',
            'slug' => 'leadership-platinum'
        ),
        array(
            'title' => 'Leadership Group Gold',
            'desc' => '($50,000+)',
            'slug' => 'leadership-gold'
        ),
        array(
            'title' => 'Leadership Group Silver',
            'desc' => '($35,000+)',
            'slug' => 'leadership-silver'
        ),
        array(
            'title' => 'Leadership Group',
            'desc' => '($25,000+)',
            'slug' => 'leadership'
        ),
        array(
            'title' => 'Friendship Group Platinum',
            'desc' => '($15,000+)',
            'slug' => 'friendship-platinum'
        ),
        array(
            'title' => 'Friendship Group Gold',
            'desc' => '($10,000+)',
            'slug' => 'friendship-gold'
        ),
        array(
            'title' => 'Friendship Group Silver',
            'desc' => '($5,000+)',
            'slug' => 'friendship-silver',
            'min' => 0
        ),
        array(
            'title' => 'Friendship Group',
            'desc' => '($2,500+)',
            'slug' => 'friendship'
        ),
        array(
            'title' => 'Community Group',
            'desc' => '(up to $2,499)',
            'slug' => 'community'
        )
    );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'role',
                        'field' => 'slug',
                        'terms' => "$school_year-record-owner"
                )
        ),
        'meta_key' => 'lname',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'connected_type' => 'constituents_to_records',
        'connected_to' => 'any',
        'connected_query' => array(
            'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'capital-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
            ),
        )
    ) );

    p2p_type( 'constituents_to_records' )->each_connected( $query, array(
        'connected_meta' => array( 'role' => 'Record Owner' ),
        'tax_query' => array(
                    array(
                            'taxonomy' => 'gift',
                            'field' => 'slug',
                            'terms' => 'capital-giving'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    if ( $query->have_posts() ) {

        foreach( $levels as $level ) {

            $content .= '<h2>' . $level['title'] . ' ' . $level['desc'] . '</h2>';
            $content .= '<ul class="ar-list">';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                foreach( $post->records as $record ) {
                    if ( !has_term( $level['slug'], 'gift', $record->ID ) ) {
                        continue;
                    }

                    $record_custom = get_post_custom( $record->ID );

                    $title = hkr_dnrs_get_title_by_record( $record_custom, 'cc_rec', array($post) );

                    if ( $title == 'Anonymous' ) {
                        $anonymous++;
                        continue;
                    }

                    if ( has_term('capital-giving-bold', 'gift', $record->ID ) ) {
                        $content .= '<li><strong>' . $title . '</strong></li>';
                    }
                    else {
                        $content .= '<li>' . $title . '</li>';
                    }
                }
            }
            
            if ( $anonymous )
                $content .= "<li>Anonymous ($anonymous)</li>";

            $content .= '</ul>';
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return $content;
}



function hkr_dnrs_get_title_by_record( $record_custom, $recognition, $constituents = array() ) {
    $title = '';
    if ( !empty($record_custom[$recognition][0]) ) {
        $title = $record_custom[$recognition][0];
    }
    else if ( !empty($record_custom['inf_addr'][0]) ) {
        $title = $record_custom['inf_addr'][0];
    }
    else {
        foreach( $constituents as $constituent ) {
            $cons_custom = get_post_custom( $constituent->ID );
            $roles = p2p_get_meta( $constituent->p2p_id, 'role' );

            if ( in_array( 'Organization', $roles ) ) {
                if ( !empty($cons_custom['org_name'][0]) ) {
                    $title = $cons_custom['org_name'][0];
                }
            }
            else {
                if ( !empty($cons_custom['fname'][0]) || !empty($cons_custom['lname'][0]) ) {
                    $title .= trim( $cons_custom['fname'][0] . ' ' . $cons_custom['lname'][0] ) . ' ';
                }
            }
        }
    }

    $title = trim($title);

    if ( empty($title) )
        $title = 'Anonymous';

    return $title;
}

function hkr_dnrs_get_title_by_cons( $cons_custom ) {
    $title = 'Anonymous';

    $class_year = substr( $cons_custom['class_year'][0], -2);
    $class_year = ( !empty($class_year) ) ? ' \'' . $class_year : '';

    if ( !empty($cons_custom['fname'][0]) || !empty($cons_custom['lname'][0]) ) {
        $title = trim( $cons_custom['fname'][0] . ' ' . $cons_custom['lname'][0] . $class_year );
    }

    return $title;
}

function hkr_dnrs_get_title_by_org( $cons_custom ) {
    $title = 'Anonymous';

    if ( !empty($cons_custom['org_name'][0]) ) {
        $title = $cons_custom['org_name'][0];
    }

    return $title;
}


?>