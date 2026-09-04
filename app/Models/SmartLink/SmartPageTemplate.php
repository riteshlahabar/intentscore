<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;

class SmartPageTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'industry', 'description', 'sections', 'is_active'];

    protected function casts(): array
    {
        return ['sections' => 'array', 'is_active' => 'boolean'];
    }

    /** Every section type the MVP supports, in the order of PDF section 15. */
    public const SECTION_LABELS = [
        'intro' => 'Personalized Introduction',
        'website_audit' => 'Website Audit',
        'google_audit' => 'Google Business Audit',
        'instagram_audit' => 'Instagram Audit',
        'free_tools' => 'Free Tools',
        'solution' => 'Recommended Solution',
        'portfolio' => 'Portfolio / Examples',
        'cta' => 'CTA',
    ];

    public static function sectionLabel(string $type): string
    {
        return self::SECTION_LABELS[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /** Seed data for the templates shipped with the MVP. */
    public const DEFAULTS = [
        [
            'name' => 'Grooming Business',
            'slug' => 'grooming-business',
            'industry' => 'Pet Grooming',
            'description' => 'Full audit-led page with all three calculators. Matches Template 1 in the scope document.',
            'sections' => ['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta'],
        ],
        [
            'name' => 'Short Pitch',
            'slug' => 'short-pitch',
            'industry' => 'General',
            'description' => 'Lean page: message, recommendation, portfolio and CTA only.',
            'sections' => ['intro', 'solution', 'portfolio', 'cta'],
        ],
    ];
}
