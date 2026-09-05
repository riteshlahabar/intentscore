<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Templates now render as one of a set of full ported landing-page designs
 * (Landrick) instead of all sharing the single classic Blade view - this
 * column says which. Null/unrecognised values keep rendering the classic
 * view, so older rows are unaffected until explicitly assigned a design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smart_page_templates', function (Blueprint $table) {
            $table->string('design', 40)->nullable()->after('description');
        });

        DB::table('smart_page_templates')->where('slug', 'grooming-business')->update(['design' => 'agency']);

        DB::table('smart_page_templates')->insertOrIgnore([
            [
                'name' => 'Business Consulting',
                'slug' => 'business-consulting',
                'industry' => 'Professional Services',
                'description' => 'Consulting-led design: growth framing, benefits grid and portfolio.',
                'design' => 'consulting',
                'sections' => json_encode(['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta']),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Social Media Growth',
                'slug' => 'social-media-growth',
                'industry' => 'Retail & Local Shops',
                'description' => 'Social-first design built around the Instagram audit and visual portfolio.',
                'design' => 'social-marketing',
                'sections' => json_encode(['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta']),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Modern Business',
                'slug' => 'modern-business',
                'industry' => 'General',
                'description' => 'General-purpose modern business design. Replaces Short Pitch.',
                'design' => 'modern-business',
                'sections' => json_encode(['intro', 'website_audit', 'google_audit', 'instagram_audit', 'free_tools', 'solution', 'portfolio', 'cta']),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('smart_page_templates')->where('slug', 'short-pitch')->delete();
    }

    public function down(): void
    {
        DB::table('smart_page_templates')->whereIn('slug', ['business-consulting', 'social-media-growth', 'modern-business'])->delete();
        DB::table('smart_page_templates')->where('slug', 'grooming-business')->update(['design' => null]);

        Schema::table('smart_page_templates', function (Blueprint $table) {
            $table->dropColumn('design');
        });
    }
};
