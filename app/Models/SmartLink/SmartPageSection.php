<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartPageSection extends Model
{
    protected $fillable = [
        'smart_page_id', 'section_type', 'title', 'content', 'data', 'display_order', 'enabled',
    ];

    protected function casts(): array
    {
        return ['data' => 'array', 'enabled' => 'boolean'];
    }

    public function smartPage(): BelongsTo
    {
        return $this->belongsTo(SmartPage::class, 'smart_page_id');
    }

    public function field(string $key, string $default = ''): string
    {
        return (string) (($this->data[$key] ?? null) ?: $default);
    }
}
