<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('permission_wall')->default(false);
            $table->boolean('permission_photo')->default(false);
            $table->boolean('permission_video')->default(false);
            $table->boolean('type')->default(false);
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities_settings');
    }
};
