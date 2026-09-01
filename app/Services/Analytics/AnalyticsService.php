<?php

namespace App\Services\Analytics;

use App\Models\Analytics\PresentationEvent;
use App\Models\Analytics\PresentationSession;
use App\Models\Presentation\Presentation;
use App\Services\Common\UserAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AnalyticsService
{
    private const SCORE_EVENTS = [
        'page_opened',
        'url_opened',
        'button_clicked',
        'section_viewed',
        'video_progress',
    ];

    public function __construct(private readonly UserAgentService $userAgent)
    {
    }

    public function track(
        Presentation $presentation,
        Request $request,
        array $data
    ): ?PresentationEvent {
        $agent = $this->userAgent->parse($request->userAgent());

        $session = PresentationSession::firstOrCreate(
            ['session_uuid' => $data['session_uuid']],
            [
                'presentation_id' => $presentation->id,
                'visitor_uuid' => $data['visitor_uuid'] ?? null,
                'ip_address' => $request->ip(),
                'country' => $request->header('CF-IPCountry'),
                'city' => $request->header('CF-IPCity'),
                'device_type' => $agent['device'],
                'browser' => $agent['browser'],
                'operating_system' => $agent['os'],
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'referrer' => mb_substr(
                    (string) ($data['referrer'] ?? $request->headers->get('referer')),
                    0,
                    2000
                ),
                'source' => mb_substr((string) ($data['source'] ?? 'direct'), 0, 60),
                'started_at' => now(),
                'last_activity_at' => now(),
            ]
        );

        abort_unless($session->presentation_id === $presentation->id, 422, 'Invalid session.');

        $session->update([
            'last_activity_at' => now(),
            'current_section' => $data['section_key'] ?? $session->current_section,
        ]);

        if ($data['event_type'] === 'heartbeat') {
            return null;
        }

        if ($data['event_type'] === 'section_time' && ! empty($data['duration_ms'])) {
            $seconds = min(300, (int) floor($data['duration_ms'] / 1000));
            $session->increment('active_seconds', $seconds);
        }

        $event = PresentationEvent::create([
            'presentation_id' => $presentation->id,
            'session_id' => $session->id,
            'event_type' => $data['event_type'],
            'section_key' => $data['section_key'] ?? null,
            'element_key' => $data['element_key'] ?? null,
            'target_url' => isset($data['target_url'])
                ? mb_substr($data['target_url'], 0, 2000)
                : null,
            'duration_ms' => isset($data['duration_ms'])
                ? min((int) $data['duration_ms'], 1800000)
                : null,
            'meta' => Arr::only(
                $data['meta'] ?? [],
                ['label', 'value', 'scroll_depth', 'video_percent']
            ),
            'occurred_at' => now(),
        ]);

        if (in_array($data['event_type'], self::SCORE_EVENTS, true)) {
            $this->refreshScore($presentation);
        }

        if ($presentation->status === 'published' && $data['event_type'] === 'page_opened') {
            $presentation->update(['status' => 'viewed']);
        }

        return $event;
    }

    public function refreshScore(Presentation $presentation): int
    {
        $events = $presentation->events();

        $visitScore = min(4, (clone $events)->where('event_type', 'page_opened')->count()) * 5;

        $pricingScore = min(
            2,
            (clone $events)
                ->where('event_type', 'section_viewed')
                ->where('section_key', 'pricing')
                ->count()
        ) * 12;

        $demoScore = min(
            2,
            (clone $events)
                ->where('event_type', 'url_opened')
                ->where('element_key', 'like', '%demo%')
                ->count()
        ) * 15;

        $contactScore = min(
            1,
            (clone $events)
                ->where('event_type', 'button_clicked')
                ->whereIn('element_key', ['whatsapp', 'interested', 'request_discussion'])
                ->count()
        ) * 25;

        $depthScore = (clone $events)
            ->where('event_type', 'scroll_depth')
            ->whereJsonContains('meta->scroll_depth', 75)
            ->exists() ? 10 : 0;

        $returnVisitScore = $this->returnVisitScore($presentation);

        $score = min(
            100,
            $visitScore + $pricingScore + $demoScore + $contactScore + $depthScore + $returnVisitScore
        );

        $update = ['engagement_score' => $score];

        if ($score >= 60 && in_array($presentation->status, ['published', 'viewed'], true)) {
            $update['status'] = 'engaged';
        }

        $presentation->updateQuietly($update);

        return $score;
    }

    private function returnVisitScore(Presentation $presentation): int
    {
        $sessionCount = $presentation->sessions()->count();
        $visitorCount = $presentation->sessions()
            ->whereNotNull('visitor_uuid')
            ->distinct()
            ->count('visitor_uuid');

        if ($sessionCount <= 1 && $visitorCount <= 1) {
            return 0;
        }

        return min(2, max(0, $sessionCount - 1)) * 5;
    }
}
