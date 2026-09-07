<?php

namespace App\Services\SmartLink;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Runs a Google PageSpeed Insights (Lighthouse) audit for a prospect's website. */
class PageSpeedInsightsService
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /** @return array<string,mixed> Fields ready to fill a WebsiteAudit row. */
    public function audit(string $url, string $strategy = 'mobile'): array
    {
        $response = Http::timeout(90)->get(self::ENDPOINT, array_filter([
            'url' => $url,
            'strategy' => $strategy,
            'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            'key' => config('services.pagespeed.key'),
        ]));

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->reason();
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
