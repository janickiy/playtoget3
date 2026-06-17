<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_region', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('country_id');
            $table->string('name_ru', 50);
            $table->string('name_en', 50);
            $table->integer('sort')->default(0);

            $table->index('country_id', 'country_id');
            $table->index('name_en', 'name_en');
            $table->index('name_ru', 'name_ru');
            $table->index('sort', 'sort');

            $table->foreign('country_id', 'geo_region_ibfk_1')->references('id')->on('geo_country')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_region');
    }
};
