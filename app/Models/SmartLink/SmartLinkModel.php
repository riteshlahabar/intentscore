<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SmartLinkModel extends Model
{
    protected $table = 'smart_links';

    protected $fillable = ['prospect_id', 'slug', 'status'];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function smartPage(): HasOne
    {
        return $this->hasOne(SmartPage::class, 'smart_link_id');
    }

    public function publicUrl(): string
    {
        return url('/s/'.$this->slug);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
