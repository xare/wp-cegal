<?php

namespace Inc\cegal\Api;

use WP_Query;

class CegalApiDbManager {
    const CEGAL_LOG_TABLE = 'cegal_log';
    const CEGAL_LINES_TABLE = 'cegal_lines';
    const CEGAL_LOGGER_TABLE = 'cegal_logger';
    static $cegalLogKeys = [
		'start_date', // date
		'end_date', // date
		'status', // string waiting | enqueued | processed
		'scanned_items', // int number of lines
        'processed_items', // int number of lines
	];

    static $cegalLinesKeys = [
        'log_id', // int relation oneToMany with dilve_log
        'isbn',    // string
        'path',    // string
        'url_origin', // string
        'url_target', // string
        'date',    // date
        'isError', // boolean
        'error',   // string
        'attempts', // int
    ];
    public $cegalLoggerKeys = [
        'date',
        'ean', // int
		'url', // int
		'metadata', // json
    ];

    /**
     * insertFile
     *
     * @param  string $filepath
     * @param  array $data
     * @param  string $filename
     * @return mixed
     */
    public function insertFile( string $filepath, array $data, string $filename ): mixed {
        // Validate data before proceeding
        if ( empty( $data ) ) {
            var_dump( 'Data is empty. Skipping file creation.' );
            return false;
        }
        try {

            file_put_contents( $filepath, $data['data'] );
            var_dump( 'FILE SUCCES FULLY STORED IN THE SYSTEM at' . $filepath );
        } catch ( \Exception $exception ) {
            var_dump( 'Could not create file: ' . $exception->getMessage() );
            return false;
        }
		return $this->insertAttachment( $filename, $filepath );
    }
    /**
     * CegalApiDbManager->insertAttachment
     * Inserts the file to the file manager.
     *
     * @param  string $filename
     * @param  string $filepath
     * @return mixed
     */
    public function insertAttachment( string $filename, string $filepath ): mixed {
        $args = [
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'PORTADA: '. $filename,
            'post_content' => 'PORTADA: '. $filepath . $filename,
            'post_status' => 'inherit',
            'guid' => wp_upload_dir()[ 'baseurl' ] . '/portadas/' . $filename,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => get_gmt_from_date(current_time('mysql')),
        ];

        try {
            $attachment_id = wp_insert_attachment( $args, $filepath, 0 );

            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata( $attachment_id, $filepath ) );

            return $attachment_id ;
        } catch(\Exception $exception) {
            error_log( "Exception: ".$exception->getMessage() );
            return 0;
        }
    }

    /**
     * isAttachment
     *
     * @param  string $filename
     * @return mixed
     */
    public function isAttachment( string $filename ): mixed {
        $attachments = get_posts([
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'meta_key' => '_wp_attached_file',
			'meta_value' => 'portadas/' . $filename,
			'posts_per_page' => 1,
		]);
        //var_dump($attachments);
        return ( !empty( $attachments ) )? $attachments : false;
    }

    /**
     * hasAttachment
     * @param int $product_id
     * @return bool
     */

    public function hasAttachment($product_id): bool {
        // Get the EAN number from product metadata
        $ean = get_post_meta($product_id, '_ean', true);

        // Check if EAN is set
        if (empty($ean)) {
            return false;
        }

        // Construct the expected file path
        $upload_dir = wp_upload_dir();
        $expected_file_path = $upload_dir['basedir'] . '/portada/' . $ean . '.jpg';

        // Get the ID of the product's featured image
        $thumbnail_id = get_post_thumbnail_id($product_id);

        // Check if the product has a featured image
        if (!$thumbnail_id) {
            return false;
        }

        // Get the file path of the featured image
        $thumbnail_path = get_attached_file($thumbnail_id);
        var_dump($thumbnail_path);
        var_dump($expected_file_path);
        // Compare the paths
        if ($thumbnail_path === $expected_file_path) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * set_featured_image_for_product
     *
     * @param  int $file_id
     * @param  string $ean
     * @return mixed
     */
    function set_featured_image_for_product( int $file_id, string $ean): mixed {
		$args = array(
			'post_type' => 'product',
			'meta_query' => array(
				array(
					'key' => '_ean',
					'value' => $ean,
				),
			),
		);

    	$products = get_posts($args);
        if ( empty( $products )) return false;
        $product = $products[0];

        // Check if a thumbnail is already set for the product
        if ( !$this->hasAttachment( $product->ID ) ) {
            set_post_thumbnail( $product->ID, $file_id );
        }
        return $product->ID;
    }

    /**
     * getProducts
     *
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getProducts( int $limit = -1, int $offset = 0 ): array {
        $args = [
            'status' => 'publish',
            'limit' => $limit,
			'offset' => $offset
        ];
        return wc_get_products($args);
    }

    /**
     * countAllProducts
     *
     * @return int
     */
    public function countAllProducts(): int {
        return count( wc_get_products( [
            'status' => 'publish',
            'limit' => -1,
        ]));
	}

    public function assignToProduct( string $isbn ): bool {
        global $wpdb; // Access the WordPress database object

        // Escape the EAN to prevent SQL injection
        $ean_escaped = esc_sql($isbn);
        var_dump($ean_escaped);
        // Build the query to find the attachment post with the specified filename
        $query = "
            SELECT ID
            FROM $wpdb->posts
            WHERE post_title LIKE %s
            AND post_type = 'attachment'
        ";

        $like_pattern = '%' . $wpdb->esc_like($ean_escaped . '.jpg') . '%';
        $attachment_id = $wpdb->get_var($wpdb->prepare($query, $like_pattern));

        // If no attachment is found, return a placeholder or a message
        if (!$attachment_id) {
            return false;
        }
        // Assuming set_featured_image_for_product does what it says

        $this->set_featured_image_for_product($attachment_id, $isbn);

        wp_reset_postdata(); // Reset post data to the original query
        return true;

    }

}