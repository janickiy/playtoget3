<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities_settings', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->tinyInteger('permission_wall')->default(0);
            $table->tinyInteger('permission_photo')->default(0);
            $table->tinyInteger('permission_video')->default(0);
            $table->tinyInteger('type')->default(0);
            $table->integer('community_id')->nullable();

            $table->index('community_id', 'idx_communities_settings_community');

            $table->foreign('community_id', 'fk_communities_settings_community_id')->references('id')->on('communities')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities_settings');
    }
};
