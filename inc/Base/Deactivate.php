<?php

/**
 * @package Cegal
 */
namespace Inc\cegal\Base;

 class Deactivate {
  public static function deactivate() {
    flush_rewrite_rules();
  }
 }