<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('photoalbum_id')->nullable();
            $table->string('small_photo', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('owner_id')->nullable();
            $table->tinyInteger('banned')->default(0);
            $table->tinyInteger('moderate')->nullable()->default(0);
            $table->timestamps();

            $table->index('photoalbum_id', 'id_photoalbum');
            $table->index(['photoalbum_id', 'moderate', 'id'], 'idx_photos_album_moderate_id');
            $table->index(['banned', 'photoalbum_id'], 'idx_photos_banned_album');
            $table->index(['owner_id', 'created_at'], 'idx_photos_owner_created');

            $table->foreign('photoalbum_id', 'fk_photos_photoalbum_id')->references('id')->on('photoalbums')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('owner_id', 'fk_photos_owner_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
