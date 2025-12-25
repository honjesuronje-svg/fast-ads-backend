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
        Schema::create('ad_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ad_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable(); // For A/B testing
            $table->date('report_date');
            $table->string('time_granularity', 20)->default('hour'); // hour, day, week, month
            
            // Metrics
            $table->integer('impressions')->default(0);
            $table->integer('starts')->default(0);
            $table->integer('completions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0); // percentage
            $table->decimal('click_through_rate', 5, 2)->default(0); // percentage
            $table->decimal('revenue', 15, 2)->default(0); // if applicable
            $table->integer('unique_viewers')->default(0);
            
            // Performance metrics
            $table->decimal('avg_duration_watched', 10, 2)->default(0); // seconds
            $table->integer('total_duration_watched')->default(0); // seconds
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('ad_campaigns')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('ad_variants')->onDelete('cascade');
            
            $table->index(['tenant_id', 'report_date', 'time_granularity']);
            $table->index(['ad_id', 'report_date']);
            $table->index(['campaign_id', 'report_date']);
            $table->index(['channel_id', 'report_date']);
            $table->unique(['tenant_id', 'ad_id', 'campaign_id', 'channel_id', 'variant_id', 'report_date', 'time_granularity'], 'unique_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_reports');
    }
};

