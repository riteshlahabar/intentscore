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
         * 401, 403 and 429 are one refusal wearing three hats: Instagram serves the same
         * "require_login" body with whichever code the edge decides on, and a host that
         * gets 429 keeps getting it. Retrying does not help, so they share one message -
         * telling a salesperson to "try again later" would just send them back to a
         * button that cannot work.
         */
        if (in_array($response->status(), [401, 403, 429], true) || $response->json('require_login')) {
            throw new RuntimeException(
                'Instagram no longer serves public profile data without a login (HTTP '.$response->status().'), '
                .'so this will not succeed on a retry. A provider has to be configured: register it in '
                .'config/services.php under instagram.sources and point INSTAGRAM_SOURCE at it.'
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
