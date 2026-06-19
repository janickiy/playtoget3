<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportTypeSeeder extends Seeder
{
    /**
     * Seeds sport types exported from the current database.
     */
    public function run(): void
    {
        $types = require database_path('seeders/data/sport_types.php');
        $typesWithoutParents = array_map(
            static fn (array $type): array => array_replace($type, ['parent_id' => null]),
            $types,
        );

        DB::table('sport_types')->upsert(
            $typesWithoutParents,
            ['id'],
            ['name', 'parent_id'],
        );

        DB::table('sport_types')->upsert(
            $types,
            ['id'],
            ['name', 'parent_id'],
        );
    }
}
