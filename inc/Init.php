<?php

namespace Inc\cegal;

use Inc\cegal\Base\CegalScanProductFormController;
use Inc\cegal\Base\CegalScanProductsFormController;
use Inc\cegal\Base\Cron;
use Inc\cegal\Base\Enqueue;
use Inc\cegal\Commands\CegalHelloCommand;
use Inc\cegal\Commands\CegalScanProductsCommand;
use Inc\cegal\Commands\CegalMediaCleanup;
use Inc\cegal\Commands\CegalScanProductCommand;
use Inc\cegal\Pages\Dashboard;

final class Init
{
  /**
   * Store all the classes inside an array
   *
   * @return array Full list of classes
   */
  public static function get_services():Array {
    return [
      Dashboard::class,
      CegalScanProductsCommand::class,
      CegalHelloCommand::class,
      CegalScanProductCommand::class,
      CegalMediaCleanup::class,
      CegalScanProductsFormController::class,
      CegalScanProductFormController::class,
      Enqueue::class,
      Cron::class,
    ];
  }

  /**
   * Loop through the classes, initialize them
   * and call the register() method if it exists
   *
   * @return void
   */
  public static function register_services() {
    foreach(self::get_services() as $class){
      $service = self::instantiate( $class );
      if(method_exists($service,'register')) {
          $service->register();
      }
    }
  }
  /**
   * Initialize the class
   *
   * @param [type] $class class from the services array
   * @return class instance new instance of the class
   */
  private static function instantiate( $class ) {
    return new $class();
  }
}
