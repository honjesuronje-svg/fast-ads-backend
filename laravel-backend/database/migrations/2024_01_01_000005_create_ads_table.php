<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('ad_campaigns')->onDelete('set null');
            $table->string('name');
            $table->string('vast_url', 512);
            $table->integer('duration_seconds');
            $table->enum('ad_type', ['linear', 'non-linear', 'companion'])->default('linear');
            $table->string('click_through_url', 512)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('campaign_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

