<?php

namespace Inc\cegal\Pages;

use Inc\cegal\Api\SettingsApi;
use Inc\cegal\Base\BaseController;
use Inc\cegal\Api\Callbacks\AdminCallbacks;

class Dashboard extends BaseController {
    public $settings;
    public $pages = [];
	public $subpages = []; // Add this line to define subpages
    public $callbacks;


    public function register() {
        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();
        $this->setPages();
		$this->setSubpages();
        $this->setSettings();
		$this->setSections();
		$this->setFields();
        $this->settings
			->addPages( $this->pages )
			->withSubPage( 'Dashboard' )
			->addSubPages( $this->subpages )
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
	// Define this new method to add your subpages
    public function setSubpages() {
        $this->subpages = [
            [
                'parent_slug' => 'Cegal', // Parent menu slug
                'page_title' => 'Cegal Scan Product', // Page title
                'menu_title' => 'Scan Product', // Menu title
                'capability' => 'manage_options', // Capability
                'menu_slug' => 'cegal_scan_product', // Menu slug
                'callback' => [$this->callbacks, 'adminScanProduct'] // Callback function, define it in AdminCallbacks class
			],
			[
                'parent_slug' => 'Cegal', // Parent menu slug
                'page_title' => 'Cegal Log', // Page title
                'menu_title' => 'Cegal Log Table', // Menu title
                'capability' => 'manage_options', // Capability
                'menu_slug' => 'cegal_log_table', // Menu slug
                'callback' => [$this->callbacks, 'adminLogTable'] // Callback function, define it in AdminCallbacks class
            ],
			[
                'parent_slug' => 'Cegal', // Parent menu slug
                'page_title' => 'Cegal Lines', // Page title
                'menu_title' => 'Cegal Lines Table', // Menu title
                'capability' => 'manage_options', // Capability
                'menu_slug' => 'cegal_lines_table', // Menu slug
                'callback' => [$this->callbacks, 'adminLinesTable'] // Callback function, define it in AdminCallbacks class
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