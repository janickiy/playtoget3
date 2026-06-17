<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_roles', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->integer('community_id')->nullable();
            $table->tinyInteger('role')->nullable();
            $table->string('role_description', 255)->nullable();

            $table->index('community_id', 'id_community');
            $table->index('user_id', 'id_user');
            $table->index(['community_id', 'user_id', 'role'], 'idx_community_roles_community_user_role');
            $table->index(['user_id', 'role'], 'idx_community_roles_user_role');

            $table->foreign('user_id', 'fk_community_roles_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('community_id', 'fk_community_roles_community_id')->references('id')->on('communities')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_roles');
    }
};
