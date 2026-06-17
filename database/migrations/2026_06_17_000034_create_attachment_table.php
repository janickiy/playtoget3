<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('type', 50)->nullable();
            $table->integer('content_id');
            $table->integer('photo_id');

            $table->index('content_id', 'id_content');
            $table->index('photo_id', 'idx_attachment_photo');
            $table->index(['type', 'content_id'], 'idx_attachment_type_content');

            $table->foreign('photo_id', 'fk_attachment_photo_id')->references('id')->on('photos')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment');
    }
};
