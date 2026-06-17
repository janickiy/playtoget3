<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_city', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('country_id');
            $table->integer('region_id');
            $table->string('name_ru', 50);
            $table->string('name_en', 50);
            $table->integer('sort')->default(0);

            $table->index('country_id', 'country_id');
            $table->index('name_en', 'name_en');
            $table->index('name_ru', 'name_ru');
            $table->index('region_id', 'region_id');
            $table->index('sort', 'sort');

            $table->foreign('country_id', 'fk_geo_city_country_id')->references('id')->on('geo_country')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('region_id', 'fk_geo_city_region_id')->references('id')->on('geo_region')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_city');
    }
};
