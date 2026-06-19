<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoRegionSeeder extends Seeder
{
    /**
     * Seeds regions exported from the current database.
     */
    public function run(): void
    {
        $regions = require database_path('seeders/data/geo_regions.php');

        foreach (array_chunk($regions, 500) as $chunk) {
            DB::table('geo_region')->upsert(
                $chunk,
                ['id'],
                ['country_id', 'name_ru', 'name_en', 'sort'],
            );
        }
    }
}
