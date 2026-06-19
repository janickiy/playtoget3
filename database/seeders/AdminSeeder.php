<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Seeds administrator accounts exported from the current database.
     */
    public function run(): void
    {
        $admins = require database_path('seeders/data/admins.php');

        DB::table('admin')->upsert(
            $admins,
            ['id'],
            ['login', 'password', 'name', 'role', 'remember_token', 'created_at', 'updated_at'],
        );
    }
}
