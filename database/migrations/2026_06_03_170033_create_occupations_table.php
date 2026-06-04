<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('kind')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('month_start')->nullable();
            $table->unsignedSmallInteger('year_start')->nullable();
            $table->unsignedTinyInteger('month_finish')->nullable();
            $table->unsignedSmallInteger('year_finish')->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupations');
    }
};
