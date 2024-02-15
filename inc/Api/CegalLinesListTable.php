<?php
namespace Inc\cegal\Api;

use WP_List_Table;
use Inc\cegal\Api\CegalApiDbManager;

class CegalLinesListTable extends WP_List_Table {

    private $dilveApiDbLinesManager;

    public function __construct() {
        parent::__construct([
            'singular' => 'cegal_lines',  // Singular label of the table
            'plural'   => 'cegal_lines', // Plural label of the table
            'ajax'     => true             // Does this table support ajax?
        ]);

    }
    public function prepare_items() {
        global $wpdb;
        $cegalApiDbLinesManager = new CegalApiDbLinesManager;
        $linesTable = $wpdb->prefix . $cegalApiDbLinesManager::CEGAL_LINES_TABLE;
        // define data set for WP_List_Table => data
        $where = ''; // Initialize where clause
        if ( isset( $_POST['filter_isbn'] ) && !empty( $_POST['filter_isbn'] )) {
            $isbn = sanitize_text_field( $_POST['filter_isbn'] );
            $where = $wpdb->prepare(' WHERE isbn = %s', $isbn );
        }
        if ( isset( $_POST['filter_path'] ) && !empty( $_POST['filter_path'] )) {
            $path = sanitize_text_field( $_POST['filter_path'] );
            $where = $wpdb->prepare(' WHERE path = %s', $path);
        }
        if ( isset( $_POST['filter_url_origin'] ) && !empty( $_POST['filter_url_origin'] )) {
            $url_origin = sanitize_text_field($_POST['filter_url_origin']);
            $where = $wpdb->prepare(' WHERE url_origin = %s', $url_origin);
        }
        if ( isset( $_POST['filter_url_target']) && !empty( $_POST['filter_url_target'] )) {
            $url_target = sanitize_text_field($_POST['filter_url_target']);
            $where = $wpdb->prepare(' WHERE url_target = %s', $url_target);
        }
        if ( isset( $_POST['filter_date'] ) && !empty( $_POST['filter_date'] )) {
            $date = sanitize_text_field( $_POST['filter_date'] );
            $where = $wpdb->prepare( ' WHERE date = %s', $date );
        }
        if ( isset( $_POST['filter_isError'] ) && !empty( $_POST['filter_isError'] )) {
            $isError = sanitize_text_field( $_POST['filter_isError'] );
            $where = $wpdb->prepare( ' WHERE isError = %d', $isError );
        }
        if ( isset( $_POST['filter_error'] ) && !empty( $_POST['filter_error'] )) {
            $error = sanitize_text_field( $_POST['filter_error'] );
            $where = $wpdb->prepare( ' WHERE error = %s', $error );
        }
        if ( isset( $_POST['filter_attempts'] ) && !empty( $_POST['filter_attempts'] )) {
            $attempts = sanitize_text_field( $_POST['filter_attempts'] );
            $where = $wpdb->prepare( ' WHERE attempts = %d', $attempts );
        }
        if ( isset( $_POST['filter_log_id'] ) && !empty( $_POST['filter_log_id'] )) {
            $log_id = sanitize_text_field( $_POST['filter_log_id'] );
            $where = $wpdb->prepare( ' WHERE log_id = %d', $log_id );
        }

        $orderby = isset( $_GET['orderby'] ) ? trim( $_GET['orderby'] ): "id";
        $order = isset( $_GET['order'] ) ? trim( $_GET['order'] ): "ASC";

        $search_term = isset($_POST['s'])? trim($_POST['s']) : "";
        if ($search_term) {
            $search_term = sanitize_text_field($search_term);
            $where = " WHERE isbn LIKE '%{$search_term}%'
                        OR path LIKE '%{$search_term}%'
                        OR url_origin LIKE '%{$search_term}%'
                        OR url_target LIKE '%{$search_term}%'
                        OR error LIKE '%{$search_term}%'";
        }
        // First, get the total count of items
        $sql_count = "SELECT COUNT(*) FROM {$linesTable} {$where}";
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

        $table = $wpdb->prefix . $cegalApiDbManager::CEGAL_LINES_TABLE;
        $sql_data = $wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT {$per_page} OFFSET {$offset}");
        return  $wpdb->get_results($sql_data, ARRAY_A);
    }

    public function get_columns() {
        $columns = [
            'id' => 'ID',
            'image' => 'Image',
            'log_id' => 'Log ID',
            'isbn' => 'ISBN',
            'booktitle' => 'Book Title',
            'path' => 'Path',
            'url_origin' => 'URL Origen',
            'url_target' => 'URL Target',
            'date' => 'Date',
            'isError' => 'Is Error',
            'error' => 'Error',
            'attempts' => 'Attempts',
            'asignar' => 'Asignar',
        ];
        return $columns;
    }

    public function get_hidden_columns() {
        return [];
    }

    public function get_sortable_columns() {
        return [
            'id' => ['id', false],
            'log_id' => ['log_id', false],
            'isbn' => ['isbn', false],
            'booktitle' => ['booktitle', false],
            'path' => ['path', false],
            'url_origin' => ['url_origin', false],
            'url_target' => ['url_target', false],
            'date' => ['date', false],
            'isError' => ['isError', false],
            'error' => ['error', false],
            'attempts' => ['attempts', false],
            'asignar' => ['asignar', false],
        ];
    }

    /**
     * A description of the entire PHP function.
     *
     * @param $item description
     * @param $column_name description
     * @return Some_Return_Value
     */
    public function column_default( $item, $column_name ) {
        $cegalApiDbLinesManager = new CegalApiDbLinesManager;
        if ( $item['book_id'] == null || !$item['book_id'] ) {
            $book_id = 0;
        } else {
            $book_id = (int) $item['book_id'];
        }
        $assignButton = "<button
                            type='submit'
                            data-action='assign-to-product'
                            data-isbn='" . $item['isbn'] . "'
                            class='button button-primary'>
                                Asignar
                            </button />";
        return match($column_name) {
            'id' => $item[$column_name],
            'log_id' => $item[$column_name],
            'isbn' => $item[$column_name],
            'path' => $item[$column_name],
            'booktitle' => "<a href='post.php?post=" . $book_id . "&action=edit' target='_blank'>" . $item[$column_name] . "</a>",
            'url_origin' => "<a href='" . $item[$column_name] . "' target='_blank'>" . $item[$column_name] . "</a>",
            'url_target' => $item[$column_name],
            'date' => $item[$column_name],
            'error' => $item[$column_name],
            'isError' => $item[$column_name],
            'attempts' => $item[$column_name],
            'image' => $cegalApiDbLinesManager->get_product_featured_image_html($item['isbn']),
            'asignar' => $assignButton,
            default => 'no value',
        };
    }
}