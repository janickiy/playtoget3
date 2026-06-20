<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();

        foreach ($this->settings() as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key_cd' => $setting['key_cd']],
                $setting + [
                    'published' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key_cd', array_column($this->settings(), 'key_cd'))->delete();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function settings(): array
    {
        return [
            [
                'key_cd' => 'SLOGAN',
                'name' => 'Site slogan',
                'type' => 'TEXT',
                'display_value' => 'Footer slogan displayed after the PlayToGet name.',
                'value' => 'Sport inside',
            ],
            [
                'key_cd' => 'MODERATE_EVENTS',
                'name' => 'Moderate events',
                'type' => 'TEXT',
                'display_value' => 'Set to 1 to show only confirmed events on the frontend.',
                'value' => '0',
            ],
            [
                'key_cd' => 'MODERATE_COMMUNITIES',
                'name' => 'Moderate communities',
                'type' => 'TEXT',
                'display_value' => 'Set to 1 to show only confirmed groups and teams on the frontend.',
                'value' => '0',
            ],
        ];
    }
};
