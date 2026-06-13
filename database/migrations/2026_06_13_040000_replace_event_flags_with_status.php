<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'status')) {
                $table->tinyInteger('status')->default(0)->after('address');
            }
        });

        if (Schema::hasColumn('events', 'banned') || Schema::hasColumn('events', 'moderate')) {
            DB::table('events')->update([
                'status' => DB::raw($this->legacyStatusExpression()),
            ]);
        }

        $this->dropIndexIfExists('events_moderate_date_from_index');
        $this->dropIndexIfExists('events_banned_date_from_index');

        Schema::table('events', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['banned', 'moderate'],
                fn (string $column): bool => Schema::hasColumn('events', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->addIndexIfMissing('events_status_index', ['status']);
        $this->addIndexIfMissing('events_status_date_from_index', ['status', 'date_from']);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'moderate')) {
                $table->boolean('moderate')->default(true)->after('updated_at');
            }

            if (! Schema::hasColumn('events', 'banned')) {
                $table->boolean('banned')->default(false)->after('moderate');
            }
        });

        if (Schema::hasColumn('events', 'status')) {
            DB::table('events')->update([
                'banned' => DB::raw('CASE WHEN status = 2 THEN 1 ELSE 0 END'),
                'moderate' => DB::raw('CASE WHEN status = 1 THEN 1 ELSE 0 END'),
            ]);
        }

        $this->dropIndexIfExists('events_status_index');
        $this->dropIndexIfExists('events_status_date_from_index');

        Schema::table('events', function (Blueprint $table): void {
            if (Schema::hasColumn('events', 'status')) {
                $table->dropColumn('status');
            }
        });

        $this->addIndexIfMissing('events_moderate_date_from_index', ['moderate', 'date_from']);
        $this->addIndexIfMissing('events_banned_date_from_index', ['banned', 'date_from']);
    }

    private function legacyStatusExpression(): string
    {
        $banned = Schema::hasColumn('events', 'banned') ? 'banned' : '0';
        $moderate = Schema::hasColumn('events', 'moderate') ? 'moderate' : '0';

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

        Schema::table('events', function (Blueprint $table) use ($index, $columns): void {
            $table->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('events', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                ['events', $index],
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
            [DB::getDatabaseName(), 'events', $index]
        );

        return (bool) ($result->index_exists ?? false);
    }
};
