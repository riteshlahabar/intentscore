<?php

namespace App\Models\SmartLink;

use Illuminate\Database\Eloquent\Model;

class SmartPageTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'industry', 'description', 'design', 'sections', 'is_active'];

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

    /** Every ported design a template can render as. Null/unknown falls back to the classic view. */
    public const DESIGNS = ['agency', 'consulting', 'social-marketing', 'modern-business'];

    /** Seed data for the templates shipped with the MVP. */
    public const DEFAULTS = [
        [
            'name' => 'Grooming Business',
            'slug' => 'grooming-business',
            'industry' => 'Pet Grooming',
            'description' => 'Full audit-led page with all three calculators. Matches Template 1 in the scope document.',
            'design' => 'agency',
            'sections' => ['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta'],
        ],
        [
            'name' => 'Business Consulting',
            'slug' => 'business-consulting',
            'industry' => 'Professional Services',
            'description' => 'Consulting-led design: growth framing, benefits grid and portfolio.',
            'design' => 'consulting',
            'sections' => ['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta'],
        ],
        [
            'name' => 'Social Media Growth',
            'slug' => 'social-media-growth',
            'industry' => 'Retail & Local Shops',
            'description' => 'Social-first design built around the Instagram audit and visual portfolio.',
            'design' => 'social-marketing',
            'sections' => ['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta'],
        ],
        [
            'name' => 'Modern Business',
            'slug' => 'modern-business',
            'industry' => 'General',
            'description' => 'General-purpose modern business design. Replaces Short Pitch.',
            'design' => 'modern-business',
            'sections' => ['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta'],
        ],
    ];
}
