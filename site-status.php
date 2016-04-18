<?php
/*
Plugin Name: Site Status
Plugin URI:
Description: Example plugin that will check the status of websites
Version: 1.0
Author: ericjuden
Author URI: http://ericjuden.com
License: GPLv2 or later
Text Domain: site-status
*/

define('SITE_STATUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SITE_STATUS_DIRECTORY_PLUGIN_URL', plugins_url('', __FILE__));
define('EVERY_FIVE_MINUTES', 60*5);

require_once(SITE_STATUS_PLUGIN_DIR . '/includes/class.sites.php');

class Site_Status {
    var $sites;

    function __construct(){
        $this->sites = new Site_Status_Sites();

        add_action( 'init' , array( $this , 'init' ) );
        add_action( 'site_status_check_sites' , array( $this , 'cron' ) );
        add_filter( 'cron_schedules', array( $this , 'cron_schedules' ) );
    }

    function cron() {
        $sites_array = get_posts(
            array(
                'numberposts' => -1,
                'post_status' => 'publish',
                'post_type' => $this->sites->post_type
            )
        );

        foreach( $sites_array as $key => $site ) {
            $site_custom = get_post_custom($site->ID);

            if( isset( $site_custom['url'] ) && $site_custom[ 'url' ][0] != '' ) {
                $site_response = wp_remote_head($site_custom[ 'url' ][0]);

                if( get_class( $site_response ) == 'WP_Error' ) {
                    // Something happened, mark as down
                    update_post_meta( $site->ID , 'last_status' , 'down' );
                    update_post_meta( $site->ID , 'last_updated' , time());
                } else {
                    // Success! Figure out what response code we got back
                    $response_code = wp_remote_retrieve_response_code($site_response);
                    update_post_meta( $site->ID , 'last_status' , $this->process_response_code( $response_code ) );
                    update_post_meta( $site->ID , 'last_updated' , time());
                }
            }
        }
    }

    function cron_schedules( $schedules ) {
        $schedules['five_minutes'] = array(
            'interval' => 60*5,
            'display' => __('Every 5 Minutes')
        );

        return $schedules;
    }

    function process_response_code( $response_code ) {
        if( $response_code <= 399 ) {
            return 'up';
        } else {
            return 'down';
        }
    }

    function init() {
        $timezone_offset = get_option('gmt_offset');

        if( !wp_next_scheduled( 'site_status_check_sites' ) ) {
            // Schedule next run for 1 hour from now
		    wp_schedule_event(time() + $timezone_offset + 60*60, 'hourly', 'site_status_check_sites' );

            // Run since it currently hasn't run yet
            $this->cron();
        }
    }
}
$site_status = new Site_Status();
?>