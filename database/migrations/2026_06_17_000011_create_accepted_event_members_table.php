<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accepted_event_members', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('eventable_type', 50)->nullable();
            $table->integer('member_id')->nullable();
            $table->tinyInteger('role')->nullable();
            $table->integer('event_id')->nullable();

            $table->index(['event_id', 'member_id', 'eventable_type', 'role'], 'idx_aem_event_member_type_role');
            $table->index(['member_id', 'role'], 'idx_aem_member_role');

            $table->foreign('member_id', 'fk_accepted_event_members_member_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('event_id', 'fk_accepted_event_members_event_id')->references('id')->on('events')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accepted_event_members');
    }
};
