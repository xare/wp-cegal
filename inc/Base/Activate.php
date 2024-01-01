<?php

/**
 * @package Cegal
 */

namespace Inc\cegal\Base;

 class Activate {
  public static function activate() {
    global $wpdb;
    flush_rewrite_rules();

    $default = [];

    if ( !get_option('cegal_settings')) {
      update_option('cegal_settings', $default);
    }

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  }
 }