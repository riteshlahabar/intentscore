<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('strategy', 10)->default('mobile');
            $table->string('status', 20)->default('completed')->index();
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('accessibility_score')->nullable();
            $table->unsignedTinyInteger('best_practices_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedInteger('lcp_ms')->nullable();
            $table->unsignedInteger('fcp_ms')->nullable();
            $table->unsignedInteger('tbt_ms')->nullable();
            $table->unsignedInteger('speed_index_ms')->nullable();
            $table->decimal('cls', 5, 3)->nullable();
            $table->longText('screenshot')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audits');
    }
};
