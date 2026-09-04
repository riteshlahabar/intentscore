<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartEvent extends Model
{
    protected $fillable = [
        'prospect_id', 'smart_page_id', 'smart_link_id', 'section_id', 'event_type',
        'section_type', 'session_id', 'visitor_id', 'duration_ms', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    /** Human labels used by the prospect activity timeline (PDF section 9). */
    public const LABELS = [
        'page_opened' => 'Opened Smart Page',
        'return_visit' => 'Returned to Smart Page',
        'time_spent' => 'Time spent on page',
        'section_viewed' => 'Viewed section',
        'section_clicked' => 'Expanded section',
        'calculator_opened' => 'Opened calculator',
        'calculator_completed' => 'Used calculator',
        'result_viewed' => 'Viewed calculator result',
        'cta_clicked' => 'Clicked CTA',
        'contact_clicked' => 'Clicked contact',
        'whatsapp_clicked' => 'Clicked WhatsApp',
        'calendar_clicked' => 'Clicked calendar',
        'email_clicked' => 'Clicked email',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function label(): string
    {
        $base = self::LABELS[$this->event_type] ?? ucwords(str_replace('_', ' ', $this->event_type));

        if ($this->section_type && in_array($this->event_type, ['section_viewed', 'section_clicked'], true)) {
            return $base.': '.SmartPageTemplate::sectionLabel($this->section_type);
        }

        if ($this->event_type === 'time_spent' && $this->duration_ms) {
            return $base.' ('.max(1, (int) round($this->duration_ms / 1000)).'s)';
        }

        $tool = $this->metadata['tool'] ?? null;

        return $tool ? $base.': '.$tool : $base;
    }
}
