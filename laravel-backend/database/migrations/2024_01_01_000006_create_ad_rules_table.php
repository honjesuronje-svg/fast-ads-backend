<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->enum('rule_type', ['geo', 'device', 'time', 'channel', 'day_of_week']);
            $table->enum('rule_operator', ['equals', 'in', 'not_in', 'contains', 'range']);
            $table->text('rule_value');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('ad_id');
            $table->index('rule_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_rules');
    }
};

