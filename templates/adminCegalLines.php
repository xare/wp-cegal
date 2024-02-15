<?php

use Inc\cegal\Api\CegalLinesListTable;
use Inc\cegal\Api\CegalLogListTable;
?>

<div class="wrap">
    <h1>Cegal LINES</h1>
    <?php settings_errors(); ?>
    <!-- FILTERS -->
    <?php
        global $wpdb;
        $linesTable = $wpdb->prefix . 'cegal_lines';

        // Fetch distinct types
        $isbn_sql = "SELECT DISTINCT date FROM {$linesTable}";
        $isbn_dates = $wpdb->get_col($isbn_sql);

        $path_sql = "SELECT DISTINCT path FROM {$linesTable}";
        $path = $wpdb->get_col($path_sql);

        $url_origin_sql = "SELECT DISTINCT url_origin FROM {$linesTable}";
        $url_origin = $wpdb->get_col($url_origin_sql);

        $isError_sql = "SELECT DISTINCT isError FROM {$linesTable}";
        $isError = $wpdb->get_col($isError_sql);

        $error_sql = "SELECT DISTINCT error FROM {$linesTable}";
        $error = $wpdb->get_col($error_sql);

        $attempts_sql = "SELECT DISTINCT attempts FROM {$linesTable}";
        $attempts = $wpdb->get_col($attempts_sql);
        ?>
    <form method="post">
        <select name="filter_isbn"></select>
        <select name="filter_path"></select>
        <select name="filter_url_origin"></select>
        <select name="filter_url_target"></select>
        <select name="filter_isError"></select>
        <select name="filter_error"></select>
    </form>
    <!-- Page display -->
    <?php
        $wp_list_table = new CegalLinesListTable;
        $wp_list_table->prepare_items();
        // Render the table
        echo "<form method='post' name='cegal_lines_search' action='".$_SERVER['PHP_SELF']."?page=cegal_lines'>";
        $wp_list_table->search_box("Cegal Lines Search", "search_cegal_lines");
        echo "</form>";
        $wp_list_table->display();
    ?>

</div>