<div class="wrap">
    <h1>Acciones</h1>

    <form method="post" action="#tab-3" id="cegalScanProduct">
        <?php wp_nonce_field('cegal_scan_product_form', 'cegal_scan_nonce'); ?>

        <?php
            $buttons = [
                ['Scan Product', 'primary', 'scan_product'],
            ];
        ?>
        <input type="text" name="isbn">
        <?php
            array_map( function( $button ) {
                list( $label, $type, $name ) = $button;
                submit_button( $label, $type, $name, false );
            }, $buttons );
        ?>
    </form>
    <div data-container="cegal" class="terminal"></div>
</div>