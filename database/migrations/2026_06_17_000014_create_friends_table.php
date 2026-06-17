<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('friend_id')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->dateTime('added');

            $table->index('friend_id', 'id_friend');
            $table->index('user_id', 'id_user');
            $table->index(['friend_id', 'status', 'user_id'], 'idx_friends_friend_status_user');
            $table->index(['user_id', 'status', 'friend_id'], 'idx_friends_user_status_friend');

            $table->foreign('user_id', 'fk_friends_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('friend_id', 'fk_friends_friend_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
