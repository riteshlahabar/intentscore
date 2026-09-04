<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentScore extends Model
{
    protected $fillable = ['prospect_id', 'score', 'intent_level', 'alert_seen'];

    protected function casts(): array
    {
        return ['alert_seen' => 'boolean'];
    }

    /** Intent bands from PDF section 11. */
    public const LEVELS = [
        'LOW' => [0, 10],
        'ENGAGED' => [11, 25],
        'INTERESTED' => [26, 50],
        'HIGH INTENT' => [51, PHP_INT_MAX],
    ];

    public static function levelFor(int $score): string
    {
        foreach (self::LEVELS as $level => [$min, $max]) {
            if ($score >= $min && $score <= $max) {
                return $level;
            }
        }

        return 'LOW';
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function badgeClass(): string
    {
        return match ($this->intent_level) {
            'HIGH INTENT' => 'soft-red',
            'INTERESTED' => 'soft-amber',
            'ENGAGED' => 'soft-blue',
            default => 'soft-gray',
        };
    }
}
