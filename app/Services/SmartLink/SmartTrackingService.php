<?php

namespace App\Services\SmartLink;

use App\Models\SmartLink\SmartEvent;
use App\Models\SmartLink\SmartPage;
use App\Models\SmartLink\SmartPageVisit;
use App\Services\Common\UserAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Records the events listed in section 8 and keeps the intent score current.
 * Tracking is wired into every Smart Page automatically (section 17).
 */
class SmartTrackingService
{
    /** Events the page is allowed to send. Anything else is ignored. */
    public const ALLOWED = [
        'page_opened', 'time_spent', 'section_viewed', 'section_clicked',
        'calculator_opened', 'calculator_completed', 'result_viewed',
        'cta_clicked', 'contact_clicked', 'whatsapp_clicked', 'calendar_clicked', 'email_clicked',
    ];

    /** Events that change the score and therefore trigger a recalculation. */
    private const SCORING = [
        'page_opened', 'section_viewed', 'calculator_completed',
        'cta_clicked', 'contact_clicked', 'whatsapp_clicked', 'calendar_clicked', 'email_clicked',
    ];

    public function __construct(
        private readonly UserAgentService $userAgent,
        private readonly IntentScoreService $intentScore,
    ) {
    }

    public function track(SmartPage $page, Request $request, array $data): ?SmartEvent
    {
        if (! in_array($data['event_type'], self::ALLOWED, true)) {
            return null;
        }

        $visit = $this->touchVisit($page, $request, $data);

        if ($data['event_type'] === 'time_spent') {
            // Cap a single report at five minutes so a forgotten open tab cannot inflate engagement.
            $visit->increment('active_seconds', min(300, (int) floor(($data['duration_ms'] ?? 0) / 1000)));
        }

        $section = $data['section_type'] ?? null;

        $sectionId = $section
            ? $page->sections->firstWhere('section_type', $section)?->id
            : null;

        $event = SmartEvent::create([
            'prospect_id' => $page->prospect_id,
            'smart_page_id' => $page->id,
            'smart_link_id' => $page->smart_link_id,
            'section_id' => $sectionId,
            'event_type' => $data['event_type'],
            'section_type' => $section,
            'session_id' => $data['session_id'],
            'visitor_id' => $data['visitor_id'] ?? null,
            'duration_ms' => isset($data['duration_ms']) ? min((int) $data['duration_ms'], 1800000) : null,
            'metadata' => Arr::only($data['metadata'] ?? [], ['tool', 'label', 'value', 'result']),
            'occurred_at' => now(),
        ]);

        if (in_array($data['event_type'], self::SCORING, true)) {
            $this->intentScore->recalculate($page->prospect);
        }

        return $event;
    }

    private function touchVisit(SmartPage $page, Request $request, array $data): SmartPageVisit
    {
        $visitorId = $data['visitor_id'] ?? null;

        $visit = SmartPageVisit::where('session_id', $data['session_id'])->first();

        if ($visit) {
            $visit->update(['last_activity_at' => now()]);

            return $visit;
        }

        $agent = $this->userAgent->parse($request->userAgent());

        $isReturn = $visitorId && SmartPageVisit::where('prospect_id', $page->prospect_id)
            ->where('visitor_id', $visitorId)
            ->exists();

        $visit = SmartPageVisit::create([
            'prospect_id' => $page->prospect_id,
            'smart_page_id' => $page->id,
            'session_id' => $data['session_id'],
            'visitor_id' => $visitorId,
            'is_return_visit' => $isReturn,
            'ip_address' => $request->ip(),
            'device_type' => $agent['device'] ?? null,
            'browser' => $agent['browser'] ?? null,
            'operating_system' => $agent['os'] ?? null,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        if ($isReturn) {
            SmartEvent::create([
                'prospect_id' => $page->prospect_id,
                'smart_page_id' => $page->id,
                'smart_link_id' => $page->smart_link_id,
                'event_type' => 'return_visit',
                'session_id' => $data['session_id'],
                'visitor_id' => $visitorId,
                'occurred_at' => now(),
            ]);
        }

        return $visit;
    }
}
