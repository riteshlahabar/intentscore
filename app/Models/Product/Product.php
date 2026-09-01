<?php

namespace App\Models\Product;

use App\Models\Presentation\Presentation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'tagline',
        'summary',
        'description',
        'base_price',
        'currency',
        'default_timeline_days',
        'cover_image',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function demoLinks(): HasMany
    {
        return $this->hasMany(ProductDemoLink::class)->orderBy('sort_order');
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(Presentation::class);
    }
}
