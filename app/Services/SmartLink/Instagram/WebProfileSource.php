<?php

namespace App\Services\SmartLink\Instagram;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Reads a profile straight from the endpoint instagram.com's own web app uses.
 *
 * This needs no key and no Meta app, but Instagram has rolled out mandatory login on it:
 * as of the last check every unauthenticated call answers HTTP 401 with
 * {"message":"Please wait a few minutes before you try again.","require_login":true,
 * "igweb_rollout":true} - from residential connections as well as hosting, so it is not
 * only an IP block. The same is true of the profile HTML, /embed/ and the oembed URL,
 * which all return the login shell with no counts in it.
 *
 * It is kept because it costs nothing to try and Instagram has reopened guest access
 * before, but a paid ProfileSource is what makes the feature work today.
 */
class WebProfileSource implements ProfileSource
{
    private const ENDPOINT = 'https://www.instagram.com/api/v1/users/web_profile_info/';

    /** Public web app id. Instagram returns a login wall without it. */
    private const WEB_APP_ID = '936619743392459';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    public function profile(string $username): array
    {
        try {
            $response = Http::withHeaders([
                'x-ig-app-id' => self::WEB_APP_ID,
                'User-Agent' => self::USER_AGENT,
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://www.instagram.com/'.$username.'/',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->timeout(30)->get(self::ENDPOINT, ['username' => $username]);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not reach Instagram: '.$e->getMessage(), 0, $e);
        }

        if ($response->status() === 404) {
            throw new RuntimeException('No public Instagram account found for @'.$username.'.');
        }

        /*
         * 401 and 403 are the login wall, and no amount of retrying clears them: the
         * anonymous endpoint has been closed and only a session cookie gets past it.
         */
        if (in_array($response->status(), [401, 403], true) || $response->json('require_login')) {
            throw new RuntimeException(
                'Instagram no longer serves profile data to anonymous requests (HTTP '.$response->status().'), '
                .'so retrying will not help. Add INSTAGRAM_SESSION_ID to your .env to read profiles through a '
                .'logged-in Instagram session instead.'
            );
        }

        /*
         * 429 is a throttle rather than the wall, and it does clear - but waiting it out
         * only earns a 401, because the anonymous endpoint is closed either way. Saying
         * "try again later" here would be true and still useless, so it points at the
         * one thing that changes the outcome.
         */
        if ($response->status() === 429) {
            throw new RuntimeException(
                'Instagram is throttling this server (HTTP 429), and once that clears the anonymous endpoint '
                .'answers with a login wall anyway. Set INSTAGRAM_SESSION_ID in your .env - without it this '
                .'button cannot return data.'
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
}
