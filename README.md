# Init Ad Engine – Flexible, Multi-Format, Secure
> Lightweight and flexible ad engine for WordPress — banners, popups, sticky ads, floating buttons, and popunders with full control.

**No bloat. Just fast, flexible advertising for WordPress.**

[![Version](https://img.shields.io/badge/stable-v1.7-blue.svg)](https://wordpress.org/plugins/init-ad-engine/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

**Init Ad Engine** lets you easily insert banners, popups, sticky ads, floating CTA buttons, and popunders into your WordPress site — all without writing code.

Built for flexibility, speed, and clean admin experience.

### Highlights
- **20+ ad positions** for desktop and mobile  
- **Popunder, popup, sticky, and floating** formats  
- **Floating Button** with built-in animations, icons, and multi-link rotation  
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
- Optional schedule window (start/end date) per position  
- Optional frequency cap (hours) for static banner positions  
- Responsive and performance-optimized  
- No tracking, no database writes — **ultra lightweight**

### Ad Positions

**Desktop (PC):**  
Billboard, Balloon Left/Right, Float Left/Right, Catfish Top/Bottom, Popup Center, Before/After Content  

**Mobile:**  
Mini Billboard, Sticky Top/Bottom, Popup Center, Before/After Content  

**Special:**  
Popunder, Global Header/Footer Code Injection

**Floating Button:**  
Corner-pinned CTA button, independent from the positions above — see [Floating Button](#floating-button) below.

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

## Floating Button

A corner-pinned, attention-grabbing CTA button, configured in its own settings tab — same idea as Affiliate Gate, but for a persistent floating widget.

**Highlights:**
- Pin to any of the **4 screen corners**, with independent horizontal/vertical spacing from the edges
- **6 built-in animation effects** — Heartbeat, Pulse, Shake, Bounce, Tada, Swing (or None)
- **9 built-in SVG icons** — Tag, Gift, Fire, Bell, Cart, Percent, Star, Lock, Arrow — no image upload needed
- **Multiple links** via a one-per-line textarea; a random link is shown on each page view
- Optional **badge label** (e.g. `VOUCHER 50%`) with its own colors
- Close (x) button with an option to **force-open the link** on dismiss
- Fully customizable **button and badge colors**
- **Device targeting** (Desktop / Mobile / Both) and optional **date-range scheduling**

**Version 1.7 Update:**  
New ad format introduced — see the [Floating Button](#floating-button) settings tab.

## Developer Filters

| Filter | Description |
|---------|-------------|
| `init_plugin_suite_ad_engine_use_kses` | Enable or disable escaping of ad snippets (default: `true`) |
| `init_plugin_suite_ad_engine_allowed_tags` | Modify the allow-list for tags/attributes |
| `init_plugin_suite_ad_engine_disable_all_ads` | Disable all ad injections globally |
| `init_plugin_suite_ad_engine_should_enqueue_affiliate_gate` | Control when Affiliate Gate scripts are enqueued |
| `init_plugin_suite_ad_engine_affiliate_gate_data` | Filter the data passed to the Affiliate Gate script |
| `init_plugin_suite_ad_engine_should_enqueue_floating_cta` | Control when the Floating Button script is enqueued |
| `init_plugin_suite_ad_engine_floating_cta_data` | Filter the data passed to the Floating Button script |

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
