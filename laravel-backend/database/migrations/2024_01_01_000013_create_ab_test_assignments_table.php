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
        Schema::create('ab_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ad_id'); // Parent ad
            $table->unsignedBigInteger('variant_id'); // Assigned variant
            $table->string('viewer_identifier', 255); // session_id, device_id, or user_id
            $table->string('identifier_type', 50)->default('session'); // session, device, user
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('ad_variants')->onDelete('cascade');
            
            // Ensure one variant per viewer per ad
            $table->unique(['ad_id', 'viewer_identifier', 'identifier_type'], 'unique_viewer_variant');
            $table->index(['tenant_id', 'ad_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_test_assignments');
    }
};

