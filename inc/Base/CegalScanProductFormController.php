<?php

namespace Inc\cegal\Base;

use Inc\cegal\Api\CegalApi;

class CegalScanProductFormController {
    /**
     * register
     *
     * @return void
     */

    public function register() {
        $actions = [
            'scan_product',
        ];
        //add_action('wp_ajax_cegal_log_queue', [$this, 'ajaxHandleDilveLogQueue']);
        foreach ( $actions as $action ) {
            $camelCase = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $action ) ) );
            add_action( 'wp_ajax_cegal_' . $action, [ $this, 'ajaxHandle' . $camelCase ] );
        }
    }
    /**
     * ajaxHandleScanProduct
     *
     * @return void
     */

     public function ajaxHandleScanProduct() {
        check_ajax_referer('cegal_scan_product_form', 'cegal_nonce');
        update_option('cegal_admin_notice', 'File Checked!');
        $cegalApi = new CegalApi;
        $isbn = $_POST['isbn'];
        $response = $cegalApi->create_cover( $isbn );
        if ( is_array( $response ) && strstr( $response[0], 'cURL error 28' ) ) {
            $html = "failed";
        } else {
            $html = $response;
        }

        wp_send_json_success( ['response' => $response, 'message' => $html ] );
     }

}