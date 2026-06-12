<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settigs', function (Blueprint $table) {
            $table->id();
            $table->string('key_cd')->unique();
            $table->string('name')->nullable();
            $table->string('type');
            $table->string('display_value')->nullable();
            $table->text('value');
            $table->boolean('published')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settigs');
    }
};
