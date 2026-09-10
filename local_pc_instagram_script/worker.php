<?php

/**
 * IntentScore Instagram worker - runs on a normal PC, not on the server.
 *
 * Instagram refuses the portal's shared-hosting IP no matter what cookie it sends, so
 * the portal only queues audits and this script does the fetching from an ordinary home
 * or office connection, which Instagram still answers.
 *
 * Everything here dials out: it asks the portal for queued work, fetches each profile
 * from Instagram, and posts the result back. Nothing has to reach this PC, so it works
 * behind a router with no port forwarding and no fixed IP.
 *
 * Run it with:  php worker.php          (one pass, which is what Task Scheduler uses)
 *               php worker.php --loop   (keeps running, checking every POLL_SECONDS)
 */

if (! file_exists(__DIR__.'/config.php')) {
    exit("config.php is missing. Copy config.example.php to config.php and fill it in.\n");
}

$config = require __DIR__.'/config.php';

foreach (['portal_url', 'worker_token'] as $required) {
    if (trim((string) ($config[$required] ?? '')) === '') {
        exit("config.php is missing a value for '{$required}'.\n");
    }
}

/*
 * The Instagram cookie is pasted in the portal under Settings > Instagram Session and
 * read from there, so refreshing it is one edit in one place rather than an edit here as
 * well. A value left in config.php still wins, which keeps an older install working.
 */
$config['poll_seconds'] = (int) ($config['poll_seconds'] ?? 10) ?: 10;

const ENDPOINT = 'https://www.instagram.com/api/v1/users/web_profile_info/';
const WEB_APP_ID = '936619743392459';
const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

/** Instagram throttles a burst, so profiles are spaced out rather than fired together. */
const GAP_BETWEEN_PROFILES = 20;

/*
 * --check <username> fetches one profile and prints what came back without touching the
 * portal or the queue, which is the quickest way to tell an Instagram problem apart from
 * a portal problem when something is not working.
 */
if (($checkAt = array_search('--check', $argv, true)) !== false) {
    check($config, (string) ($argv[$checkAt + 1] ?? ''));

    exit;
}

$loop = in_array('--loop', $argv, true);

do {
    try {
        runOnce($config);
    } catch (Throwable $e) {
        say('ERROR  '.$e->getMessage());
    }

    if ($loop) {
        sleep((int) $config['poll_seconds']);
    }
} while ($loop);

/** Prints what Instagram gives back for one profile, and which route it came from. */
function check(array $config, string $username): void
{
    if ($username === '') {
        exit("Usage: php worker.php --check <username>\n");
    }

    say("checking @{$username}");

    $route = 'profile page';

    /*
     * ENGAGEMENT FETCH DISABLED.
     *
     * instagramProfile() calls Instagram's JSON endpoint, which is the only source of
     * per-post likes and comments - and therefore of the engagement and consistency
     * scores. Instagram answers it with HTTP 429 for this account, so every audit spent
     * a blocked request before falling back anyway. Reading the profile page directly
     * skips that, and profile information is never blocked.
     *
     * To bring engagement scoring back, restore the commented block below.
     */
    // try {
    //     $user = instagramProfile($config, $username);
    // } catch (TransientError $e) {
    //     say('  API: '.$e->getMessage());
    //     say('  trying the profile page instead …');
    //     $route = 'profile page';
    //     $user = profileFromPage($username);
    // }

    try {
        $user = profileFromPage($username);
    } catch (Throwable $e) {
        say('  FAILED: '.$e->getMessage());

        return;
    }

    $posts = $user['edge_owner_to_timeline_media']['edges'] ?? [];

    say("  OK - read via {$route}");

    foreach ([
        'name' => $user['full_name'] ?? null,
        'followers' => $user['edge_followed_by']['count'] ?? null,
        'following' => $user['edge_follow']['count'] ?? null,
        'posts' => $user['edge_owner_to_timeline_media']['count'] ?? null,
        'category' => $user['category_name'] ?? null,
        'website' => $user['external_url'] ?? null,
        'address' => $user['business_address_json'] ?? null,
        'bio' => isset($user['biography']) ? substr((string) $user['biography'], 0, 60) : null,
        'recent posts read' => count($posts).($posts === [] ? ' (no engagement data)' : ''),
        'profile picture' => ($user['profile_pic_url_hd'] ?? $user['profile_pic_url'] ?? null) ? 'yes' : 'no',
    ] as $label => $value) {
        say('    '.str_pad($label, 18).': '.($value === null || $value === '' ? '-' : $value));
    }
}

