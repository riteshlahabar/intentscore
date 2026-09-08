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

foreach (['portal_url', 'worker_token', 'instagram_session_id'] as $required) {
    if (trim((string) ($config[$required] ?? '')) === '') {
        exit("config.php is missing a value for '{$required}'.\n");
    }
}

const ENDPOINT = 'https://www.instagram.com/api/v1/users/web_profile_info/';
const WEB_APP_ID = '936619743392459';
const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

/** Instagram throttles a burst, so profiles are spaced out rather than fired together. */
const GAP_BETWEEN_PROFILES = 20;

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

    try {
        $user = instagramProfile($config, $username);
    } catch (TransientError $e) {
        say('  skipped: '.$e->getMessage().' (stays queued, will retry next run)');

        return;
    } catch (Throwable $e) {
        say('  failed: '.$e->getMessage());
        postResult($config, ['id' => $id, 'error' => $e->getMessage()]);

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
            'Referer: https://www.instagram.com/'.$username.'/',
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
        throw new RuntimeException('The Instagram session has expired. Copy a fresh sessionid into config.php.');
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
    $session = trim((string) $config['instagram_session_id']);

    if ($session === '') {
        throw new RuntimeException('instagram_session_id is empty in config.php.');
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
