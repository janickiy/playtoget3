<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoleIdToMenuItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(config('menu.table_prefix') . config('menu.table_name_items'), function (Blueprint $table): void {
            $table->integer('role_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('menu.table_prefix') . config('menu.table_name_items'), function (Blueprint $table): void {
            $table->dropColumn('role_id');
        });
    }
}