function runOnce(array $config): void
{
    $jobs = fetchQueue($config);

    if ($jobs === []) {
        say('nothing queued');

        return;
    }

    say(count($jobs).' queued');

    foreach ($jobs as $i => $job) {
        if ($i > 0) {
            sleep(GAP_BETWEEN_PROFILES);
        }

        handle($config, (int) $job['id'], (string) $job['username']);
    }
}

/**
 * A problem that is expected to pass on its own, such as Instagram throttling. The job
 * is deliberately left queued rather than reported as failed, so the next run picks it
 * up again instead of a salesperson having to re-enter the link.
 */
class TransientError extends RuntimeException
{
}

function handle(array $config, int $id, string $username): void
{
    say("  @{$username} …");

    /*
     * ENGAGEMENT FETCH DISABLED - see the note in checkOne(). The JSON endpoint is the
     * only source of per-post engagement and Instagram returns 429 for it, so the
     * profile page is read directly. The counts and the profile arrive; engagement and
     * consistency stay unscored.
     */
    // try {
    //     $user = instagramProfile($config, $username);
    // } catch (TransientError $e) {
    //     say('  '.$e->getMessage().' - reading the profile page instead');
    //     $user = profileFromPage($username);
    //     say('  page fallback worked (no per-post engagement data)');
    // }

    try {
        $user = profileFromPage($username);
        say('  profile info read (engagement fetch is switched off)');
    } catch (Throwable $e) {
        say('  skipped: '.$e->getMessage().' (stays queued, will retry next run)');

        return;
    }

    postResult($config, [
        'id' => $id,
        'user' => $user,
        'profile_pic' => picture($user['profile_pic_url_hd'] ?? $user['profile_pic_url'] ?? null),
    ]);

    say('  done: '.($user['edge_followed_by']['count'] ?? '?').' followers');
}

/** @return array<string,mixed> Instagram's data.user object. */
function instagramProfile(array $config, string $username): array
{
    $cookies = cookieJar($config);

    [$status, $body] = request(
        ENDPOINT.'?username='.urlencode($username),
        [
            'x-ig-app-id: '.WEB_APP_ID,
            'x-asbd-id: 129477',
            'x-ig-www-claim: 0',
            'x-requested-with: XMLHttpRequest',
            'User-Agent: '.USER_AGENT,
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.9',
            "Referer: https://www.instagram.com/{$username}/",
            'Origin: https://www.instagram.com',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
            isset($cookies['csrftoken']) ? 'x-csrftoken: '.$cookies['csrftoken'] : null,
            'Cookie: '.cookieHeader($cookies),
        ]
    );

    if ($status === 404) {
        throw new RuntimeException("No Instagram account found for @{$username}.");
    }

    if ($status === 401 || $status === 403) {
        throw new RuntimeException('The Instagram session has expired. Paste a fresh cookie in the portal under Settings > Instagram Session.');
    }

    if ($status === 429) {
        throw new TransientError('Instagram is throttling this account (HTTP 429).');
    }

    if ($status === 0) {
        throw new TransientError('No reply from Instagram - check this PC\'s internet.');
    }

    if ($status !== 200) {
        throw new RuntimeException("Instagram returned HTTP {$status}.");
    }

    $user = json_decode($body, true)['data']['user'] ?? null;

    if (! is_array($user)) {
        throw new RuntimeException('Instagram returned no profile data.');
    }

    return $user;
}

/**
 * Builds the same data.user shape out of the profile page's meta tags, for when the JSON
 * endpoint is throttled but the page still renders.
 *
 * Instagram writes the whole summary into one meta description:
 *   "2,988 Followers, 7,492 Following, 69 Posts - TurnKey Infotech (@handle) on Instagram: "bio""
 *
 * Category, website, address and per-post engagement are not on the page - they arrive
 * through the very API call that was refused - so those stay empty and the portal scores
 * what it has.
 *
 * @return array<string,mixed>
 */
