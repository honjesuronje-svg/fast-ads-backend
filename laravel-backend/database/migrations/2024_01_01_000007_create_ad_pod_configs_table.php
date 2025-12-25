<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_pod_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('position_type', ['pre-roll', 'mid-roll', 'post-roll']);
            $table->integer('min_ads')->default(1);
            $table->integer('max_ads')->default(3);
            $table->integer('max_duration_seconds')->default(120);
            $table->enum('fill_strategy', ['strict', 'best_effort'])->default('best_effort');
            $table->timestamps();

            $table->index(['tenant_id', 'channel_id'], 'idx_tenant_channel');
            $table->index('position_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_pod_configs');
    }
};

