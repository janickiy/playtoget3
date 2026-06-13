<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sport_blocks')) {
            return;
        }

        $this->dropIndexIfExists('sport_blocks_type_owner_id_active_index');

        if (Schema::hasColumn('sport_blocks', 'active')) {
            Schema::table('sport_blocks', function (Blueprint $table): void {
                $table->dropColumn('active');
            });
        }

        $this->addIndexIfMissing('sport_blocks_type_owner_id_index', ['type', 'owner_id']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('sport_blocks')) {
            return;
        }

        $this->dropIndexIfExists('sport_blocks_type_owner_id_index');

        if (! Schema::hasColumn('sport_blocks', 'active')) {
            Schema::table('sport_blocks', function (Blueprint $table): void {
                $table->boolean('active')->default(false)->after('owner_id');
            });
        }

        $this->addIndexIfMissing('sport_blocks_type_owner_id_active_index', ['type', 'owner_id', 'active']);
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $index, array $columns): void
    {
        if ($this->indexExists($index)) {
            return;
        }

        Schema::table('sport_blocks', function (Blueprint $table) use ($index, $columns): void {
            $table->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('sport_blocks', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                ['sport_blocks', $index],
            );

            return $result !== null;
        }

        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT EXISTS (
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = ?
                    AND table_name = ?
                    AND index_name = ?
            ) AS index_exists',
            [DB::getDatabaseName(), 'sport_blocks', $index],
        );

        return (bool) ($result->index_exists ?? false);
    }
};
