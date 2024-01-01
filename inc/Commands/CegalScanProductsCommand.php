<?php

namespace Inc\cegal\Commands;

use Inc\cegal\Api\CegalApi;
use Inc\cegal\Api\CegalApiDbManager;
use WP_CLI;

/**
 * Class CegalScanProductsCommand
 */
class CegalScanProductsCommand {

    private $cegalApi;
    public function __construct() {
        $this->cegalApi = new CegalApi();
    }
	public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'cegal scanProducts', [$this, 'execute'] );
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
     *     wp cegal scanProducts
     *
     * @when after_wp_load
     */
    public function execute( $args, $assoc_args ) {

        $cegalApi = new CegalApi;
        $cegalApiDbManager = new CegalApiDbManager;
        $batch_size = 10;
        $totalLines = $cegalApiDbManager->countAllProducts();
        var_dump( $totalLines );
		$response = [];
        WP_CLI::line( 'Number of products: ' . $totalLines );
        while( $totalLines > $batch_size ) {
            $response[] = $cegalApi->scanProducts($batch_size);
        }
        var_dump( $response );
        WP_CLI::line( 'All products scanned ' );
    }
}

