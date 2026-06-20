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
        $countryIds = $this->validIds('geo_country', $targets, 'country_id');
        $regionIds = $this->validIds('geo_region', $targets, 'region_id');
        $cityIds = $this->validIds('geo_city', $targets, 'city_id');

        $targets = array_map(static function (array $target) use ($countryIds, $regionIds, $cityIds): array {
            $target['country_id'] = isset($countryIds[(int) $target['country_id']])
                ? (int) $target['country_id']
                : null;
            $target['region_id'] = isset($regionIds[(int) $target['region_id']])
                ? (int) $target['region_id']
                : null;
            $target['city_id'] = isset($cityIds[(int) $target['city_id']])
                ? (int) $target['city_id']
                : null;

            return $target;
        }, $targets);

        DB::table('geo_target')->upsert(
            $targets,
            ['id', 'target_type', 'target_id'],
            ['country_id', 'region_id', 'city_id'],
        );
    }

    /**
     * Returns valid IDs for the target relation column.
     */
    private function validIds(string $table, array $targets, string $column): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (array $target): int => (int) ($target[$column] ?? 0), $targets),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->mapWithKeys(static fn (int $id): array => [$id => true])
            ->all();
    }
}
