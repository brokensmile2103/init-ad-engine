<?php
if (!defined('ABSPATH')) exit;

// Register admin menu
add_action('admin_menu', 'init_plugin_suite_ad_engine_register_menu');
function init_plugin_suite_ad_engine_register_menu() {
    add_options_page(
        __('Init Ad Engine Settings', 'init-ad-engine'),
        __('Init Ad Engine', 'init-ad-engine'),
        'manage_options',
        INIT_PLUGIN_SUITE_AD_ENGINE_SLUG,
        'init_plugin_suite_ad_engine_render_settings_page'
    );
}

add_action('admin_init', function () {
    if (
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is unslashed and sanitized below
        isset($_POST['init_ad_engine']) &&
        current_user_can('manage_options') &&
        check_admin_referer('init_ad_engine_save_settings')
    ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash and sanitize the input data
        $raw_data = wp_unslash($_POST['init_ad_engine']);
        $sanitized_data = init_plugin_suite_ad_engine_sanitize_settings($raw_data);
        update_option(INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, $sanitized_data);
        wp_safe_redirect(add_query_arg('settings-updated', 'true', admin_url('options-general.php?page=' . INIT_PLUGIN_SUITE_AD_ENGINE_SLUG)));
        exit;
    }
});

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'settings_page_init-ad-engine') {
        return;
    }

    wp_enqueue_media();

    // Đảm bảo có file admin.js (có thể rỗng) để làm handle cho inline script
    wp_register_script(
        'init-ad-engine-admin',
        INIT_PLUGIN_SUITE_AD_ENGINE_ASSETS_URL . 'js/admin.js',
        array('jquery'),
        INIT_PLUGIN_SUITE_AD_ENGINE_VERSION,
        true
    );
    wp_enqueue_script('init-ad-engine-admin');

    // KHÔNG dùng heredoc/nowdoc: dùng chuỗi thường + double-quotes trong JS
    $inline_js = '(function(){'
        . 'document.addEventListener("DOMContentLoaded",function(){'
            . 'var tabs=document.querySelectorAll(".nav-tab");'
            . 'var contents=document.querySelectorAll(".tab-content");'
            . 'if(!tabs.length){return;}'
            . 'var storageKey="initAdEngineLastTab";'
            . 'function activateTab(tab){'
                . 'if(!tab){return;}'
                . 'tabs.forEach(function(t){t.classList.remove("nav-tab-active");});'
                . 'contents.forEach(function(c){c.style.display="none";});'
                . 'tab.classList.add("nav-tab-active");'
                . 'var targetSelector=tab.getAttribute("href");'
                . 'if(targetSelector){'
                    . 'var target=document.querySelector(targetSelector);'
                    . 'if(target){target.style.display="block";}'
                . '}'
                . 'try{window.localStorage.setItem(storageKey,targetSelector);}catch(e){}'
            . '}'
            . 'tabs.forEach(function(tab){'
                . 'tab.addEventListener("click",function(e){'
                    . 'e.preventDefault();'
                    . 'activateTab(tab);'
                . '});'
            . '});'
            . 'var initialHref=null;'
            . 'try{initialHref=window.localStorage.getItem(storageKey);}catch(e){}'
            . 'var initialTab=null;'
            . 'if(initialHref){'
                . 'tabs.forEach(function(t){'
                    . 'if(!initialTab && t.getAttribute("href")===initialHref){initialTab=t;}'
                . '});'
            . '}'
            . 'if(initialTab){'
                . 'activateTab(initialTab);'
            . '}else{'
                . 'activateTab(tabs[0]);'
            . '}'
        . '});'
    . '})();';

    wp_add_inline_script('init-ad-engine-admin', $inline_js, 'after');
});

