<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_notification', function (Blueprint $table) {
            $table->id();
            $table->string('subject_ru')->nullable();
            $table->string('subject_en');
            $table->text('content_ru');
            $table->text('content_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_notification');
    }
};
