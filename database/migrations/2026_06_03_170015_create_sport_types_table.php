<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('sport_types')->nullOnDelete();
            $table->timestamps();

            $table->index(['parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_types');
    }
};
