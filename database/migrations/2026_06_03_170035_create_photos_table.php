<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photoalbum_id')->nullable()->constrained('photoalbums')->nullOnDelete();
            $table->string('small_photo')->nullable();
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->boolean('banned')->default(false);
            $table->boolean('moderate')->default(false);
            $table->timestamps();

            $table->index(['photoalbum_id', 'moderate', 'id']);
            $table->index(['owner_id', 'created_at']);
            $table->index(['banned', 'photoalbum_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
