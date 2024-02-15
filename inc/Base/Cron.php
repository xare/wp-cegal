<?php

/**
 * @package cegal
 */
namespace Inc\cegal\Base;

use Inc\cegal\Api\CegalApi;

class Cron extends BaseController {

    public function register() {
        if ( ! wp_next_scheduled( 'cegal_cron_event' ) ) {
            wp_schedule_event( time(), 'daily', 'cegal_cron_event' );
        }
        add_action( 'cegal_cron_event', [ $this, 'cegalCron' ] );
    }
    /**
     * cegalCron
     *
     * @return void
     */
    function cegalCron() {
        $cegalApi = new cegalApi;
        $cegalApi->scanProducts();
    }
}