<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('sender_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->text('content')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->timestamps();

            $table->index('receiver_id', 'id_receiver');
            $table->index('receiver_id', 'id_receiver_2');
            $table->index('sender_id', 'id_sender');
            $table->index('sender_id', 'id_sender_2');
            $table->index(['receiver_id', 'sender_id', 'status', 'created_at'], 'idx_messages_receiver_sender_status_created');
            $table->index(['sender_id', 'receiver_id', 'status', 'created_at'], 'idx_messages_sender_receiver_status_created');

            $table->foreign('sender_id', 'fk_messages_sender_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('receiver_id', 'fk_messages_receiver_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
