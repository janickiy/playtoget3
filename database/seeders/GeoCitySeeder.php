<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoCitySeeder extends Seeder
{
    /**
     * Seeds cities exported from the current database.
     */
    public function run(): void
    {
        $cities = require database_path('seeders/data/geo_cities.php');

        foreach (array_chunk($cities, 500) as $chunk) {
            DB::table('geo_city')->upsert(
                $chunk,
                ['id'],
                ['country_id', 'region_id', 'name_ru', 'name_en', 'sort'],
            );
        }
    }
}
