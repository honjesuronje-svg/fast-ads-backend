<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('frequency_caps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ad_id')->nullable(); // null = campaign-level cap
            $table->unsignedBigInteger('campaign_id')->nullable(); // null = ad-level cap
            $table->string('viewer_identifier', 255); // session_id, device_id, or user_id
            $table->string('identifier_type', 50)->default('session'); // session, device, user
            $table->integer('impression_count')->default(0);
            $table->string('time_window', 50)->default('day'); // hour, day, week, month
            $table->integer('max_impressions')->default(3); // max impressions in time window
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('ad_campaigns')->onDelete('cascade');
            
            $table->index(['tenant_id', 'viewer_identifier', 'identifier_type', 'time_window']);
            $table->index(['ad_id', 'viewer_identifier']);
            $table->index(['campaign_id', 'viewer_identifier']);
            $table->index('window_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequency_caps');
    }
};

