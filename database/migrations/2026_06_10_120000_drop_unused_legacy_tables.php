<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mail_notification');
        Schema::dropIfExists('sport_events');
    }

    public function down(): void
    {
        Schema::create('mail_notification', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_ru')->nullable();
            $table->string('subject_en');
            $table->text('content_ru');
            $table->text('content_en');
        });

        Schema::create('sport_events', function (Blueprint $table): void {
            $table->id();
            $table->string('header')->nullable();
            $table->string('announce')->nullable();
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }
};
