<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('sport_blocks', 'status')) {
                $table->tinyInteger('status')->default(0)->after('owner_id');
            }
        });

        if (Schema::hasColumn('sport_blocks', 'banned') || Schema::hasColumn('sport_blocks', 'moderate')) {
            DB::table('sport_blocks')->update([
                'status' => DB::raw($this->legacyStatusExpression()),
            ]);
        }

        $this->dropIndexIfExists('sport_blocks_banned_type_index');
        $this->dropIndexIfExists('sport_blocks_moderate_type_index');

        Schema::table('sport_blocks', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['banned', 'moderate'],
                fn (string $column): bool => Schema::hasColumn('sport_blocks', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->addIndexIfMissing('sport_blocks_status_type_index', ['status', 'type']);
    }

    public function down(): void
    {
        Schema::table('sport_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('sport_blocks', 'banned')) {
                $table->boolean('banned')->default(false)->after('owner_id');
            }
        });

        if (Schema::hasColumn('sport_blocks', 'status')) {
            DB::table('sport_blocks')->update([
                'banned' => DB::raw('CASE WHEN status = 2 THEN 1 ELSE 0 END'),
            ]);
        }

        $this->dropIndexIfExists('sport_blocks_status_type_index');

        Schema::table('sport_blocks', function (Blueprint $table): void {
            if (Schema::hasColumn('sport_blocks', 'status')) {
                $table->dropColumn('status');
            }
        });

        $this->addIndexIfMissing('sport_blocks_banned_type_index', ['banned', 'type']);
    }

    private function legacyStatusExpression(): string
    {
        $banned = Schema::hasColumn('sport_blocks', 'banned') ? 'banned' : '0';

        if (Schema::hasColumn('sport_blocks', 'moderate')) {
            return "CASE WHEN {$banned} = 1 THEN 2 WHEN moderate = 1 THEN 1 ELSE 0 END";
        }

        return "CASE WHEN {$banned} = 1 THEN 2 ELSE 1 END";
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
            [DB::getDatabaseName(), 'sport_blocks', $index]
        );

        return (bool) ($result->index_exists ?? false);
    }
};
