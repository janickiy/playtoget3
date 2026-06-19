<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_general_ci';

            $table->integer('id', true);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->string('nickname', 255)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->date('birthday')->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('telegram', 255)->nullable();
            $table->text('whatsapp')->nullable();
            $table->text('viber')->nullable();
            $table->string('website', 255)->nullable();
            $table->text('about')->nullable();
            $table->text('about_sport')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('cover_page', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('email', 'idx_users_email');
            $table->index('status', 'users_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
