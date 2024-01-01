<form method="post" action="options.php">
    <?php
        settings_fields( 'cegal_settings' );
        do_settings_sections( 'cegal' );
        submit_button();
    ?>
</form>