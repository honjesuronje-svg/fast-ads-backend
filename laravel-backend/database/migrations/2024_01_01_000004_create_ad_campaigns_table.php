<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('budget', 15, 2)->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['status', 'start_date', 'end_date'], 'idx_status_dates');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};

