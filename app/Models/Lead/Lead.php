<?php

namespace App\Models\Lead;

use App\Models\Client\Client;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'product_id',
        'owner_id',
        'title',
        'source',
        'status',
        'priority',
        'expected_value',
        'next_follow_up_at',
        'requirement',
        'notes',
    ];

    protected $casts = [
        'expected_value' => 'decimal:2',
        'next_follow_up_at' => 'date',
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

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
