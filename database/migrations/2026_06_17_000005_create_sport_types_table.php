<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_types', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->integer('parent_id')->nullable();

            $table->index('parent_id', 'id_parent');
            $table->index(['parent_id', 'name'], 'idx_sport_types_parent_name');

            $table->foreign('parent_id', 'fk_sport_types_parent_id')->references('id')->on('sport_types')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_types');
    }
};
