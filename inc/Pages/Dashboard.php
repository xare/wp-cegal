<?php

namespace Inc\cegal\Pages;

use Inc\cegal\Api\SettingsApi;
use Inc\cegal\Base\BaseController;
use Inc\cegal\Api\Callbacks\AdminCallbacks;

class Dashboard extends BaseController {
    public $settings;
    public $pages = [];
    public $callbacks;


    public function register() {
        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();
        $this->setPages();
        $this->setSettings();
		$this->setSections();
		$this->setFields();
        $this->settings
			->addPages( $this->pages )
			->withSubPage( 'Dashboard' )
			//->addSubPages( $this->subpages )
			->register();
        /* $this->storeCegal(); */

    }


    public function setPages(){
		$this->pages = [
			[
				'page_title' => __('Cegal','Cegal'),
				'menu_title' =>  __('Cegal','Cegal'),
				'capability' => 'manage_options',
				'menu_slug' => 'Cegal',
				'callback' => [$this->callbacks, 'adminDashboard'] ,
				'icon_url' => 'dashicons-admin-plugins',
				'position' => 110
			]
		];
	}

    public function setSettings()
	{
		$args = [
			[
				'option_group'=> 'cegal_settings',
				'option_name' => 'cegal_settings',
				'callback' => [$this->callbacks, 'textSanitize']
            ]
		];

		$this->settings->setSettings( $args );

		// Save the default option if it doesn't exist
		if ( !get_option('cegal_settings') ) {
			$default_settings = [
				'cegal_user' => ''
			];
			update_option('cegal_settings', $default_settings);
		}
	}

    public function setSections()
	{
		$args = [
					[
						'id'=> 'cegal_admin_index',
						'title' => 'Settings Manager',
						'callback' => [$this->callbacks , 'adminSectionManager'],
						'page' => 'cegal' //From menu_slug
					]
		];
		$this->settings->setSections( $args );
	}

    public function setFields()
	{
		$args = [
                    [
						'id'=> 'cegal_user',
						'title' => 'Cegal User Name',
						'callback' => [$this->callbacks, 'textField'],
						'page' => 'cegal', //From menu_slug
						'section' => 'cegal_admin_index',
						'args' => [
							'option_name' => 'cegal_settings',
							'label_for' => 'cegal_user',
							'class' => 'regular-text'
						]
					],
					[
						'id'=> 'cegal_pass',
						'title' => 'Cegal Password',
						'callback' => [$this->callbacks, 'textField'],
						'page' => 'cegal', //From menu_slug
						'section' => 'cegal_admin_index',
						'args' => [
							'option_name' => 'cegal_settings',
							'label_for' => 'cegal_pass',
							'class' => 'regular-text'
						]
					]
                ];
		$this->settings->setFields( $args );
	}
}