<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_blocks', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->text('about')->nullable();
            $table->string('place', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('type', 10)->nullable();
            $table->integer('owner_id')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('recommended')->default(0);
            $table->timestamps();

            $table->index('type', 'idx_sport_blocks_banned_type');
            $table->index('name', 'idx_sport_blocks_name');
            $table->index(['type', 'owner_id'], 'idx_sport_blocks_type_owner_active');
            $table->index('recommended', 'sport_blocks_recommended_index');
            $table->index(['status', 'type'], 'sport_blocks_status_type_index');
            $table->index(['type', 'owner_id'], 'sport_blocks_type_owner_id_index');

            $table->foreign('owner_id', 'fk_sport_blocks_owner_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_blocks');
    }
};
