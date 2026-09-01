<?php

namespace App\Models\Presentation;

use App\Models\Analytics\PresentationEvent;
use App\Models\Analytics\PresentationSession;
use App\Models\Client\Client;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'product_id',
        'owner_id',
        'reference_no',
        'public_token',
        'title',
        'status',
        'price',
        'currency',
        'intro_message',
        'client_requirements',
        'recommended_solution',
        'deliverables',
        'implementation_timeline',
        'support_details',
        'terms',
        'valid_until',
        'published_at',
        'engagement_score',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'valid_until' => 'date',
        'published_at' => 'datetime',
        'engagement_score' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PresentationSection::class)->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PresentationSession::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PresentationEvent::class);
    }

    public function isPubliclyAvailable(): bool
    {
        $allowedStatuses = ['published', 'viewed', 'engaged', 'negotiation', 'won'];
        $withinValidity = ! $this->valid_until || $this->valid_until->copy()->endOfDay()->isFuture();

        return in_array($this->status, $allowedStatuses, true) && $withinValidity;
    }
}
