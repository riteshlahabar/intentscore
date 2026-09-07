<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteAudit extends Model
{
    protected $fillable = [
        'prospect_id', 'url', 'strategy', 'status',
        'performance_score', 'accessibility_score', 'best_practices_score', 'seo_score',
        'lcp_ms', 'fcp_ms', 'tbt_ms', 'speed_index_ms', 'cls',
        'screenshot', 'error_message',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function badgeClass(?int $score): string
    {
        return match (true) {
            $score === null => 'soft-gray',
            $score >= 90 => 'soft-green',
            $score >= 50 => 'soft-amber',
            default => 'soft-red',
        };
    }
}
