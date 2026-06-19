<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportLevelSeeder extends Seeder
{
    /**
     * Seeds sport levels exported from the current database.
     */
    public function run(): void
    {
        $levels = require database_path('seeders/data/sport_levels.php');

        DB::table('sport_level')->upsert(
            $levels,
            ['id'],
            ['name'],
        );
    }
}
