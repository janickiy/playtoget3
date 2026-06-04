<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accepted_event_members', function (Blueprint $table) {
            $table->id();
            $table->string('eventable_type', 50)->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedTinyInteger('role')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            $table->index(['event_id', 'member_id', 'eventable_type', 'role'], 'idx_aem_event_member_type_role');
            $table->index(['member_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accepted_event_members');
    }
};
