<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('about')->nullable();
            $table->string('place', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->string('website')->nullable();
            $table->string('type', 20)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(false);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->index(['type', 'owner_id', 'active']);
            $table->index('name');
            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_blocks');
    }
};
