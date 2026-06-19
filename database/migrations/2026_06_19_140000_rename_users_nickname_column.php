<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'users';
    private const OLD_COLUMN = 'second' . 'name';
    private const NEW_COLUMN = 'nickname';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, self::OLD_COLUMN) && ! Schema::hasColumn(self::TABLE, self::NEW_COLUMN)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->renameColumn(self::OLD_COLUMN, self::NEW_COLUMN);
            });

            return;
        }

        if (Schema::hasColumn(self::TABLE, self::OLD_COLUMN) && Schema::hasColumn(self::TABLE, self::NEW_COLUMN)) {
            DB::table(self::TABLE)
                ->where(function ($query): void {
                    $query->whereNull(self::NEW_COLUMN)->orWhere(self::NEW_COLUMN, '');
                })
                ->update([self::NEW_COLUMN => DB::raw(self::OLD_COLUMN)]);

            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropColumn(self::OLD_COLUMN);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(self::TABLE, self::NEW_COLUMN) && ! Schema::hasColumn(self::TABLE, self::OLD_COLUMN)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->renameColumn(self::NEW_COLUMN, self::OLD_COLUMN);
            });

            return;
        }

        if (Schema::hasColumn(self::TABLE, self::NEW_COLUMN) && Schema::hasColumn(self::TABLE, self::OLD_COLUMN)) {
            DB::table(self::TABLE)
                ->where(function ($query): void {
                    $query->whereNull(self::OLD_COLUMN)->orWhere(self::OLD_COLUMN, '');
                })
                ->update([self::OLD_COLUMN => DB::raw(self::NEW_COLUMN)]);

            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropColumn(self::NEW_COLUMN);
            });
        }
    }
};
