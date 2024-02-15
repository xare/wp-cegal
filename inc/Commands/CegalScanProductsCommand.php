<?php

namespace Inc\cegal\Commands;

use Inc\cegal\Api\CegalApi;
use Inc\cegal\Api\CegalApiDbLinesManager;
use Inc\cegal\Api\CegalApiDbLogManager;
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
        $cegalApiDbLogManager = new CegalApiDbLogManager;
        $cegalApiDbLinesManager = new CegalApiDbLinesManager;
        $batch_size = 10;
        $totalLines = $cegalApiDbManager->countAllProducts();
        $products = $cegalApiDbManager->getProducts();
		$response = [];
        $i = 0;
        WP_CLI::line( 'Number of products: ' . $totalLines );
        $log_id = $cegalApiDbLogManager->insertLogData('logged', $totalLines);
        foreach($products as $product) {
            $ean = get_post_meta( $product->get_id(), '_ean', true );
            WP_CLI::line( 'Product scanned: ' . $ean);
            $line_id = $cegalApiDbLinesManager->insertLinesData( $log_id, $ean );
            if ( $cegalApiDbManager->hasAttachment( $product->get_id() ) ) {
                $cegalApiDbLinesManager->setError( $ean, 'This product has already a cover.' );
                continue;
            }
            if ($file = $cegalApi->create_cover( $ean )) {
			    $cegalApiDbManager->set_featured_image_for_product( $file->ID, $ean );
			    $cegalApiDbLinesManager->setBook($product->get_title(), $product->get_id(), $line_id);
                WP_CLI::line( 'Product Title: ' . $product->get_title() );
            }
        }

        var_dump( $response );
        WP_CLI::line( 'All products scanned ' );
    }
}

