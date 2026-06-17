<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videoalbums', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->string('videoalbumable_type', 255)->nullable();
            $table->integer('owner_id');
            $table->timestamps();

            $table->index(['owner_id', 'videoalbumable_type'], 'idx_videoalbums_owner_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videoalbums');
    }
};
