<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videoalbums', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('videoalbumable_type')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();

            $table->index(['owner_id', 'videoalbumable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videoalbums');
    }
};
