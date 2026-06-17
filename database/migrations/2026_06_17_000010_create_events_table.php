<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->dateTime('date_from')->nullable();
            $table->dateTime('date_to')->nullable();
            $table->text('description')->nullable();
            $table->string('sport_type', 255)->nullable();
            $table->string('cover_page', 255);
            $table->string('place', 100);
            $table->text('address')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->index(['status', 'date_from'], 'events_status_date_from_index');
            $table->index('status', 'events_status_index');
            $table->index('id', 'id');
            $table->index('id', 'id_2');
            $table->index('date_from', 'idx_events_banned_date');
            $table->index('date_from', 'idx_events_moderate_date');
            $table->index('name', 'idx_events_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
