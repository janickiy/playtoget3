<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->nullable();
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('photo_id');
            $table->timestamps();

            $table->index(['type', 'content_id']);
            $table->index('content_id');
            $table->index('photo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment');
    }
};
