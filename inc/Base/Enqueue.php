<?php

/**
 * @package cegal
 */

namespace Inc\cegal\Base;

use Inc\cegal\Base\BaseController;
class Enqueue extends BaseController {
  public function register(){
    $page = filter_input(INPUT_GET, 'page', FILTER_DEFAULT);
    if (is_admin() && $page === 'Cegal')
      add_action ( 'admin_enqueue_scripts', [$this, 'enqueue_admin']);
    //add_action ( 'enqueue_scripts', [$this, 'enqueue']);
  }
function enqueue() {
        //enqueue all our scripts

        wp_enqueue_script('media_upload');
        wp_enqueue_media();
        wp_enqueue_style('CegalStyle', $this->plugin_url . 'dist/css/cegal.min.css');
        wp_enqueue_script('CegalScript', $this->plugin_url . 'dist/js/cegal.min.js');
      }
  function enqueue_admin() {
        // enqueue all our scripts
        wp_enqueue_style('CegalAdminStyle', $this->plugin_url .'dist/css/cegalAdmin.min.css');
        wp_enqueue_script('CegalAdminScript', $this->plugin_url .'dist/js/cegalAdmin.min.js');
      }
}