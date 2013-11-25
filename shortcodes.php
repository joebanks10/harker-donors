<?php

/* Annual Giving Class Years */
add_shortcode( 'dnrs_class_year', 'hkr_dnrs_class_year_shortcode' );

function hkr_dnrs_class_year_shortcode( $atts ) {

    extract($atts = shortcode_atts( array(
        'class_year' => 0,
        'school_year' => 0
    ), $atts ));

    if ( !$class_year )
        return;

    if ( !$school_year ) {
        global $post;
        $school_year = hkr_get_school_year( $post->ID );
        if ( !$school_year ) return;
    }

    if ( $school_year == '2011-12' ) {
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
    }
    else if ( $school_year == '2012-13' ) {
        $class_totals = array(
            '2025' => 77,
            '2024' => 78,
            '2023' => 86,
            '2022' => 99,
            '2021' => 108,
            '2020' => 121,
            '2019' => 163,
            '2018' => 162,
            '2017' => 170,
            '2016' => 188,
            '2015' => 186,
            '2014' => 174,
            '2013' => 179
        ); 
    }
    else if ( $school_year == '2013-14' ) {
        $class_totals = array(
            '2029' => 34,
            '2028' => 42,
            '2027' => 17,
            '2026' => 81,
            '2025' => 81,
            '2024' => 88,
            '2023' => 107,
            '2022' => 120,
            '2021' => 129,
            '2020' => 172,
            '2019' => 163,
            '2018' => 169,
            '2017' => 191,
            '2016' => 188,
            '2015' => 187,
            '2014' => 176
        ); 
    }
    else {
        return;
    }

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
        $has_pledge = false;
        $has_snr_brick = false;
        $has_spag = false;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;
            $child = hkr_dnrs_get_title_by_cons( $post->ID );

            if ( empty ( $post->records ) ) {
                // initial query missed the association, so query again
                $post->records = get_posts( array(
                    'connected_type' => 'constituents_to_records',
                    'connected_items' => $post,
                    'nopaging' => true,
                    'suppress_filters' => false,
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
                ) );
            }

            foreach( $post->records as $record ) {

                $record_custom = get_post_custom( $record->ID );
                $gift_terms = wp_get_object_terms( $record->ID , 'gift', array( 'fields' => 'slugs' ) );

                if ( empty( $post->parents ) ) {
                    // initial query missed the association, so query again
                    $post->parents = get_posts( array(
                          'connected_type' => 'parent_to_child',
                          'connected_items' => $post,
                          'nopaging' => true,
                          'suppress_filters' => false,
                          'tax_query' => array(
                                array(
                                        'taxonomy' => 'role',
                                        'field' => 'slug',
                                        'terms' => "$school_year-parent"
                                )
                        )
                    ) );
                }

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'inf_addr', $post->parents );

                $pledge_class = ( has_term('annual-giving-pledge', 'gift', $record->ID ) ) ? 'ag-pledge' : '';
                if ( $pledge_class ) {
                    $has_pledge = true;
                }

                $icon = ' ';
                if ( in_array('senior-brick', $gift_terms ) ) {
                    $icon .= '<i class="icon-tint"></i>';
                    $has_snr_brick = true;
                }
                if ( in_array('senior-parent-appreciation-gift', $gift_terms ) ) {
                    $icon .= '<i class="icon-star"></i>';
                    $has_spag = true;
                }

                $classes = $gift_terms;
                $classes[] = $pledge_class;

                $list .= '<li class="' . implode(' ', get_post_class( $classes, $record->ID ) ) . '">' . $title . '<br />' . $child . $icon . '</li>';
            }
            $class_count++;
        }

        if ( isset($class_totals[$class_year]) ) {
            $percent = round( $class_count/$class_totals[$class_year] * 100 );
            if ( $has_pledge ) {
                $stat = "<h2>$percent% ($class_count out of {$class_totals[$class_year]}) gave/pledged.</h2>";
            }
            else {
                $stat = "<h2>$percent% ($class_count out of {$class_totals[$class_year]}) gave.</h2>";
            }
        
            $content .= $stat;
        }

        if ( $has_pledge ) {
            $content .= '<p>Gave | <span class="ag-pledge">Pledged</span></p>';
        }

        if ( !empty( $list ) ) {
            $content .= '<ul class="ar-list">' . $list . '</ul>';
            if ( $has_snr_brick || $has_spag ) {
                $content .= '<p>';
                if ( $has_snr_brick ) {
                    $content .= '<em class="icon-tint"></em> Graduating seniors honored with inscribed name brick<br />';
                }
                if ( $has_spag ) {
                    $content .= '<em class="icon-star"></em> Family participated in the Senior Parent Appreciation Gift';
                }
                $content .= '</p>';
            }
        }
        else {
            $content .= '<p>There are no donors at this time.</p>';
        }
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Annual Giving Levels */
add_shortcode( 'annual_giving', 'hkr_dnrs_ag_shortcode' );

