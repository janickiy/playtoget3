<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('secondname')->nullable();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('telegram')->nullable();
            $table->text('whatsapp')->nullable();
            $table->text('viber')->nullable();
            $table->string('website')->nullable();
            $table->text('about')->nullable();
            $table->text('about_sport')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('language', ['ru', 'en'])->default('ru');
            $table->boolean('confirmed')->default(false);
            $table->boolean('banned')->default(false);
            $table->boolean('deleted')->default(false);

            $table->index(['confirmed', 'banned', 'deleted']);
            $table->index(['lastname', 'firstname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
