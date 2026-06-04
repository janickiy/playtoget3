<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $timestampTables = [
        'admin',
        'catalog',
        'comments',
        'communities',
        'events',
        'messages',
        'news_rss_sport',
        'photoalbums',
        'photos',
        'sport_blocks',
        'sport_events',
        'users',
        'videoalbums',
        'videos',
    ];

    private array $addUpdatedAtTo = [
        'comments',
        'messages',
        'photos',
        'sport_events',
        'videos',
    ];

    public function up(): void
    {
        $originalSqlMode = $this->withoutZeroDateSqlMode();

        try {
            foreach ($this->timestampTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $this->prepareColumn($table, 'created_at');
                $this->prepareColumn($table, 'updated_at');

                if (! Schema::hasColumn($table, 'updated_at') && in_array($table, $this->addUpdatedAtTo, true)) {
                    Schema::table($table, function (Blueprint $schema) {
                        $schema->timestamp('updated_at')->nullable()->after('created_at');
                    });

                    if (Schema::hasColumn($table, 'created_at')) {
                        DB::statement("UPDATE `{$table}` SET `updated_at` = `created_at` WHERE `created_at` IS NOT NULL");
                    }
                }

                $this->convertColumn($table, 'created_at');
                $this->convertColumn($table, 'updated_at');
            }
        } finally {
            $this->restoreSqlMode($originalSqlMode);
        }
    }

    public function down(): void
    {
        foreach ($this->addUpdatedAtTo as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'updated_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $schema) {
                $schema->dropColumn('updated_at');
            });
        }
    }

    private function prepareColumn(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` DATETIME NULL");
        DB::statement("UPDATE `{$table}` SET `{$column}` = NULL WHERE CAST(`{$column}` AS CHAR) = '0000-00-00 00:00:00'");
    }

    private function convertColumn(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NULL DEFAULT NULL");
    }

    private function withoutZeroDateSqlMode(): string
    {
        $mode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $filtered = collect(explode(',', $mode))
            ->reject(fn (string $item) => in_array($item, ['NO_ZERO_IN_DATE', 'NO_ZERO_DATE'], true))
            ->implode(',');

        DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($filtered));

        return $mode;
    }

    private function restoreSqlMode(string $mode): void
    {
        DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($mode));
    }
};
