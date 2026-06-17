<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('videoalbum_id')->nullable();
            $table->string('provider', 255)->nullable();
            $table->string('video', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('owner_id')->nullable();
            $table->tinyInteger('banned')->default(0);
            $table->timestamps();

            $table->index('owner_id', 'id_user');
            $table->index(['videoalbum_id', 'created_at'], 'idx_videos_album_created');
            $table->index(['banned', 'videoalbum_id'], 'idx_videos_banned_album');
            $table->index(['owner_id', 'created_at'], 'idx_videos_owner_created');
            $table->index('videoalbum_id', 'videoalbum_id');

            $table->foreign('videoalbum_id', 'fk_videos_videoalbum_id')->references('id')->on('videoalbums')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('owner_id', 'fk_videos_owner_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
