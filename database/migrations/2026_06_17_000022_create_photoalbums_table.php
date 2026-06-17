<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photoalbums', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('photoalbumable_type', 255)->nullable();
            $table->integer('owner_id');

            $table->index('owner_id', 'id_owner');
            $table->index('owner_id', 'id_photoalbum_type');
            $table->index(['owner_id', 'photoalbumable_type'], 'idx_photoalbums_owner_type');
            $table->index('photoalbumable_type', 'photoalbum_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photoalbums');
    }
};
