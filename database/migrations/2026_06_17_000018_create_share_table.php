<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('shareable_type', 255)->nullable();
            $table->dateTime('time')->nullable();
            $table->integer('content_id')->nullable();

            $table->index('content_id', 'id_content');
            $table->index('user_id', 'id_user');
            $table->index(['content_id', 'shareable_type', 'user_id'], 'idx_share_content_type_user');
            $table->index(['user_id', 'time'], 'idx_share_user_time');

            $table->foreign('user_id', 'fk_share_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share');
    }
};
