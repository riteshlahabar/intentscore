<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartPageVisit extends Model
{
    protected $fillable = [
        'prospect_id', 'smart_page_id', 'session_id', 'visitor_id', 'is_return_visit',
        'ip_address', 'device_type', 'browser', 'operating_system', 'active_seconds',
        'started_at', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_return_visit' => 'boolean',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }
}
