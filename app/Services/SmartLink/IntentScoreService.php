<?php

namespace App\Services\SmartLink;

use App\Models\SmartLink\IntentScore;
use App\Models\SmartLink\IntentScoreRule;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartEvent;
use Illuminate\Support\Collection;

/**
 * Rule-based intent score from section 10 of the scope document.
 *
 * The score is always recomputed from the stored events rather than incremented,
 * so replays, deleted events and changed admin weights all stay consistent.
 */
class IntentScoreService
{
    private ?Collection $cachedRules = null;

    /** Memoised: the prospect timeline asks for the weights once per rendered event. */
    public function rules(): Collection
    {
        return $this->cachedRules ??= IntentScoreRule::where('is_active', true)
            ->get()
            ->keyBy('event_key');
    }

    public function recalculate(Prospect $prospect): IntentScore
    {
        $events = $prospect->events()->get();
        $rules = $this->rules();

        $counts = [
            'page_opened' => $events->where('event_type', 'page_opened')->count(),
            'return_visit' => $events->where('event_type', 'return_visit')->count(),
            'section_viewed' => $events->where('event_type', 'section_viewed')
                ->pluck('section_type')->filter()->unique()->count(),
            'portfolio_viewed' => $events->where('event_type', 'section_viewed')
                ->where('section_type', 'portfolio')->count() > 0 ? 1 : 0,
            'solution_viewed' => $events->where('event_type', 'section_viewed')
                ->where('section_type', 'solution')->count() > 0 ? 1 : 0,
            'calculator_completed' => $events->where('event_type', 'calculator_completed')
                ->pluck('metadata.tool')->filter()->unique()->count(),
            'cta_clicked' => $events->where('event_type', 'cta_clicked')->count(),
            'contact_clicked' => $events->whereIn('event_type', [
                'contact_clicked', 'whatsapp_clicked', 'email_clicked', 'calendar_clicked',
            ])->count(),
        ];

        $score = 0;

        foreach ($counts as $key => $count) {
            $rule = $rules->get($key);

            if (! $rule || $count < 1) {
                continue;
            }

            $times = $rule->max_times ? min($count, $rule->max_times) : $count;
            $score += $times * $rule->points;
        }

        $score = max(0, $score);
        $level = IntentScore::levelFor($score);

        $record = $prospect->intentScore ?: new IntentScore(['prospect_id' => $prospect->id]);
        $wasHigh = $record->intent_level === 'HIGH INTENT';

        $record->score = $score;
        $record->intent_level = $level;

        // Re-arm the in-app alert (section 18) the first time a prospect reaches HIGH INTENT.
        if ($level === 'HIGH INTENT' && ! $wasHigh) {
            $record->alert_seen = false;
        }

        $record->save();
        $prospect->setRelation('intentScore', $record);

        return $record;
    }

    /** Points a single event contributed, used for the "why" column on the timeline. */
    public function pointsFor(SmartEvent $event): int
    {
        $key = match ($event->event_type) {
            'whatsapp_clicked', 'email_clicked', 'calendar_clicked', 'contact_clicked' => 'contact_clicked',
            'section_viewed' => match ($event->section_type) {
                'portfolio' => 'portfolio_viewed',
                'solution' => 'solution_viewed',
                default => 'section_viewed',
            },
            default => $event->event_type,
        };

        return (int) ($this->rules()->get($key)?->points ?? 0);
    }
}
