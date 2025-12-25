<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->enum('position_type', ['pre-roll', 'mid-roll', 'post-roll']);
            $table->integer('offset_seconds');
            $table->integer('duration_seconds');
            $table->integer('priority')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('channel_id');
            $table->index(['position_type', 'offset_seconds'], 'idx_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_breaks');
    }
};

