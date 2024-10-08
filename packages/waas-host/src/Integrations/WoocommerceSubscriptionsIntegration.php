<?php

namespace WaaSHost\Integrations;

use WaaSHost\Core\WPCSProduct;
use WaaSHost\Core\WPCSTenant;
use WaaSHost\Features\SingleLoginService;

class WoocommerceSubscriptionsIntegration
{
    public static function init()
    {
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            add_action('woocommerce_subscription_status_active', [__CLASS__, 'create_tenant_when_subscription_active'], 10, 1);
            add_action('woocommerce_subscription_status_cancelled', [__CLASS__, 'remove_tenant_when_subscription_expired']);
            add_action('woocommerce_subscription_details_table', [__CLASS__, 'after_subscription_details_html']);
            add_action('woocommerce_subscription_details_table', [__CLASS__, 'show_login_link']);
            add_action('woocommerce_subscription_details_table', [__CLASS__, 'show_tenant_status']);
            add_action('ssd_add_simple_product_before_calculate_totals', [__CLASS__, 'on_add_send_update_tenant_user_roles'], 20, 1);
            add_action('wcs_user_removed_item', [__CLASS__, 'on_remove_send_update_tenant_user_roles'], 20, 2);
            add_filter('wpcs_get_customer_id_by_subscription_id_for_login_guard', [__CLASS__, 'subscription_id_to_customer_id'], 10, 2);
            add_filter('wpcs_get_customer_username_by_subscription_id', [__CLASS__, 'subscription_id_to_customer_username'], 10, 2);
            add_filter('wpcs_subscription_details_url', [__CLASS__, 'get_subscription_detail_page'], 10, 2);
        }
    }

    public static function create_tenant_when_subscription_active(\WC_Subscription $subscription)
    {
        $order = $subscription->get_parent();
        do_action('wpcs_subscription_created', $subscription->get_id(), $order);
    }

    public static function remove_tenant_when_subscription_expired(\WC_Subscription $subscription)
    {
        do_action('wpcs_subscription_expired', $subscription->get_id());
    }

    public static function after_subscription_details_html(\WC_Subscription $subscription)
    {
        $order = $subscription->get_parent();
        do_action('wpcs_after_subscription_details_html', $subscription->get_id(), $order);
    }

    public static function show_login_link(\WC_Subscription $subscription)
    {
        $order = $subscription->get_parent();
        $subscription_id = $subscription->get_id();
        $login_link = SingleLoginService::get_login_link($subscription_id, $order);
        $username = SingleLoginService::get_formatted_username($subscription_id);
        echo "<a href='$login_link' target='_blank' class='wpcs-single-login-button'>Login as: $username <span class='dashicons dashicons-admin-network'></span></a>";
    }

    public static function show_tenant_status(\WC_Subscription $subscription)
    {
        $tenant = new WPCSTenant($subscription->get_id());
?>
        <div>
            Website status: <?php echo $tenant->get_status(); ?>
        </div>
<?php
    }

    public static function subscription_id_to_email_filter($value, $subscription_id)
    {
        $subscription = new \WC_Subscription($subscription_id);
        $order = $subscription->get_parent();
        return $order->get_billing_email();
    }

    public static function subscription_id_to_customer_id($value, $subscription_id)
    {
        $subscription = new \WC_Subscription($subscription_id);
        $order = $subscription->get_parent();
        return $order->get_customer_id();
    }

    public static function subscription_id_to_customer_username($value, $subscription_id)
    {
        $subscription = new \WC_Subscription($subscription_id);
        $order = $subscription->get_parent();
        return $order->get_formatted_billing_full_name();
    }

    public static function on_add_send_update_tenant_user_roles(\WC_Subscription $subscription): void
    {
        $order_items = $subscription->get_items();
        $subscription_roles = [];
        foreach ($order_items as $order_Item) {
            $product_user_role = get_post_meta($order_Item->get_product_id(), WPCSProduct::WPCS_PRODUCT_ROLE_META, true);
            $subscription_roles[] = $product_user_role;
        }
        do_action('wpcs_tenant_roles_changed', $subscription->get_id(), $subscription_roles);
    }

    public static function on_remove_send_update_tenant_user_roles(\WC_Order_Item_Product $line_item, \WC_Subscription $subscription): void
    {
        $order_items = $subscription->get_items();
        $subscription_roles = [];
        foreach ($order_items as $order_Item) {
            if ($order_Item->get_id() === $line_item->get_id()) {
                continue;
            }

            $product_user_role = get_post_meta($order_Item->get_product_id(), WPCSProduct::WPCS_PRODUCT_ROLE_META, true);
            $subscription_roles[] = $product_user_role;
        }

        do_action('wpcs_tenant_roles_changed', $subscription->get_id(), $subscription_roles);
    }

    public static function get_subscription_detail_page($storefront_url, $subscription_id)
    {
        return "$storefront_url/my-account/view-subscription/$subscription_id/";
    }
}
