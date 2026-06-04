<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('geo_country')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name_ru', 80);
            $table->string('name_en', 80);
            $table->unsignedInteger('sort')->default(0);

            $table->index('sort');
            $table->index('name_ru');
            $table->index('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_region');
    }
};
