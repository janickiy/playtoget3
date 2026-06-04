<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photoalbums', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('photoalbumable_type')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();

            $table->index('photoalbumable_type');
            $table->index(['owner_id', 'photoalbumable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photoalbums');
    }
};
