<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('likeable_type', 255)->nullable();
            $table->integer('content_id')->nullable();
            $table->dateTime('time')->nullable();

            $table->index('content_id', 'id_content');
            $table->index('user_id', 'id_user');
            $table->index('user_id', 'id_user_2');
            $table->index(['content_id', 'likeable_type', 'user_id'], 'idx_likes_content_type_user');
            $table->index(['user_id', 'time'], 'idx_likes_user_time');
            $table->unique(['user_id', 'likeable_type', 'content_id'], 'likes_user_type_content_unique');

            $table->foreign('user_id', 'fk_likes_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
