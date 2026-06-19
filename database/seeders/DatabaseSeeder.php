<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SportLevelSeeder::class,
            SportTypeSeeder::class,
            GeoCountrySeeder::class,
            GeoRegionSeeder::class,
            GeoCitySeeder::class,
            GeoTargetSeeder::class,
        ]);
    }
}
