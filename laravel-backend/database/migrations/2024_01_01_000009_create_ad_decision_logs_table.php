<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('ad_break_id', 128)->nullable();
            $table->string('request_geo', 2)->nullable();
            $table->string('request_device', 50)->nullable();
            $table->json('ads_selected')->nullable();
            $table->integer('decision_time_ms')->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_decision_logs');
    }
};

