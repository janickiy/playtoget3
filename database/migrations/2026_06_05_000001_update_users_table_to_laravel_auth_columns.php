<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'confirmation_token',
        'confirmation_sent_at',
        'reset_password_token',
        'reset_password_sent_at',
        'remember_created_at',
    ];

    public function up(): void
    {
        $this->withRelaxedSqlMode(function (): void {
            DB::statement('ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL');

            if (! Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->rememberToken()->after('password');
                });
            }

            $this->dropIndexIfExists('idx_users_confirmation_token');
            $this->dropIndexIfExists('idx_users_reset_password_token');

            $columnsToDrop = array_values(array_filter(
                self::LEGACY_COLUMNS,
                fn (string $column): bool => Schema::hasColumn('users', $column)
            ));

            if ($columnsToDrop !== []) {
                Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        });
    }

    public function down(): void
    {
        $this->withRelaxedSqlMode(function (): void {
            $columnsToAdd = array_values(array_filter(
                self::LEGACY_COLUMNS,
                fn (string $column): bool => ! Schema::hasColumn('users', $column)
            ));

            if ($columnsToAdd !== []) {
                Schema::table('users', function (Blueprint $table) use ($columnsToAdd) {
                    foreach ($columnsToAdd as $column) {
                        match ($column) {
                            'confirmation_token' => $table->string($column)->nullable()->after('password'),
                            'confirmation_sent_at',
                            'reset_password_sent_at',
                            'remember_created_at' => $table->dateTime($column)->nullable()->after('password'),
                            'reset_password_token' => $table->string($column, 200)->nullable()->after('password'),
                        };
                    }
                });
            }

            if (! $this->indexExists('idx_users_confirmation_token') && Schema::hasColumn('users', 'confirmation_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('confirmation_token', 'idx_users_confirmation_token');
                });
            }

            if (! $this->indexExists('idx_users_reset_password_token') && Schema::hasColumn('users', 'reset_password_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('reset_password_token', 'idx_users_reset_password_token');
                });
            }

            if (Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropRememberToken();
                });
            }
        });
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($index) {
            $table->dropIndex($index);
        });
    }

    private function indexExists(string $index): bool
    {
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

        return (bool) $result->index_exists;
    }

    private function withRelaxedSqlMode(callable $callback): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $callback();

            return;
        }

        $originalMode = DB::selectOne('SELECT @@SESSION.sql_mode AS sql_mode')->sql_mode ?? '';

        DB::statement("SET SESSION sql_mode = ''");

        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote($originalMode));
        }
    }
};
