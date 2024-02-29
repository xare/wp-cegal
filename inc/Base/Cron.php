<?php

/**
 * @package cegal
 */
namespace Inc\cegal\Base;

use Inc\cegal\Api\CegalApi;
use Inc\cegal\Api\CegalApiDbLogManager;
use Inc\cegal\Api\CegalApiDbManager;

class Cron extends BaseController {

    public function register() {

        add_filter( 'cron_schedules', [ $this,'custom_cron_schedule'] );
        if ( ! wp_next_scheduled( 'cegal_cron_event' ) ) {
            wp_schedule_event( time(), 'hourly', 'cegal_cron_event' );
        }
        add_action( 'cegal_cron_event', [ $this, 'cegalCron' ] );
    }

    function custom_cron_schedule( $schedules ) {
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 15 * 60, // 15 minutes in seconds
            'display'  => __( 'Every 15 Minutes' ),
        );
        return $schedules;
    }
    /**
     * cegalCron
     *
     * @return void
     */
    function cegalCron() {
        $batch_size = 38;
        $cegalApi = new cegalApi;
        $cegalApiDbManager = new CegalApiDbManager;
        $cegalApiDbLogManager = new CegalApiDbLogManager;
        error_log('Start Cron '. date('Y-m-d') );
        $totalLines = $cegalApiDbManager->countAllProducts();
        $log_id = $cegalApiDbLogManager->insertLogData('logged', $totalLines);
        do {
            $offset = get_option( 'last_processed_offset', 0 );
            $jsonResponse = $cegalApi->scanProducts($log_id, $batch_size, $offset );
            $responseArray = json_decode($jsonResponse, true);
            error_log(var_export($responseArray, true));
            error_log(var_export($responseArray['hasMore'], true));
            update_option( 'last_processed_offset', $offset + $batch_size );
            if($responseArray['hasMore'] == false) {
                update_option( 'last_processed_offset', 0 );
            }
        } while( $responseArray['hasMore'] == true );
        error_log('End Cron '. $jsonResponse );
    }
}