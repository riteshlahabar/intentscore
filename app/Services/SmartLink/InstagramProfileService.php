<?php

namespace App\Services\SmartLink;

use App\Services\SmartLink\Instagram\ProfileSource;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Turns a pasted Instagram link into an audit row for a prospect: the same numbers a
 * visitor sees on instagram.com/<username>, plus four scores over them.
 *
 * Where the profile is read from is the ProfileSource's job, so this class holds only
 * the parts that never change with the provider - reading the username out of whatever
 * the salesperson pasted, and scoring the profile.
 */
class InstagramProfileService
{
    /** Profile paths that are not accounts, so a pasted post link fails clearly. */
    private const RESERVED = ['p', 'reel', 'reels', 'tv', 'stories', 'explore', 'accounts', 'directory'];

    /** Stored inline like the PageSpeed screenshot; anything larger is dropped. */
    private const MAX_PIC_BYTES = 400000;

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    public function __construct(private ProfileSource $source)
    {
    }

    /**
     * A failed read is returned as a row rather than thrown, exactly like a failed
     * PageSpeed run, so the attempt and its reason stay visible on the prospect screen.
     *
     * @return array<string,mixed> Fields ready to fill an InstagramAudit row.
     */
    public function audit(string $link): array
    {
        try {
            $username = $this->username($link);
        } catch (Throwable $e) {
            return [
                'username' => '',
                'profile_url' => $link,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ];
        }

        $base = [
            'username' => $username,
            'profile_url' => 'https://www.instagram.com/'.$username.'/',
        ];

        try {
            return $base + $this->fields($this->source->profile($username));
        } catch (Throwable $e) {
            return $base + ['status' => 'failed', 'error_message' => $e->getMessage()];
        }
    }

    /**
     * Scores a profile that was fetched somewhere else - by the local worker PC, whose
     * home connection Instagram still answers - so a pushed result is read by exactly the
     * same code as a result this server fetched itself.
     *
     * The worker sends the picture it already downloaded, because re-fetching it from the
     * blocked server would only lose it.
     *
     * @param  array<string,mixed>  $user  Instagram's `data.user` object.
     * @return array<string,mixed>
     */
    public function rowFromProfile(string $username, array $user, ?string $picture = null): array
    {
        return [
            'username' => $username,
            'profile_url' => 'https://www.instagram.com/'.$username.'/',
        ] + $this->fields($user, $picture);
    }

    /**
     * Accepts anything a salesperson is likely to paste: a full profile URL with or
     * without protocol or query string, an @handle, or the bare username.
     */
    public function username(string $link): string
    {
        $value = trim($link);

        if ($value === '') {
            throw new RuntimeException('Enter an Instagram profile link or username.');
        }

        if (str_contains($value, 'instagram.com')) {
            $path = parse_url(str_starts_with($value, 'http') ? $value : 'https://'.$value, PHP_URL_PATH) ?? '';
            $value = explode('/', trim($path, '/'))[0] ?? '';
        }

        $value = ltrim((string) strtok($value, '?'), '@');

        if ($value === '' || in_array(strtolower($value), self::RESERVED, true)) {
            throw new RuntimeException('That link does not point to an Instagram profile.');
        }

        if (! preg_match('/^[A-Za-z0-9._]{1,30}$/', $value)) {
            throw new RuntimeException('"'.$value.'" is not a valid Instagram username.');
        }

        return strtolower($value);
    }

    /** @return array<string,mixed> */
    private function fields(array $user, ?string $picture = null): array
    {
        $followers = (int) ($user['edge_followed_by']['count'] ?? 0);
        $following = (int) ($user['edge_follow']['count'] ?? 0);
        $posts = $this->recentPosts($user);
        $engagement = $this->engagement($posts, $followers);
        $cadence = $this->cadence($posts);

        // A read that returned no posts is not a complete audit, even though the counts
        // came back. Instagram answers the detailed request with HTTP 429 when it has
        // limited the account, and the worker then falls back to scraping the profile
        // page, which carries follower/following/post counts but no per-post data. That
        // used to be saved as 'completed' with a null error, so a throttled audit was
        // indistinguishable from a healthy one in the database and every engagement
        // figure silently read as a dash.
        //
        // A private account is a different case and stays 'completed': there are no
        // posts to read, the audit is as complete as it will ever be, and re-running it
        // would change nothing.
        $private = (bool) ($user['is_private'] ?? false);
        $limited = ! $private && $posts === [] && $followers > 0;

        $profile = [
            'status' => $limited ? 'partial' : 'completed',
            'error_message' => $limited
                ? 'Instagram limited the detailed request, so only the public counts were read. Engagement and posting consistency need a re-run.'
                : null,
            'full_name' => $user['full_name'] ?: null,
            'category' => $user['category_name'] ?? $user['business_category_name'] ?? null,
            'biography' => $user['biography'] ?: null,
            'external_url' => $user['external_url'] ?: null,
            'business_address' => $this->address($user),
            'profile_pic' => $picture ?: $this->picture($user['profile_pic_url_hd'] ?? $user['profile_pic_url'] ?? null),
            'is_verified' => (bool) ($user['is_verified'] ?? false),
            'is_business' => (bool) ($user['is_business_account'] ?? false),
            'is_private' => (bool) ($user['is_private'] ?? false),
            'followers' => $followers,
            'following' => $following,
            'posts_count' => (int) ($user['edge_owner_to_timeline_media']['count'] ?? 0),
        ];

        return $profile + $engagement + $cadence + [
            'follow_ratio' => round($followers / max($following, 1), 2),
            'profile_score' => $this->profileScore($profile),
            'audience_score' => $this->audienceScore($followers, $following),
            'engagement_score' => $this->engagementScore($engagement['engagement_rate']),
            'consistency_score' => $this->consistencyScore($cadence),
        ];
    }

