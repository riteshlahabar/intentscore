<?php

namespace App\Services\SmartLink;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/** Runs a Google PageSpeed Insights (Lighthouse) audit for a prospect's website. */
class PageSpeedInsightsService
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public const STRATEGIES = ['mobile', 'desktop'];

    /**
     * Runs a mobile and a desktop audit in parallel (one waits on the other otherwise).
     * Heavy pages under PSI's mobile throttling simulation can take well over a minute,
     * so this gives each request a generous timeout rather than PSI's usual 20-40s.
     *
     * @return array<string,array<string,mixed>> keyed by strategy, each ready to fill a WebsiteAudit row.
     */
    public function auditBoth(string $url): array
    {
        $responses = Http::pool(fn (Pool $pool) => collect(self::STRATEGIES)
            ->mapWithKeys(fn ($strategy) => [
                $strategy => $pool->as($strategy)->timeout(170)->get(self::ENDPOINT, $this->params($url, $strategy)),
            ])
            ->all());

        return collect(self::STRATEGIES)
            ->mapWithKeys(fn ($strategy) => [$strategy => $this->parse($responses[$strategy] ?? null, $strategy)])
            ->all();
    }

    private function params(string $url, string $strategy): array
    {
        return array_filter([
            'url' => $url,
            'strategy' => $strategy,
            'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            'key' => config('services.pagespeed.key'),
        ]);
    }

    /** @return array<string,mixed> */
    private function parse(mixed $response, string $strategy): array
    {
        try {
            if ($response instanceof Throwable) {
                throw $response;
            }

            if (! $response instanceof Response || $response->failed()) {
                $message = $response instanceof Response
                    ? ($response->json('error.message') ?? $response->reason())
                    : 'No response from PageSpeed Insights.';
                throw new RuntimeException("PageSpeed Insights request failed: {$message}");
            }

            $result = $response->json('lighthouseResult');

            if (! $result) {
                throw new RuntimeException('PageSpeed Insights returned no report for this URL.');
            }

            $categories = $result['categories'] ?? [];
            $audits = $result['audits'] ?? [];

            return [
                'strategy' => $strategy,
                'status' => 'completed',
                'performance_score' => $this->scoreOf($categories, 'performance'),
                'accessibility_score' => $this->scoreOf($categories, 'accessibility'),
                'best_practices_score' => $this->scoreOf($categories, 'best-practices'),
                'seo_score' => $this->scoreOf($categories, 'seo'),
                'lcp_ms' => $this->metricOf($audits, 'largest-contentful-paint'),
                'fcp_ms' => $this->metricOf($audits, 'first-contentful-paint'),
                'tbt_ms' => $this->metricOf($audits, 'total-blocking-time'),
                'speed_index_ms' => $this->metricOf($audits, 'speed-index'),
                'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                'screenshot' => $audits['final-screenshot']['details']['data'] ?? null,
            ];
        } catch (Throwable $e) {
            return [
                'strategy' => $strategy,
                'status' => 'failed',
                'error_message' => $this->scrub($e->getMessage()),
            ];
        }
    }

    /** Guzzle puts the full request URL in its exception messages, API key included. */
    private function scrub(string $message): string
    {
        return preg_replace('/([?&]key=)[^&\s]+/i', '$1[hidden]', $message);
    }

    private function scoreOf(array $categories, string $key): ?int
    {
        $score = $categories[$key]['score'] ?? null;

        return $score === null ? null : (int) round($score * 100);
    }

    private function metricOf(array $audits, string $key): ?int
    {
        $value = $audits[$key]['numericValue'] ?? null;

        return $value === null ? null : (int) round($value);
    }
}
