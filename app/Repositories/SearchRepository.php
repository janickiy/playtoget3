<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;

class SearchRepository
{
    /**
     * Connects repositories of entities that are generally searched.
     */
    public function __construct(
        private readonly CommunityRepository $communities,
        private readonly EventRepository $events,
        private readonly SportBlockRepository $sportBlocks,
    ) {
    }

    /**
     * Collects general search results for the main site entities.
     *
     * @return array<string, Collection>
     */
    public function results(string $query, ?User $viewer = null, int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return [
                'teams' => collect(),
                'groups' => collect(),
                'events' => collect(),
                'sportBlocks' => collect(),
            ];
        }

        return [
            'teams' => $this->communities
                ->popularTeams($limit, 0, ['search' => $query], $viewer)
                ->map(fn (array $team): array => $team + [
                    'status' => '',
                    'can_edit' => false,
                ]),
            'groups' => $this->communities
                ->popularGroups($limit, 0, ['search' => $query], $viewer)
                ->map(fn (array $group): array => $group + [
                    'status' => '',
                    'can_edit' => false,
                ]),
            'events' => $this->events->popularEvents($limit, 0, ['search' => $query], $viewer),
            'sportBlocks' => $this->sportBlockResults($query, $limit),
        ];
    }

    /**
     * Searches for sport blocks of all types and adds a route for each card.
     */
    private function sportBlockResults(string $query, int $limit): Collection
    {
        return collect([
            'playground' => [
                'routePrefix' => 'front.playgrounds',
                'typeLabel' => 'Playground',
            ],
            'shop' => [
                'routePrefix' => 'front.shops',
                'typeLabel' => 'Shop',
            ],
            'fitness' => [
                'routePrefix' => 'front.fitness',
                'typeLabel' => 'Fitness',
            ],
        ])->flatMap(function (array $meta, string $type) use ($query, $limit): Collection {
            return $this->sportBlocks
                ->serializedByType($type, ['search' => $query], $limit)
                ->map(fn (array $item): array => $item + [
                    'route_prefix' => $meta['routePrefix'],
                    'type_label' => $meta['typeLabel'],
                ]);
        })->values();
    }
}
