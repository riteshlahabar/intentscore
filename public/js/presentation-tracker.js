(() => {
    const config = window.PRESENTATION_TRACKING;
    if (!config) return;

    const makeUuid = () => {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
            const random = Math.random() * 16 | 0;
            const value = character === 'x' ? random : (random & 0x3 | 0x8);
            return value.toString(16);
        });
    };

    const sessionUuid = makeUuid();
    const visitorStorageKey = config.visitorStorageKey || 'sales_portal_visitor';
    let visitorUuid = localStorage.getItem(visitorStorageKey);

    if (!visitorUuid) {
        visitorUuid = makeUuid();
        localStorage.setItem(visitorStorageKey, visitorUuid);
    }

    const source = new URLSearchParams(window.location.search).get('src') || 'direct';
    let currentSection = null;
    let sectionStartedAt = 0;
    let pageVisible = document.visibilityState === 'visible';
    let lastInteractionAt = Date.now();

    const sendEvent = (eventType, data = {}) => {
        const payload = {
            _token: config.csrf,
            session_uuid: sessionUuid,
            visitor_uuid: visitorUuid,
            event_type: eventType,
            referrer: document.referrer || '',
            source,
            ...data,
        };

        const body = JSON.stringify(payload);

        if (navigator.sendBeacon && eventType === 'section_time') {
            const blob = new Blob([body], {type: 'application/json'});
            navigator.sendBeacon(config.trackUrl, blob);
            return;
        }

        fetch(config.trackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
                'Accept': 'application/json',
            },
            body,
            keepalive: true,
        }).catch(() => {});
    };

    const finishCurrentSection = () => {
        if (!currentSection || !sectionStartedAt) return;

        const durationMs = Date.now() - sectionStartedAt;
        const recentlyActive = Date.now() - lastInteractionAt < 120000;

        if (durationMs > 800 && pageVisible && recentlyActive) {
            sendEvent('section_time', {
                section_key: currentSection,
                duration_ms: Math.min(durationMs, 300000),
            });
        }

        sectionStartedAt = 0;
    };

    sendEvent('page_opened', {meta: {label: document.title}});

    ['click', 'scroll', 'keydown', 'touchstart'].forEach((eventName) => {
        window.addEventListener(
            eventName,
            () => { lastInteractionAt = Date.now(); },
            {passive: true},
        );
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            finishCurrentSection();
            pageVisible = false;
            return;
        }

        pageVisible = true;
        if (currentSection) sectionStartedAt = Date.now();
    });

    window.addEventListener('beforeunload', finishCurrentSection);

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting || entry.intersectionRatio < 0.35) return;

            const sectionKey = entry.target.dataset.section;
            if (currentSection === sectionKey) return;

            finishCurrentSection();
            currentSection = sectionKey;
            sectionStartedAt = Date.now();
            sendEvent('section_viewed', {section_key: sectionKey});
        });
    }, {threshold: [0.35]});

    document.querySelectorAll('[data-section]').forEach((element) => {
        sectionObserver.observe(element);
    });

    document.querySelectorAll('[data-track]').forEach((element) => {
        element.addEventListener('click', () => {
            sendEvent('button_clicked', {
                section_key: element.closest('[data-section]')?.dataset.section || currentSection,
                element_key: element.dataset.track,
                meta: {label: (element.textContent || '').trim().slice(0, 120)},
            });
        });
    });

    document.querySelectorAll('a[data-url-track]').forEach((element) => {
        element.addEventListener('click', () => {
            sendEvent('url_opened', {
                section_key: element.closest('[data-section]')?.dataset.section || currentSection,
                element_key: element.dataset.urlTrack,
                target_url: element.href,
            });
        });
    });

    document.querySelectorAll('[data-secret-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.secretToggle);
            if (!target) return;

            const hidden = target.dataset.hidden === '1';
            target.textContent = hidden ? target.dataset.secret : '••••••••';
            target.dataset.hidden = hidden ? '0' : '1';
            button.textContent = hidden ? 'Hide' : 'Show';

            if (hidden) {
                sendEvent('credential_revealed', {
                    section_key: 'demo',
                    element_key: button.dataset.copyKey || 'password',
                });
            }
        });
    });

    document.querySelectorAll('[data-copy-value]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copyValue);
                sendEvent('credential_copied', {
                    section_key: 'demo',
                    element_key: button.dataset.copyKey || 'credential',
                });

                const previousLabel = button.textContent;
                button.textContent = 'Copied';
                setTimeout(() => { button.textContent = previousLabel; }, 1000);
            } catch (_) {
                // Clipboard can be blocked by the browser. The page remains usable.
            }
        });
    });

    const recordedDepths = new Set();
    window.addEventListener('scroll', () => {
        const documentHeight = document.documentElement.scrollHeight;
        if (!documentHeight) return;

        const depth = Math.round(
            ((window.scrollY + window.innerHeight) / documentHeight) * 100,
        );

        [25, 50, 75, 100].forEach((threshold) => {
            if (depth >= threshold && !recordedDepths.has(threshold)) {
                recordedDepths.add(threshold);
                sendEvent('scroll_depth', {meta: {scroll_depth: threshold}});
            }
        });
    }, {passive: true});

    document.querySelectorAll('video[data-video-track]').forEach((video) => {
        const recordedProgress = new Set();

        video.addEventListener('play', () => {
            sendEvent('video_started', {
                section_key: 'media',
                element_key: video.dataset.videoTrack,
            });
        });

        video.addEventListener('timeupdate', () => {
            if (!video.duration) return;

            const percent = Math.floor((video.currentTime / video.duration) * 100);
            [25, 50, 75, 100].forEach((threshold) => {
                if (percent >= threshold && !recordedProgress.has(threshold)) {
                    recordedProgress.add(threshold);
                    sendEvent('video_progress', {
                        section_key: 'media',
                        element_key: video.dataset.videoTrack,
                        meta: {video_percent: threshold},
                    });
                }
            });
        });
    });

    window.setInterval(() => {
        if (pageVisible) {
            sendEvent('heartbeat', {section_key: currentSection});
        }
    }, 20000);
})();
