<?php
/**
 * @package Cegal
 */

 namespace Inc\cegal\Base;

 class BaseController
 {
  public $plugin_path;
  public $plugin_url;
  public $plugin;
  public $plugin_templates_path;
  public array $managers;

  public function __construct() {
    $this->plugin_path = plugin_dir_path( dirname( __FILE__, 2));
    $this->plugin_templates_path = plugin_dir_path( dirname( __FILE__, 2)).'/templates';
    $this->plugin_url = plugin_dir_url( dirname( __FILE__, 2));
    $this->plugin = plugin_basename( dirname( __FILE__, 3) ) . '/cegal.php';
    $this->managers = [
      'cpt_manager' => 'Activate CPT Manager',
    ];
  }

  public function activated(string $key)
  {
    $option = get_option('cegal');
    return isset($option[$key]) ? $option[$key] : false;
  }

}