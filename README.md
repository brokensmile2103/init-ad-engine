# Init Ad Engine – Flexible, Multi-Format, Secure
> Lightweight and flexible ad engine for WordPress — banners, popups, sticky ads, and popunders with full control.

**No bloat. Just fast, flexible advertising for WordPress.**

[![Version](https://img.shields.io/badge/stable-v1.4-blue.svg)](https://wordpress.org/plugins/init-ad-engine/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

**Init Ad Engine** lets you easily insert banners, popups, sticky ads, and popunders into your WordPress site — all without writing code.

Built for flexibility, speed, and clean admin experience.

### Highlights
- **20+ ad positions** for desktop and mobile  
- **Popunder, popup, sticky, and floating** formats  
- **Affiliate Gate** with multiple display modes  
- **Secure escaping** using `wp_kses` with developer filters  
- **Zero dependencies** — vanilla JS only, loaded only when needed  

## Features

- Tabbed admin UI with separate device sections  
- Inline styles only — no external CSS  
- Global `<head>` and `<footer>` injection fields  
- Popunder with cooldown and click trigger  
- Exit-intent and delay-based popup options  
- Fallback HTML/JS per ad slot  
- Responsive and performance-optimized  
- No tracking, no database writes — **ultra lightweight**

### Ad Positions

**Desktop (PC):**  
Billboard, Balloon Left/Right, Float Left/Right, Catfish Top/Bottom, Popup Center, Before/After Content  

**Mobile:**  
Mini Billboard, Sticky Top/Bottom, Popup Center, Before/After Content  

**Special:**  
Popunder, Global Header/Footer Code Injection

## Affiliate Gate

Display promotional content in place of regular content, with control over how and when it appears.

**Display Modes:**
- Always-on
- Expire-after-click (for X hours)
- Random percentage
- Every X page views
- Custom step list (e.g. `1,3,5`)

**Version 1.3 Update:**  
Now supports **multiple affiliate links**, separated by commas — one is chosen **randomly** each time.  
Includes inline field description for clarity.

## Developer Filters

| Filter | Description |
|---------|-------------|
| `init_plugin_suite_ad_engine_use_kses` | Enable or disable escaping of ad snippets (default: `true`) |
| `init_plugin_suite_ad_engine_allowed_tags` | Modify the allow-list for tags/attributes |
| `init_plugin_suite_ad_engine_disable_all_ads` | Disable all ad injections globally |
| `init_ad_engine_should_enqueue_affiliate_gate` | Control when Affiliate Gate scripts are enqueued |

## Installation

1. Upload to `/wp-content/plugins/init-ad-engine`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Settings → Init Ad Engine** and configure your placements

## Security Notes

- All ad snippets are sanitized with `wp_kses` by default  
- Only administrators (`manage_options`) can configure ads  
- Developers can disable escaping via `init_plugin_suite_ad_engine_use_kses`  
- Global opt-out available with `init_plugin_suite_ad_engine_disable_all_ads`

## License

GPLv2 or later — open source, minimal, developer-first.

## Part of Init Plugin Suite

Init Content Protector is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of blazing-fast, no-bloat plugins made for WordPress developers who care about quality and speed.
