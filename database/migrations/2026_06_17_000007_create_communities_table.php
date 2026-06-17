<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('type', 255)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('recommended')->default(0);
            $table->string('name', 255)->nullable();
            $table->text('about')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('cover_page', 255)->nullable();
            $table->string('place', 100)->nullable();
            $table->string('sport_type', 255)->nullable();
            $table->timestamps();

            $table->index('recommended', 'communities_recommended_index');
            $table->index('status', 'communities_status_index');
            $table->index(['type', 'status'], 'communities_type_status_index');
            $table->index('type', 'idx_communities_banned_type');
            $table->index('name', 'idx_communities_name');
            $table->index('type', 'idx_communities_type_moderate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
