<?php

namespace Inc\cegal\Commands;

use WP_CLI;

/**
 * Class CegalHelloCommand
 */
class CegalHelloCommand {
	public function register() {
        if ( class_exists( 'WP_CLI' ) ) {
            WP_CLI::add_command( 'cegal hello', [$this, 'execute'] );
        }
    }
    /**
     * Prints a hello world message
     *
     * ## OPTIONS
     *
     *
     * ## EXAMPLES
     *
     *     wp cegal hello
     *
     * @when after_wp_load
     */
    public function execute( $args, $assoc_args ) {
        WP_CLI::line( 'Hello, World!' );
    }
}