function profileFromPage(string $username): array
{
    /*
     * These headers are the whole trick. Instagram serves a bare app shell with no meta
     * tags to anything that does not look like a browser opening a page, and the full
     * navigation set - Upgrade-Insecure-Requests, the Sec-Fetch-* quartet and the sec-ch-ua
     * hints - is what makes it render the real profile. It works signed out as well, so no
     * cookie is sent: this route needs no Instagram account at all.
     */
    [$status, $html] = request(
        "https://www.instagram.com/{$username}/",
        [
            'User-Agent: '.USER_AGENT,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-User: ?1',
            'Sec-Fetch-Dest: document',
            'sec-ch-ua: "Chromium";v="126", "Google Chrome";v="126", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ]
    );

    if ($status !== 200) {
        throw new TransientError("The profile page also returned HTTP {$status}.");
    }

    /*
     * Both meta tags carry the counts, but only name="description" carries the bio, so it
     * is tried first; og:description says "See Instagram photos and videos from X" where
     * the other says the account's real name.
     */
    $summary = metaContent($html, 'name="description"') ?? metaContent($html, 'property="og:description"');

    if ($summary === null) {
        throw new TransientError('The profile page did not include the profile summary.');
    }

    if (! preg_match('/^([\d.,KMkm]+)\s+Followers,\s+([\d.,KMkm]+)\s+Following,\s+([\d.,KMkm]+)\s+Posts\s*[-–]\s*(.*?)\s*\(@/u', $summary, $m)) {
        throw new TransientError('Could not read the counts from the profile page.');
    }

    preg_match('/on Instagram:\s*"(.*)"\s*$/us', $summary, $bio);

    /* og:title is "<name> (@handle) • Instagram photos and videos" - the cleanest name. */
    $title = metaContent($html, 'property="og:title"') ?? '';
    $name = preg_match('/^(.*?)\s*\(@/u', $title, $t)
        ? $t[1]
        : preg_replace('/^See Instagram photos and videos from\s+/iu', '', $m[4]);

    return [
        'full_name' => trim((string) $name) !== '' ? trim((string) $name) : null,
        'biography' => isset($bio[1]) ? trim($bio[1]) : null,
        'category_name' => null,
        'external_url' => null,
        'business_address_json' => null,
        'profile_pic_url_hd' => metaContent($html, 'property="og:image"'),
        'is_verified' => false,
        'is_business_account' => false,
        'is_private' => false,
        'edge_followed_by' => ['count' => compactNumber($m[1])],
        'edge_follow' => ['count' => compactNumber($m[2])],
        'edge_owner_to_timeline_media' => ['count' => compactNumber($m[3]), 'edges' => []],
    ];
}

/**
 * Reads one meta tag's content, with the entities Instagram escapes into it decoded.
 *
 * The tag is located first and the content read out of it second, because Instagram is
 * not consistent about attribute order - og:* tags put property before content, while the
 * plain description tag writes content="…" name="description", and a single pattern that
 * assumes one order silently misses the other.
 */
function metaContent(string $html, string $attribute): ?string
{
    if (! preg_match('/<meta[^>]*'.preg_quote($attribute, '/').'[^>]*>/i', $html, $tag)) {
        return null;
    }

    if (! preg_match('/content="([^"]*)"/i', $tag[0], $m)) {
        return null;
    }

    $content = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return trim($content) === '' ? null : $content;
}

/** Instagram shortens large counts in the meta tag, so "1.2M" has to become 1200000. */
function compactNumber(string $value): int
{
    $number = (float) str_replace(',', '', rtrim($value, 'KMkm'));

    return (int) round($number * match (strtoupper(substr($value, -1))) {
        'K' => 1000,
        'M' => 1000000,
        default => 1,
    });
}

/**
 * The picture is downloaded here rather than on the server, because the server's IP is
 * the thing Instagram is refusing in the first place.
 */
function picture(?string $url): ?string
{
    if (! $url) {
        return null;
    }

    [$status, $body] = request($url, ['User-Agent: '.USER_AGENT]);

    if ($status !== 200 || strlen($body) > 400000) {
        return null;
    }

    return 'data:image/jpeg;base64,'.base64_encode($body);
}

/** @return array<int,array<string,mixed>> */
function fetchQueue(array $config): array
{
    [$status, $body] = request(
        rtrim($config['portal_url'], '/').'/api/instagram/pending',
        ['X-Worker-Token: '.$config['worker_token'], 'Accept: application/json']
    );

    /* Status 0 means the request never got a reply - wrong portal_url, or no internet. */
    if ($status === 0) {
        throw new RuntimeException('Could not reach the portal at '.$config['portal_url'].'. Check portal_url in config.php and this PC\'s internet.');
    }

    if ($status === 401) {
        throw new RuntimeException('Portal rejected the worker token. Check INSTAGRAM_WORKER_TOKEN matches config.php.');
    }

    if ($status !== 200) {
        throw new RuntimeException("Portal returned HTTP {$status} when asking for queued audits.");
    }

    return json_decode($body, true)['jobs'] ?? [];
}

/**
 * Reads the Instagram cookie the portal holds. It is asked for once per run and kept in
 * memory, so a pass over ten queued profiles does not fetch the same cookie ten times.
 */
function portalSession(array $config): string
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    [$status, $body] = request(
        rtrim($config['portal_url'], '/').'/api/instagram/session',
        ['X-Worker-Token: '.$config['worker_token'], 'Accept: application/json']
    );

    if ($status === 401) {
        throw new RuntimeException('Portal rejected the worker token. Check INSTAGRAM_WORKER_TOKEN matches config.php.');
    }

    if ($status !== 200) {
        throw new RuntimeException("Portal returned HTTP {$status} when asking for the Instagram session.");
    }

    return $cached = trim((string) (json_decode($body, true)['session'] ?? ''));
}

