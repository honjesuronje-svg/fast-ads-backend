<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key', 64)->unique();
            $table->string('api_secret', 128);
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->json('allowed_domains')->nullable();
            $table->integer('rate_limit_per_minute')->default(1000);
            $table->timestamps();

            $table->index('api_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

