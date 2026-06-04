<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_target', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('country_id')->nullable()->constrained('geo_country')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('geo_region')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('geo_city')->nullOnDelete();
            $table->timestamps();

            $table->unique(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_target');
    }
};
