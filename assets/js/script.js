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
        height: auto;
        max-height: 100vh;
        display: block;
        margin: 0 auto;
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
            const wrapper = document.createElement('div');
            wrapper.className = 'init-ad-engine ad-' + position;
            if (!['billboard', 'miniBillboard'].includes(position)) {
                wrapper.classList.add('fixed');
            }

            const inner = document.createElement('div');
            inner.className = 'ad-inner';

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
                closeBtn.onclick = () => wrapper.remove();
                inner.appendChild(closeBtn);
            }

            // Content
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
(function () {
    const data = window.InitPluginSuiteAdEngine;
    if (!data || typeof data !== 'object') return;

    const popunder = data.popunder;
    if (!popunder || !popunder.url) return;

    const STORAGE_KEY_LAST = 'initPopunderLast';
    const STORAGE_KEY_CLICK = 'initPopunderClick';
    const now = Date.now();
    const lastShown = parseInt(localStorage.getItem(STORAGE_KEY_LAST) || '0');
    const clickThreshold = parseInt(popunder.click_threshold) || 1;
    const delayHours = parseInt(popunder.delay_hours) || 24;

    if ((now - lastShown) < delayHours * 3600 * 1000) return;

    let clickCount = parseInt(sessionStorage.getItem(STORAGE_KEY_CLICK) || '0');
    const trigger = () => {
        const a = document.createElement('a');
        a.href = popunder.url;
        a.target = '_blank';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        console.log(popunder.url);

        localStorage.setItem(STORAGE_KEY_LAST, Date.now().toString());
        sessionStorage.setItem(STORAGE_KEY_CLICK, '0');
    };

    const clickHandler = (e) => {
        const target = e.target.closest('a');
        if (target && target.target === '_blank') return;

        clickCount++;
        sessionStorage.setItem(STORAGE_KEY_CLICK, clickCount.toString());
        if (clickCount >= clickThreshold) {
            document.removeEventListener('click', clickHandler);
            trigger();
        }
    };

    document.addEventListener('click', clickHandler);
})();

document.addEventListener('DOMContentLoaded', () => {
    const pop = window.InitPluginSuiteAdEngine?.popunder;
    if (!pop || !pop.url) return;

    const LAST_KEY = 'initPopunderLast';
    const CLICK_KEY = 'initPopunderClick';
    const now = Date.now();
    const lastShown = parseInt(localStorage.getItem(LAST_KEY) || '0');
    const delayHours = parseInt(pop.delay_hours) || 24;
    const clickThreshold = parseInt(pop.click_threshold) || 1;

    if ((now - lastShown) < delayHours * 3600 * 1000) return;

    let clicks = parseInt(sessionStorage.getItem(CLICK_KEY) || '0');

    const triggerPopunder = () => {
        const a = document.createElement('a');
        a.href = pop.url;
        a.target = '_blank';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        localStorage.setItem(LAST_KEY, now.toString());
        sessionStorage.setItem(CLICK_KEY, '0');
    };

    const handleClick = () => {
        clicks++;
        sessionStorage.setItem(CLICK_KEY, clicks.toString());
        if (clicks >= clickThreshold) {
            document.body.removeEventListener('click', handleClick);
            triggerPopunder();
        }
    };

    document.body.addEventListener('click', handleClick, { once: false });
});
