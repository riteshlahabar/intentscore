<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Presentations/Products/Clients/Leads/Follow-ups/Analytics modules were
 * removed from the app (out of scope for the Smart Links MVP), so their
 * tables are dropped here. `settings` is excluded - it is shared and still
 * used for company branding.
 *
 * 2026_08_22_000200_create_sales_portal_tables.php was trimmed to only
 * create `settings`, so on a brand-new install these tables never exist and
 * dropIfExists() below is a harmless no-op. This migration stays required
 * for any database that ran the old, fuller version of that migration
 * before this cleanup.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'presentation_events',
            'presentation_sessions',
            'presentation_sections',
            'presentations',
            'follow_ups',
            'leads',
            'product_demo_links',
            'product_media',
            'product_features',
            'products',
            'clients',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally not restored: this migration is a deliberate removal
        // of the legacy Presentation Portal schema, not a reversible change.
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('whatsapp', 30)->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('country')->default('India');
            $table->string('status', 30)->default('prospect')->index();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->string('tagline')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('base_price', 14, 2)->nullable();
            $table->string('currency', 8)->default('INR');
            $table->unsignedInteger('default_timeline_days')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('type', 20)->default('image');
            $table->string('file_path')->nullable();
            $table->text('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_demo_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('url');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('type', 30)->default('website');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('source', 40)->nullable()->index();
            $table->string('status', 30)->default('new')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->decimal('expected_value', 14, 2)->nullable();
            $table->date('next_follow_up_at')->nullable()->index();
            $table->text('requirement')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('follow_up_at')->index();
            $table->string('type', 30)->default('call');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_no')->unique();
            $table->string('public_token', 64)->unique();
            $table->string('title');
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('price', 14, 2)->nullable();
            $table->string('currency', 8)->default('INR');
            $table->text('intro_message')->nullable();
            $table->longText('client_requirements')->nullable();
            $table->longText('recommended_solution')->nullable();
            $table->longText('deliverables')->nullable();
            $table->longText('implementation_timeline')->nullable();
            $table->longText('support_details')->nullable();
            $table->longText('terms')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedTinyInteger('engagement_score')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presentation_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 60);
            $table->string('custom_title')->nullable();
            $table->longText('custom_content')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['presentation_id', 'section_key']);
        });

        Schema::create('presentation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained()->cascadeOnDelete();
            $table->uuid('session_uuid')->unique();
            $table->uuid('visitor_uuid')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('device_type', 30)->nullable()->index();
            $table->string('browser', 60)->nullable();
            $table->string('operating_system', 60)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('source', 60)->nullable()->index();
            $table->string('current_section', 60)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('active_seconds')->default(0);
            $table->timestamps();
        });

        Schema::create('presentation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('presentation_sessions')->cascadeOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('section_key', 60)->nullable()->index();
            $table->string('element_key', 120)->nullable()->index();
            $table->text('target_url')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['presentation_id', 'occurred_at']);
        });
    }
};
