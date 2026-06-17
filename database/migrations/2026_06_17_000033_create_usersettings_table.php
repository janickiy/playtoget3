<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usersettings', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->tinyInteger('permission_send_message')->default(0);
            $table->tinyInteger('permission_view_profile')->default(0);
            $table->tinyInteger('permission_view_friends')->default(0);
            $table->tinyInteger('permission_view_photo')->default(0);
            $table->tinyInteger('permission_view_video')->default(0);
            $table->tinyInteger('permission_view_wall')->default(0);
            $table->tinyInteger('permission_comment_photo')->default(0);
            $table->tinyInteger('permission_comment_video')->default(0);
            $table->tinyInteger('permission_comment_wall')->default(0);
            $table->enum('notification_friends_request', ['yes', 'no']);
            $table->enum('notification_private_messages', ['yes', 'no']);
            $table->enum('notification_wall_comments', ['yes', 'no']);
            $table->enum('notification_picture_comments', ['yes', 'no']);
            $table->enum('notification_video_comments', ['yes', 'no']);
            $table->enum('notification_answers_in_comments', ['yes', 'no']);
            $table->enum('notification_events', ['yes', 'no']);
            $table->enum('notification_birthdays', ['yes', 'no']);
            $table->integer('user_id')->nullable();

            $table->index('user_id', 'id_user');

            $table->foreign('user_id', 'fk_usersettings_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usersettings');
    }
};
