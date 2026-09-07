<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instagram_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->string('profile_url');
            $table->string('status', 20)->default('completed')->index();

            $table->string('full_name')->nullable();
            $table->string('category')->nullable();
            $table->text('biography')->nullable();
            $table->string('external_url')->nullable();
            $table->string('business_address')->nullable();
            $table->longText('profile_pic')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_business')->default(false);
            $table->boolean('is_private')->default(false);

            $table->unsignedBigInteger('followers')->nullable();
            $table->unsignedBigInteger('following')->nullable();
            $table->unsignedBigInteger('posts_count')->nullable();

            $table->unsignedTinyInteger('profile_score')->nullable();
            $table->unsignedTinyInteger('audience_score')->nullable();
            $table->unsignedTinyInteger('engagement_score')->nullable();
            $table->unsignedTinyInteger('consistency_score')->nullable();

            $table->decimal('engagement_rate', 6, 2)->nullable();
            $table->decimal('follow_ratio', 8, 2)->nullable();
            $table->unsignedInteger('avg_likes')->nullable();
            $table->unsignedInteger('avg_comments')->nullable();
            $table->decimal('posts_per_month', 6, 1)->nullable();
            $table->unsignedInteger('days_since_last_post')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_audits');
    }
};
