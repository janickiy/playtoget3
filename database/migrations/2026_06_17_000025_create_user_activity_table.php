<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->dateTime('last_activity')->nullable();

            $table->index('user_id', 'id_user');
            $table->index(['user_id', 'last_activity'], 'idx_user_activity_user_last');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity');
    }
};
