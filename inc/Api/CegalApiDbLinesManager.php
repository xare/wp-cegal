<?php

namespace Inc\cegal\Api;

class CegalApiDbLinesManager extends CegalApiDbManager {

    /**
     * insertLinesData
     * int $log_id,
     * string $isbn,
     * string $path = '',
     * string $url_origin = '',
     * string $url_target = '',
     * string $error = '',
     * int $attempts = 0
     * @return mixed
     */
    public function insertLinesData(
                        int $log_id,
                        string $isbn,
                        string $path = '',
                        string $url_origin = '',
                        string $url_target = '',
                        string $error = '',
                        int $attempts = 0
                          ): mixed {
		global $wpdb;
		$linesValues = [
			$log_id,
            $isbn,
            $path,
            $url_origin,
            $url_target,
            date('Y-m-d H:i:s'), // start_date
            false,
			$error, // error
            $attempts // scanned_products
		];
		$insertArray = array_combine(self::$cegalLinesKeys, $linesValues);
		try {
			$wpdb->insert($wpdb->prefix . self::CEGAL_LINES_TABLE,
						$insertArray,
						['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d']);
            return $wpdb->insert_id;
		} catch (\Exception $e) {
            wp_error('This line has not been properly inserted into the database due to an error: '.$e->getMessage());
            return false;
        }
	}

    public function setError( string $isbn, string $error ) {
        global $wpdb;
        $id = $this->getLineId($isbn);

        $table_name = $wpdb->prefix.self::CEGAL_LINES_TABLE; // Replace with your actual table name if different
        $data = [ 'isError' => true, 'error' => $error ];
        $where = ['id' => $id];
        $format = ['%s']; // string format
        $where_format = ['%d']; // integer format
        try {
            $wpdb->update( $table_name, $data, $where, $format, $where_format);
            return true;
        } catch( \Exception $exception ) {
            wp_error('Unable to update the row.'.$exception->getMessage());
            return false;
        }
    }

    /**
     * set_origin_url
     *
     * @param  string $isbn
     * @param  string $origin_url
     * @return bool
     */
    public function set_origin_url( string $isbn, string $url_origin ): bool {
        global $wpdb;
        $table_name = $wpdb->prefix.self::CEGAL_LINES_TABLE; // Replace with your actual table name if different
        $data = ['url_origin' => $url_origin ];
        $where = ['isbn' => $isbn ];
        $format = ['%s']; // string format
        $where_format = ['%s']; // integer format
        try {
            $wpdb->update( $table_name, $data, $where, $format, $where_format);
            return true;
        } catch( \Exception $exception ) {
            wp_error('Unable to update the row. '.$exception->getMessage());
            $this->setError( $isbn.'.jpg', $exception->getMessage() );
            return false;
        }
    }

    /**
     * getLineId
     *
     * @param  string $isbn
     * @return mixed
     */
    public function getLineId( string $isbn ): mixed {
        global $wpdb;
        $table_name = $wpdb->prefix.self::CEGAL_LINES_TABLE;
        $sql = "SELECT id FROM $table_name WHERE isbn = '$isbn'";
        try {
            $id = $wpdb->get_var($sql);
            return $id;
        } catch( \Exception $exception ) {
            wp_error('Unable to get the line id. '.$exception->getMessage());
            return false;
        }
    }

    /**
     * setBook
     *
     * @param  text $title
     * @param  int $book_id
     * @param  int $line_id
     * @return bool
     */
    public function setBook(string $title, int $book_id, int $line_id): bool {
        global $wpdb;
        $table_name = $wpdb->prefix.self::CEGAL_LINES_TABLE; // Replace with your actual table name if different
        $data = [ 'booktitle' => $title, 'book_id' => $book_id ];
        $where = ['id' => $line_id];
        $format = ['%s', '%d']; // string format
        $where_format = ['%d']; // integer format
        try {
            $wpdb->update( $table_name, $data, $where, $format, $where_format);
            return true;
        } catch( \Exception $exception ) {
            wp_error('Unable to update the row. '.$exception->getMessage());
            return false;
        }
    }

    /**
     * set_url_target
     *
     * @param  int $line_id
     * @param  int $product_id
     * @return void
     */
    public function set_url_target(int $line_id, int $product_id){
        $product = wc_get_product($product_id);
        $image_id  = $product->get_image_id();
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        global $wpdb;
        $table_name = $wpdb->prefix.self::CEGAL_LINES_TABLE; // Replace with your actual table name if different
        $sql = "UPDATE $table_name SET url_target = '".$image_url."' WHERE id = $line_id";
        $wpdb->query($sql);
    }

    /**
     * get_product_featured_image_html
     *
     * @param  string $ean
     * @return string
     */
    function get_product_featured_image_html(string $ean): string {
        global $wpdb; // Access the WordPress database object
        // Escape the EAN to prevent SQL injection
        $ean_escaped = esc_sql($ean);

        // Build the query to find the attachment post with the specified filename
        $query = "
            SELECT ID
            FROM $wpdb->posts
            WHERE post_title LIKE %s
            AND post_type = 'attachment'
        ";
        $likequery = "%{$ean_escaped}%";
        $attachment_id = $wpdb->get_var( $wpdb->prepare( $query, $likequery ));

        // If no attachment is found, return a placeholder or a message
    if ( !$attachment_id ) {
        return 'No image found for this EAN.';
    }

    // Get the image URL and alt text
    $image_url = wp_get_attachment_image_url($attachment_id, 'full'); // Change 'full' to any other image size if needed
    $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

    // Generate and return the HTML for the image
    $html = '<a href="' . esc_url(get_edit_post_link($attachment_id)) . '" target="_blank">';
    $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" style="max-width:50px; height:auto;" />';
    $html .= '</a>';
    return $html;
    }

}