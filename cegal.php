<?php
    /*
    Plugin Name: Cegal for WP plugin
    Description: Cegal plugin for WordPress
    Version: 1.0
    Author: xare@katakrak.net
    */

defined( 'ABSPATH' ) or die ( 'Acceso prohibido');

// Require once the Composer Autoload
if( file_exists( dirname( __FILE__).'/vendor/autoload.php' ) ){
  require_once dirname( __FILE__).'/vendor/autoload.php';
}

/**
 * The code that runs during plugin Activation
 *
 * @return void
 */
function activate_cegal(){
  Inc\cegal\Base\Activate::activate();
}
register_activation_hook( __FILE__, 'activate_cegal');

/**
 * The code that runs during plugin Deactivation
 *
 * @return void
 */
function deactivate_cegal(){
  Inc\cegal\Base\Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_cegal');

if(class_exists( 'Inc\\cegal\\Init' )) {
  Inc\cegal\Init::register_services();
}