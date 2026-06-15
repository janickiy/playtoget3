<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usersettings')) {
            return;
        }

        foreach (['permission_send_message', 'permission_view_profile'] as $column) {
            if (! Schema::hasColumn('usersettings', $column)) {
                continue;
            }

            DB::table('usersettings')
                ->where($column, 2)
                ->update([$column => 1]);
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
