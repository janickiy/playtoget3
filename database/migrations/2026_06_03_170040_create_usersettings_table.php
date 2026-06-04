<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usersettings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('permission_send_message')->default(0);
            $table->unsignedTinyInteger('permission_view_profile')->default(0);
            $table->unsignedTinyInteger('permission_view_friends')->default(0);
            $table->unsignedTinyInteger('permission_view_photo')->default(0);
            $table->unsignedTinyInteger('permission_view_video')->default(0);
            $table->unsignedTinyInteger('permission_view_wall')->default(0);
            $table->unsignedTinyInteger('permission_comment_photo')->default(0);
            $table->unsignedTinyInteger('permission_comment_video')->default(0);
            $table->unsignedTinyInteger('permission_comment_wall')->default(0);
            $table->enum('notification_friends_request', ['yes', 'no'])->default('yes');
            $table->enum('notification_private_messages', ['yes', 'no'])->default('yes');
            $table->enum('notification_wall_comments', ['yes', 'no'])->default('yes');
            $table->enum('notification_picture_comments', ['yes', 'no'])->default('yes');
            $table->enum('notification_video_comments', ['yes', 'no'])->default('yes');
            $table->enum('notification_answers_in_comments', ['yes', 'no'])->default('yes');
            $table->enum('notification_events', ['yes', 'no'])->default('yes');
            $table->enum('notification_birthdays', ['yes', 'no'])->default('yes');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usersettings');
    }
};
