(function () {
    const run = () => {
        const config = window.InitAdGateConfig || {};
        const contentSelector = config.selector || '#entry-content';
        const contentEl = document.querySelector(contentSelector);
        if (!contentEl) return;

        const postIdMatch = document.body.className.match(/postid-(\d+)/);
        const postId = postIdMatch ? postIdMatch[1] : null;
        if (!postId) return;

        const now = Date.now();
        const mode = config.mode || 'expire';
        const expireHours = parseInt(config.expire_hours || 6, 10);
        const randomPercent = parseInt(config.random_percent || 50, 10);
        const everyX = parseInt(config.every_x || 3, 10);
        const customStepsRaw = config.custom_steps || '';

        // --- NEW: pick affiliate link (single or random from list) ---
        const selectedAffLink = pickAffiliateLink(config.aff_link);

        let shouldShow = true;
        let storageKey = '';

        switch (mode) {
            case 'always':
                storageKey = `aff_gate_post_${postId}`;
                try {
                    const stored = JSON.parse(localStorage.getItem(storageKey));
                    if (stored?.expireAt > now) shouldShow = false;
                } catch (e) {}
                break;

            case 'expire':
                storageKey = 'aff_gate_expire';
                try {
                    const stored = JSON.parse(localStorage.getItem(storageKey));
                    if (stored?.expireAt > now) shouldShow = false;
                } catch (e) {}
                break;

            case 'random':
                shouldShow = Math.random() * 100 < randomPercent;
                break;

            case 'every_x':
                storageKey = 'aff_gate_view_count';
                let count = 0;
                try {
                    count = parseInt(localStorage.getItem(storageKey) || '0', 10);
                } catch (e) {}

                shouldShow = count % everyX === 0;
                localStorage.setItem(storageKey, (count + 1).toString());
                break;

            case 'custom_steps':
                storageKey = 'aff_gate_custom_steps';
                const stepList = (config.custom_steps || '')
                    .split(',')
                    .map(s => parseInt(s.trim(), 10))
                    .filter(n => !isNaN(n) && n > 0)
                    .sort((a, b) => a - b);

                if (!stepList.length) {
                    shouldShow = false;
                    break;
                }

                let stepData = loadStepData(storageKey, now, expireHours);
                saveStepData(storageKey, stepData);

                shouldShow = stepList.includes(stepData.count);

                const maxStep = stepList[stepList.length - 1];
                if (stepData.count >= maxStep && stepData.expireAt === 0) {
                    stepData.expireAt = now + expireHours * 3600 * 1000;
                    saveStepData(storageKey, stepData);
                }
                break;
        }

        if (shouldShow) {
            // Ẩn nội dung gốc
            contentEl.style.display = 'none';

            // Tạo block quảng cáo
            const adWrap = document.createElement('div');
            adWrap.className = 'init-affiliate-gate-block';
            adWrap.style = 'margin: 2em 0; text-align: center;';

            const intro = config.aff_intro ? `<p>${config.aff_intro}</p>` : '';
            const outro = config.aff_outro ? `<p>${config.aff_outro}</p>` : '';
            const banner = (config.aff_banner && selectedAffLink)
                ? `<a href="${selectedAffLink}" class="init-aff-link" target="_blank" rel="nofollow noopener"><img src="${config.aff_banner}" style="max-width:100%; height:auto;" /></a>`
                : '';
            const link = selectedAffLink
                ? `<p>👉 <a href="${selectedAffLink}" class="init-aff-link" target="_blank" rel="nofollow noopener">${selectedAffLink}</a></p>`
                : '';

            adWrap.innerHTML = `${intro}${link}${banner}${outro}`;
            contentEl.parentNode.insertBefore(adWrap, contentEl);

            adWrap.addEventListener('click', function (e) {
                const linkEl = e.target.closest('.init-aff-link');
                if (linkEl) {
                    e.preventDefault();

                    const href = linkEl.getAttribute('href');
                    if (!href) return;

                    if (mode === 'expire') {
                        const expireAt = Date.now() + expireHours * 3600 * 1000;
                        localStorage.setItem('aff_gate_expire', JSON.stringify({ expireAt }));
                    } else if (mode === 'always') {
                        const expireAt = Date.now() + expireHours * 3600 * 1000;
                        localStorage.setItem(`aff_gate_post_${postId}`, JSON.stringify({ expireAt }));
                    }

                    adWrap.remove();
                    contentEl.style.display = '';
                    window.open(href, '_blank');
                }
            });
        }

        const blurOverlay = config.blur_overlay || {};
        const blurLink = blurOverlay.link;
        const blurSelector = blurOverlay.selector;
        const blurStepsRaw = blurOverlay.steps || '';

        if (blurLink && blurSelector && blurStepsRaw) {
            const blurStepList = blurStepsRaw
                .split(',')
                .map(s => parseInt(s.trim(), 10))
                .filter(n => !isNaN(n) && n > 0)
                .sort((a, b) => a - b);

            if (blurStepList.length > 0) {
                const blurStorageKey = 'aff_gate_blur_step_data';
                let blurData = loadStepData(blurStorageKey, now, expireHours);
                saveStepData(blurStorageKey, blurData);

                const blurShouldShow = blurStepList.includes(blurData.count);
                if (blurShouldShow) {
                    const blurTargets = document.querySelectorAll(blurSelector);
                    blurTargets.forEach(blurTarget => {
                        const overlay = document.createElement('a');
                        overlay.href = blurLink;
                        overlay.target = '_blank';
                        overlay.rel = 'nofollow noopener';
                        overlay.className = 'init-affiliate-blur-overlay';
                        overlay.style = `
                            position: absolute;
                            top: 0; left: 0; width: 100%; height: 100%;
                            z-index: 9999;
                            display: block;
                        `;

                        blurTarget.style.position = 'relative';
                        blurTarget.appendChild(overlay);

                        overlay.addEventListener('click', () => {
                            setTimeout(() => {
                                overlay.remove();
                                blurTarget.style.position = '';
                            }, 100);
                        });
                    });
                }

                const blurMaxStep = blurStepList[blurStepList.length - 1];
                if (blurData.count >= blurMaxStep && blurData.expireAt === 0) {
                    blurData.expireAt = now + expireHours * 3600 * 1000;
                    saveStepData(blurStorageKey, blurData);
                }
            }
        }

    };

    // --- Helpers ---
    function pickAffiliateLink(raw) {
        if (!raw) return '';

        // Accept array or string
        let items = [];
        if (Array.isArray(raw)) {
            items = raw;
        } else if (typeof raw === 'string') {
            // Handle cases like "url1,%20url2,%20url3"
            // 1) Replace %20 -> space, 2) split by commas, 3) trim, 4) decode & validate http(s)
            const s = raw.replace(/%20/gi, ' ');
            items = s.split(',').map(x => x.trim());
        }

        // Normalize + validate
        items = items
            .map(x => safeDecode(x).trim())
            .filter(x => /^https?:\/\//i.test(x));

        if (items.length === 0) return '';
        if (items.length === 1) return items[0];

        const idx = Math.floor(Math.random() * items.length);
        return items[idx];
    }

    function safeDecode(s) {
        try { return decodeURIComponent(s); } catch (e) { return s; }
    }

    function loadStepData(storageKey, now, expireHours) {
        let data = { count: 0, expireAt: 0, lastUrl: '' };
        const currentUrl = window.location.href.split('#')[0];

        try {
            data = JSON.parse(localStorage.getItem(storageKey)) || data;
        } catch (e) {}

        // Reset nếu hết hạn
        if (data.expireAt <= now && data.expireAt !== 0) {
            data = { count: 0, expireAt: 0, lastUrl: '' };
        }

        // Chỉ tăng nếu khác trang
        if (data.lastUrl !== currentUrl) {
            data.count++;
            data.lastUrl = currentUrl;
        }

        return data;
    }

    function saveStepData(storageKey, data) {
        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        requestAnimationFrame(run);
    }
})();
