<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_target', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('target_type', 20);
            $table->integer('target_id');
            $table->integer('country_id')->nullable();
            $table->integer('region_id')->nullable();
            $table->integer('city_id')->nullable();

            $table->index('city_id', 'city_id');
            $table->index('country_id', 'country_id');
            $table->primary(['id', 'target_type', 'target_id']);
            $table->index('region_id', 'region_id');
            $table->index(['target_type', 'target_id'], 'target_type');

            $table->foreign('country_id', 'fk_geo_target_country_id')->references('id')->on('geo_country')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('region_id', 'fk_geo_target_region_id')->references('id')->on('geo_region')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('city_id', 'fk_geo_target_city_id')->references('id')->on('geo_city')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_target');
    }
};
