<?php

namespace App\Repositories\Concerns;

use App\Models\GeoTarget;

trait SyncsGeoTargets
{
    /**
     * Synchronizes the relationship entity with the city in geo_target.
     */
    private function syncGeoTarget(string $targetType, int $targetId, int $cityId): void
    {
        if ($cityId < 1) {
            return;
        }

        GeoTarget::query()->updateOrCreate([
            'target_type' => $targetType,
            'target_id' => $targetId,
        ], [
            'city_id' => $cityId,
        ]);
    }
}
