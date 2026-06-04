<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->boolean('banned')->default(false);
            $table->string('name')->nullable();
            $table->text('about')->nullable();
            $table->timestamps();
            $table->string('avatar')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('place', 100)->nullable();
            $table->string('sport_type')->nullable();
            $table->boolean('moderate')->default(true);

            $table->index(['type', 'moderate']);
            $table->index('name');
            $table->index(['banned', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
