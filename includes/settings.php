<?php

class HarkerAnnualSettings {

    private $class_totals; // number of students in each class for each school year
    private $picnic_sponsor_levels; // picnic sponsor levels for each school year
    private $picnic_giving_groups;

    public function __construct() {
        
        $this->class_totals = array(
            '2011-12' => array(
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
            ),
            '2012-13' => array(
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
            ),
            '2013-14' => array(
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
            ),
            '2014-15' => array(
                '2030' => 48,
                '2029' => 44,
                '2028' => 21,
                '2027' => 81,
                '2026' => 79,
                '2025' => 88,
                '2024' => 107,
                '2023' => 124,
                '2022' => 129,
                '2021' => 170,
                '2020' => 172,
                '2019' => 170,
                '2018' => 192,
                '2017' => 188,
                '2016' => 187,
                '2015' => 187
            )
        );

        $this->picnic_sponsor_levels = array(
            '2011-12' => array(
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
            ),
            '2012-13' => array(
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
            ),
            '2013-14' => array(
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
            )
        );

        $this->picnic_giving_groups = array(
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
    }

    public function get_class_totals($school_year = '') {
        if ( isset($this->class_totals[$school_year]) ) {
            return $this->class_totals[$school_year];
        } else {
            return false;
        }
    }

    public function get_picnic_sponsor_levels($school_year = '') {
        if ( isset($this->picnic_sponsor_levels[$school_year]) ) {
            return $this->picnic_sponsor_levels[$school_year];
        } else {
            return $this->picnic_sponsor_levels['2013-14']; // levels are standardized at this point (hopefully)
        }
    }

    public function get_picnic_giving_groups($school_year = '') {
        if ( $school_year == '2013-14' ) {
            array_shift( $this->picnic_giving_groups );
            return $this->picnic_giving_groups;
        } else {
            return $this->picnic_giving_groups;
        }
    }
}

$hkr_annual_settings = new HarkerAnnualSettings();  

?>