function hkr_dnrs_ag_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

    $cached_year = str_replace('-', '_', $school_year);
    $cached_content = get_transient($cached_year . '_ag_levels_cached');
    if( $cached_content ) {
        return apply_filters( 'hkr_dnrs_list', $cached_content );
    }

    set_time_limit(120);

    $levels = array(
        array(
            'label' => 'Leadership Circle ($25,000+)',
            'slug' => 'leadership-circle',
            'min' => 25000,
            'max' => 999999999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Eagles\' Circle ($10,000+)',
            'slug' => 'eagles-circle',
            'min' => 10000,
            'max' => 24999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Trustees\' Circle ($7,500+)',
            'slug' => 'trustees-circle',
            'min' => 7500,
            'max' => 9999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Benefactors\' Circle ($5,000+)',
            'slug' => 'benefactors-circle',
            'min' => 5000,
            'max' => 7499,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Head of School\'s Circle ($2,500+)',
            'slug' => 'head-of-schools-circle',
            'min' => 2500,
            'max' => 4999,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Ambassadors\' Circle ($1,000+)',
            'slug' => 'ambassadors-circle',
            'min' => 1000,
            'max' => 2499,
            'list' => '',
            'anonymous' => 0
        ),
        array(
            'label' => 'Friends (up to $999)',
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
    $has_pledge = false;

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

                $pledge_class = ( has_term('annual-giving-pledge', 'gift', $record->ID ) ) ? 'class="ag-pledge"' : '';
                if ( $pledge_class ) {
                    $has_pledge = true;
                }

                if ( $title == 'Anonymous' ) {
                    $levels[$i]['anonymous']++;
                    continue;
                }

                $levels[$i]['list'] .= "<li $pledge_class>$title</li>";
            }

        }
    }

    if ( $has_pledge ) {
        $content .= '<p>Gave | <span class="ag-pledge">Pledged</span></p>';
    }

    foreach( $levels as $level ) {
        if ( $level['anonymous'] )
            $level['list'] .= "<li>Anonymous ({$level['anonymous']})</li>";

        $content .= '<a name="' . $level['slug'] . '"></a>';
        $content .= '<h2>' . $level['label'] . '</h2>';
        if ( !empty( $level['list']) ) {
            $content .= '<ul class="ar-list">' . $level['list'] . '</ul>';
        }
        else {
            $content .= '<p>There are no donors at this time.</p>';
        }
        $content .= '<p><a href="#top">Back to top</a></p>';
    }

    wp_reset_postdata();
    set_transient($cached_year . '_ag_levels_cached', $content, 60 * 60 * 24 );
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Alumni Leaders & Pacesetters */
add_shortcode( 'alumni_lp', 'hkr_dnrs_alumni_lp_shortcode' );

function hkr_dnrs_alumni_lp_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    		'relation' => 'AND',
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

            $class_year = hkr_dnrs_get_class_year( $post->ID );
            if ( !$class_year ) {
                continue;
            }

            $title = hkr_dnrs_get_title_by_cons( $post->ID );

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
            if ( !empty($group['list']) ) {
                $content .= '<ul class="ar-list">' . $group['list'] . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }
        }

    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Organizations */
add_shortcode( 'organizations', 'hkr_dnrs_orgs_shortcode' );

