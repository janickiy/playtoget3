<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_social_accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('id', true);
            $table->integer('user_id');
            $table->string('provider', 50);
            $table->string('provider_user_id', 255);
            $table->string('email', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('avatar')->nullable();
            $table->timestamps();

            $table->index('user_id', 'user_social_accounts_user_id_index');
            $table->unique(['provider', 'provider_user_id'], 'user_social_provider_user_unique');
            $table->index(['user_id', 'provider'], 'user_social_user_provider_index');

            $table->foreign('user_id', 'fk_user_social_accounts_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_social_accounts');
    }
};
