<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('video_id')->nullable();
            $table->dateTime('time')->nullable();

            $table->index(['video_id', 'user_id', 'time'], 'idx_video_views_video_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
