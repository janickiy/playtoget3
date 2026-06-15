<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('news_rss_sport');

        if (Schema::hasTable('log') && ! Schema::hasTable('logs')) {
            Schema::rename('log', 'logs');
        }

        $this->addRecommendedColumn('communities');
        $this->addRecommendedColumn('sport_blocks');
    }

    public function down(): void
    {
        $this->dropRecommendedColumn('sport_blocks');
        $this->dropRecommendedColumn('communities');

        if (Schema::hasTable('logs') && ! Schema::hasTable('log')) {
            Schema::rename('logs', 'log');
        }
    }

    private function addRecommendedColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'recommended')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->tinyInteger('recommended')->default(0)->after('status');
            $table->index('recommended');
        });
    }

    private function dropRecommendedColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'recommended')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('recommended');
        });
    }
};
