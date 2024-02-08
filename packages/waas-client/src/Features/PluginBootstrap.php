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
}
