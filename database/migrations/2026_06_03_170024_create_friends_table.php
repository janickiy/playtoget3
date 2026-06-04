<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('friend_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('status')->nullable();
            $table->dateTime('added');

            $table->index(['user_id', 'status', 'friend_id']);
            $table->index(['friend_id', 'status', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