    /**
     * Instagram ships the last dozen posts alongside the profile, which is what makes an
     * engagement rate possible without a second request. A private account ships none.
     *
     * @return array<int,array<string,int>>
     */
    private function recentPosts(array $user): array
    {
        $posts = [];

        foreach ($user['edge_owner_to_timeline_media']['edges'] ?? [] as $edge) {
            $node = $edge['node'] ?? null;

            if (! is_array($node)) {
                continue;
            }

            $posts[] = [
                'likes' => (int) ($node['edge_liked_by']['count'] ?? $node['edge_media_preview_like']['count'] ?? 0),
                'comments' => (int) ($node['edge_media_to_comment']['count'] ?? 0),
                'at' => (int) ($node['taken_at_timestamp'] ?? 0),
            ];
        }

        return $posts;
    }

    /** @return array<string,mixed> */
    private function engagement(array $posts, int $followers): array
    {
        if ($posts === [] || $followers === 0) {
            return ['avg_likes' => null, 'avg_comments' => null, 'engagement_rate' => null];
        }

        $likes = array_sum(array_column($posts, 'likes')) / count($posts);
        $comments = array_sum(array_column($posts, 'comments')) / count($posts);

        return [
            'avg_likes' => (int) round($likes),
            'avg_comments' => (int) round($comments),
            'engagement_rate' => round(($likes + $comments) / $followers * 100, 2),
        ];
    }

    /**
     * Posting rate is measured across the sample's own span rather than a fixed window,
     * so an account that posted twelve times in a week is not read the same as one that
     * spread the same twelve posts over two years.
     *
     * @return array<string,mixed>
     */
    private function cadence(array $posts): array
    {
        $times = array_filter(array_column($posts, 'at'));

        if ($times === []) {
            return ['posts_per_month' => null, 'days_since_last_post' => null];
        }

        $spanDays = (max($times) - min($times)) / 86400;

        return [
            'posts_per_month' => $spanDays < 1 ? null : round(count($times) / ($spanDays / 30), 1),
            'days_since_last_post' => (int) max(0, floor((time() - max($times)) / 86400)),
        ];
    }

    private function address(array $user): ?string
    {
        $address = json_decode((string) ($user['business_address_json'] ?? ''), true);

        if (! is_array($address)) {
            return null;
        }

        $parts = array_filter([
            $address['street_address'] ?? null,
            $address['city_name'] ?? null,
            $address['zip_code'] ?? null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Instagram's CDN links are signed and expire within days, so the picture is stored
     * inline the way the PageSpeed screenshot is - a saved audit stays viewable later.
     */
    private function picture(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(15)->withHeaders(['User-Agent' => self::USER_AGENT])->get($url);

            if ($response->failed() || strlen($response->body()) > self::MAX_PIC_BYTES) {
                return null;
            }

            $type = $response->header('Content-Type') ?: 'image/jpeg';

            return str_starts_with($type, 'image/')
                ? 'data:'.$type.';base64,'.base64_encode($response->body())
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** Does the profile tell a visitor who this business is and how to reach it? */
    private function profileScore(array $profile): int
    {
        $weights = [
            'profile_pic' => 15,
            'full_name' => 15,
            'biography' => 20,
            'category' => 15,
            'external_url' => 20,
            'business_address' => 15,
        ];

        $score = 0;

        foreach ($weights as $field => $weight) {
            $score += $profile[$field] ? $weight : 0;
        }

        return $score;
    }

    /**
     * Following far more accounts than follow back is the classic follow-for-follow
     * pattern, so the ratio carries most of the weight. Reach counts for something on
     * its own, which stops a large account scoring near zero on ratio alone.
     */
    private function audienceScore(int $followers, int $following): int
    {
        $ratio = $followers / max($following, 1);

        $ratioScore = match (true) {
            $ratio >= 2 => 100.0,
            $ratio >= 1 => 60 + ($ratio - 1) * 40,
            default => $ratio * 60,
        };

        $reachScore = $followers < 1 ? 0.0 : min(100, log10($followers) / log10(50000) * 100);

        return (int) round($ratioScore * 0.7 + $reachScore * 0.3);
    }

    /** 6% of followers interacting is a strong account, so that is treated as full marks. */
    private function engagementScore(?float $rate): ?int
    {
        return $rate === null ? null : (int) round(min(100, $rate / 6 * 100));
    }

    /** Three posts a week is full marks, and a stale feed is capped however good the rate. */
    private function consistencyScore(array $cadence): ?int
    {
        if ($cadence['posts_per_month'] === null) {
            return null;
        }

        $stale = match (true) {
            $cadence['days_since_last_post'] > 90 => 20,
            $cadence['days_since_last_post'] > 30 => 50,
            default => 100,
        };

        return (int) round(min($cadence['posts_per_month'] / 12 * 100, $stale));
    }
}
