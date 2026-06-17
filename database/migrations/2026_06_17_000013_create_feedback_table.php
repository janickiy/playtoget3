<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('subject', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->text('answer')->nullable();
            $table->dateTime('time')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_feedback_status');
            $table->index('time', 'idx_feedback_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
