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
        Schema::create('ad_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ad_id'); // Parent ad
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('vast_url')->nullable();
            $table->string('video_file_path')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('traffic_percentage')->default(50); // 0-100, how much traffic this variant gets
            $table->integer('priority')->default(0); // Higher priority variants served first
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->json('metadata')->nullable(); // Additional variant-specific data
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            
            $table->index(['tenant_id', 'ad_id', 'status']);
            $table->index(['ad_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_variants');
    }
};