// Sanitize settings data
function init_plugin_suite_ad_engine_sanitize_settings($input) {
    $sanitized = [];

    if (!is_array($input)) {
        return $sanitized;
    }

    // Define allowed position keys
    $allowed_positions = [
        'billboard', 'balloonLeft', 'balloonRight', 'floatLeft', 'floatRight',
        'catfishTop', 'catfishBottom', 'popupCenterPC', 'popupCenterMobile',
        'stickyTopMobile', 'stickyBottomMobile', 'miniBillboard',
        'beforeContentPC', 'beforeContentMobile', 'afterContentPC', 'afterContentMobile',
        'popunder'
    ];

    // Sanitize each position
    foreach ($input as $key => $value) {
        if ($key === 'global_head' || $key === 'global_footer') {
            // Intentionally allow raw HTML/JS for global head/footer codes for ad tags.
            // These are admin-only fields and will be output in controlled slots.
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $sanitized[$key] = is_string($value) ? $value : '';
        } elseif (in_array($key, $allowed_positions, true)) {
            $sanitized[$key] = init_plugin_suite_ad_engine_sanitize_position_data($value, $key);
        }
    }

    if (isset($input['aff_gate']) && is_array($input['aff_gate'])) {
        $aff_schedule_start = trim((string) ($input['aff_gate']['schedule_start'] ?? ''));
        $aff_schedule_end   = trim((string) ($input['aff_gate']['schedule_end'] ?? ''));

        $sanitized['aff_gate'] = [
            'selector'       => sanitize_text_field($input['aff_gate']['selector'] ?? ''),
            'link'           => esc_url_raw($input['aff_gate']['link'] ?? ''),
            'banner'         => esc_url_raw($input['aff_gate']['banner'] ?? ''),
            'intro'          => wp_kses_post($input['aff_gate']['intro'] ?? ''),
            'outro'          => wp_kses_post($input['aff_gate']['outro'] ?? ''),
            'mode'           => in_array($input['aff_gate']['mode'] ?? '', ['always', 'expire', 'random', 'every_x', 'custom_steps'], true) ? $input['aff_gate']['mode'] : 'expire',
            'random_percent' => absint($input['aff_gate']['random_percent'] ?? 50),
            'every_x'        => absint($input['aff_gate']['every_x'] ?? 3),
            'expire_hours'   => absint($input['aff_gate']['expire_hours'] ?? 6),
            'custom_steps'   => sanitize_text_field($input['aff_gate']['custom_steps'] ?? ''),
            // Strict Y-m-d only; a malformed value is dropped instead of
            // risking a gate that silently disables/enables at the wrong time.
            'schedule_start' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $aff_schedule_start) ? $aff_schedule_start : '',
            'schedule_end'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $aff_schedule_end) ? $aff_schedule_end : '',
            'blur_overlay'   => [
                'link'       => esc_url_raw($input['aff_gate']['blur_overlay']['link'] ?? ''),
                'selector'   => sanitize_text_field($input['aff_gate']['blur_overlay']['selector'] ?? ''),
                'steps'      => sanitize_text_field($input['aff_gate']['blur_overlay']['steps'] ?? ''),
            ],
        ];
    }

    if (isset($input['floating_cta']) && is_array($input['floating_cta'])) {
        $fcta_in = $input['floating_cta'];

        $fcta_schedule_start = trim((string) ($fcta_in['schedule_start'] ?? ''));
        $fcta_schedule_end   = trim((string) ($fcta_in['schedule_end'] ?? ''));

        // One URL per line; blank/invalid lines are dropped so a stray
        // typo can't leave a broken link in the rotation.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed and sanitized line-by-line below
        $fcta_links_raw   = is_string($fcta_in['links'] ?? '') ? $fcta_in['links'] : '';
        $fcta_links_lines = preg_split('/\r\n|\r|\n/', $fcta_links_raw);
        $fcta_links_clean = [];
        foreach ($fcta_links_lines as $fcta_line) {
            $fcta_line = trim($fcta_line);
            if ($fcta_line === '') {
                continue;
            }
            $fcta_escaped = esc_url_raw($fcta_line);
            if ($fcta_escaped !== '') {
                $fcta_links_clean[] = $fcta_escaped;
            }
        }

        $fcta_allowed_corners = ['bottom-right', 'bottom-left', 'top-right', 'top-left'];
        $fcta_allowed_effects = ['none', 'heartbeat', 'pulse', 'shake', 'bounce', 'tada', 'swing'];
        $fcta_allowed_icons   = ['none', 'tag', 'gift', 'fire', 'bell', 'cart', 'percent', 'star', 'lock', 'arrow'];
        $fcta_allowed_devices = ['both', 'desktop', 'mobile'];

        $sanitized['floating_cta'] = [
            'enabled'             => (($fcta_in['enabled'] ?? '') === '1') ? '1' : '',
            'corner'              => in_array($fcta_in['corner'] ?? '', $fcta_allowed_corners, true) ? $fcta_in['corner'] : 'bottom-right',
            'offset_x'            => absint($fcta_in['offset_x'] ?? 20),
            'offset_y'            => absint($fcta_in['offset_y'] ?? 20),
            'effect'              => in_array($fcta_in['effect'] ?? '', $fcta_allowed_effects, true) ? $fcta_in['effect'] : 'heartbeat',
            'icon'                => in_array($fcta_in['icon'] ?? '', $fcta_allowed_icons, true) ? $fcta_in['icon'] : 'tag',
            'badge_text'          => sanitize_text_field($fcta_in['badge_text'] ?? ''),
            'button_text'         => sanitize_text_field($fcta_in['button_text'] ?? ''),
            'links'               => implode("\n", $fcta_links_clean),
            'open_new_tab'        => (($fcta_in['open_new_tab'] ?? '') === '_blank') ? '_blank' : '',
            'force_open_on_close' => (($fcta_in['force_open_on_close'] ?? '') === '1') ? '1' : '',
            'bg_color'            => sanitize_hex_color($fcta_in['bg_color'] ?? '') ?: '#ee4d2d',
            'text_color'          => sanitize_hex_color($fcta_in['text_color'] ?? '') ?: '#ffffff',
            'badge_bg_color'      => sanitize_hex_color($fcta_in['badge_bg_color'] ?? '') ?: '#b71c1c',
            'badge_text_color'    => sanitize_hex_color($fcta_in['badge_text_color'] ?? '') ?: '#ffffff',
            'device'              => in_array($fcta_in['device'] ?? '', $fcta_allowed_devices, true) ? $fcta_in['device'] : 'both',
            'schedule_start'      => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fcta_schedule_start) ? $fcta_schedule_start : '',
            'schedule_end'        => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fcta_schedule_end) ? $fcta_schedule_end : '',
        ];
    }

    return $sanitized;
}

