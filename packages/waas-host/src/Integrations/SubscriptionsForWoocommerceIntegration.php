<?php

namespace WaaSHost\Integrations;

use Error;
use WaaSHost\Core\WPCSTenant;
use WaaSHost\Features\SingleLoginService;
use WaaSHost\Core\WPCSProduct;

class SubscriptionsForWoocommerceIntegration
{
    public static function init()
    {
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        if (is_plugin_active('subscriptions-for-woocommerce/subscriptions-for-woocommerce.php')) {
            add_action('wps_sfw_order_status_changed', [__CLASS__, 'create_tenant_when_subscription_created'], 10, 2);
            add_action('wps_sfw_subscription_cancel', [__CLASS__, 'remove_tenant_when_subscription_expired']);
            add_action('wps_sfw_after_subscription_details', [__CLASS__, 'ater_subscription_details_html'], 100);
            add_filter('wpcs_subscription_id_email_for_login_guard', [__CLASS__, 'subscription_id_to_email_filter'], 10, 2);
            add_filter('wpcs_get_customer_id_by_subscription_id_for_login_guard', [__CLASS__, 'subscription_id_to_customer_id'], 10, 2);
            add_filter('wpcs_get_customer_username_by_subscription_id', [__CLASS__, 'subscription_id_to_customer_username'], 10, 2);
            add_filter('wps_sfw_subscription_details_html', [__CLASS__, 'show_tenant_status'], 10, 1);
            add_filter('wps_sfw_subscription_details_html', [__CLASS__, 'show_login_link'], 10, 1);
            add_filter('wpcs_subscription_details_url', [__CLASS__, 'get_subscription_detail_page'], 10, 2);
            add_action('woocommerce_new_order_item', [__CLASS__, 'je_moer'], 10, 3);
        }
    }

    public static function je_moer($item_id, $item, $order_id)
    {
        $order = new \WC_Order($order_id);

        $substatus = get_post_meta($order_id, 'wps_subscription_status', true);
        if ($substatus !== "active") {
            return;
        }

        $order_items = $order->get_items();
        $subscription_roles = [];
        foreach ($order_items as $order_Item) {
            $product_user_role = get_post_meta($order_Item->get_product_id(), WPCSProduct::WPCS_PRODUCT_ROLE_META, true);
            $subscription_roles[] = $product_user_role;
        }
        do_action('wpcs_tenant_roles_changed', $order_id, $subscription_roles);
    }

    public static function create_tenant_when_subscription_created($order_id, $subscription_id)
    {
        $order = new \WC_Order($order_id);
        do_action('wpcs_subscription_created', $subscription_id, $order);
    }

    public static function remove_tenant_when_subscription_expired($subscription_id)
    {
        do_action('wpcs_subscription_expired', $subscription_id);
    }

    public static function ater_subscription_details_html($subscription_id)
    {
        $order = new \WC_Order($subscription_id);
        do_action('wpcs_after_subscription_details_html', $subscription_id, $order);
    }

    public static function subscription_id_to_email_filter($value, $subscription_id)
    {
        $order = new \WC_Order($subscription_id);
        return $order->get_billing_email();
    }

    public static function subscription_id_to_customer_id($value, $subscription_id)
    {
        $order = new \WC_Order($subscription_id);
        $parent_order = new \WC_Order($order->get_parent_id());
        return $parent_order->get_customer_id();
    }

    public static function subscription_id_to_customer_username($value, $subscription_id)
    {
        $order = new \WC_Order($subscription_id);
        $parent_order = new \WC_Order($order->get_parent_id());
        return $parent_order->get_formatted_billing_full_name();
    }

    public static function show_tenant_status($subscription_id)
    {
        $tenant = new WPCSTenant($subscription_id);
?>
        <tr>
            <td>Website status</td>
            <td><?php echo $tenant->get_status(); ?></td>
        </tr>
        <?php
    }

    public static function show_login_link($subscription_id)
    {
        $tenant = new WPCSTenant($subscription_id);
        $tenant_status = $tenant->get_status();

        if ($tenant_status != WPCSTenant::READY) {

        ?>
            <tr>
                <td colspan="2">
                    <p><?php __("A direct login link to your site will be shown here once your site is ready!", WPCS_WAAS_HOST_TEXTDOMAIN); ?></p>
                </td>
            </tr>
        <?php

            return;
        }

        $order = new \WC_Order($subscription_id);
        $login_link = SingleLoginService::get_login_link($subscription_id, $order);
        ?>
        <tr>
            <td colspan="2">
                <a href='<?php echo $login_link; ?>' target='_blank' class="wpcs-single-login-button">
                    Login as <?php echo SingleLoginService::get_formatted_username($subscription_id); ?>
                </a>
            </td>
        </tr>
<?php
    }

    public static function get_subscription_detail_page($storefront_url, $subscription_id)
    {
        return "$storefront_url/my-account/show-subscription/$subscription_id";
    }
}
