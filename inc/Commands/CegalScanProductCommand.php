<?php

namespace Inc\cegal\Commands;

use Inc\cegal\Api\CegalApi;
use WP_CLI;

/**
 * Class CegalScanProductCommand
 */
class CegalScanProductCommand {
	public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'cegal scanProduct', [$this, 'execute'] );
        }
    }
    /**
     * Prints a hello world message
     *
     * ## OPTIONS
     *
     *
     * ## EXAMPLES
     *
     *     wp cegal scanProduct
     *
     * @when after_wp_load
     */
    public function execute( $args, $assoc_args ) {
        $cegalApi = new CegalApi;
        $ean = $args[0];
        $disponibilidad = $cegalApi->create_cover($ean);
        var_dump($disponibilidad);
        WP_CLI::line( 'Scan product: '. $ean );
    }
}

