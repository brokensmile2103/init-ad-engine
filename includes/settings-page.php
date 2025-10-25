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
            . 'if(!tabs.length){return;}'
            . 'tabs.forEach(function(tab){'
                . 'tab.addEventListener("click",function(e){'
                    . 'e.preventDefault();'
                    . 'document.querySelectorAll(".nav-tab").forEach(function(t){t.classList.remove("nav-tab-active");});'
                    . 'document.querySelectorAll(".tab-content").forEach(function(c){c.style.display="none";});'
                    . 'tab.classList.add("nav-tab-active");'
                    . 'var target=document.querySelector(tab.getAttribute("href"));'
                    . 'if(target){target.style.display="block";}'
                . '});'
            . '});'
        . '});'
        . 'jQuery(document).on("click",".upload-image-button",function(e){'
            . 'e.preventDefault();'
            . 'var button=jQuery(this);'
            . 'var field=button.prev(\'input[type="text"]\');'
            . 'var frame=wp.media({title:"Select or Upload Image",button:{text:"Use this image"},multiple:false});'
            . 'frame.on("select",function(){'
                . 'var attachment=frame.state().get("selection").first().toJSON();'
                . 'if(field.length){field.val(attachment.url);}'
            . '});'
            . 'frame.open();'
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
            $sanitized[$key] = init_plugin_suite_ad_engine_sanitize_position_data($value);
        }
    }

    if (isset($input['aff_gate']) && is_array($input['aff_gate'])) {
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
            'blur_overlay'   => [
                'link'       => esc_url_raw($input['aff_gate']['blur_overlay']['link'] ?? ''),
                'selector'   => sanitize_text_field($input['aff_gate']['blur_overlay']['selector'] ?? ''),
                'steps'      => sanitize_text_field($input['aff_gate']['blur_overlay']['steps'] ?? ''),
            ],
        ];
    }

    return $sanitized;
}

// Sanitize individual position data
function init_plugin_suite_ad_engine_sanitize_position_data($data) {
    if (!is_array($data)) {
        return [];
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
                $sanitized[$field] = absint($value);
                break;
            default:
                $sanitized[$field] = sanitize_text_field($value);
                break;
        }
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
        <tr>
            <th><label><?php esc_html_e('Fallback ad code', 'init-ad-engine'); ?></label></th>
            <td>
                <textarea name="init_ad_engine[<?php echo esc_attr($position); ?>][fallback]"
                    rows="5" class="large-text code"><?php echo esc_textarea($settings[$position]['fallback'] ?? ''); ?></textarea>
                <p class="description"><?php esc_html_e('Optional HTML/JS ad code shown when no banner is set.', 'init-ad-engine'); ?></p>
            </td>
        </tr>

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

            <?php submit_button(__('Save Settings', 'init-ad-engine')); ?>
        </form>
    </div>
    <?php
}
