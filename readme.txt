=== Init Ad Engine – Flexible, Multi-Format, Secure ===
Contributors: brokensmile.2103
Tags: ads, banner, popup, popunder, content locking
Requires at least: 5.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight and flexible ad engine for WordPress. Place banners, popups, sticky ads, and popunders across desktop and mobile with full control.

== Description ==

**Init Ad Engine** lets you insert various ad formats into your WordPress site with zero coding required.

Built for flexibility and performance:
- Tabbed admin UI with clear separation by device type
- All styles are inline or embedded – no external CSS files
- Multiple ad formats supported: popups, sticky bars, floating ads, popunder, before/after content
- Optional fallback HTML/JS code when no image is provided

**Available ad positions:**

- **Desktop (PC):**  
Billboard, Balloon Left/Right, Float Left/Right, Catfish Top/Bottom, Popup Center, Before/After Content

- **Mobile:**  
Mini Billboard, Sticky Top/Bottom, Popup Center, Before/After Content

- **Special:**  
Popunder (opens new tab on first click), Global header/footer code injection

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-ad-engine](https://github.com/brokensmile2103/init-ad-engine)

== Features ==

* 20+ configurable ad positions
* Popunder with cooldown and click threshold
* Exit-intent or delay-based popup behavior
* Global `<head>` and `</body>` injection fields
* Fallback HTML/JS ad code per position
* Minimalist inline CSS for fast load
* Responsive support for all device types
* Clean admin UI with media uploader
* Affiliate Gate with flexible display logic (always-on, expire-after-click, random %, every X pages)

== Filters for Developers ==

- `init_plugin_suite_ad_engine_use_kses`  
  Control whether ad snippets are escaped via `wp_kses`. Default: `true`.

- `init_plugin_suite_ad_engine_allowed_tags`  
  Extend or modify the allow-list of permitted tags/attributes for ad snippets.

- `init_plugin_suite_ad_engine_disable_all_ads`  
  Disable all ad injections globally. Default: `false`.

- `init_ad_engine_should_enqueue_affiliate_gate`  
  Control whether the Affiliate Gate scripts should be enqueued.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/init-ad-engine` or install from the WordPress Plugin Directory.
2. Activate it via the **Plugins** menu.
3. Go to **Settings → Init Ad Engine** and configure your ad placements.

== Frequently Asked Questions ==

= Will it conflict with page caching or optimization plugins? =
No. Ad rendering is done via inline JS and dynamic DOM injection. It's fully compatible with caching.

= Can I use custom JavaScript ad tags? =
Yes. Each position supports a fallback HTML/JS block, including `<script>` tags.

= Does it support responsive behavior? =
Yes. Each ad position is associated with desktop, mobile, or both. The plugin auto-detects screen size.

= Does it track views or clicks? =
No. Version 1.0 focuses on display only. Analytics may be added in future updates.

== Screenshots ==

1. **PC Settings Tab** – Configure desktop-only ad positions such as Billboard, Catfish, Popup Center PC, and sidebar floats  
2. **Mobile Settings Tab** – Configure mobile-specific units including Sticky Top/Bottom, Mini Billboard, and Popup Center Mobile  
3. **Popunder & Global Tab** – Configure popunder behavior and global head/footer injection  
4. **Affiliate Gate Tab** – Full settings UI for content gating, steps, random modes, blur overlay, and banner configuration

== Changelog ==

= 1.4 – November 14, 2025 =
- UX: Settings screen now **remembers the last active tab** and automatically restores it when you reopen the page
- UX: Improved height/scroll behavior for **“Popunder & Global”** tab to prevent sudden jumps when switching tabs
- Dev: Refactored tab-switching logic to a single inline handler using `localStorage` for per-admin tab state
- Maint: Kept existing markup and option structure intact to avoid breaking any saved configurations or integrations

= 1.3 – October 25, 2025 =
- New: Affiliate Gate now supports **multiple affiliate links** separated by commas — one will be displayed **randomly** on each trigger
- UX: Added description text under Affiliate Link field — “Enter multiple links separated by commas (,) to display them randomly.”
- Dev: Implemented `pickAffiliateLink()` helper to normalize, decode, and randomly select a valid `http/https` link from list
- Maint: Code cleanup and safe decoding of `%20` characters in link strings for better compatibility

= 1.2 – September 10, 2025 =
- Security: Global head and footer injection now use `wp_kses` with a custom allow-list for late escaping (security hardening)
- New: Added filter `init_plugin_suite_ad_engine_use_kses` to toggle escaping (default `true`)
  Developers can return `false` to output raw snippets without escaping
- New: Added filter `init_plugin_suite_ad_engine_allowed_tags` to extend or modify the allow-list of permitted tags/attributes
- Maint: Refactored injection code to use a unified `init_plugin_suite_ad_engine_render_snippet()` helper function

= 1.1 – July 14, 2025 =
- New: Affiliate Gate – hide content and display affiliate promo with banner, link, intro/outro text
- New: Affiliate Gate now supports 4 display modes – always-on, expire-after-click, random percentage, and every X pages
- New: Added support for custom step list (e.g. "1,3,5") to control exactly which pageviews show the gate
- New: Added "blur overlay" option – insert semi-transparent clickable layer over any selector, with its own step logic
- New: Blur overlay and custom steps use persistent localStorage counters with auto-reset after `expire_hours`
- New: Refreshing the same page no longer increases step count – only new pageviews are tracked
- New: Customizable selector for content gating
- New: Inline script config injected in <head> to avoid layout shift
- New: Filter `init_ad_engine_should_enqueue_affiliate_gate` allows theme to control when to enqueue the affiliate gate

= 1.0 – May 29, 2025 =
- Initial release
- Support for 20+ ad positions
- Popunder with configurable triggers
- Global header/footer code fields
- Simple fallback system for HTML/JS ads

== Security Notes ==

- Ad snippets entered in the plugin settings are considered user-provided content.  
- By default, all snippets are escaped at render time using `wp_kses` with a restricted allow-list of tags and attributes.  
- Developers can disable escaping via the `init_plugin_suite_ad_engine_use_kses` filter if they explicitly trust their input.  
- All ad injections can be globally disabled via the `init_plugin_suite_ad_engine_disable_all_ads` filter.  
- Only administrators with the `manage_options` capability can configure or modify global ad settings.

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
