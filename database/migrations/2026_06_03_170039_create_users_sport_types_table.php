<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_sport_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sport_type', 100);
            $table->foreignId('sport_level_id')->nullable()->constrained('sport_level')->nullOnDelete();
            $table->boolean('search_team')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sport_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_sport_types');
    }
};
