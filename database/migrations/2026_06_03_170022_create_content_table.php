<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('text')->nullable();
            $table->enum('hide', ['show', 'hide'])->default('show');
            $table->timestamps();

            $table->index('hide');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content');
    }
};
