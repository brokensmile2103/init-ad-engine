(function () {
    // Built-in icon set (simple, self-contained SVGs — no external requests).
    var ICONS = {
        none: '',
        tag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5V4a1 1 0 0 1 1-1h7.5L21 11.5 12.5 20 3 11.5z"/><circle cx="7.5" cy="7.5" r="1.5" fill="currentColor" stroke="none"/></svg>',
        gift: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="9" width="18" height="11" rx="1"/><path d="M3 13h18"/><path d="M12 9v11"/><path d="M12 9c-1.5-4-6-4-6-1 0 1.5 2 1 6 1zm0 0c1.5-4 6-4 6-1 0 1.5-2 1-6 1z"/></svg>',
        fire: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1 3-3 4-3 8a3 3 0 0 0 6 0c0-1-1-2-1-2 2 1 4 4 4 7a7 7 0 1 1-14 0c0-5 4-6 4-9 0-2 1-3 4-4z"/></svg>',
        bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>',
        cart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.5" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none"/><path d="M2 3h2l2.6 12.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L21 7H5.2"/></svg>',
        percent: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
        star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.3 6.9.7-5.2 4.6 1.5 6.8L12 17l-6.1 3.4 1.5-6.8L2.2 9l6.9-.7L12 2z"/></svg>',
        lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
        arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="13 5 20 12 13 19"/></svg>'
    };

    // Effect name -> { CSS animation name, duration in seconds }.
    var EFFECTS = {
        none: null,
        heartbeat: { name: 'initFcHeartbeat', duration: '1.6s' },
        pulse: { name: 'initFcPulse', duration: '1.8s' },
        shake: { name: 'initFcShake', duration: '1.4s' },
        bounce: { name: 'initFcBounce', duration: '2s' },
        tada: { name: 'initFcTada', duration: '2s' },
        swing: { name: 'initFcSwing', duration: '2s' }
    };

    function injectStyles() {
        if (document.getElementById('init-floating-cta-style')) return;

        var style = document.createElement('style');
        style.id = 'init-floating-cta-style';
        style.textContent =
            '.init-floating-cta{position:fixed;z-index:99999;}' +
            '.init-floating-cta-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px 11px 14px;' +
            'border-radius:999px;text-decoration:none;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;' +
            'font-size:14px;font-weight:700;box-shadow:0 4px 14px rgba(0,0,0,.25);white-space:nowrap;}' +
            '.init-floating-cta-btn svg{width:20px;height:20px;flex-shrink:0;}' +
            '.init-floating-cta-badge{position:absolute;top:-12px;left:12px;padding:2px 9px;border-radius:999px;' +
            'font-size:10px;font-weight:700;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,.25);' +
            'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;}' +
            '.init-floating-cta-close{position:absolute;top:-9px;left:-9px;width:22px;height:22px;border-radius:50%;' +
            'background:rgba(0,0,0,.55);color:#fff;border:none;cursor:pointer;font-size:14px;line-height:22px;' +
            'text-align:center;padding:0;}' +
            '@keyframes initFcHeartbeat{0%,100%{transform:scale(1);}15%{transform:scale(1.12);}30%{transform:scale(1);}' +
            '45%{transform:scale(1.08);}60%{transform:scale(1);}}' +
            '@keyframes initFcPulse{0%,100%{box-shadow:0 0 0 0 rgba(0,0,0,.35);}50%{box-shadow:0 0 0 10px rgba(0,0,0,0);}}' +
            '@keyframes initFcShake{0%,100%{transform:translateX(0);}20%{transform:translateX(-4px);}' +
            '40%{transform:translateX(4px);}60%{transform:translateX(-3px);}80%{transform:translateX(3px);}}' +
            '@keyframes initFcBounce{0%,20%,50%,80%,100%{transform:translateY(0);}40%{transform:translateY(-10px);}70%{transform:translateY(-5px);}}' +
            '@keyframes initFcTada{0%{transform:scale(1) rotate(0);}10%,20%{transform:scale(.9) rotate(-3deg);}' +
            '30%,50%,70%,90%{transform:scale(1.1) rotate(3deg);}40%,60%,80%{transform:scale(1.1) rotate(-3deg);}100%{transform:scale(1) rotate(0);}}' +
            '@keyframes initFcSwing{20%{transform:rotate(15deg);}40%{transform:rotate(-10deg);}' +
            '60%{transform:rotate(5deg);}80%{transform:rotate(-5deg);}100%{transform:rotate(0);}}';

        document.head.appendChild(style);
    }

    function pickLink(links) {
        if (!Array.isArray(links) || links.length === 0) return '';
        if (links.length === 1) return links[0];
        return links[Math.floor(Math.random() * links.length)];
    }

    function run() {
        var config = window.InitAdEngineFloatingCta;
        if (!config || typeof config !== 'object') return;

        var link = pickLink(config.links);
        if (!link) return;

        injectStyles();

        var corner = ['bottom-right', 'bottom-left', 'top-right', 'top-left'].indexOf(config.corner) !== -1
            ? config.corner
            : 'bottom-right';
        var offsetX = Math.max(0, parseInt(config.offset_x, 10) || 0);
        var offsetY = Math.max(0, parseInt(config.offset_y, 10) || 0);

        var wrapper = document.createElement('div');
        wrapper.className = 'init-floating-cta';

        if (corner.indexOf('top') === 0) {
            wrapper.style.top = offsetY + 'px';
        } else {
            wrapper.style.bottom = offsetY + 'px';
        }
        if (corner.indexOf('right') !== -1) {
            wrapper.style.right = offsetX + 'px';
        } else {
            wrapper.style.left = offsetX + 'px';
        }

        var link_el = document.createElement('a');
        link_el.className = 'init-floating-cta-btn';
        link_el.href = link;
        if (config.target === '_blank') {
            link_el.target = '_blank';
            link_el.rel = 'nofollow noopener';
        }
        link_el.style.background = config.bg_color || '#ee4d2d';
        link_el.style.color = config.text_color || '#ffffff';

        var effect = EFFECTS[config.effect] || EFFECTS.heartbeat;
        if (effect) {
            link_el.style.animation = effect.name + ' ' + effect.duration + ' ease-in-out infinite';
        }

        var iconSvg = ICONS[config.icon] || '';
        if (iconSvg) {
            var iconWrap = document.createElement('span');
            iconWrap.innerHTML = iconSvg;
            link_el.appendChild(iconWrap.firstChild);
        }

        var label = document.createElement('span');
        label.textContent = config.button_text || '';
        link_el.appendChild(label);

        wrapper.appendChild(link_el);

        if (config.badge_text) {
            var badge = document.createElement('span');
            badge.className = 'init-floating-cta-badge';
            badge.textContent = config.badge_text;
            badge.style.background = config.badge_bg_color || '#b71c1c';
            badge.style.color = config.badge_text_color || '#ffffff';
            wrapper.appendChild(badge);
        }

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'init-floating-cta-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.addEventListener('click', function () {
            if (config.force_open_on_close && link) {
                window.open(link, '_blank', 'noopener');
            }
            wrapper.remove();
        });
        wrapper.appendChild(closeBtn);

        document.body.appendChild(wrapper);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
