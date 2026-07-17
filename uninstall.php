<?php
/**
 * Uninstall script for Init Ad Engine
 *
 * @package Init_Ad_Engine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Xóa option khi gỡ plugin.
// Lưu ý: KHÔNG dùng constant INIT_PLUGIN_SUITE_AD_ENGINE_OPTION ở đây vì
// WordPress không load file plugin chính khi chạy uninstall.php, nên
// constant đó chưa từng được định nghĩa tại thời điểm này.
delete_option( 'init_plugin_suite_ad_engine_settings' );
