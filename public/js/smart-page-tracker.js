/*
 * Smart Page tracker.
 *
 * Loaded automatically on every Smart Page, so a salesperson never has to add a
 * tracking code (scope document, section 17). Sends the event set from section 8.
 */
(function () {
    'use strict';

    var root = document.getElementById('smart-page');
    if (!root) {
        return;
    }

    var endpoint = root.dataset.trackUrl;
    var token = document.querySelector('meta[name="csrf-token"]');
    var VISITOR_KEY = 'smart_page_visitor';

    function uuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function visitorId() {
        try {
            var stored = localStorage.getItem(VISITOR_KEY);
            if (!stored) {
                stored = uuid();
                localStorage.setItem(VISITOR_KEY, stored);
            }
            return stored;
        } catch (e) {
            // Private browsing or blocked storage: the visit still counts, it just
            // cannot be recognised as a return visit.
            return null;
        }
    }

    var sessionId = uuid();
    var visitor = visitorId();

    function send(eventType, payload) {
        var body = Object.assign(
            { session_id: sessionId, visitor_id: visitor, event_type: eventType },
            payload || {}
        );

        // sendBeacon cannot set the CSRF header, so every event goes through fetch
        // with keepalive, which still completes while the page is being closed.
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token ? token.content : ''
            },
            body: json,
            keepalive: true
        }).catch(function () {});
    }

    send('page_opened');

    /* ---- Section views and time spent ---------------------------------- */

    var seenSections = {};
    var sectionEnteredAt = {};

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    var type = entry.target.dataset.section;
                    if (!type) {
                        return;
                    }

                    if (entry.isIntersecting) {
                        sectionEnteredAt[type] = Date.now();

                        if (!seenSections[type]) {
                            seenSections[type] = true;
                            send('section_viewed', { section_type: type });
                        }
                    } else if (sectionEnteredAt[type]) {
                        var ms = Date.now() - sectionEnteredAt[type];
                        delete sectionEnteredAt[type];

                        if (ms > 2000) {
                            send('time_spent', { section_type: type, duration_ms: ms });
                        }
                    }
                });
            },
            { threshold: 0.4 }
        );

        document.querySelectorAll('[data-section]').forEach(function (el) {
            observer.observe(el);
        });
    }

    /* ---- Clicks: sections, CTAs, contact -------------------------------- */

    document.addEventListener('click', function (event) {
        var el = event.target.closest('[data-track]');
        if (!el) {
            return;
        }

        send(el.dataset.track, {
            section_type: el.dataset.section || null,
            metadata: { label: el.dataset.label || el.textContent.trim().slice(0, 80) }
        });
    });

    /* ---- Calculators ---------------------------------------------------- */

    var openedTools = {};

    document.querySelectorAll('[data-tool]').forEach(function (form) {
        var tool = form.dataset.tool;

        form.addEventListener('focusin', function () {
            if (!openedTools[tool]) {
                openedTools[tool] = true;
                send('calculator_opened', { section_type: 'free_tools', metadata: { tool: tool } });
            }
        });
    });

    window.smartPageCalculated = function (tool, result) {
        send('calculator_completed', {
            section_type: 'free_tools',
            metadata: { tool: tool, result: String(result).slice(0, 120) }
        });
        send('result_viewed', { section_type: 'free_tools', metadata: { tool: tool } });
    };

    /* ---- Total time on page --------------------------------------------- */

    var openedAt = Date.now();
    var reported = false;

    function reportTotalTime() {
        if (reported) {
            return;
        }
        reported = true;
        send('time_spent', { duration_ms: Date.now() - openedAt });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            reportTotalTime();
        }
    });

    window.addEventListener('pagehide', reportTotalTime);
})();
