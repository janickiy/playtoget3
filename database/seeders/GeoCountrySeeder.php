<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoCountrySeeder extends Seeder
{
    /**
     * Seeds countries exported from the current database.
     */
    public function run(): void
    {
        $countries = require database_path('seeders/data/geo_countries.php');

        DB::table('geo_country')->upsert(
            $countries,
            ['id'],
            ['name_ru', 'name_en', 'code', 'sort'],
        );
    }
}
