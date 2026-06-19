<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoTargetSeeder extends Seeder
{
    /**
     * Seeds geo target relations exported from the current database.
     */
    public function run(): void
    {
        $targets = require database_path('seeders/data/geo_targets.php');

        DB::table('geo_target')->upsert(
            $targets,
            ['id', 'target_type', 'target_id'],
            ['country_id', 'region_id', 'city_id'],
        );
    }
}
