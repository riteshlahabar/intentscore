<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('smart_page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->text('description')->nullable();
            $table->json('sections');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_name')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('industry')->nullable()->index();
            $table->string('location')->nullable()->index();
            $table->string('offer')->nullable();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('new')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('smart_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 24)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('smart_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('smart_page_templates')->nullOnDelete();
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();
            $table->text('personalized_message')->nullable();
            $table->string('cta_text')->default('Let us discuss this');
            $table->text('cta_url')->nullable();
            $table->string('cta_type', 20)->default('whatsapp');
            $table->string('status', 20)->default('published')->index();
            $table->timestamps();
        });

        Schema::create('smart_page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_page_id')->constrained()->cascadeOnDelete();
            $table->string('section_type', 60);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['smart_page_id', 'section_type']);
        });

        Schema::create('smart_page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_page_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_id')->unique();
            $table->uuid('visitor_id')->nullable()->index();
            $table->boolean('is_return_visit')->default(false);
            $table->string('ip_address', 64)->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('operating_system', 60)->nullable();
            $table->unsignedBigInteger('active_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('smart_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('smart_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('smart_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('smart_page_sections')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('section_type', 60)->nullable()->index();
            $table->uuid('session_id')->index();
            $table->uuid('visitor_id')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['prospect_id', 'occurred_at']);
        });

        Schema::create('intent_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0)->index();
            $table->string('intent_level', 20)->default('LOW')->index();
            $table->boolean('alert_seen')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('intent_score_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 60)->unique();
            $table->string('label');
            $table->integer('points')->default(0);
            $table->unsignedInteger('max_times')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 40);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'sales_activities', 'intent_score_rules', 'intent_scores', 'smart_events',
            'smart_page_visits', 'smart_page_sections', 'smart_pages', 'smart_links',
            'prospects', 'smart_page_templates',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
