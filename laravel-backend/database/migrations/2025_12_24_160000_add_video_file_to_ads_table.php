<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('video_file_path', 512)->nullable()->after('vast_url');
            $table->enum('ad_source', ['vast_url', 'uploaded_video'])->default('vast_url')->after('video_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['video_file_path', 'ad_source']);
        });
    }
};

