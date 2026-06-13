<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table): void {
            if (! Schema::hasColumn('communities', 'status')) {
                $table->tinyInteger('status')->default(0)->after('type');
            }
        });

        if (Schema::hasColumn('communities', 'banned') || Schema::hasColumn('communities', 'moderate')) {
            DB::table('communities')->update([
                'status' => DB::raw($this->legacyStatusExpression()),
            ]);
        }

        $this->dropIndexIfExists('communities_type_moderate_index');
        $this->dropIndexIfExists('communities_banned_type_index');

        Schema::table('communities', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['banned', 'moderate'],
                fn (string $column): bool => Schema::hasColumn('communities', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->addIndexIfMissing('communities_status_index', ['status']);
        $this->addIndexIfMissing('communities_type_status_index', ['type', 'status']);
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table): void {
            if (! Schema::hasColumn('communities', 'banned')) {
                $table->boolean('banned')->default(false)->after('type');
            }

            if (! Schema::hasColumn('communities', 'moderate')) {
                $table->boolean('moderate')->default(true)->after('sport_type');
            }
        });

        if (Schema::hasColumn('communities', 'status')) {
            DB::table('communities')->update([
                'banned' => DB::raw('CASE WHEN status = 2 THEN 1 ELSE 0 END'),
                'moderate' => DB::raw('CASE WHEN status = 1 THEN 1 ELSE 0 END'),
            ]);
        }

        $this->dropIndexIfExists('communities_status_index');
        $this->dropIndexIfExists('communities_type_status_index');

        Schema::table('communities', function (Blueprint $table): void {
            if (Schema::hasColumn('communities', 'status')) {
                $table->dropColumn('status');
            }
        });

        $this->addIndexIfMissing('communities_type_moderate_index', ['type', 'moderate']);
        $this->addIndexIfMissing('communities_banned_type_index', ['banned', 'type']);
    }

    private function legacyStatusExpression(): string
    {
        $banned = Schema::hasColumn('communities', 'banned') ? 'banned' : '0';
        $moderate = Schema::hasColumn('communities', 'moderate') ? 'moderate' : '0';

        return "CASE WHEN {$banned} = 1 THEN 2 WHEN {$moderate} = 1 THEN 1 ELSE 0 END";
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $index, array $columns): void
    {
        if ($this->indexExists($index)) {
            return;
        }

        Schema::table('communities', function (Blueprint $table) use ($index, $columns): void {
            $table->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('communities', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                ['communities', $index],
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
            [DB::getDatabaseName(), 'communities', $index]
        );

        return (bool) ($result->index_exists ?? false);
    }
};
