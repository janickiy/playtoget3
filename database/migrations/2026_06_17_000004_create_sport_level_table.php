<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_level', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_level');
    }
};
