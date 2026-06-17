<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('id', true);
            $table->string('key_cd', 255);
            $table->string('name', 255)->nullable();
            $table->string('type', 255);
            $table->string('display_value', 255)->nullable();
            $table->text('value');
            $table->tinyInteger('published')->default(1);
            $table->timestamps();

            $table->unique('key_cd', 'settigs_key_cd_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
