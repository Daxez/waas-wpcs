<?php

use WaaSHost\Api\RolesController;
use WaaSHost\Api\SingleLogin;
use WaaSHost\Core\ConfigService;
use WaaSHost\Core\HttpService;
use WaaSHost\Core\WPCSService;
use WaaSHost\Features\PluginBootstrap;
use WaaSHost\Features\TenantsAddOnSubscriptionManager;
use WaaSHost\Features\TenantsSubscriptionManager;
use WaaSHost\Features\UserAccountSubscriptionsSettings;
use WaaSHost\Features\UserWcTenantsCheckout;
use WaaSHost\Features\AdminWpcsSettings;
use WaaSHost\Features\AdminWpcsHome;
use WaaSHost\Features\AdminNotices;
use WaaSHost\Features\AddonProductCategory;
use WaaSHost\Features\Notifications;
use WaaSHost\Features\WooCommerceCartValidator;
use WaaSHost\Features\WooCommerceProductData;
use WaaSHost\Features\WPCSTenantStatusService;
use WaaSHost\Integrations\SelfServiceDashboard\SelfServiceDashboardIntegration;
use WaaSHost\Integrations\WoocommerceSubscriptionsIntegration;
use WaaSHost\Integrations\SubscriptionsForWoocommerceIntegration;

require_once 'vendor/autoload.php';

/**
 * @package WaaSHost
 * @version 2.2.5
 */
/*
Plugin Name: WaaS Host
Plugin URI: https://github.com/Daxez/waas-wpcs
Description: This plugin is used to create tenants on WPCS.io with support of WordPress, WooCommerce, WooCommerce Subscriptions and Self-service Dashboard for WooCommerce Subscriptions.
Author: WPCS
Version: 2.2.5
Author URI: https://wpcs.io
Update URI: wpcs-waas-host
*/

define( 'WPCS_WAAS_HOST_SLUG', 'wpcs-waas-host' );
define( 'WPCS_WAAS_HOST_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCS_WAAS_HOST_UPDATE_URI', 'wpcs-waas-host' );
define( 'WPCS_WAAS_HOST_VERSION', '2.2.5' );
define( 'WPCS_WAAS_HOST_TEXTDOMAIN', 'wpcs-waas-host-textdomain' );

// Controllers to list for APIs
$wpcsConfigService = new ConfigService();
$wpcs_http_service = new HttpService($wpcsConfigService);
$wpcsService = new WPCSService($wpcs_http_service);
new RolesController($wpcsService);

// Managers to list for Events

// UI
SingleLogin::init();
new TenantsSubscriptionManager($wpcsService);
new TenantsAddOnSubscriptionManager($wpcsService);
new UserAccountSubscriptionsSettings($wpcsService);
new UserWcTenantsCheckout($wpcsService);
new WPCSTenantStatusService($wpcsService);
AdminNotices::init();
new AdminWpcsHome($wpcsConfigService);
new AdminWpcsSettings($wpcsService, $wpcsConfigService);
new WooCommerceProductData($wpcsService);
new WooCommerceCartValidator();
WaaSHost\Assets\Module::init();

// Integrations
SelfServiceDashboardIntegration::init();
SubscriptionsForWoocommerceIntegration::init();
WoocommerceSubscriptionsIntegration::init();

WaaSHost\Updater\Module::init();
new WaaSHost\Migrations\Module($wpcsService);
AddonProductCategory::init();
Notifications::init();

// Plugin Bootstrap
new PluginBootstrap();
