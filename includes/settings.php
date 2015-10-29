<?php

// Object stores schoolyear-specific settings of Harker Donors plugin.
// TODO: Create an admin interface to manage all of this.
$hkr_annual_settings = new HarkerAnnualSettings();  

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
            ),
            '2015-16' => array(
                '2031' => 48,
                '2030' => 48,
                '2029' => 23,
                '2028' => 81,
                '2027' => 81,
                '2026' => 88,
                '2025' => 110,
                '2024' => 125,
                '2023' => 132,
                '2022' => 177,
                '2021' => 175,
                '2020' => 175,
                '2019' => 198,
                '2018' => 193,
                '2017' => 190,
                '2016' => 187
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
                    'title' => 'Colorful Cornucopias',
                    'desc' => '($5,000 &amp; above)',
                    'min' => 5000,
                    'max' => 999999
                ),
                array(
                    'title' => 'Golden Gourds',
                    'desc' => '($2,500-$4,999)',
                    'min' => 2500,
                    'max' => 4999
                ),
                array(
                    'title' => 'Bountiful Bales',
                    'desc' => '($1,500-$2,499)',
                    'min' => 1500,
                    'max' => 2499
                ),
                array(
                    'title' => 'Jumping Jack-O\'-Lanterns',
                    'desc' => '($1,000-$1,499)',
                    'min' => 1000,
                    'max' => 1499
                ),
                array(
                    'title' => 'Fanciful Farmers',
                    'desc' => '$500-$900',
                    'min' => 500,
                    'max' => 900
                ),
                array(
                    'title' => 'Cawing Crows',
                    'desc' => '$250-$499',
                    'min' => 250,
                    'max' => 499
                )
            ),
            '2015-16' => array(
                array(
                    'title' => 'Cawing Crows',
                    'desc' => '$3,000',
                    'min' => 3000,
                    'max' => 999999
                ),
                array(
                    'title' => 'Hoppin Goblin\'s',
                    'desc' => '$2,000',
                    'min' => 2000,
                    'max' => 2999
                ),
                array(
                    'title' => 'Gonzo Ghosts',
                    'desc' => '$1,500',
                    'min' => 1500,
                    'max' => 1999
                ),
                array(
                    'title' => 'Wild Witches',
                    'desc' => '$1,000',
                    'min' => 1000,
                    'max' => 1499
                ),
                array(
                    'title' => 'Zoomin Zombies',
                    'desc' => '$500',
                    'min' => 500,
                    'max' => 999
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

    public function get_class_total($class_year = '', $school_year = '') {
        if ( isset($this->class_totals[$school_year][$class_year]) ) {
            return $this->class_totals[$school_year][$class_year];
        } else {
            return false;
        }
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
            return array(); // no sponsor levels available
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

if( is_admin() ) {
    $hkr_donors_settings_page = new HarkerDonorsSettingsPage();
} 

// Global admin settings for Harker Donors plugin
class HarkerDonorsSettingsPage {

    // stores option values
    private $options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_page() {
        // This page will be under "Settings"
        add_options_page(
            'Harker Donors Settings', 
            'Harker Donors', 
            'manage_options', 
            'hkr-donors-settings', 
            array( $this, 'create_admin_page' )
        );
    }

    // Options page callback
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'hkr_donors_options' );
        ?>
        <div class="wrap">
            <h2>Harker Donors Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'hkr_donors_options_group' );   
                do_settings_sections( 'hkr-donors-settings' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    // Register and add settings
    public function page_init() {        
        register_setting(
            'hkr_donors_options_group', // Option group
            'hkr_donors_options' // Option name
        );

        add_settings_section(
            'hkr_donors_general_section', // ID
            'Global Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'hkr-donors-settings' // Page
        );  

        add_settings_field(
            'last_modified', // ID
            'Data Last Modified', // Title 
            array( $this, 'last_modified_callback' ), // Callback
            'hkr-donors-settings', // Page
            'hkr_donors_general_section' // Section           
        );    
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['last_modified'] ) )
            $new_input['last_modified'] = absint( $input['last_modified'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info() {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function last_modified_callback() {
        printf(
            '<input type="text" id="last_modified" name="hkr_donors_options[last_modified]" value="%s" />',
            isset( $this->options['last_modified'] ) ? esc_attr( $this->options['last_modified']) : ''
        );
    }
} 

?>