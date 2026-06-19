<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportLevelSeeder extends Seeder
{
    /**
     * Seeds default English sport levels.
     */
    public function run(): void
    {
        foreach ($this->sportLevels() as $name) {
            DB::table('sport_level')->updateOrInsert(
                ['name' => $name],
                ['name' => $name],
            );
        }
    }

    /**
     * Returns sport levels used as default catalog values.
     *
     * @return string[]
     */
    private function sportLevels(): array
    {
        return [
            'Beginner',
            'Amateur',
            'Intermediate',
            'Advanced',
            'Semi-professional',
            'Professional',
            'Coach',
            'Certified coach',
            'Expert',
            'Master',
            'Candidate Master of Sports',
            'Master of Sports',
        ];
    }
}
