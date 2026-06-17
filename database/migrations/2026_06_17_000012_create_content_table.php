<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('title', 255);
            $table->text('text')->nullable();
            $table->string('slug', 255);
            $table->tinyInteger('published')->default(1);
            $table->timestamps();

            $table->unique('slug', 'slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content');
    }
};
