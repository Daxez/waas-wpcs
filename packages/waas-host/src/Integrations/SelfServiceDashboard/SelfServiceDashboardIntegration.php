<?php

namespace WaaSHost\Integrations\SelfServiceDashboard;

use WaaSHost\Core\WPCSProduct;

class SelfServiceDashboardIntegration
{
    public static function init()
    {
        if (!function_exists('is_plugin_active'))
        {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        if (is_plugin_active('self-service-dashboard-for-woocommerce-subscriptions/self-service-dashboard-for-woocommerce-subscriptions.php'))
        {
            add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_addon_loading_indicator']);
            add_filter('ssd_add_product_link', [__CLASS__, 'rename_add_new_product_to_add_new_subscription']);
            add_filter('ssd_product_query_args', [__CLASS__, 'only_show_addons_when_adding_ons'], 10, 1);
        }
    }

    public static function enqueue_addon_loading_indicator()
    {
        if(\is_account_page())
        {
            $script_name = 'add-on-loading-indicator.js';
            $src = plugin_dir_url( __FILE__ ) . "/assets/js/$script_name";
            $version = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . "assets/js/$script_name" ));
            wp_enqueue_script('add-on-loading-indicator', $src, ['jquery'], $version, true);
        }
    }

    public static function rename_add_new_product_to_add_new_subscription($link)
    {
        return str_replace('Add new product', __('Add new add-on', WPCS_WAAS_HOST_TEXTDOMAIN), $link);
    }

    public static function only_show_addons_when_adding_ons($args)
    {
        $args['meta_query'][] = array(
			'key' => WPCSProduct::IS_WPCS_PRODUCT_META,
			'value' => esc_attr( WPCSProduct::WPCS_PRODUCT_TYPE_ADDON ),
		);

        return $args;
    }
}
