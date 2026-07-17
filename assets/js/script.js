document.addEventListener('DOMContentLoaded', () => {
    const data = window.InitPluginSuiteAdEngine;
    if (!data || typeof data !== 'object') return;

    const isMobile = matchMedia('(max-width: 768px)').matches;

    // Append styles to <head>
    const style = document.createElement('style');
    style.textContent = `
    .init-ad-engine { 
        z-index: 9998;
        max-height: 100vh;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .init-ad-engine.fixed { position: fixed; }
    .init-ad-engine img {
        max-width: 100%;
        height: auto;
        max-height: 100vh;
        display: block;
        margin: 0 auto;
    }
    .init-ad-engine-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9997;
        background: rgba(0, 0, 0, 0.6);
        animation: initAdEngineFadeIn 0.15s ease-out;
    }
    @keyframes initAdEngineFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .init-ad-engine .close-btn {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 18px;
        background: rgba(0,0,0,0.5);
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        line-height: 25px;
        text-align: center;
        cursor: pointer;
    }
    .ad-billboard,
    .ad-miniBillboard {
        display: block;
        text-align: center;
        margin: 20px auto;
    }
    .ad-balloonRight .close-btn {
        left: 10px;
        right: auto;
    }
    .ad-inner {
        position: relative;
        display: inline-block;
        max-width: 100%;
        max-height: 100vh;
        text-align: center;
    }
`;

    document.head.appendChild(style);

    Object.entries(data).forEach(([position, item]) => {
        const device = item.device || 'both';
        const shouldRender =
            device === 'both' ||
            (device === 'desktop' && !isMobile) ||
            (device === 'mobile' && isMobile);

        if (!shouldRender) return;

        const renderAd = () => {
            const isPopupCenter = position === 'popupCenterPC' || position === 'popupCenterMobile';

            // Build the inner content FIRST. If there's nothing to show,
            // bail out before touching the DOM at all (no backdrop, no
            // wrapper) so empty/unconfigured positions render nothing.
            const inner = document.createElement('div');
            inner.className = 'ad-inner';

            if (item.img) {
                const a = document.createElement('a');
                a.href = item.url || '#';
                if (item.target === '_blank') a.target = '_blank';

                const img = document.createElement('img');
                img.src = item.img;
                img.alt = 'Ad';
                img.loading = 'lazy';

                a.appendChild(img);
                inner.appendChild(a);
            } else if (item.fallback) {
                const fallbackWrap = document.createElement('div');
                fallbackWrap.innerHTML = item.fallback;
                inner.appendChild(fallbackWrap);
            } else {
                return;
            }

            let backdrop = null;
            if (isPopupCenter) {
                backdrop = document.createElement('div');
                backdrop.className = 'init-ad-engine-backdrop';
                document.body.appendChild(backdrop);
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'init-ad-engine ad-' + position;
            if (!['billboard', 'miniBillboard'].includes(position)) {
                wrapper.classList.add('fixed');
            }

            const closeAll = () => {
                wrapper.remove();
                if (backdrop) backdrop.remove();
            };

            if (backdrop) {
                backdrop.addEventListener('click', closeAll);
            }

            // Positioning styles
            switch (position) {
                case 'balloonLeft':
                    wrapper.style.cssText += 'bottom:0;left:0;';
                    break;
                case 'balloonRight':
                    wrapper.style.cssText += 'bottom:0;right:0;';
                    break;
                case 'floatLeft':
                    wrapper.style.cssText += 'top:0;left:0;';
                    break;
                case 'floatRight':
                    wrapper.style.cssText += 'top:0;right:0;';
                    break;
                case 'catfishTop':
                case 'stickyTopMobile':
                    wrapper.style.cssText += 'top:0;left:0;width:100%;text-align:center;';
                    break;
                case 'catfishBottom':
                case 'stickyBottomMobile':
                    wrapper.style.cssText += 'bottom:0;left:0;width:100%;text-align:center;';
                    break;
                case 'popupCenterPC':
                case 'popupCenterMobile':
                    wrapper.style.cssText += 'top:50%;left:50%;transform:translate(-50%,-50%);';
                    break;
                default:
                    wrapper.style.cssText += 'top:0;left:0;';
            }

            // Close button
            if (!['billboard', 'miniBillboard'].includes(position)) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'close-btn';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = () => closeAll();
                inner.appendChild(closeBtn);
            }

            wrapper.appendChild(inner);

            if (['billboard', 'miniBillboard'].includes(position)) {
                document.body.insertAdjacentElement('afterbegin', wrapper);
            } else {
                document.body.appendChild(wrapper);
            }

            // Save timestamp
            if (position.startsWith('popupCenter')) {
                const key = 'initAdShown-' + position;
                localStorage.setItem(key, Date.now().toString());
            }
        };

        // Popup behavior
        if (position.startsWith('popupCenter')) {
            const type = item.display || 'immediate';
            const delay = parseInt(item.delay) || 5;
            const cooldown = parseInt(item.delay_hours) || 24;
            const key = 'initAdShown-' + position;
            const last = parseInt(localStorage.getItem(key) || '0');
            const now = Date.now();

            const canShow = (now - last) >= cooldown * 3600 * 1000;

            if (!canShow) return;

            if (type === 'immediate') {
                renderAd();
            } else if (type === 'delay') {
                setTimeout(renderAd, delay * 1000);
            } else if (type === 'exit') {
                let exitTriggered = false;
                document.addEventListener('mouseout', (e) => {
                    if (!exitTriggered && e.clientY < 0 && !e.relatedTarget) {
                        exitTriggered = true;
                        renderAd();
                    }
                });
            }
        } else {
            renderAd();
        }
    });
});

