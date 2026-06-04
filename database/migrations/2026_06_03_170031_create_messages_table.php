<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'receiver_id', 'status', 'created_at'], 'idx_messages_sender_receiver_status_created');
            $table->index(['receiver_id', 'sender_id', 'status', 'created_at'], 'idx_messages_receiver_sender_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
