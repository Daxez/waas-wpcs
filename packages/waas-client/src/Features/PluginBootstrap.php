<?php

namespace WaaSClient\Features;

class PluginBootstrap
{
    const TENANT_ROLES = 'WPCS_TENANT_ROLES';
    const TENANT_ROLES_CONSTANT = 'WPCS_TENANT_ROLES';
    const API_V1_NAMESPACE = 'waas-client/v1';
    const PLUGIN_NAME = 'waas-client/index.php';
    const PLUGIN_VERSION = '2.0.0';
    const WPCS_TENANT_NO_ADMINISTRATOR_PLUGIN_CAPS = 'WPCS_TENANT_NO_ADMINISTRATOR_PLUGIN_CAPS';
    const WPCS_REMOVED_ADMINISTRATOR_PLUGIN_CAPS = 'WPCS_REMOVED_ADMINISTRATOR_PLUGIN_CAPS';

    public static function init()
    {
        add_action('init', [__CLASS__, 'set_or_remove_admin_plugin_caps']);
        add_filter('plugin_action_links', [__CLASS__, 'show_plugins_managed_by_waas_client'], PHP_INT_MAX, 2);
    }

    public static function set_or_remove_admin_plugin_caps() {
        $plugins_capabilities = ['activate_plugins', 'delete_plugins', 'install_plugins', 'update_plugins', 'edit_plugins', 'upload_plugins'];
        $caps_removed = get_option(self::WPCS_REMOVED_ADMINISTRATOR_PLUGIN_CAPS, false);
        
        if (defined(self::WPCS_TENANT_NO_ADMINISTRATOR_PLUGIN_CAPS) && constant(self::WPCS_TENANT_NO_ADMINISTRATOR_PLUGIN_CAPS) == 'true' && getenv('WPCS_IS_TENANT') == 'true') {
            if (!$caps_removed) {
                $role = get_role('administrator');
                foreach ($plugins_capabilities as $capability) {
                    $role->remove_cap($capability);
                }
                
                update_option(self::WPCS_REMOVED_ADMINISTRATOR_PLUGIN_CAPS, true);
            }

            return;
        }

        if ($caps_removed) {
            $role = get_role('administrator');
            foreach ($plugins_capabilities as $capability) {
                $role->add_cap($capability);
            }

            delete_option(self::WPCS_REMOVED_ADMINISTRATOR_PLUGIN_CAPS);
        }
    }

    public static function show_plugins_managed_by_waas_client($actions, $plugin_file) {
        // We're not running in a tenant, so show the activation links.
        if(getenv('WPCS_IS_TENANT') != 'true') {
            return $actions;
        }

        // Tenant roles have not been defined, so the WaaS Client won't do anything.
        if(!defined(self::TENANT_ROLES_CONSTANT)) {
            return $actions;
        }

        // The WaaS Client itself should always have the deactivate button available.
        if($plugin_file === WPCS_WAAS_CLIENT_BASENAME) {
            return $actions;
        }

        unset($actions['activate']);
        unset($actions['deactivate']);

        return array_merge($actions, [
            'wpcs_active' => is_plugin_active( $plugin_file ) ? __('Activated by the WaaS Client', WPCS_WAAS_CLIENT_TEXTDOMAIN) : __('Deactivated by the WaaS Client', WPCS_WAAS_CLIENT_TEXTDOMAIN),
        ]);
    }
}
