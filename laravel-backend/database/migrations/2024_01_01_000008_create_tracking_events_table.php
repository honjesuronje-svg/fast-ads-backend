<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'impression',
                'start',
                'first_quartile',
                'midpoint',
                'third_quartile',
                'complete',
                'click',
                'error'
            ]);
            $table->string('session_id', 128)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('geo_country', 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('timestamp');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->index('ad_id');
            $table->index('event_type');
            $table->index('timestamp');
            $table->index(['tenant_id', 'timestamp'], 'idx_tenant_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};

