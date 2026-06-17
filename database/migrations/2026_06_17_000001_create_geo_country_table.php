<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_country', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name_ru', 50);
            $table->string('name_en', 50);
            $table->string('code', 5);
            $table->integer('sort')->default(0);

            $table->index('code', 'code');
            $table->index('name_en', 'name_en');
            $table->index('name_ru', 'name_ru');
            $table->index('sort', 'sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_country');
    }
};
