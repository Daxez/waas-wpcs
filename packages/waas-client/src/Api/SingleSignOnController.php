<?php

namespace WaaSClient\Api;

use WaaSClient\Core\DecryptionService;
use WaaSClient\Features\PluginBootstrap;
use WP_Error;
use WP_REST_Request;

class SingleSignOnController
{
    public const TENANT_PUBLIC_KEY = 'TENANT_PUBLIC_KEY';
    public const WPCS_PUBLIC_KEY_CONST_NAME = 'WPCS_PUBLIC_KEY';
    private DecryptionService $decryptionService;

    public function __construct(DecryptionService $decryptionService)
    {
        $this->decryptionService = $decryptionService;

        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_rest_routes()
    {
        register_rest_route(PluginBootstrap::API_V1_NAMESPACE, '/single_login/verify', array(
            'methods' => 'GET',
            'callback' => [$this, 'verify_single_login'],
        ));
    }

    public function verify_single_login(WP_REST_Request $request)
    {
        $token_encoded = urlencode($request->get_param('token'));
        $token_decoded = base64_decode(urldecode($token_encoded));

        $public_key = '';
        if (!defined(static::WPCS_PUBLIC_KEY_CONST_NAME)) {
            $public_key = get_option(static::TENANT_PUBLIC_KEY);

            if ($public_key === false || empty($public_key)) {
                return new WP_Error('Cannot determine public key!');
            }
        } else {
            $public_key = base64_decode(constant(static::WPCS_PUBLIC_KEY_CONST_NAME));
        }

        $data = $this->decryptionService->decrypt($public_key, $token_decoded);

        if (!$data) {
            return new WP_Error('Decryption failed');
        }

        $data = json_decode($data);

        $purpose = $data->purpose;
        if ($purpose !== "login") {
            return new WP_Error('Token not suitable to log in');
        }

        $expires_in = $data->expires + 0;
        $utc_now = gmdate("U") + 0;
        if ($expires_in  < $utc_now) {
            return new WP_Error('Token expired');
        }

        $user_username = $data->username;
        $user = get_user_by('login', $user_username);

        if (!$user) {
            return new WP_Error('User not found');
        }

        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);
        $redirect_to = apply_filters('wildcloud/sso/admin_url', admin_url(), $user, $data);
        wp_redirect($redirect_to);
        exit();
    }
}
