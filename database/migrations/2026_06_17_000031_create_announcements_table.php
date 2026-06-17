<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->unsignedBigInteger('id', true);
            $table->string('title', 255);
            $table->text('text')->nullable();
            $table->string('slug', 255);
            $table->tinyInteger('published')->default(1);
            $table->timestamps();

            $table->index('published', 'announcements_published_index');
            $table->unique('slug', 'announcements_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
