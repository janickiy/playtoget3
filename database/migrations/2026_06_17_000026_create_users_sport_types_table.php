<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_sport_types', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('sport_type', 100);
            $table->integer('sport_level_id')->nullable();
            $table->tinyInteger('search_team')->nullable();

            $table->index('sport_level_id', 'id_sport_level');
            $table->index('user_id', 'id_user');
            $table->index(['user_id', 'sport_level_id'], 'idx_users_sport_types_user_level');

            $table->foreign('user_id', 'fk_users_sport_types_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_sport_types');
    }
};
