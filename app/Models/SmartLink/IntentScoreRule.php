<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;

class IntentScoreRule extends Model
{
    protected $fillable = ['event_key', 'label', 'points', 'max_times', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Default weights from PDF section 10. Seeded, then editable by an admin. */
    public const DEFAULTS = [
        ['event_key' => 'page_opened', 'label' => 'Page opened', 'points' => 2, 'max_times' => 1],
        ['event_key' => 'return_visit', 'label' => 'Return visit', 'points' => 5, 'max_times' => 4],
        ['event_key' => 'section_viewed', 'label' => 'Section viewed', 'points' => 1, 'max_times' => 8],
        ['event_key' => 'portfolio_viewed', 'label' => 'Portfolio viewed', 'points' => 3, 'max_times' => 1],
        ['event_key' => 'calculator_completed', 'label' => 'Calculator completed', 'points' => 8, 'max_times' => 3],
        ['event_key' => 'solution_viewed', 'label' => 'Recommended Solution viewed', 'points' => 5, 'max_times' => 1],
        ['event_key' => 'cta_clicked', 'label' => 'CTA clicked', 'points' => 15, 'max_times' => 2],
        ['event_key' => 'contact_clicked', 'label' => 'Contact / WhatsApp clicked', 'points' => 20, 'max_times' => 2],
    ];
}
