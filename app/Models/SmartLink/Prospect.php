<?php

namespace App\Models\SmartLink;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospect extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'contacted', 'follow_up', 'meeting', 'won', 'lost'];

    protected $fillable = [
        'business_name', 'contact_name', 'website', 'email', 'phone',
        'industry', 'location', 'offer', 'salesperson_id', 'status',
    ];

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function smartLink(): HasOne
    {
        return $this->hasOne(SmartLinkModel::class, 'prospect_id');
    }

    public function smartPage(): HasOne
    {
        return $this->hasOne(SmartPage::class, 'prospect_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SmartEvent::class, 'prospect_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(SmartPageVisit::class, 'prospect_id');
    }

    public function intentScore(): HasOne
    {
        return $this->hasOne(IntentScore::class, 'prospect_id');
    }

    public function salesActivities(): HasMany
    {
        return $this->hasMany(SalesActivity::class, 'prospect_id');
    }
}
