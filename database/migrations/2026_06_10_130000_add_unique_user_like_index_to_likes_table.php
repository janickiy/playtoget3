<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('likes')
            ->selectRaw('MIN(id) as keep_id, user_id, content_id, likeable_type, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->whereNotNull('content_id')
            ->whereNotNull('likeable_type')
            ->groupBy('user_id', 'content_id', 'likeable_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('likes')
                ->where('user_id', $duplicate->user_id)
                ->where('content_id', $duplicate->content_id)
                ->where('likeable_type', $duplicate->likeable_type)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('likes', function (Blueprint $table): void {
            $table->unique(['user_id', 'likeable_type', 'content_id'], 'likes_user_type_content_unique');
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table): void {
            $table->dropUnique('likes_user_type_content_unique');
        });
    }
};
