<?php

namespace WaaSHost\Features;

use WaaSHost\Core\WPCSTenant;
use WaaSHost\Core\WPCSService;
use WaaSHost\Core\Exceptions\InvalidDomainException;
use WaaSHost\Core\WPCSProduct;

class UserWcTenantsCheckout
{
    private WPCSService $wpcsService;

    public function __construct(WPCSService $wpcsService)
    {
        $this->wpcsService = $wpcsService;
        add_filter('woocommerce_checkout_fields', [$this, 'render_wpcs_checkout_fields']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_website_name_field']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'add_wpcs_checkout_fields']);
    }

    public function render_wpcs_checkout_fields($fields)
    {
        $base_product_in_cart = false;
        $is_renewal = false;

        $items = \WC()->cart->cart_contents;
        foreach ($items as $key => $item)
        {
            $wpcs_product = new WPCSProduct($item['product_id']);
            if($wpcs_product->is_wpcs_product())
            {
                if(array_key_exists('subscription_renewal', $item))
                {
                    $is_renewal = true;
                }

                $base_product_in_cart = true;
                break;
            }
        }

        if($base_product_in_cart && !$is_renewal)
        {
            $fields['billing'][WPCSTenant::WPCS_WEBSITE_NAME_META] = [
                'label' => 'Website Name',
                'required' => true,
                'priority' => 30,
                'class' => apply_filters('wpcs_website_name_checkout_field_classes', ['form-row-wide']),
            ];
        }

        return $fields;
    }

    public function validate_website_name_field($data)
    {
        if(!array_key_exists(WPCSTenant::WPCS_WEBSITE_NAME_META, $data))
        {
            // No website name found after checkout, so never mind then
            return;
        }

        $tenant_root_domain = get_option('wpcs_host_settings_root_domain', '');
        if (strlen($tenant_root_domain) === 0) {
            // If there is no tenant root domain, the WPCS platform will handle creating unique
            return;
        }

        // Construct tenant custom domain name
        $websiteName = $data[WPCSTenant::WPCS_WEBSITE_NAME_META];
        $subdomain = sanitize_text_field(sanitize_title_with_dashes(remove_accents($websiteName))) . "." . $tenant_root_domain;

        global $wpdb;

        $tbl = $wpdb->prefix . 'postmeta';
        $tbl2 = $wpdb->prefix . 'posts';
        $prepare_guery = $wpdb->prepare("SELECT post_id FROM $tbl m INNER JOIN $tbl2 p ON m.post_id = p.id WHERE p.post_type = 'shop_subscription' AND p.post_status <> 'wc-cancelled' AND m.meta_key ='" . WPCSTenant::WPCS_WEBSITE_NAME_META . "' AND m.meta_value = '%s'", $subdomain);
        $get_values = $wpdb->get_col($prepare_guery);

        if (count($get_values) > 0) {
            // If there already is a Post with that kind of WPCS_WEBSITE_NAME_META then add a WC Error
            wc_add_notice(__('Website name is already taken! Please use a different one.', WPCS_WAAS_HOST_TEXTDOMAIN), 'error');
            return;
        }

        try {
            $domainAvailable = $this->wpcsService->domain_available($subdomain);
            if($domainAvailable){
                return;
            }

            wc_add_notice(__('Website name is already taken! Please use a different one.', WPCS_WAAS_HOST_TEXTDOMAIN), 'error');
        } catch(InvalidDomainException $e) {
            wc_add_notice(__('The resulting domain is not valid.', WPCS_WAAS_HOST_TEXTDOMAIN), 'error');
        }
    }

    function add_wpcs_checkout_fields($order_id)
    {
        update_post_meta($order_id, WPCSTenant::WPCS_WEBSITE_NAME_META, sanitize_text_field($_POST[WPCSTenant::WPCS_WEBSITE_NAME_META]));
    }
}
