<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('ip', 255)->nullable();
            $table->string('user_agent', 255);
            $table->dateTime('last_sign_in_at')->nullable();

            $table->index(['user_id', 'last_sign_in_at'], 'idx_log_user_last_sign_in');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
