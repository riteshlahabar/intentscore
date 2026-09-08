<?php

namespace App\Services\SmartLink\Instagram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Reads a public profile from the same endpoint as WebProfileSource, but signed in.
 *
 * Instagram did not remove public profile data - it removed anonymous access to it, and
 * answers every unauthenticated call with {"require_login":true}. One logged-in session
 * cookie satisfies that, which is what makes this work from shared hosting where the
 * anonymous call is refused outright: the request is accepted on the strength of the
 * session rather than the server's IP address.
 *
 * The cookie belongs in INSTAGRAM_SESSION_ID and nowhere else - it is a live login, so
 * it should come from a throwaway Instagram account kept for this, never from the
 * company's real one, and it stops working when that account logs out or changes its
 * password. Instagram will also throttle an account that is queried too hard, so this
 * is meant for a salesperson auditing prospects by hand, not for bulk runs.
 */
class SessionCookieSource implements ProfileSource
{
    private const ENDPOINT = 'https://www.instagram.com/api/v1/users/web_profile_info/';

    private const WEB_APP_ID = '936619743392459';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    public function profile(string $username): array
    {
        $session = trim((string) config('services.instagram.session_id'));

        if ($session === '') {
            throw new RuntimeException('No Instagram session is configured. Add INSTAGRAM_SESSION_ID to your .env to enable Instagram audits.');
        }

        $cookies = $this->cookies($session);

        /*
         * Instagram answers a signed-in request that does not look like the web app with
         * 429 rather than an error that explains itself, so one retry covers a genuine
         * burst without hiding a request that is simply shaped wrong.
         */
        foreach ([0, 4] as $attempt => $wait) {
            if ($wait > 0) {
                sleep($wait);
            }

            $response = $this->call($username, $cookies);

            if ($response->status() !== 429) {
                break;
            }
        }

        if ($response->status() === 404) {
            throw new RuntimeException('No Instagram account found for @'.$username.'.');
        }

        /* Signed in, these mean the session is the problem rather than the profile. */
        if (in_array($response->status(), [401, 403], true) || $response->json('require_login')) {
            throw new RuntimeException('The Instagram session has expired or been logged out. Copy a fresh sessionid cookie into INSTAGRAM_SESSION_ID and run php artisan config:clear.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException(
                'Instagram is throttling this account (HTTP 429). Give it 10-15 minutes, and browse Instagram '
                .'normally in a browser with the same account once - a session that has never been used from a '
                .'browser is throttled hardest. If it keeps happening, copy a fresh sessionid.'
            );
        }

        if ($response->failed()) {
            throw new RuntimeException('Instagram request failed: HTTP '.$response->status().'.');
        }

        $user = $response->json('data.user');

        if (! is_array($user)) {
            throw new RuntimeException('Instagram returned no profile data for @'.$username.'.');
        }

        return $user;
    }

    private function call(string $username, array $cookies): Response
    {
        /*
         * These are the headers instagram.com sends on this exact request. The x-asbd-id
         * and x-ig-www-claim pair in particular is what marks a call as coming from the
         * web app - without them a valid session still draws a throttle. Empty values are
         * stripped because sending an empty x-csrftoken is worse than sending none.
         */
        $headers = array_filter([
            'x-ig-app-id' => self::WEB_APP_ID,
            'x-asbd-id' => '129477',
            'x-ig-www-claim' => '0',
            'x-csrftoken' => $cookies['csrftoken'] ?? '',
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => self::USER_AGENT,
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => 'https://www.instagram.com/'.$username.'/',
            'Origin' => 'https://www.instagram.com',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Cookie' => $this->header($cookies),
        ], fn ($value) => $value !== '');

        try {
            return Http::withHeaders($headers)->timeout(30)->get(self::ENDPOINT, ['username' => $username]);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reach Instagram: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Builds the cookie jar from whatever was pasted into .env - either the whole cookie
     * header copied out of devtools, or just the sessionid value.
     *
     * ds_user_id is not guessed: a sessionid is "<user id>%3A<token>%3A...", so the
     * account id is already in it, and sending the two together is what a browser does.
     *
     * @return array<string,string>
     */
    private function cookies(string $session): array
    {
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

        $cookies['csrftoken'] ??= trim((string) config('services.instagram.csrf_token'));
        $cookies['ds_user_id'] ??= explode(':', urldecode($cookies['sessionid']))[0] ?? '';

        return array_filter($cookies, fn ($value) => $value !== '');
    }

    /** @param array<string,string> $cookies */
    private function header(array $cookies): string
    {
        return implode('; ', array_map(
            fn ($name, $value) => $name.'='.$value,
            array_keys($cookies),
            $cookies
        ));
    }
}
