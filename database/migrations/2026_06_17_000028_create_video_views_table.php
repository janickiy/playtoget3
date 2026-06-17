<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_views', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('video_id', 255)->nullable();
            $table->dateTime('time')->nullable();

            $table->index(['video_id', 'user_id', 'time'], 'idx_video_views_video_user_time');

            $table->foreign('user_id', 'fk_video_views_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
