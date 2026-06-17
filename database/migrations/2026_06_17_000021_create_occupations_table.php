<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupations', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->tinyInteger('kind')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('month_start')->nullable();
            $table->integer('year_start')->nullable();
            $table->tinyInteger('month_finish')->nullable();
            $table->integer('year_finish')->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamps();

            $table->index('user_id', 'id_user');
            $table->index(['user_id', 'kind'], 'idx_occupations_user_kind');

            $table->foreign('user_id', 'fk_occupations_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupations');
    }
};
