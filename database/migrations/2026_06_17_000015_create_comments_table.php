<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('commentable_type', 50)->nullable();
            $table->integer('content_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('behalfable_type', 50);
            $table->integer('behalf_id');
            $table->text('content')->nullable();
            $table->timestamps();

            $table->integer('parent_id');

            $table->index('content_id', 'id_content');
            $table->index('parent_id', 'id_parent');
            $table->index('user_id', 'id_user');
            $table->index(['commentable_type', 'content_id', 'created_at'], 'idx_comments_type_content_created');
            $table->index(['user_id', 'created_at'], 'idx_comments_user_created');

            $table->foreign('user_id', 'fk_comments_user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
