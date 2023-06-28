<?php

namespace WaaSHost\Integrations;

class WoocommerceSubscriptionsIntegration
{
    public static function init()
    {
        if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            add_action('woocommerce_checkout_subscription_created', [__CLASS__, 'create_tenant_when_subscription_created'], 10, 2);
            add_action('woocommerce_subscription_status_cancelled', [__CLASS__, 'remove_tenant_when_subscription_expired']);
            add_action('woocommerce_subscription_details_table', [__CLASS__, 'after_subscription_details_html']);
            add_action('ssd_add_simple_product_before_calculate_totals', [__CLASS__, 'on_add_send_update_tenant_user_roles'], 20, 1);
            add_action('wcs_user_removed_item', [__CLASS__, 'on_remove_send_update_tenant_user_roles'], 20, 2);
            add_filter('wpcs_subscription_id_email_for_login_guard', [__CLASS__, 'subscription_id_to_email_filter'], 10, 2);
        }
    }

    public static function create_tenant_when_subscription_created(\WC_Subscription $subscription, \WC_Order $order)
    {
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

    public static function subscription_id_to_email_filter($value, $subscription_id)
    {
        $subscription = new \WC_Subscription($subscription_id);
        $order = $subscription->get_parent();
        $order->get_billing_email();
    }

    public function on_add_send_update_tenant_user_roles(\WC_Subscription $subscription): void
    {
        $order_items = $subscription->get_items();
        $subscription_roles = [];
        foreach ($order_items as $order_Item) {
            $product_user_role = get_post_meta($order_Item->get_product_id(), WPCSTenant::WPCS_PRODUCT_ROLE_META, true);
            $subscription_roles[] = $product_user_role;
        }
        do_action('wpcs_tenant_roles_changed', $subscription->get_id(), $subscription_roles);
    }

    public function on_remove_send_update_tenant_user_roles(\WC_Order_Item_Product $line_item, \WC_Subscription $subscription): void
    {
        $order_items = $subscription->get_items();
        $subscription_roles = [];
        foreach ($order_items as $order_Item) {
            if ($order_Item->get_id() === $line_item->get_id()) {
                continue;
            }

            $product_user_role = get_post_meta($order_Item->get_product_id(), WPCSTenant::WPCS_PRODUCT_ROLE_META, true);
            $subscription_roles[] = $product_user_role;
        }

        do_action('wpcs_tenant_roles_changed', $subscription->get_id(), $subscription_roles);
    }
}
