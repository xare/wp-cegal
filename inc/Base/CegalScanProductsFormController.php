<?php
/**
 * @package cegal
 */
namespace Inc\cegal\Base;

use Inc\cegal\Api\CegalApi;
use Inc\cegal\Api\CegalApiDbManager;
use Inc\cegal\Base\BaseController;

/**
 * @class CegalScanProductsFormController
 */
class CegalScanProductsFormController extends BaseController
{
    public $adminNotice = '';
    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        //add_action('admin_init', [$this, 'handleFormSubmission']);
        $actions = [
            'hello_world',
            'scan_products',
        ];
        //add_action('wp_ajax_cegal_log_queue', [$this, 'ajaxHandleDilveLogQueue']);
        foreach ( $actions as $action ) {
            $camelCase = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $action ) ) );
            add_action( 'wp_ajax_cegal_' . $action, [ $this, 'ajaxHandle' . $camelCase ] );
        }

        add_action('admin_notices', [ $this, 'displayAdminNotice' ]);
    }

    /**
     * ajaxHandleHelloWorld
     *
     * @return void
     */
    public function ajaxHandleHelloWorld() {
        check_ajax_referer('cegal_scan_products_form', 'cegal_nonce');
        update_option('cegal_admin_notice', 'Hello world!');
        wp_send_json_success(['message' => 'Hello world!']);
    }

    /**
     * ajaxHandleScanProducts
     *
     * @return void
     */
    public function ajaxHandleScanProducts() {
        check_ajax_referer('cegal_scan_products_form', 'cegal_nonce');
        update_option('cegal_admin_notice', 'File Checked!');
        $batch_size = ( isset( $_POST['batch_size'] ) && $_POST['batch_size'] != null ) ? $_POST['batch_size']  : -1;
		$offset = ( isset( $_POST['batch_size'] ) && $_POST['batch_size'] != null ) ? $_POST['batch_size'] : 0;
        $cegalApi = new CegalApi;
        $cegalApiDbManager = new CegalApiDbManager;
        $totalLines = $cegalApiDbManager->countAllProducts();
        var_dump($totalLines);
		$response = [];
		while( $totalLines > $batch_size ) {
            $response[] = $cegalApi->scanProducts($batch_size, $offset, $totalLines);
            $offset = $offset + $batch_size;
        }
        $response['progress'] = ( $offset / $totalLines ) * 100;
        wp_send_json_success($response);
    }

    public function displayAdminNotice() {
        if ($this->adminNotice !== '') {
            echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . $this->adminNotice . '</p>';
            echo '</div>';
        }
    }
}