function hkr_dnrs_orgs_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    else {
        $content .= '<p>There are no donors at the time.';
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Faculty & Staff */
add_shortcode( 'faculty_staff', 'hkr_dnrs_fac_staff_shortcode' );

function hkr_dnrs_fac_staff_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    else {
        $content .= '<p>There are no donors at the time.';
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Grandparents */
add_shortcode( 'grandparents', 'hkr_dnrs_gp_shortcode' );

function hkr_dnrs_gp_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Grandparents</h2>';
    
    if ( $query->have_posts() ) {

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
                $children .= hkr_dnrs_get_title_by_cons( $child->ID );
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
    else {
        $content .= '<p>There are no donors at the time.';
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Friends */
add_shortcode( 'friends', 'hkr_dnrs_friends_shortcode' );

function hkr_dnrs_friends_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Friends</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at the time.';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Honorary Gifts */
add_shortcode( 'honorary_gifts', 'hkr_dnrs_iho_shortcode' );

function hkr_dnrs_iho_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Honorary Gifts</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at the time.';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Memorial Gifts */
add_shortcode( 'memorial_gifts', 'hkr_dnrs_imo_shortcode' );

function hkr_dnrs_imo_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Memorial Gifts</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at the time.';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Eagle Clubs */
add_shortcode( 'eagle_clubs', 'hkr_dnrs_eagle_clubs_shortcode' );

function hkr_dnrs_eagle_clubs_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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

            $list = '';
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

                    $list .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous ) 
                $list .= "<li>Anonymous ($anonymous)</li>";
            
            $content .= '<h2>' . $club['title'] . '</h2>';
            if ( !empty($list) ) {
                $content .= '<ul class="ar-list">' . $list . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }

            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}


/* Eagle's Nest Club */
add_shortcode( 'ag_eagles_club', 'hkr_dnrs_ag_eagles_club_shortcode' );

function hkr_dnrs_ag_eagles_club_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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

        $list = '';
        $anonymous = 0;
        $has_pledge = false;

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

                $pledge_class = ( has_term('annual-giving-pledge', 'gift', $record->ID ) ) ? 'class="ag-pledge"' : '';
                if ( $pledge_class ) {
                    $has_pledge = true;
                }

                if ( has_term( 'eagles-nest-bold', 'gift', $record->ID ) && !$pledge_class ) {
                    $list .= "<li><strong>$title</strong></li>";
                }
                else {
                    $list .= "<li $pledge_class>$title</li>";
                }
            }
        }
        
        if ( $anonymous )
            $list .= "<li>Anonymous ($anonymous)</li>";
        
    }

    $content .= '<h2>Annual Giving Eagle\'s Nest Club</h2>';
    if ( !empty($list) ) {
        if ( $has_pledge ) {
            $content .= '<p>Gave | <span class="ag-pledge">Pledged</span></p>';
        }
        $content .= '<ul class="ar-list">' . $list . '</ul>';
    }
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Picnic */
add_shortcode( 'picnic', 'hkr_dnrs_picnic_shortcode' );