// Sanitize individual position data
function init_plugin_suite_ad_engine_sanitize_position_data($data, $position = '') {
    if (!is_array($data)) {
        $data = [];
    }

    $sanitized = [];

    foreach ($data as $field => $value) {
        switch ($field) {
            case 'img':
                $sanitized[$field] = esc_url_raw($value);
                break;
            case 'url':
                $sanitized[$field] = esc_url_raw($value);
                break;
            case 'target':
                $sanitized[$field] = ($value === '_blank') ? '_blank' : '';
                break;
            case 'fallback':
                // Intentionally allow raw HTML/JS for fallback ad code.
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $sanitized[$field] = is_string($value) ? $value : '';
                break;
            case 'display':
                $allowed_display = ['immediate', 'delay', 'exit'];
                $sanitized[$field] = in_array($value, $allowed_display, true) ? $value : 'immediate';
                break;
            case 'delay':
            case 'delay_hours':
            case 'click_threshold':
            case 'cap_hours':
                $sanitized[$field] = absint($value);
                break;
            case 'schedule_start':
            case 'schedule_end':
                $value = is_string($value) ? trim($value) : '';
                // Only accept a strict Y-m-d date; anything else is dropped
                // so a malformed value can never silently disable a position.
                $sanitized[$field] = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
                break;
            case 'open_on_close':
                $sanitized[$field] = ($value === '1') ? '1' : '';
                break;
            default:
                $sanitized[$field] = sanitize_text_field($value);
                break;
        }
    }

    // 'target' is rendered as a checkbox ("Open in new tab?"), so unchecking
    // it means the browser sends NO 'target' key at all in $_POST. Without
    // this, an explicit "uncheck" gets silently lost and the frontend falls
    // back to its '_blank' default as if the position was never configured.
    // Popunder has no such checkbox, so it's excluded.
    if ($position !== 'popunder' && !isset($sanitized['target'])) {
        $sanitized['target'] = '';
    }

    // Same story for the "Open link on close (x)" checkbox: only positions
    // that actually render a close button expose this field.
    $closable_positions = array(
        'balloonLeft', 'balloonRight', 'floatLeft', 'floatRight',
        'catfishTop', 'catfishBottom',
        'popupCenterPC', 'popupCenterMobile',
        'stickyTopMobile', 'stickyBottomMobile',
    );

    if (in_array($position, $closable_positions, true) && !isset($sanitized['open_on_close'])) {
        $sanitized['open_on_close'] = '';
    }

    return $sanitized;
}

