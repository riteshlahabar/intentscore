<?php

/**
 * Copy this file to config.php and fill in the four values.
 *
 * config.php holds a live Instagram login, so it stays on this PC: it is gitignored and
 * must never be committed or copied onto the server.
 */

return [
    // Where the portal lives, e.g. https://portal.yourdomain.com
    'portal_url' => 'https://your-portal-domain.com',

    // Must be identical to INSTAGRAM_WORKER_TOKEN in the portal's .env.
    // Generate a long random one, for example with: php -r "echo bin2hex(random_bytes(32));"
    'worker_token' => '',

    /*
     * The sessionid cookie of a logged-in Instagram account.
     *
     * Chrome: log in to Instagram, press F12, Application tab, Cookies,
     * https://www.instagram.com, copy the value of the "sessionid" row. Pasting the whole
     * cookie header works too and is slightly more reliable, because it carries csrftoken
     * and mid along with it.
     *
     * Use a throwaway account, never the company one - Instagram may restrict an account
     * that is queried this way, and the cookie stops working when that account logs out
     * or changes its password.
     */
    'instagram_session_id' => '',

    // How often --loop mode checks the portal for queued audits.
    'poll_seconds' => 300,
];
