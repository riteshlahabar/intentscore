<?php

/**
 * Copy this file to config.php and fill in the two values.
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
     * Leave this empty. The Instagram cookie is pasted in the portal under
     * Settings > Instagram Session and this script reads it from there, so it is kept in
     * one place instead of two. Filling it in here overrides the portal's copy, which is
     * only useful for testing a different account.
     */
    'instagram_session_id' => '',

    /*
     * How often --loop mode asks the portal for queued audits. This only talks to the
     * portal, never to Instagram, so a short interval does not increase the load on the
     * Instagram account - it only makes a clicked audit start sooner.
     */
    'poll_seconds' => 10,
];