function hkr_dnrs_picnic_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

    if ( $school_year == '2011-12' ) {
        $sponsor_levels = array(
            array(
                'title' => 'Towering Top Hats',
                'desc' => '($5,000+)',
                'min' => 5000,
                'max' => 999999
            ),
            array(
                'title' => 'Stately Stetsons',
                'desc' => '($2,500+)',
                'min' => 2500,
                'max' => 4999
            ),
            array(
                'title' => 'Fancy Fedoras',
                'desc' => '($1,500+)',
                'min' => 1500,
                'max' => 2499
            ),
            array(
                'title' => 'Dashing Derbies',
                'desc' => '($1,000+)',
                'min' => 1000,
                'max' => 1499
            ),
            array(
                'title' => 'Beautiful Bonnets',
                'desc' => '($500+)',
                'min' => 500,
                'max' => 999
            ),
            array(
                'title' => 'Teenie Beanies',
                'desc' => '($250+)',
                'min' => 250,
                'max' => 499
            ),
        );
    }
    else if ( $school_year == '2012-13' ) {
        $sponsor_levels = array(
            array(
                'title' => 'Best in Show',
                'desc' => '($5,000+)',
                'min' => 5000,
                'max' => 999999
            ),
            array(
                'title' => 'Great Growlers',
                'desc' => '($2,500+)',
                'min' => 2500,
                'max' => 4999
            ),
            array(
                'title' => 'High "Heelers"',
                'desc' => '($1,200+)',
                'min' => 1500,
                'max' => 2499
            ),
            array(
                'title' => 'Harker Barkers',
                'desc' => '($600+)',
                'min' => 600,
                'max' => 1199
            ),
            array(
                'title' => 'Doggie Diggers',
                'desc' => '($300+)',
                'min' => 300,
                'max' => 599
            )
        );
    }
    

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
            $list = '';
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

                    $list .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $list .= "<li>Anonymous ($anonymous)</li>";

            $content .= '<h3>' . $level['title'] . ' '. $level['desc'] . '</h3>';
            if ( !empty($list) ) {
                $content .= '<ul class="ar-list">' . $list . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }
            
            $query->rewind_posts();
        }

        foreach( $groups as $group ) {

            $list = '';
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

                    $list .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $list .= "<li>Anonymous ($anonymous)</li>";

            $content .= '<h2>' . $group['title'] . '</h2>';
            if ( !empty($list) ) {
                $content .= '<ul class="ar-list">' . $list . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Fashion Show */
add_shortcode( 'fashion_show', 'hkr_dnrs_fs_shortcode' );

function hkr_dnrs_fs_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
            $list = '';
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

                    $list .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $list .= "<li>Anonymous ($anonymous)</li>";

            $content .= '<h3>' . $level['title'] . ' '. $level['desc'] . '</h3>';
            if ( !empty($list) ) {
                $content .= '<ul class="ar-list">' . $list . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }
            $query->rewind_posts();
        }

        foreach( $groups as $group ) {

            $list = '';
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

                    $list .= '<li>' . $title . '</li>';
                }
            }

            if ( $anonymous )
                $list .= "<li>Anonymous ($anonymous)</li>";

            $content .= '<h2>' . $group['title'] . '</h2>';
            if ( !empty($list) ) {
                $content .= '<ul class="ar-list">' . $list . '</ul>';
            }
            else {
                $content .= '<p>There are no donors at this time.</p>';
            }
            $query->rewind_posts();
        }
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Alumni */
add_shortcode( 'alumni', 'hkr_dnrs_alumni_shortcode' );

function hkr_dnrs_alumni_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                'relation' => 'AND',
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
    $content .= '<h2>Alumni</h2>';

    if ( $query->have_posts() ) {

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

        if ( $anonymous ) {
            $content .= "<li>Anonymous ($anonymous)</li>";
        }

        $content .= '</ul>';

    }
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Alumni Parents */
add_shortcode( 'alumni_parents', 'hkr_dnrs_alparents_shortcode' );

function hkr_dnrs_alparents_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Alumni Parents</h2>';

    if ( $query->have_posts() ) {

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
                $children .= hkr_dnrs_get_title_by_cons( $child->ID );
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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Senior Class Gift */
add_shortcode( 'senior_class_gift', 'hkr_dnrs_senior_class_gift_shortcode' );

function hkr_dnrs_senior_class_gift_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

    $class_year = hkr_get_end_school_year( $school_year );

    $query = new WP_Query( array(
        'post_type' => 'constituent',
        'nopaging' => true,
        'tax_query' => array(
                array(
                        'taxonomy' => 'class_year',
                        'field' => 'slug',
                        'terms' => $class_year
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
    $content .= '<h2>Senior Class Gift</h2>';

    if ( $query->have_posts() ) {

        $content .= '<ul class="ar-list">';

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            $content .= '<li>' . get_the_title() . '</li>';
        }
        $content .= '</ul>';
    }
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Senior Parent Appreciation Gift */
add_shortcode( 'spag', 'hkr_dnrs_spag_shortcode' );

function hkr_dnrs_spag_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Senior Parent Appreciation Gift</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* John Near Excellence in History Education Endowment */
add_shortcode( 'john_near_end', 'hkr_dnrs_john_near_shortcode' );

function hkr_dnrs_john_near_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>John Near Excellence in History Education Endowment</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Sharron Mittelstet Endowment */
add_shortcode( 'sharron_end', 'hkr_dnrs_sharronm_shortcode' );

function hkr_dnrs_sharronm_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>Endowed Scholarship Fund in Memory of Sharron Mittelstet</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Sandy Padgett Endowment */
add_shortcode( 'sandy_end', 'hkr_dnrs_sandy_shortcode' );

function hkr_dnrs_sandy_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
                            'terms' => 'sandy-padgett-endowment'
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
                            'terms' => 'sandy-padgett-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    $content .= '<h2>Endowed Scholarship Fund in Memory of Sandy Padgett</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}

/* Sandy Padgett Endowment */
add_shortcode( 'jason_end', 'hkr_dnrs_jason_shortcode' );

function hkr_dnrs_jason_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
                            'terms' => 'jason-berry-endowment'
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
                            'terms' => 'jason-berry-endowment'
                    ),
                    array(
                            'taxonomy' => 'school_year',
                            'field' => 'slug',
                            'terms' => $school_year
                    )
        )
    ), 'records' );

    $content = '';
    $content .= '<h2>Endowed Scholarship Fund in Memory of Jason Berry</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Nichols Planned Giving */
