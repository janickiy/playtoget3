<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('videoalbum_id')->nullable()->constrained('videoalbums')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('video')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->boolean('banned')->default(false);
            $table->timestamps();

            $table->index(['videoalbum_id', 'created_at']);
            $table->index(['owner_id', 'created_at']);
            $table->index(['banned', 'videoalbum_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
