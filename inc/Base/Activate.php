<?php

/**
 * @package Cegal
 */

namespace Inc\cegal\Base;

 class Activate {
  const CEGAL_VERSION = '1.0.1';
  public static function activate() {
    global $wpdb;
    flush_rewrite_rules();
    $default = [];
    if ( !get_option('cegal_settings')) {
      update_option('cegal_settings', $default);
    }
    update_option('cegal_version', self::CEGAL_VERSION);
    self::cegal_update_tables();

  }

  public static function cegal_update_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $cegal_logger_table_name = $wpdb->prefix . 'cegal_logger';
    $cegal_log_table_name = $wpdb->prefix . 'cegal_log';
    $cegal_lines_table_name = $wpdb->prefix . 'cegal_lines';

    $cegal_log_sql = "CREATE TABLE $cegal_log_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `end_date` datetime NULL DEFAULT NULL,
      `status` varchar(255) NOT NULL,
      `scanned_items` mediumint(9) NOT NULL,
      `processed_items` mediumint(9) NOT NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

    $cegal_logger_sql = "CREATE TABLE $cegal_logger_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `log_id` mediumint(9) unsigned,
        `geslib_id` mediumint(9) NULL,
        `type` varchar(255) NOT NULL,
        `action` varchar(255) NOT NULL,
        `entity` varchar(255) NOT NULL,
        `metadata` text,
        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) $charset_collate;";

    $cegal_lines_sql = "CREATE TABLE $cegal_lines_table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      `log_id` mediumint(9) unsigned,
        `isbn` varchar(255) NOT NULL,
        `path` varchar(255) NOT NULL,
        `url_origin` varchar(255) NOT NULL,
        `url_target` varchar(255) NOT NULL,
        `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `isError` boolean,
        `error` varchar(255) NOT NULL,
        `attempts` mediumint(9) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $cegal_logger_sql );
    dbDelta( $cegal_log_sql );
    dbDelta( $cegal_lines_sql );
  }
 }