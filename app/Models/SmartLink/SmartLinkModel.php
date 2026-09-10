<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    /**
     * Public address of the Smart Page: /{id}/{business-name}.
     *
     * Only the id is matched by the router; the name segment is decorative. That way a
     * renamed prospect does not break links already sent out — the route redirects a
     * stale name to the current one. Two prospects may share a business name, so the
     * id is what keeps the address unique. The legacy /s/{slug} form still resolves.
     */
    public function publicUrl(): string
    {
        return url('/'.$this->id.'/'.$this->nameSlug());
    }

    /** Business name reduced to a URL segment: "Bark & Bath" => "bark-bath". */
    public function nameSlug(): string
    {
        $slug = Str::slug((string) ($this->prospect?->business_name ?? ''));

        // A name made only of characters Str::slug drops would leave a bare "/12/",
        // which the two-segment route cannot match. Fall back to a literal.
        return $slug !== '' ? $slug : 'page';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