function postResult(array $config, array $payload): void
{
    [$status, $body] = request(
        rtrim($config['portal_url'], '/').'/api/instagram/result',
        ['X-Worker-Token: '.$config['worker_token'], 'Content-Type: application/json', 'Accept: application/json'],
        json_encode($payload)
    );

    if ($status !== 200) {
        say("  portal rejected the result (HTTP {$status}): ".substr($body, 0, 200));
    }
}

/**
 * Kept on stream wrappers rather than cURL: plenty of Windows PHP builds ship without
 * the cURL extension enabled, and this has to run on whatever PHP the office PC has.
 *
 * @param  array<int,?string>  $headers
 * @return array{0:int,1:string}
 */
function request(string $url, array $headers, ?string $body = null): array
{
    $options = [
        'method' => $body === null ? 'GET' : 'POST',
        'header' => implode("\r\n", array_filter($headers)),
        'timeout' => 30,
        'ignore_errors' => true,
    ];

    if ($body !== null) {
        $options['content'] = $body;
    }

    $response = @file_get_contents($url, false, stream_context_create(['http' => $options]));
    $status = 0;

    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $m)) {
            $status = (int) $m[1];
        }
    }

    return [$status, (string) $response];
}

/**
 * Accepts either the whole cookie header copied out of devtools or just the sessionid.
 * ds_user_id is not guessed - a sessionid is "<user id>%3A<token>%3A...", so the account
 * id is already inside it, and a browser always sends the two together.
 *
 * @return array<string,string>
 */
function cookieJar(array $config): array
{
    $session = trim((string) ($config['instagram_session_id'] ?? ''));

    if ($session === '') {
        $session = portalSession($config);
    }

    if ($session === '') {
        throw new RuntimeException('No Instagram session is saved in the portal. Open Settings > Instagram Session and paste the cookie there.');
    }

    $cookies = [];

    if (str_contains($session, 'sessionid=')) {
        foreach (explode(';', $session) as $pair) {
            [$name, $value] = array_pad(explode('=', trim($pair), 2), 2, '');

            if ($name !== '' && $value !== '') {
                $cookies[trim($name)] = trim($value);
            }
        }
    } else {
        $cookies['sessionid'] = $session;
    }

    $cookies['ds_user_id'] ??= explode(':', urldecode($cookies['sessionid']))[0] ?? '';

    return array_filter($cookies, fn ($value) => $value !== '');
}

/** @param array<string,string> $cookies */
function cookieHeader(array $cookies): string
{
    return implode('; ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($cookies), $cookies));
}

function say(string $message): void
{
    echo date('Y-m-d H:i:s').'  '.$message.PHP_EOL;
}