// Handle popunder (run after all DOM loaded)
document.addEventListener('DOMContentLoaded', () => {
    const data = window.InitPluginSuiteAdEngine;
    if (!data || typeof data !== 'object') return;

    const popunder = data.popunder;
    const popunderUrl = popunder && typeof popunder.url === 'string' ? popunder.url.trim() : '';
    if (!popunderUrl) return;

    const STORAGE_KEY_LAST = 'initPopunderLast';
    const STORAGE_KEY_CLICK = 'initPopunderClick';
    const clickThreshold = Math.max(1, parseInt(popunder.click_threshold, 10) || 1);
    const delayHours = Math.max(0, parseInt(popunder.delay_hours, 10) || 0) || 24;

    // Safe wrappers: some browsers (Safari private mode, locked-down webviews,
    // certain extensions) throw when touching localStorage/sessionStorage.
    // A thrown error here used to silently kill the whole popunder feature.
    const safeGet = (storage, key) => {
        try { return storage.getItem(key); } catch (e) { return null; }
    };
    const safeSet = (storage, key, value) => {
        try { storage.setItem(key, value); } catch (e) { /* ignore */ }
    };

    let clickCount = parseInt(safeGet(sessionStorage, STORAGE_KEY_CLICK) || '0', 10) || 0;

    const isInCooldown = () => {
        const lastShown = parseInt(safeGet(localStorage, STORAGE_KEY_LAST) || '0', 10) || 0;
        return (Date.now() - lastShown) < delayHours * 3600 * 1000;
    };

    const trigger = () => {
        const a = document.createElement('a');
        a.href = popunderUrl;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        safeSet(localStorage, STORAGE_KEY_LAST, Date.now().toString());
        safeSet(sessionStorage, STORAGE_KEY_CLICK, '0');
    };

    const clickHandler = (e) => {
        const target = e.target.closest('a');
        if (target && target.target === '_blank') return;

        // Re-check cooldown on every click instead of once at page load,
        // so the listener stays active even if the tab was left open across
        // the cooldown boundary, and testing/QA doesn't need a hard reload.
        if (isInCooldown()) return;

        clickCount++;
        safeSet(sessionStorage, STORAGE_KEY_CLICK, clickCount.toString());
        if (clickCount >= clickThreshold) {
            clickCount = 0;
            trigger();
        }
    };

    document.addEventListener('click', clickHandler);
});
