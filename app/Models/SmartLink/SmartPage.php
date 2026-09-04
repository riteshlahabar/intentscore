<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartPage extends Model
{
    protected $fillable = [
        'prospect_id', 'smart_link_id', 'template_id', 'heading', 'subheading',
        'personalized_message', 'cta_text', 'cta_url', 'cta_type', 'status',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function smartLink(): BelongsTo
    {
        return $this->belongsTo(SmartLinkModel::class, 'smart_link_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmartPageTemplate::class, 'template_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SmartPageSection::class, 'smart_page_id')->orderBy('display_order');
    }

    public function section(string $type): ?SmartPageSection
    {
        return $this->sections->firstWhere('section_type', $type);
    }
}
