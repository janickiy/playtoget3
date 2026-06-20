<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Seeds default application settings.
     */
    public function run(): void
    {
        $settings = require database_path('seeders/data/settings.php');

        DB::table('settings')->upsert(
            $settings,
            ['key_cd'],
            ['name', 'type', 'display_value', 'value', 'published', 'updated_at'],
        );
    }
}
