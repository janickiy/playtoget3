<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        if (Schema::hasTable('settigs')) {
            Schema::rename('settigs', 'settings');

            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key_cd')->unique();
            $table->string('name')->nullable();
            $table->string('type');
            $table->string('display_value')->nullable();
            $table->text('value');
            $table->boolean('published')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && ! Schema::hasTable('settigs')) {
            Schema::rename('settings', 'settigs');
        }
    }
};
