<?php

namespace App\Services\SmartLink\Instagram;

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

        $csrf = trim((string) config('services.instagram.csrf_token'));

        try {
            $response = Http::withHeaders([
                'x-ig-app-id' => self::WEB_APP_ID,
                'User-Agent' => self::USER_AGENT,
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://www.instagram.com/'.$username.'/',
                'X-Requested-With' => 'XMLHttpRequest',
                'x-csrftoken' => $csrf,
                'Cookie' => $this->cookie($session, $csrf),
            ])->timeout(30)->get(self::ENDPOINT, ['username' => $username]);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reach Instagram: '.$e->getMessage(), 0, $e);
        }

        if ($response->status() === 404) {
            throw new RuntimeException('No Instagram account found for @'.$username.'.');
        }

        /*
         * Signed in, these codes mean the session itself is the problem rather than the
         * profile: it has expired, been logged out, or been throttled by Instagram.
         */
        if (in_array($response->status(), [401, 403], true) || $response->json('require_login')) {
            throw new RuntimeException('The Instagram session has expired or been logged out. Refresh INSTAGRAM_SESSION_ID in your .env.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Instagram is throttling this Instagram account. Wait a few minutes before auditing another profile.');
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

    /** Accepts either the bare sessionid value or a whole copied cookie header. */
    private function cookie(string $session, string $csrf): string
    {
        if (str_contains($session, 'sessionid=')) {
            return $session;
        }

        return implode('; ', array_filter([
            'sessionid='.$session,
            $csrf === '' ? null : 'csrftoken='.$csrf,
        ]));
    }
}
