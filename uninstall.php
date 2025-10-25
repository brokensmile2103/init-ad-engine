<?php
/**
 * Uninstall script for Init Ad Engine
 *
 * @package Init_Ad_Engine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xóa option khi gỡ plugin
delete_option( INIT_PLUGIN_SUITE_AD_ENGINE_OPTION );
