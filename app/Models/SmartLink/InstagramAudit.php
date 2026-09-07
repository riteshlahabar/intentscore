<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAudit extends Model
{
    protected $fillable = [
        'prospect_id', 'username', 'profile_url', 'status',
        'full_name', 'category', 'biography', 'external_url', 'business_address',
        'profile_pic', 'is_verified', 'is_business', 'is_private',
        'followers', 'following', 'posts_count',
        'profile_score', 'audience_score', 'engagement_score', 'consistency_score',
        'engagement_rate', 'follow_ratio', 'avg_likes', 'avg_comments',
        'posts_per_month', 'days_since_last_post',
        'error_message',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_business' => 'boolean',
        'is_private' => 'boolean',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /** The four category scores averaged, so the card can lead with one number. */
    public function overallScore(): ?int
    {
        $scores = array_filter([
            $this->profile_score,
            $this->audience_score,
            $this->engagement_score,
            $this->consistency_score,
        ], fn ($score) => $score !== null);

        return $scores === [] ? null : (int) round(array_sum($scores) / count($scores));
    }
}
