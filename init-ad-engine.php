<?php
/**
 * Plugin Name: Init Ad Engine
 * Plugin URI: https://inithtml.com/plugin/init-ad-engine/
 * Description: A lightweight but powerful ad display engine for WordPress. Smart placement, no code required.
 * Version: 1.3
 * Author: Init HTML
 * Author URI: https://inithtml.com/
 * Text Domain: init-ad-engine
 * Domain Path: /languages
 * Requires at least: 5.5
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('INIT_PLUGIN_SUITE_AD_ENGINE_VERSION', '1.3');
define('INIT_PLUGIN_SUITE_AD_ENGINE_SLUG', 'init-ad-engine');
define('INIT_PLUGIN_SUITE_AD_ENGINE_OPTION', 'init_plugin_suite_ad_engine_settings');
define('INIT_PLUGIN_SUITE_AD_ENGINE_URL', plugin_dir_url(__FILE__));
define('INIT_PLUGIN_SUITE_AD_ENGINE_PATH', plugin_dir_path(__FILE__));
define('INIT_PLUGIN_SUITE_AD_ENGINE_ASSETS_URL', INIT_PLUGIN_SUITE_AD_ENGINE_URL . 'assets/');
define('INIT_PLUGIN_SUITE_AD_ENGINE_ASSETS_PATH', INIT_PLUGIN_SUITE_AD_ENGINE_PATH . 'assets/');
define('INIT_PLUGIN_SUITE_AD_ENGINE_INCLUDES_PATH', INIT_PLUGIN_SUITE_AD_ENGINE_PATH . 'includes/');
define('INIT_PLUGIN_SUITE_AD_ENGINE_LANGUAGES_PATH', INIT_PLUGIN_SUITE_AD_ENGINE_PATH . 'languages/');

/**
 * Allowed tags/attributes for ad snippets (frontend render).
 */
function init_plugin_suite_ad_engine_allowed_tags() {
    $tags = wp_kses_allowed_html( 'post' );

    // <script> phổ biến + Clickadu-specific data-*
    $tags['script'] = array(
        'id'              => true,
        'type'            => true,
        'src'             => true,
        'async'           => true,
        'defer'           => true,
        'module'          => true,
        'nomodule'        => true,
        'nonce'           => true,
        'integrity'       => true,
        'crossorigin'     => true,
        'referrerpolicy'  => true,
        'data-cfasync'    => true,
        'data-clocid'     => true,
        'data-clipid'     => true,
    );

    $tags['iframe'] = array(
        'src'             => true,
        'height'          => true,
        'width'           => true,
        'style'           => true,
        'title'           => true,
        'loading'         => true,
        'referrerpolicy'  => true,
        'allow'           => true,
        'allowfullscreen' => true,
        'sandbox'         => true,
    );

    $tags['ins'] = array(
        'class'                      => true,
        'style'                      => true,
        'data-ad-client'             => true,
        'data-ad-slot'               => true,
        'data-ad-format'             => true,
        'data-full-width-responsive' => true,
    );

    $tags['div'] = array(
        'id'    => true,
        'class' => true,
        'style' => true,
    );
    $tags['span'] = array(
        'id'    => true,
        'class' => true,
        'style' => true,
    );

    // Cho phép mở rộng từ theme/plugin khác mà không sửa core
    return apply_filters( 'init_plugin_suite_ad_engine_allowed_tags', $tags );
}

/**
 * Escape ad snippet with wp_kses, but allow disabling via filter.
 *
 * @param string $content Raw ad snippet.
 * @return string Escaped or raw, depending on filter.
 */
