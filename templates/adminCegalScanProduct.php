<div class="wrap">
    <h1>Admin Cegal Scan Product</h1>
    <form method="post" action="#">
    <?php wp_nonce_field('cegal_scan_product_form', 'cegal_nonce'); ?>
        <input type="text" name="isbn" placeholder="isbn">
        <?php
            submit_button('scan product', 'primary', 'scan_product');
        ?>
    </form>

    <div data-container="cegal_display_cover"></div>
</div>