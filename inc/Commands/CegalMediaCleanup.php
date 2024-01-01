<?php

namespace Inc\cegal\Commands;
use WP_CLI;
use WP_Query;

class CegalMediaCleanup {
    public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'cegal mediaCleanup removeAttachments', [$this, 'remove_attachments'] );
            WP_CLI::add_command( 'cegal mediaCleanup deleteFiles', [$this, 'delete_files'] );
            WP_CLI::add_command( 'cegal mediaCleanup removeUnattached', [$this, 'remove_unattached'] );
        }
    }

    /**
     * Remove all attachments from woocommerce products
     *
     * @subcommand remove-attachments
     */
    public function remove_attachments() {
        global $wpdb;

        // Get IDs of all attachment linked to a WooCommerce product
        $attachments = $wpdb->get_col(
            "SELECT pm.meta_value FROM $wpdb->postmeta pm
                LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_thumbnail_id'
                AND p.post_type = 'product'"
        );

        foreach ($attachments as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }

        WP_CLI::success('All attachments from WooCommerce products have been removed');
    }

    /**
     * Delete files in a specific directory
     *
     * @subcommand delete-files
     */
    public function delete_files() {
        $dir = ABSPATH . 'wp-content/uploads/portadas';

        foreach (glob("$dir/*") as $file) {
            var_dump( $file );
            $file_url = site_url(str_replace(ABSPATH, '', $file));
            $attachment_id = attachment_url_to_postid($file_url);
            var_dump($attachment_id);
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
        }

        WP_CLI::success('All files in the directory and the associated attachment posts have been deleted');
    }

    /**
 * Remove unattached media items
 *
 * @subcommand removeUnattached
 */
public function remove_unattached() {
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'any',
        'post_parent'    => 0, // this will target unattached media
        'posts_per_page' => -1,
    );

    $unattached = new WP_Query($args);

    if ($unattached->have_posts()) :
        while ($unattached->have_posts()) : $unattached->the_post();
            $id = get_the_ID();
            wp_delete_attachment($id, true);
        endwhile;
        WP_CLI::success('All unattached media items have been removed');
    else :
        WP_CLI::success('No unattached media items found');
    endif;

    wp_reset_postdata();
}



}

