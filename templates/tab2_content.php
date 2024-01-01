<?php
    $cegal_admin_notice = get_option('cegal_admin_notice', '');
    if ( !empty( $cegal_admin_notice ) ) {
        echo '<div class="notice">' . $cegal_admin_notice . '</div>';
        delete_option('cegal_admin_notice');  // Clear the notice
    }
?>
<div class="wrap">
    <h1>Acciones</h1>
    <form method="post" action="#tab-2" id="cegalProcess">
        <?php wp_nonce_field('cegal_scan_products_form', 'cegal_nonce'); ?>
        <?php
            $buttons = [
                ['Scan Product', 'primary', 'scan_product'],
                ['0. Hello World', 'primary', 'hello_world'],
                ['1. Scan Products', 'primary', 'scan_products'],
            ];
        ?>
        <input type="text" name="isbn">
        <?php
            array_map(function($button) {
                list($label, $type, $name) = $button;
                submit_button($label, $type, $name, false);
            }, $buttons);
        ?>
    </form>
    <div data-container="cegal" class="terminal"></div>
</div>