function render_ad_position_fields($position, $config, $settings, $sizeHints) {
    ?>
    <tr>
        <th colspan="2">
            <h2><?php echo esc_html($config['label']); ?></h2>
            <p class="description">
                <?php
                if ($config['device'] === 'mobile') {
                    esc_html_e('This ad will only appear on mobile devices.', 'init-ad-engine');
                    echo '<br>';
                } elseif ($config['device'] === 'desktop') {
                    esc_html_e('This ad will only appear on desktop.', 'init-ad-engine');
                    echo '<br>';
                }

                if (!empty($sizeHints[$position])) {
                    echo '<strong>' . esc_html__('Recommended size:', 'init-ad-engine') . '</strong> ';
                    echo esc_html($sizeHints[$position]) . '.';
                }
                ?>
            </p>
        </th>
    </tr>

    <tr>
        <th><label><?php esc_html_e('Schedule (optional)', 'init-ad-engine'); ?></label></th>
        <td>
            <input type="date" name="init_ad_engine[<?php echo esc_attr($position); ?>][schedule_start]"
                   value="<?php echo esc_attr($settings[$position]['schedule_start'] ?? ''); ?>" />
            <?php esc_html_e('to', 'init-ad-engine'); ?>
            <input type="date" name="init_ad_engine[<?php echo esc_attr($position); ?>][schedule_end]"
                   value="<?php echo esc_attr($settings[$position]['schedule_end'] ?? ''); ?>" />
            <p class="description"><?php esc_html_e('Leave both empty to run with no date limit. If only one side is set, the other side is unlimited.', 'init-ad-engine'); ?></p>
        </td>
    </tr>

    <?php if ($position === 'popunder'): ?>
        <tr>
            <th><label for="<?php echo esc_attr($position); ?>_url"><?php esc_html_e('Target URL', 'init-ad-engine'); ?></label></th>
            <td>
                <input type="url" name="init_ad_engine[<?php echo esc_attr($position); ?>][url]"
                       value="<?php echo esc_attr($settings[$position]['url'] ?? ''); ?>"
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr($position); ?>_delay"><?php esc_html_e('Time between triggers (hours)', 'init-ad-engine'); ?></label></th>
            <td>
                <input type="number" min="1" step="1"
                       name="init_ad_engine[<?php echo esc_attr($position); ?>][delay_hours]"
                       value="<?php echo esc_attr($settings[$position]['delay_hours'] ?? 24); ?>"
                       class="small-text" />
                <p class="description"><?php esc_html_e('Minimum hours before popunder can trigger again.', 'init-ad-engine'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="<?php echo esc_attr($position); ?>_clicks"><?php esc_html_e('Trigger on click number', 'init-ad-engine'); ?></label></th>
            <td>
                <input type="number" min="1" step="1"
                       name="init_ad_engine[<?php echo esc_attr($position); ?>][click_threshold]"
                       value="<?php echo esc_attr($settings[$position]['click_threshold'] ?? 1); ?>"
                       class="small-text" />
                <p class="description"><?php esc_html_e('Popunder will activate on the N-th click.', 'init-ad-engine'); ?></p>
            </td>
        </tr>
    <?php else: ?>
        <tr>
            <th><label><?php esc_html_e('Banner image URL', 'init-ad-engine'); ?></label></th>
            <td>
                <input type="text" name="init_ad_engine[<?php echo esc_attr($position); ?>][img]"
                       value="<?php echo esc_attr($settings[$position]['img'] ?? ''); ?>"
                       class="regular-text" />
                <button type="button" class="button upload-image-button"><?php esc_html_e('Choose Image', 'init-ad-engine'); ?></button>
            </td>
        </tr>
        <tr>
            <th><label><?php esc_html_e('Target URL', 'init-ad-engine'); ?></label></th>
            <td>
                <input type="url" name="init_ad_engine[<?php echo esc_attr($position); ?>][url]"
                       value="<?php echo esc_attr($settings[$position]['url'] ?? ''); ?>"
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label><?php esc_html_e('Open in new tab?', 'init-ad-engine'); ?></label></th>
            <td>
                <?php $target_val = $settings[$position]['target'] ?? '_blank'; ?>
                <label>
                    <input type="checkbox" name="init_ad_engine[<?php echo esc_attr($position); ?>][target]"
                           value="_blank" <?php checked($target_val, '_blank'); ?> />
                    <?php esc_html_e('Yes, open in new tab', 'init-ad-engine'); ?>
                </label>
            </td>
        </tr>

        <?php
        $closable_positions = array(
            'balloonLeft', 'balloonRight', 'floatLeft', 'floatRight',
            'catfishTop', 'catfishBottom',
            'popupCenterPC', 'popupCenterMobile',
            'stickyTopMobile', 'stickyBottomMobile',
        );
        ?>
        <?php if (in_array($position, $closable_positions, true)): ?>
            <tr>
                <th><label><?php esc_html_e('Close (x) button action', 'init-ad-engine'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="init_ad_engine[<?php echo esc_attr($position); ?>][open_on_close]"
                               value="1" <?php checked($settings[$position]['open_on_close'] ?? '', '1'); ?> />
                        <?php esc_html_e('Also open the Target URL (in a new tab) when the visitor dismisses this ad with the x button', 'init-ad-engine'); ?>
                    </label>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <th><label><?php esc_html_e('Fallback ad code', 'init-ad-engine'); ?></label></th>
            <td>
                <textarea name="init_ad_engine[<?php echo esc_attr($position); ?>][fallback]"
                    rows="5" class="large-text code"><?php echo esc_textarea($settings[$position]['fallback'] ?? ''); ?></textarea>
                <p class="description"><?php esc_html_e('Optional HTML/JS ad code shown when no banner is set.', 'init-ad-engine'); ?></p>
            </td>
        </tr>

        <?php
        $cap_hours_positions = array(
            'billboard', 'balloonLeft', 'balloonRight', 'floatLeft', 'floatRight',
            'catfishTop', 'catfishBottom', 'miniBillboard',
            'stickyTopMobile', 'stickyBottomMobile',
        );
        ?>
        <?php if (in_array($position, $cap_hours_positions, true)): ?>
            <tr>
                <th><label><?php esc_html_e('Frequency cap (hours)', 'init-ad-engine'); ?></label></th>
                <td>
                    <input type="number" min="0" step="1"
                           name="init_ad_engine[<?php echo esc_attr($position); ?>][cap_hours]"
                           value="<?php echo esc_attr($settings[$position]['cap_hours'] ?? 0); ?>"
                           class="small-text" />
                    <p class="description"><?php esc_html_e('Minimum hours before this ad shows again to the same browser. Leave 0 to show on every page view (default behavior).', 'init-ad-engine'); ?></p>
                </td>
            </tr>
        <?php endif; ?>

        <?php if (in_array($position, ['popupCenterPC', 'popupCenterMobile'], true)): ?>
            <tr>
                <th><label><?php esc_html_e('Display Behavior', 'init-ad-engine'); ?></label></th>
                <td>
                    <?php $behavior = $settings[$position]['display'] ?? 'immediate'; ?>
                    <select name="init_ad_engine[<?php echo esc_attr($position); ?>][display]">
                        <option value="immediate" <?php selected($behavior, 'immediate'); ?>>
                            <?php esc_html_e('Show immediately on page load', 'init-ad-engine'); ?>
                        </option>
                        <option value="delay" <?php selected($behavior, 'delay'); ?>>
                            <?php esc_html_e('Show after delay (seconds)', 'init-ad-engine'); ?>
                        </option>
                        <option value="exit" <?php selected($behavior, 'exit'); ?>>
                            <?php esc_html_e('Show on exit intent', 'init-ad-engine'); ?>
                        </option>
                    </select>
                    <input type="number" min="1" step="1"
                           name="init_ad_engine[<?php echo esc_attr($position); ?>][delay]"
                           value="<?php echo esc_attr($settings[$position]['delay'] ?? 5); ?>"
                           class="small-text"
                           placeholder="<?php esc_attr_e('Delay in seconds', 'init-ad-engine'); ?>" />
                    <p class="description"><?php esc_html_e('Select how and when the popup should appear.', 'init-ad-engine'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Time between triggers (hours)', 'init-ad-engine'); ?></label></th>
                <td>
                    <input type="number" min="1" step="1"
                           name="init_ad_engine[<?php echo esc_attr($position); ?>][delay_hours]"
                           value="<?php echo esc_attr($settings[$position]['delay_hours'] ?? 24); ?>"
                           class="small-text" />
                    <p class="description"><?php esc_html_e('Minimum hours before this popup can reappear (per device/browser).', 'init-ad-engine'); ?></p>
                </td>
            </tr>
        <?php endif; ?>
    <?php endif;
}

function init_plugin_suite_ad_engine_render_settings_page() {
    $settings = get_option(INIT_PLUGIN_SUITE_AD_ENGINE_OPTION, []);

    $positions = [
        'billboard'            => ['label' => __('Billboard (below menu, desktop only)', 'init-ad-engine'), 'device' => 'desktop'],
        'balloonLeft'          => ['label' => __('Balloon Left (bottom-left corner)', 'init-ad-engine'), 'device' => 'both'],
        'balloonRight'         => ['label' => __('Balloon Right (bottom-right corner)', 'init-ad-engine'), 'device' => 'both'],
        'floatLeft'            => ['label' => __('Float Left (left sidebar)', 'init-ad-engine'), 'device' => 'both'],
        'floatRight'           => ['label' => __('Float Right (right sidebar)', 'init-ad-engine'), 'device' => 'both'],
        'catfishTop'           => ['label' => __('Catfish Top (sticky top, desktop only)', 'init-ad-engine'), 'device' => 'desktop'],
        'catfishBottom'        => ['label' => __('Catfish Bottom (sticky bottom, desktop only)', 'init-ad-engine'), 'device' => 'desktop'],
        'popupCenterPC'        => ['label' => __('Popup Center PC (desktop only)', 'init-ad-engine'), 'device' => 'desktop'],
        'popupCenterMobile'    => ['label' => __('Popup Center Mobile (mobile only)', 'init-ad-engine'), 'device' => 'mobile'],
        'stickyTopMobile'      => ['label' => __('Sticky Top (mobile only)', 'init-ad-engine'), 'device' => 'mobile'],
        'stickyBottomMobile'   => ['label' => __('Sticky Bottom (mobile only)', 'init-ad-engine'), 'device' => 'mobile'],
        'miniBillboard'        => ['label' => __('Mini Billboard (mobile only)', 'init-ad-engine'), 'device' => 'mobile'],
        'beforeContentPC'      => ['label' => __('Before Content (desktop)', 'init-ad-engine'), 'device' => 'desktop'],
        'beforeContentMobile'  => ['label' => __('Before Content (mobile)', 'init-ad-engine'), 'device' => 'mobile'],
        'afterContentPC'       => ['label' => __('After Content (desktop)', 'init-ad-engine'), 'device' => 'desktop'],
        'afterContentMobile'   => ['label' => __('After Content (mobile)', 'init-ad-engine'), 'device' => 'mobile'],
        'popunder'             => ['label' => __('Popunder (new tab on first click)', 'init-ad-engine'), 'device' => 'special'],
    ];

    $sizeHints = [
        'billboard'             => '970×250px',
        'balloonLeft'           => '300×250px',
        'balloonRight'          => '300×250px',
        'floatLeft'             => '120×600px',
        'floatRight'            => '120×600px',
        'catfishTop'            => '728×90px',
        'catfishBottom'         => '728×90px',
        'popupCenterPC'         => '700×500px',
        'popupCenterMobile'     => '300×250px',
        'stickyTopMobile'       => '320×50px',
        'stickyBottomMobile'    => '320×50px',
        'miniBillboard'         => '320×50px',
        'beforeContentPC'       => '728×90px',
        'beforeContentMobile'   => '300×250px',
        'afterContentPC'        => '728×90px',
        'afterContentMobile'    => '300×250px',
    ];
    ?>
    <div class="wrap">
        <?php
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is just displaying a message, not processing form data
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php esc_html_e('Settings saved successfully.', 'init-ad-engine'); ?></p>
            </div>
        <?php endif; ?>

        <h1><?php esc_html_e('Init Ad Engine Settings', 'init-ad-engine'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('init_ad_engine_save_settings'); ?>

            <h2 class="nav-tab-wrapper">
                <a href="#tab-pc" class="nav-tab nav-tab-active"><?php esc_html_e('PC', 'init-ad-engine'); ?></a>
                <a href="#tab-mobile" class="nav-tab"><?php esc_html_e('Mobile', 'init-ad-engine'); ?></a>
                <a href="#tab-popunder" class="nav-tab"><?php esc_html_e('Popunder & Global', 'init-ad-engine'); ?></a>
                <a href="#tab-affiliate" class="nav-tab"><?php esc_html_e('Affiliate Gate', 'init-ad-engine'); ?></a>
                <a href="#tab-floating-cta" class="nav-tab"><?php esc_html_e('Floating Button', 'init-ad-engine'); ?></a>
            </h2>

            <div id="tab-pc" class="tab-content" style="display:block">
                <table class="form-table" role="presentation"><tbody>
                <?php
                foreach ($positions as $position => $config) {
                    if (in_array($position, ['billboard','balloonLeft','balloonRight','floatLeft','floatRight','catfishTop','catfishBottom','popupCenterPC','beforeContentPC','afterContentPC'], true)) {
                        render_ad_position_fields($position, $config, $settings, $sizeHints);
                    }
                }
                ?>
                </tbody></table>
            </div>

            <div id="tab-mobile" class="tab-content" style="display:none">
                <table class="form-table" role="presentation"><tbody>
                <?php
                foreach ($positions as $position => $config) {
                    if (in_array($position, ['miniBillboard','stickyTopMobile','stickyBottomMobile','popupCenterMobile','beforeContentMobile','afterContentMobile'], true)) {
                        render_ad_position_fields($position, $config, $settings, $sizeHints);
                    }
                }
                ?>
                </tbody></table>
            </div>

            <div id="tab-popunder" class="tab-content" style="display:none">
                <table class="form-table" role="presentation"><tbody>
                <?php
                foreach ($positions as $position => $config) {
                    if ($position === 'popunder') {
                        render_ad_position_fields($position, $config, $settings, $sizeHints);
                    }
                }
                ?>
                <tr>
                    <th><label for="global_head_code"><?php esc_html_e('Header Code', 'init-ad-engine'); ?></label></th>
                    <td>
                        <textarea name="init_ad_engine[global_head]" id="global_head_code" rows="6" class="large-text code" style="min-height:127px"><?php echo esc_textarea($settings['global_head'] ?? ''); ?></textarea>
                        <p class="description"><?php esc_html_e('Code to insert inside <head>.', 'init-ad-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="global_footer_code"><?php esc_html_e('Footer Code', 'init-ad-engine'); ?></label></th>
                    <td>
                        <textarea name="init_ad_engine[global_footer]" id="global_footer_code" rows="6" class="large-text code" style="min-height:127px"><?php echo esc_textarea($settings['global_footer'] ?? ''); ?></textarea>
                        <p class="description"><?php esc_html_e('Code to insert before </body>.', 'init-ad-engine'); ?></p>
                    </td>
                </tr>
                </tbody></table>
            </div>

            <div id="tab-affiliate" class="tab-content" style="display:none">
                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th><label for="aff_gate_selector"><?php esc_html_e('Content Selector', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="text" name="init_ad_engine[aff_gate][selector]"
                                   value="<?php echo esc_attr($settings['aff_gate']['selector'] ?? '.entry-content'); ?>"
                                   placeholder=".entry-content"
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('CSS selector of the content block to gate (e.g. .entry-content).', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_link"><?php esc_html_e('Affiliate Link (Required)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="url" name="init_ad_engine[aff_gate][link]"
                                   value="<?php echo esc_attr($settings['aff_gate']['link'] ?? ''); ?>"
                                   placeholder="https://example.com/"
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Enter multiple links separated by commas (,) to display them randomly.', 'init-ad-engine'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_banner"><?php esc_html_e('Banner Image URL', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="text" name="init_ad_engine[aff_gate][banner]"
                                   value="<?php echo esc_attr($settings['aff_gate']['banner'] ?? ''); ?>"
                                   placeholder="https://example.com/banner.jpg"
                                   class="regular-text" />
                            <button type="button" class="button upload-image-button"><?php esc_html_e('Choose Image', 'init-ad-engine'); ?></button>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_intro"><?php esc_html_e('Intro Text', 'init-ad-engine'); ?></label></th>
                        <td>
                            <textarea name="init_ad_engine[aff_gate][intro]" rows="3" class="large-text"><?php echo esc_textarea($settings['aff_gate']['intro'] ?? ''); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_outro"><?php esc_html_e('Outro Text', 'init-ad-engine'); ?></label></th>
                        <td>
                            <textarea name="init_ad_engine[aff_gate][outro]" rows="3" class="large-text"><?php echo esc_textarea($settings['aff_gate']['outro'] ?? ''); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_mode"><?php esc_html_e('Display Mode', 'init-ad-engine'); ?></label></th>
                        <td>
                            <fieldset>
                                <?php $mode = $settings['aff_gate']['mode'] ?? 'expire'; ?>
                                <label>
                                  <input type="radio" name="init_ad_engine[aff_gate][mode]" value="always" <?php checked($mode, 'always'); ?> />
                                  <?php esc_html_e('Show every time (unless clicked in this post)', 'init-ad-engine'); ?>
                                </label><br>

                                <label>
                                  <input type="radio" name="init_ad_engine[aff_gate][mode]" value="expire" <?php checked($mode, 'expire'); ?> />
                                  <?php esc_html_e('Hide after click for X hours', 'init-ad-engine'); ?>
                                </label><br>

                                <label>
                                  <input type="radio" name="init_ad_engine[aff_gate][mode]" value="random" <?php checked($mode, 'random'); ?> />
                                  <?php esc_html_e('Show randomly (by % chance)', 'init-ad-engine'); ?>
                                </label>
                                <input type="number" min="1" max="100" step="1"
                                       name="init_ad_engine[aff_gate][random_percent]"
                                       value="<?php echo esc_attr($settings['aff_gate']['random_percent'] ?? 50); ?>"
                                       class="small-text" style="width:65px;" /> %<br>

                                <label>
                                  <input type="radio" name="init_ad_engine[aff_gate][mode]" value="every_x" <?php checked($mode, 'every_x'); ?> />
                                  <?php esc_html_e('Show every X views', 'init-ad-engine'); ?>
                                </label>
                                <input type="number" min="1" step="1"
                                       name="init_ad_engine[aff_gate][every_x]"
                                       value="<?php echo esc_attr($settings['aff_gate']['every_x'] ?? 3); ?>"
                                       class="small-text" style="width:65px;" /><br>

                                <label>
                                    <input type="radio" name="init_ad_engine[aff_gate][mode]" value="custom_steps" <?php checked($mode, 'custom_steps'); ?> />
                                    <?php esc_html_e('Show on specific views (e.g. 1,3,9)', 'init-ad-engine'); ?>
                                </label>
                                <input type="text"
                                       name="init_ad_engine[aff_gate][custom_steps]"
                                       value="<?php echo esc_attr($settings['aff_gate']['custom_steps'] ?? ''); ?>"
                                       class="regular-text" style="margin-top: 4px; max-width: 200px;" />
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_expire"><?php esc_html_e('Expire Duration (Hours)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="number" min="1" step="1"
                                   name="init_ad_engine[aff_gate][expire_hours]"
                                   value="<?php echo esc_attr($settings['aff_gate']['expire_hours'] ?? 6); ?>"
                                   class="small-text" />
                            <p class="description"><?php esc_html_e('Only applies if "Show every time" or "Hide after click" or "Show on custom pages" is selected.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label><?php esc_html_e('Schedule (optional)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="date" name="init_ad_engine[aff_gate][schedule_start]"
                                   value="<?php echo esc_attr($settings['aff_gate']['schedule_start'] ?? ''); ?>" />
                            <?php esc_html_e('to', 'init-ad-engine'); ?>
                            <input type="date" name="init_ad_engine[aff_gate][schedule_end]"
                                   value="<?php echo esc_attr($settings['aff_gate']['schedule_end'] ?? ''); ?>" />
                            <p class="description"><?php esc_html_e('Leave both empty to run with no date limit. If only one side is set, the other side is unlimited.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="aff_gate_blur_link"><?php esc_html_e('Blur Overlay Link & Selector', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="url" placeholder="Affiliate link"
                                   name="init_ad_engine[aff_gate][blur_overlay][link]"
                                   value="<?php echo esc_attr($settings['aff_gate']['blur_overlay']['link'] ?? ''); ?>"
                                   class="regular-text" /><br><br>
                            <input type="text" placeholder="CSS selector to apply blur"
                                   name="init_ad_engine[aff_gate][blur_overlay][selector]"
                                   value="<?php echo esc_attr($settings['aff_gate']['blur_overlay']['selector'] ?? ''); ?>"
                                   class="regular-text" /><br><br>
                            <input type="text"
                                   name="init_ad_engine[aff_gate][blur_overlay][steps]"
                                   value="<?php echo esc_attr($settings['aff_gate']['blur_overlay']['steps'] ?? ''); ?>"
                                   class="regular-text" placeholder="e.g. 2,5,9" />
                            <p class="description"><?php esc_html_e('If all fields are set, a semi-transparent clickable overlay will appear on the selected element, only on specific views (e.g. 2,5,9).', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>
                </tbody></table>
            </div>

            <div id="tab-floating-cta" class="tab-content" style="display:none">
                <table class="form-table" role="presentation"><tbody>
                    <?php
                    $fcta = isset($settings['floating_cta']) && is_array($settings['floating_cta']) ? $settings['floating_cta'] : [];

                    $fcta_icons = [
                        'none'    => __('None', 'init-ad-engine'),
                        'tag'     => __('Tag / Voucher', 'init-ad-engine'),
                        'gift'    => __('Gift', 'init-ad-engine'),
                        'fire'    => __('Fire', 'init-ad-engine'),
                        'bell'    => __('Bell', 'init-ad-engine'),
                        'cart'    => __('Cart', 'init-ad-engine'),
                        'percent' => __('Percent / Discount', 'init-ad-engine'),
                        'star'    => __('Star', 'init-ad-engine'),
                        'lock'    => __('Lock', 'init-ad-engine'),
                        'arrow'   => __('Arrow Right', 'init-ad-engine'),
                    ];

                    $fcta_effects = [
                        'none'      => __('None', 'init-ad-engine'),
                        'heartbeat' => __('Heartbeat', 'init-ad-engine'),
                        'pulse'     => __('Pulse', 'init-ad-engine'),
                        'shake'     => __('Shake', 'init-ad-engine'),
                        'bounce'    => __('Bounce', 'init-ad-engine'),
                        'tada'      => __('Tada', 'init-ad-engine'),
                        'swing'     => __('Swing', 'init-ad-engine'),
                    ];

                    $fcta_corners = [
                        'bottom-right' => __('Bottom Right', 'init-ad-engine'),
                        'bottom-left'  => __('Bottom Left', 'init-ad-engine'),
                        'top-right'    => __('Top Right', 'init-ad-engine'),
                        'top-left'     => __('Top Left', 'init-ad-engine'),
                    ];
                    ?>
                    <tr>
                        <th colspan="2">
                            <h2><?php esc_html_e('Floating Button', 'init-ad-engine'); ?></h2>
                            <p class="description"><?php esc_html_e('A floating, attention-grabbing call-to-action button pinned to a corner of the screen.', 'init-ad-engine'); ?></p>
                        </th>
                    </tr>

                    <tr>
                        <th><label for="fcta_enabled"><?php esc_html_e('Enable', 'init-ad-engine'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="fcta_enabled" name="init_ad_engine[floating_cta][enabled]"
                                       value="1" <?php checked($fcta['enabled'] ?? '', '1'); ?> />
                                <?php esc_html_e('Show the floating button on the site', 'init-ad-engine'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_corner"><?php esc_html_e('Corner Position', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php $fcta_corner_val = $fcta['corner'] ?? 'bottom-right'; ?>
                            <select id="fcta_corner" name="init_ad_engine[floating_cta][corner]">
                                <?php foreach ($fcta_corners as $fcta_corner_key => $fcta_corner_label): ?>
                                    <option value="<?php echo esc_attr($fcta_corner_key); ?>" <?php selected($fcta_corner_val, $fcta_corner_key); ?>>
                                        <?php echo esc_html($fcta_corner_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_offset_x"><?php esc_html_e('Edge Spacing (px)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php esc_html_e('Horizontal:', 'init-ad-engine'); ?>
                            <input type="number" id="fcta_offset_x" min="0" step="1"
                                   name="init_ad_engine[floating_cta][offset_x]"
                                   value="<?php echo esc_attr($fcta['offset_x'] ?? 20); ?>"
                                   class="small-text" />
                            <?php esc_html_e('Vertical:', 'init-ad-engine'); ?>
                            <input type="number" min="0" step="1"
                                   name="init_ad_engine[floating_cta][offset_y]"
                                   value="<?php echo esc_attr($fcta['offset_y'] ?? 20); ?>"
                                   class="small-text" />
                            <p class="description"><?php esc_html_e('Distance from the selected corner\'s edges.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_effect"><?php esc_html_e('Attention Effect', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php $fcta_effect_val = $fcta['effect'] ?? 'heartbeat'; ?>
                            <select id="fcta_effect" name="init_ad_engine[floating_cta][effect]">
                                <?php foreach ($fcta_effects as $fcta_effect_key => $fcta_effect_label): ?>
                                    <option value="<?php echo esc_attr($fcta_effect_key); ?>" <?php selected($fcta_effect_val, $fcta_effect_key); ?>>
                                        <?php echo esc_html($fcta_effect_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Built-in animation to help the button catch the visitor\'s eye.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_icon"><?php esc_html_e('Icon', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php $fcta_icon_val = $fcta['icon'] ?? 'tag'; ?>
                            <select id="fcta_icon" name="init_ad_engine[floating_cta][icon]">
                                <?php foreach ($fcta_icons as $fcta_icon_key => $fcta_icon_label): ?>
                                    <option value="<?php echo esc_attr($fcta_icon_key); ?>" <?php selected($fcta_icon_val, $fcta_icon_key); ?>>
                                        <?php echo esc_html($fcta_icon_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Built-in SVG icon shown next to the button text.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_badge_text"><?php esc_html_e('Badge Text (optional)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="text" id="fcta_badge_text" name="init_ad_engine[floating_cta][badge_text]"
                                   value="<?php echo esc_attr($fcta['badge_text'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('e.g. VOUCHER 50%', 'init-ad-engine'); ?>"
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('Small badge shown above the button. Leave empty to hide it.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_button_text"><?php esc_html_e('Button Text', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="text" id="fcta_button_text" name="init_ad_engine[floating_cta][button_text]"
                                   value="<?php echo esc_attr($fcta['button_text'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('e.g. Grab the Deal', 'init-ad-engine'); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_links"><?php esc_html_e('Links', 'init-ad-engine'); ?></label></th>
                        <td>
                            <textarea id="fcta_links" name="init_ad_engine[floating_cta][links]" rows="5"
                                      class="large-text code"
                                      placeholder="https://example.com/link-1&#10;https://example.com/link-2"><?php echo esc_textarea($fcta['links'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('One link per line. When more than one link is set, a random one is shown on each page view.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_open_new_tab"><?php esc_html_e('Open in new tab?', 'init-ad-engine'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="fcta_open_new_tab" name="init_ad_engine[floating_cta][open_new_tab]"
                                       value="_blank" <?php checked($fcta['open_new_tab'] ?? '_blank', '_blank'); ?> />
                                <?php esc_html_e('Yes, open in new tab', 'init-ad-engine'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_force_open_on_close"><?php esc_html_e('Close (x) button action', 'init-ad-engine'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="fcta_force_open_on_close" name="init_ad_engine[floating_cta][force_open_on_close]"
                                       value="1" <?php checked($fcta['force_open_on_close'] ?? '', '1'); ?> />
                                <?php esc_html_e('Also open the link (in a new tab) when the visitor dismisses this button with the x button', 'init-ad-engine'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_bg_color"><?php esc_html_e('Button Colors', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php esc_html_e('Background:', 'init-ad-engine'); ?>
                            <input type="color" id="fcta_bg_color" name="init_ad_engine[floating_cta][bg_color]"
                                   value="<?php echo esc_attr($fcta['bg_color'] ?? '#ee4d2d'); ?>" />
                            <?php esc_html_e('Text:', 'init-ad-engine'); ?>
                            <input type="color" name="init_ad_engine[floating_cta][text_color]"
                                   value="<?php echo esc_attr($fcta['text_color'] ?? '#ffffff'); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_badge_bg_color"><?php esc_html_e('Badge Colors', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php esc_html_e('Background:', 'init-ad-engine'); ?>
                            <input type="color" id="fcta_badge_bg_color" name="init_ad_engine[floating_cta][badge_bg_color]"
                                   value="<?php echo esc_attr($fcta['badge_bg_color'] ?? '#b71c1c'); ?>" />
                            <?php esc_html_e('Text:', 'init-ad-engine'); ?>
                            <input type="color" name="init_ad_engine[floating_cta][badge_text_color]"
                                   value="<?php echo esc_attr($fcta['badge_text_color'] ?? '#ffffff'); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="fcta_device"><?php esc_html_e('Device', 'init-ad-engine'); ?></label></th>
                        <td>
                            <?php $fcta_device_val = $fcta['device'] ?? 'both'; ?>
                            <select id="fcta_device" name="init_ad_engine[floating_cta][device]">
                                <option value="both" <?php selected($fcta_device_val, 'both'); ?>><?php esc_html_e('Desktop & Mobile', 'init-ad-engine'); ?></option>
                                <option value="desktop" <?php selected($fcta_device_val, 'desktop'); ?>><?php esc_html_e('Desktop only', 'init-ad-engine'); ?></option>
                                <option value="mobile" <?php selected($fcta_device_val, 'mobile'); ?>><?php esc_html_e('Mobile only', 'init-ad-engine'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label><?php esc_html_e('Schedule (optional)', 'init-ad-engine'); ?></label></th>
                        <td>
                            <input type="date" name="init_ad_engine[floating_cta][schedule_start]"
                                   value="<?php echo esc_attr($fcta['schedule_start'] ?? ''); ?>" />
                            <?php esc_html_e('to', 'init-ad-engine'); ?>
                            <input type="date" name="init_ad_engine[floating_cta][schedule_end]"
                                   value="<?php echo esc_attr($fcta['schedule_end'] ?? ''); ?>" />
                            <p class="description"><?php esc_html_e('Leave both empty to run with no date limit. If only one side is set, the other side is unlimited.', 'init-ad-engine'); ?></p>
                        </td>
                    </tr>
                </tbody></table>
            </div>

            <?php submit_button(__('Save Settings', 'init-ad-engine')); ?>
        </form>
    </div>
    <?php
}