add_shortcode( 'nichols_planned_giving', 'hkr_dnrs_nichols_planned_giving_shortcode' );

function hkr_dnrs_nichols_planned_giving_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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
    $content .= '<h2>The Nichols Planned Giving Society</h2>';

    if ( $query->have_posts() ) {

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
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }

    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );

}



/* Capital Giving: Year */
add_shortcode( 'capital_giving_year', 'hkr_dnrs_cc_year_shortcode' );

function hkr_dnrs_cc_year_shortcode($atts) {

    extract($atts = shortcode_atts( array(
        'school_year' => '2011-12'
    ), $atts ));

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

        $list = '';
        $anonymous = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            foreach( $post->records as $record ) {

                $record_custom = get_post_custom( $record->ID );

                $title = hkr_dnrs_get_title_by_record( $record_custom, 'cc_rec', array($post) );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                if ( has_term('capital-giving-bold', 'gift', $record->ID ) ) {
                    $list .= '<li><strong>' . $title . '</strong></li>';
                }
                else {
                    $list .= '<li>' . $title . '</li>';
                }
            }
        }
        
        if ( $anonymous )
            $list .= "<li>Anonymous ($anonymous)</li>";

        $content .= '<h2>' . $school_year . ' Donors</h2>';
        if ( !empty($list) ) {
            $content .= '<ul class="ar-list">' . $list . '</ul>';
        }
        else {
            $content .= '<p>There are no donors at this time.</p>';
        }

    } 
    else {
        $content .= '<p>There are no donors at this time.</p>';
    }
    wp_reset_postdata();
    return apply_filters( 'hkr_dnrs_list', $content );
}



/* Capital Giving: All Time */
add_shortcode( 'capital_giving', 'hkr_dnrs_cc_shortcode' );

function hkr_dnrs_cc_shortcode($atts) {

    $levels = array(
        array(
            'title' => 'Founding Visionaries',
            'desc' => '',
            'slug' => 'founding-visionaries'
        ),
        array(
            'title' => 'Angel Investors',
            'desc' => '($2,500,000+)',
            'slug' => 'angel-investors'
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

    $content = '';
    foreach ( $levels as $level ) {
        $query = new WP_Query( array(
            'post_type' => 'constituent',
            'nopaging' => true,
            'tax_query' => array(
                    array(
                            'taxonomy' => 'role',
                            'field' => 'slug',
                            'terms' => $level['slug']
                    )
            ),
            'meta_key' => 'lname',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ) );

        if ( $query->have_posts() ) {

            $list = '';
            $anonymous = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                global $post;

                $title = get_post_meta( $post->ID, 'cc_rec', true );

                if ( $title == 'Anonymous' ) {
                    $anonymous++;
                    continue;
                }

                $list .= '<li>' . $title . '</li>';
            }
            
            if ( $anonymous )
                $list .= "<li>Anonymous ($anonymous)</li>";

        }

        $content .= '<h2>' . $level['title'] . ' ' . $level['desc'] . '</h2>';
        if ( !empty($list) ) {
            $content .= '<ul class="ar-list">' . $list . '</ul>';
        }
        else {
            $content .= '<p>There are no donors at this time.</p>';
        }

        wp_reset_postdata();
    }

    return apply_filters( 'hkr_dnrs_list', $content );
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

function hkr_dnrs_get_title_by_cons( $post_id ) {
    $title = 'Anonymous';

    $cons_custom = get_post_custom( $post_id );

    $class_year = substr( hkr_dnrs_get_class_year( $post_id ), -2);
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

add_filter( 'hkr_dnrs_list', 'hkr_dnrs_print_last_modified', 1 );

function hkr_dnrs_print_last_modified( $content ) {
    return $content . '<p><b>Last updated:</b> May 31, 2013</p>'; // TODO: make dynamic!
}


?>