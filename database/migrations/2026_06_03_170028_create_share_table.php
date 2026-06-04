<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shareable_type')->nullable();
            $table->dateTime('time')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();

            $table->index(['content_id', 'shareable_type', 'user_id']);
            $table->index(['user_id', 'time'], 'idx_share_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share');
    }
};