function init_plugin_suite_ad_engine_render_snippet( $content ) {
    $use_escape = apply_filters( 'init_plugin_suite_ad_engine_use_kses', true );

    if ( $use_escape ) {
        return wp_kses( $content, init_plugin_suite_ad_engine_allowed_tags() );
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return $content;
}

/**
 * Global head injection (admin-configured)
 */
add_action( 'wp_head', function () {
    if ( apply_filters( 'init_plugin_suite_ad_engine_disable_all_ads', false ) ) {
        return;
    }

    $settings = get_option( INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, array() );
    if ( ! empty( $settings['global_head'] ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo init_plugin_suite_ad_engine_render_snippet( $settings['global_head'] );
    }
}, 5 );

/**
 * Global footer injection (admin-configured)
 */
add_action( 'wp_footer', function () {
    if ( apply_filters( 'init_plugin_suite_ad_engine_disable_all_ads', false ) ) {
        return;
    }

    $settings = get_option( INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, array() );
    if ( ! empty( $settings['global_footer'] ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo init_plugin_suite_ad_engine_render_snippet( $settings['global_footer'] );
    }
}, 100 );

/**
 * Front enqueue + data bootstrapping
 */
add_action('wp_enqueue_scripts', function () {
    if (apply_filters('init_plugin_suite_ad_engine_disable_all_ads', false)) return;

    $settings = get_option(INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, array());
    if (empty($settings)) return;

    // =====================
    // 1) script.js (main)
    // =====================
    $positions = array(
        'billboard', 'balloonLeft', 'balloonRight',
        'floatLeft', 'floatRight',
        'catfishTop', 'catfishBottom',
        'popupCenterPC', 'popupCenterMobile',
        'stickyTopMobile', 'stickyBottomMobile',
        'miniBillboard', 'popunder'
    );

    $ad_data = array();

    foreach ($positions as $pos) {
        $config = isset($settings[$pos]) && is_array($settings[$pos]) ? $settings[$pos] : array();

        // --- PHP 7.4 compatibility: replace str_starts_with/str_contains ---
        $is_popup_center = (strpos($pos, 'popupCenter') === 0);
        $is_mobile_pos   = (strpos($pos, 'Mobile') !== false) || ($pos === 'miniBillboard');

        $has_valid_config = (
            isset($config['img']) ||
            isset($config['fallback']) ||
            $pos === 'popunder' ||
            $is_popup_center
        );

        if (empty($config) || !$has_valid_config) {
            continue;
        }

        $item = array(
            'img'      => isset($config['img']) ? $config['img'] : '',
            'url'      => isset($config['url']) ? $config['url'] : '',
            'target'   => isset($config['target']) ? $config['target'] : '_blank',
            // keep raw for client-side rendering; fallback can be HTML/JS
            'fallback' => html_entity_decode(isset($config['fallback']) ? $config['fallback'] : ''),
            'device'   => $is_mobile_pos ? 'mobile' : ($pos === 'popunder' ? 'both' : 'desktop'),
        );

        if ($is_popup_center) {
            $item['display']     = isset($config['display']) ? $config['display'] : 'immediate';
            $item['delay']       = isset($config['delay']) ? intval($config['delay']) : 5;
            $item['delay_hours'] = isset($config['delay_hours']) ? intval($config['delay_hours']) : 24;
        }

        if ($pos === 'popunder') {
            $item['url']             = isset($config['url']) ? $config['url'] : '';
            $item['delay_hours']     = isset($config['delay_hours']) ? intval($config['delay_hours']) : 24;
            $item['click_threshold'] = isset($config['click_threshold']) ? intval($config['click_threshold']) : 1;
        }

        $ad_data[$pos] = $item;
    }

    $has_real_ads = false;
    foreach ($ad_data as $item) {
        if (!empty($item['img']) || !empty($item['fallback']) || !empty($item['url'])) {
            $has_real_ads = true;
            break;
        }
    }

    if ($has_real_ads) {
        wp_register_script(
            'init-ad-engine-js',
            INIT_PLUGIN_SUITE_AD_ENGINE_ASSETS_URL . 'js/script.js',
            array(),
            INIT_PLUGIN_SUITE_AD_ENGINE_VERSION,
            true
        );
        wp_enqueue_script('init-ad-engine-js');

        // Inline config (safe JSON)
        wp_add_inline_script(
            'init-ad-engine-js',
            'window.InitPluginSuiteAdEngine = ' . wp_json_encode($ad_data) . ';'
        );
    }

    // =====================
    // 2) affiliate-gate.js
    // =====================
    $aff = isset($settings['aff_gate']) && is_array($settings['aff_gate']) ? $settings['aff_gate'] : array();

    if (!empty($aff['link'])) {
        $should_enqueue = apply_filters('init_plugin_suite_ad_engine_should_enqueue_affiliate_gate', true, $aff);

        if ($should_enqueue) {
            wp_register_script(
                'init-ad-engine-affiliate-gate',
                INIT_PLUGIN_SUITE_AD_ENGINE_ASSETS_URL . 'js/affiliate-gate.js',
                array(),
                INIT_PLUGIN_SUITE_AD_ENGINE_VERSION,
                false // print in <head>
            );
            wp_enqueue_script('init-ad-engine-affiliate-gate');

            $aff_data = array(
                'selector'       => isset($aff['selector']) ? $aff['selector'] : '#entry-content',
                'aff_link'       => $aff['link'],
                'aff_banner'     => isset($aff['banner']) ? $aff['banner'] : '',
                'aff_intro'      => isset($aff['intro']) ? $aff['intro'] : '',
                'aff_outro'      => isset($aff['outro']) ? $aff['outro'] : '',
                'mode'           => isset($aff['mode']) ? $aff['mode'] : 'expire',
                'expire_hours'   => intval(isset($aff['expire_hours']) ? $aff['expire_hours'] : 6),
                'random_percent' => intval(isset($aff['random_percent']) ? $aff['random_percent'] : 50),
                'every_x'        => intval(isset($aff['every_x']) ? $aff['every_x'] : 3),
                'custom_steps'   => isset($aff['custom_steps']) ? $aff['custom_steps'] : '',
                'blur_overlay'   => array(
                    'link'     => isset($aff['blur_overlay']['link']) ? $aff['blur_overlay']['link'] : '',
                    'selector' => isset($aff['blur_overlay']['selector']) ? $aff['blur_overlay']['selector'] : '',
                    'steps'    => isset($aff['blur_overlay']['steps']) ? $aff['blur_overlay']['steps'] : '',
                ),
            );

            wp_add_inline_script(
                'init-ad-engine-affiliate-gate',
                'window.InitAdGateConfig = ' . wp_json_encode($aff_data) . ';',
                'before'
            );
        }
    }
});

/**
 * Inject before/after content ads
 */
add_filter('the_content', 'init_plugin_suite_ad_engine_inject_content_ads');
function init_plugin_suite_ad_engine_inject_content_ads($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) return $content;
    if (apply_filters('init_plugin_suite_ad_engine_disable_all_ads', false)) return $content;

    $settings  = get_option(INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, array());
    $is_mobile = wp_is_mobile();

    $before_key = $is_mobile ? 'beforeContentMobile' : 'beforeContentPC';
    $after_key  = $is_mobile ? 'afterContentMobile'  : 'afterContentPC';

    $before_data = isset($settings[$before_key]) ? $settings[$before_key] : array();
    $after_data  = isset($settings[$after_key])  ? $settings[$after_key]  : array();

    $before_ad = init_plugin_suite_ad_engine_render_ad_block(
        $before_data,
        $is_mobile ? 'before_content_mobile' : 'before_content_pc',
        $is_mobile ? 'before-content-mobile' : 'before-content-pc'
    );

    $after_ad = init_plugin_suite_ad_engine_render_ad_block(
        $after_data,
        $is_mobile ? 'after_content_mobile' : 'after_content_pc',
        $is_mobile ? 'after-content-mobile' : 'after-content-pc'
    );

    return $before_ad . $content . $after_ad;
}

/**
 * Render a single ad block
 *
 * @param array  $data
 * @param string $filter_name
 * @param string $css_class
 * @return string
 */
function init_plugin_suite_ad_engine_render_ad_block($data, $filter_name, $css_class) {
    if (empty($data) || !is_array($data)) return '';

    $html = '';

    if (!empty($data['img']) && !empty($data['url'])) {
        $target_attr = (!empty($data['target']) && $data['target'] === '_blank') ? ' target="_blank" rel="noopener"' : '';
        $html = sprintf(
            '<a href="%s"%s><img src="%s" alt="" style="max-width:100%%;height:auto;" /></a>',
            esc_url($data['url']),
            $target_attr,
            esc_url($data['img'])
        );
    } elseif (!empty($data['fallback'])) {
        // Intentionally raw: fallback may contain third-party ad tags that must remain unescaped.
        $html = (string) $data['fallback'];
    }

    $html = apply_filters("init_plugin_suite_ad_engine_filter_{$filter_name}", $html);

    if (trim($html) === '') return '';

    $wrapper_open  = '<div class="init-ad ' . esc_attr($css_class) . '" style="text-align:center;">';
    $wrapper_close = '</div>';

    // Returning raw $html inside a controlled wrapper (admin-only content).
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return $wrapper_open . $html . $wrapper_close;
}

// Include core files
foreach (array('settings-page.php') as $file) {
    $path = INIT_PLUGIN_SUITE_AD_ENGINE_INCLUDES_PATH . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

// ==========================
// Settings link
// ==========================

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'init_plugin_suite_ad_engine_add_settings_link');
// Add a "Settings" link to the plugin row in the Plugins admin screen
function init_plugin_suite_ad_engine_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=' . INIT_PLUGIN_SUITE_AD_ENGINE_SLUG) . '">' . __('Settings', 'init-ad-engine') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
