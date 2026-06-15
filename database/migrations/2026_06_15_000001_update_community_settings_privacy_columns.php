<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communities_settings')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE communities_settings
                MODIFY permission_wall TINYINT NOT NULL DEFAULT 0,
                MODIFY permission_photo TINYINT NOT NULL DEFAULT 0,
                MODIFY permission_video TINYINT NOT NULL DEFAULT 0,
                MODIFY type TINYINT NOT NULL DEFAULT 0
        SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('communities_settings')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE communities_settings
                MODIFY permission_wall TINYINT(1) NOT NULL DEFAULT 0,
                MODIFY permission_photo TINYINT(1) NOT NULL DEFAULT 0,
                MODIFY permission_video TINYINT(1) NOT NULL DEFAULT 0,
                MODIFY type TINYINT(1) NOT NULL DEFAULT 0
        SQL);
    }
};
