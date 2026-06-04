<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->dateTime('date_from')->nullable();
            $table->dateTime('date_to')->nullable();
            $table->text('description')->nullable();
            $table->string('sport_type')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('place', 100)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->boolean('moderate')->default(true);
            $table->boolean('banned')->default(false);

            $table->index(['moderate', 'date_from']);
            $table->index('name');
            $table->index(['banned', 'date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
