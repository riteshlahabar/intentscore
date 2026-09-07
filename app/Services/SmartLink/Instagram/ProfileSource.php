<?php

namespace App\Services\SmartLink\Instagram;

use RuntimeException;

/**
 * Where a public Instagram profile is read from.
 *
 * Instagram closed its unauthenticated web endpoints (they now answer
 * {"require_login":true}), so the working source is a paid provider. Every source
 * returns the same shape - Instagram's own `data.user` object - which keeps the scoring
 * in InstagramProfileService untouched when the provider changes: a new provider is one
 * new class here plus a line in config/services.php.
 */
interface ProfileSource
{
    /**
     * @return array<string,mixed> Instagram's `data.user` shape: full_name, biography,
     *                             category_name, external_url, business_address_json,
     *                             profile_pic_url_hd, is_verified, is_business_account,
     *                             is_private, edge_followed_by.count, edge_follow.count,
     *                             edge_owner_to_timeline_media.{count,edges}.
     *
     * @throws RuntimeException When the profile cannot be read, with a message meant for
     *                          the salesperson looking at the prospect screen.
     */
    public function profile(string $username): array;
}
