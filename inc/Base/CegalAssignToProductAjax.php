<?php

namespace Inc\cegal\Base;

use Inc\cegal\Api\CegalApiDbManager;

class CegalAssignToProductAjax {

    function register() {
        add_action('wp_ajax_assign_to_product', [ $this, 'assignToProduct'] );
        add_action('wp_ajax_nopriv_assign_to_product', [ $this, 'assignToProduct'] );
    }

    function assignToProduct() {
        if(!isset($_POST['isbn'])) {
            wp_send_json_error('ISBN not found');
        }
        $isbn = sanitize_text_field($_POST['isbn']);
        $cegalApiDbManager = new CegalApiDbManager();
        if ( !$cegalApiDbManager->assignToProduct($isbn)){
            wp_send_json_error('Failed to assign product');
        }
        wp_send_json_success('Product assigned successfully');
    }
}