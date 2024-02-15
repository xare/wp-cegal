<?php
namespace Inc\cegal\Api;

use WP_List_Table;
use Inc\cegal\Api\CegalApiDbManager;

class CegalLogListTable extends WP_List_Table {

    private $cegalApiDbLoggerManager;

    public function __construct() {
        parent::__construct([
            'singular' => 'cegal_log',  // Singular label of the table
            'plural'   => 'cegal_logs', // Plural label of the table
            'ajax'     => true             // Does this table support ajax?
        ]);

    }
    public function prepare_items() {
        global $wpdb;
        $cegalApiDbLogManager = new CegalApiDbLogManager;
        $logTable = $wpdb->prefix . $cegalApiDbLogManager::CEGAL_LOG_TABLE;
        // define data set for WP_List_Table => data
        $where = ''; // Initialize where clause
        if ( isset( $_POST['filter_start_date'] ) && !empty( $_POST['filter_start_date'] )) {
            $start_date = sanitize_text_field( $_POST['filter_start_date'] );
            $where = $wpdb->prepare(' WHERE start_date = %s', $start_date );
        }
        if ( isset( $_POST['filter_end_date'] ) && !empty( $_POST['filter_end_date'] )) {
            $end_date = sanitize_text_field( $_POST['filter_end_date'] );
            $where = $wpdb->prepare(' WHERE end_date = %s', $end_date);
        }
        if ( isset( $_POST['filter_status'] ) && !empty( $_POST['filter_status'] )) {
            $status = sanitize_text_field($_POST['filter_status']);
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }
        if ( isset( $_POST['filter_scanned_items']) && !empty( $_POST['filter_scanned_items'] )) {
            $scanned_items = sanitize_text_field($_POST['filter_scanned_items']);
            $where = $wpdb->prepare(' WHERE scanned_items = %d', $scanned_items);
        }
        if ( isset( $_POST['filter_processed_items'] ) && !empty( $_POST['filter_geslib_id'] )) {
            $processed_items = sanitize_text_field( $_POST['filter_processed_items'] );
            $where = $wpdb->prepare( ' WHERE processed_items = %d', $processed_items );
        }

        $orderby = isset( $_GET['orderby'] ) ? trim( $_GET['orderby'] ): "id";
        $order = isset( $_GET['order'] ) ? trim( $_GET['order'] ): "ASC";

        $search_term = isset($_POST['s'])? trim($_POST['s']) : "";
        if ($search_term) {
            $search_term = sanitize_text_field($search_term);
            $where = " WHERE start_date LIKE '%{$search_term}%'
                                        OR end_date LIKE '%{$search_term}%'
                                        OR status LIKE '%{$search_term}%'
                                        OR scanned_items = {$search_term}
                                        OR processed_items = {$search_term}";
        }
        // First, get the total count of items
        $sql_count = "SELECT COUNT(*) FROM {$logTable} {$where}";
        $total_items = $wpdb->get_var($sql_count);

        // Set how many records per page to show
        $per_page = 20;

        // Calculate the total number of pages
        $total_pages = ceil($total_items / $per_page);


        $this->items = $this->wp_list_table_data($where, $per_page,  $orderby, $order);

        // Set the pagination arguments
        $this->set_pagination_args([
            'total_items' => $total_items, // Total number of items
            'per_page'    => $per_page,    // How many items to show on a page
            'total_pages' => $total_pages  // Total number of pages
        ]);
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns(); // Define hidden columns here if any
        $sortable = $this->get_sortable_columns(); // Define sortable columns here if any
        $this->_column_headers = [$columns, $hidden, $sortable];
    }

    public function wp_list_table_data($where = '', $per_page = 20,  $orderby = '', $order = '', $search_term = '') {
        global $wpdb;
        $cegalApiDbManager = new CegalApiDbManager;
        // Determine what page the user is currently looking at
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $logTable = $wpdb->prefix . $cegalApiDbManager::CEGAL_LOG_TABLE;
        $sql_data = $wpdb->prepare("SELECT * FROM {$logTable} {$where} ORDER BY {$orderby} {$order} LIMIT {$per_page} OFFSET {$offset}");
        return  $wpdb->get_results($sql_data, ARRAY_A);
    }

    public function get_columns() {
        $columns = [
            'id' => 'ID',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'status' => 'Status',
            'scanned_items' => 'Scanned Items',
            'processed_items' => 'Processed Items',
        ];
        return $columns;
    }

    public function get_hidden_columns() {
        return [];
    }

    public function get_sortable_columns() {
        return [
            'id' => ['id', false],
            'start_date' => ['start_date', false],
            'start_date' => ['start_date', false],
            'status' => ['status', false],
            'scanned_items' => ['scanned_items', false],
            'processed_items' => ['processed_items', false],
        ];
    }

    public function column_default( $item, $column_name ) {
        return match($column_name) {
            'id' => $item[$column_name],
            'start_date' => $item[$column_name],
            'end_date' => $item[$column_name],
            'status' => $item[$column_name],
            'scanned_items' => $item[$column_name],
            'processed_items' => $item[$column_name],
            default => 'no value',
        };
    }
}