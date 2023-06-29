<?php

use WaaSHost\Api\RolesController;
use WaaSHost\Api\SingleLogin;
use WaaSHost\Core\EncryptionService;
use WaaSHost\Core\HttpService;
use WaaSHost\Core\WPCSService;
use WaaSHost\Features\PluginBootstrap;
use WaaSHost\Features\TenantsAddOnSubscriptionManager;
use WaaSHost\Features\TenantsSubscriptionManager;
use WaaSHost\Features\UserAccountSubscriptionsSettings;
use WaaSHost\Features\UserWcTenantsCheckout;
use WaaSHost\Features\AdminWcProductRole;
use WaaSHost\Features\AdminWpcsSettings;
use WaaSHost\Features\AdminNotices;
use WaaSHost\Features\AddonProductCategory;
use WaaSHost\Integrations\WoocommerceSubscriptionsIntegration;
use WaaSHost\Integrations\SubscriptionsForWoocommerceIntegration;

require_once 'vendor/autoload.php';

/**
 * @package WaaSHost
 * @version 1.5.1
 */
/*
Plugin Name: WaaS Host
Plugin URI: https://github.com/Daxez/waas-wpcs
Description: This plugin is used to create tenants on WPCS.io with support of WordPress, WooCommerce, WooCommerce Subscriptions and Self-service Dashboard for WooCommerce Subscriptions.
Author: WPCS
Version: 1.5.1
Author URI: https://wpcs.io
Update URI: wpcs-waas-host
*/

define( 'WPCS_WAAS_HOST_SLUG', 'wpcs-waas-host' );
define( 'WPCS_WAAS_HOST_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCS_WAAS_HOST_UPDATE_URI', 'wpcs-waas-host' );
define( 'WPCS_WAAS_HOST_VERSION', '1.5.1' );
define( 'WPCS_WAAS_HOST_TEXTDOMAIN', 'wpcs-waas-host-textdomain' );

define('WPCS_API_REGION', get_option('wpcs_credentials_region_setting')); // Or eu1, depending on your region.
define('WPCS_API_KEY', get_option('wpcs_credentials_api_key_setting')); // The API Key you retrieved from the console
define('WPCS_API_SECRET', get_option('wpcs_credentials_api_secret_setting')); // The API Secret you retrieved from the console

// Controllers to list for APIs
$wpcs_http_service = new HttpService('https://api.' . WPCS_API_REGION . '.wpcs.io', WPCS_API_KEY . ":" . WPCS_API_SECRET);
$wpcsService = new WPCSService($wpcs_http_service);
$encryptionService = new EncryptionService();
new RolesController($wpcsService);

// Managers to list for Events

// UI
new SingleLogin($encryptionService);
new TenantsSubscriptionManager($wpcsService, $encryptionService);
new TenantsAddOnSubscriptionManager($wpcsService);
new AdminWpcsSettings();
new UserAccountSubscriptionsSettings($wpcsService);
new AdminWcProductRole($wpcsService);
new UserWcTenantsCheckout($wpcsService);
AdminNotices::init();

// Integrations
SubscriptionsForWoocommerceIntegration::init();
WoocommerceSubscriptionsIntegration::init();

// Updater
WaaSHost\Updater\Module::init();

// Add-on taxonomy
AddonProductCategory::init();

// Plugin Bootstrap
new PluginBootstrap();
