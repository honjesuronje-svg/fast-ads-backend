<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scte35_cues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->enum('cue_type', ['splice_insert', 'time_signal', 'segmentation_descriptor']);
            $table->decimal('cue_time_seconds', 10, 3);
            $table->integer('duration_seconds')->nullable();
            $table->integer('splice_event_id')->nullable();
            $table->boolean('out_of_network')->default(false);
            $table->timestamp('detected_at')->useCurrent();

            $table->index(['channel_id', 'cue_time_seconds'], 'idx_channel_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scte35_cues');
    }
};

