<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'status')) {
                $table->tinyInteger('status')->default(0)->after('city');
            }

            if (! Schema::hasColumn('users', 'confirmed_at')) {
                $table->dateTime('confirmed_at')->nullable()->after('status');
            }
        });

        if (Schema::hasColumn('users', 'deleted') || Schema::hasColumn('users', 'banned') || Schema::hasColumn('users', 'confirmed')) {
            DB::table('users')->update([
                'status' => DB::raw($this->legacyStatusExpression()),
                'confirmed_at' => DB::raw($this->confirmedAtExpression()),
            ]);
        }

        $this->dropIndexIfExists('users_confirmed_banned_deleted_index');

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['confirmed', 'banned', 'deleted', 'language'],
                fn (string $column): bool => Schema::hasColumn('users', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->addIndexIfMissing('users_status_index', ['status']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'confirmed')) {
                $table->boolean('confirmed')->default(false)->after('city');
            }

            if (! Schema::hasColumn('users', 'banned')) {
                $table->boolean('banned')->default(false)->after('confirmed');
            }

            if (! Schema::hasColumn('users', 'deleted')) {
                $table->boolean('deleted')->default(false)->after('banned');
            }

            if (! Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('ru')->after('city');
            }
        });

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')->update([
                'confirmed' => DB::raw('CASE WHEN status = 1 THEN 1 ELSE 0 END'),
                'banned' => DB::raw('CASE WHEN status = 2 THEN 1 ELSE 0 END'),
                'deleted' => DB::raw('CASE WHEN status = 3 THEN 1 ELSE 0 END'),
            ]);
        }

        $this->dropIndexIfExists('users_status_index');

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['status', 'confirmed_at'],
                fn (string $column): bool => Schema::hasColumn('users', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->addIndexIfMissing('users_confirmed_banned_deleted_index', ['confirmed', 'banned', 'deleted']);
    }

    private function legacyStatusExpression(): string
    {
        $deleted = Schema::hasColumn('users', 'deleted') ? 'deleted' : '0';
        $banned = Schema::hasColumn('users', 'banned') ? 'banned' : '0';
        $confirmed = Schema::hasColumn('users', 'confirmed') ? 'confirmed' : '0';

        return "CASE WHEN {$deleted} = 1 THEN 3 WHEN {$banned} = 1 THEN 2 WHEN {$confirmed} = 1 THEN 1 ELSE 0 END";
    }

    private function confirmedAtExpression(): string
    {
        if (! Schema::hasColumn('users', 'confirmed')) {
            return 'confirmed_at';
        }

        return "CASE WHEN confirmed = 1 THEN COALESCE(updated_at, created_at, CURRENT_TIMESTAMP) ELSE NULL END";
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $index, array $columns): void
    {
        if ($this->indexExists($index)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($index, $columns): void {
            $table->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($index): void {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $index): bool
    {
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
            [DB::getDatabaseName(), 'users', $index]
        );

        return (bool) ($result->index_exists ?? false);
    }
};
