<?php

require_once 'vendor/autoload.php';
require_once(ABSPATH . '/wp-admin/includes/plugin.php');

/*
Plugin Name: WaaS Client
Plugin URI: https://github.com/Daxez/waas-wpcs
Description: This plugin is used as counterpart for the WaaS-Host plugin in repository https://github.com/Daxez/waas-wpcs and enables single sign on from the Host as well as enabling/disabling certain plugins as described by the roles settings.
Author: WPCS
Version: 2.2.4
Author URI: https://wpcs.io
Update URI: wpcs-waas-client
*/

define( 'WPCS_WAAS_CLIENT_SLUG', 'wpcs-waas-client' );
define( 'WPCS_WAAS_CLIENT_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCS_WAAS_CLIENT_UPDATE_URI', 'wpcs-waas-client' );
define( 'WPCS_WAAS_CLIENT_VERSION', '2.2.4' );
define( 'WPCS_WAAS_CLIENT_TEXTDOMAIN', 'wpcs-waas-client-textdomain' );

use WaaSClient\Api\SingleSignOnController;
use WaaSClient\Core\DecryptionService;
use WaaSClient\Core\HttpService;
use WaaSClient\Features\AdminRolesSettings;
use WaaSClient\Features\PluginBootstrap;
use WaaSClient\Features\RolesManager;
use WaaSClient\Features\AdminTenantSettings;

if (!wp_doing_ajax()) {
    define("WAAS_PLUGIN_DIR_URI", plugin_dir_url(__FILE__));
    define("WAAS_PLUGIN_DIR", plugin_dir_path(__FILE__));

    define('WAAS_MAIN_HOST_URL', get_option('waas_host_website_url'));

    // Controllers to list for APIs
    $host_http_service = new HttpService(WAAS_MAIN_HOST_URL . '/wp-json/waas-host/v1');
    $decryptionService = new DecryptionService();

    // Managers to list for Events
    new RolesManager();

    // Plugin Boostrap
    PluginBootstrap::init();

    new SingleSignOnController($decryptionService);

    // Updater
    WaaSClient\Updater\Module::init();

    if (getenv("WPCS_IS_TENANT") !== 'true') {
        new AdminTenantSettings();
        new AdminRolesSettings($host_http_service);
    }
}
