<?php

$now = now();

return [
    [
        'key_cd' => 'EMAIL',
        'name' => 'info@playtoget.com',
        'type' => 'TEXT',
        'display_value' => 'Email',
        'value' => 'info@playtoget.com',
        'published' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'key_cd' => 'NAME',
        'name' => 'Project name',
        'type' => 'TEXT',
        'display_value' => 'Project name',
        'value' => 'PlayToGet',
        'published' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'key_cd' => 'SLOGAN',
        'name' => 'Site slogan',
        'type' => 'TEXT',
        'display_value' => 'Footer slogan displayed after the PlayToGet name.',
        'value' => 'Sport inside',
        'published' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'key_cd' => 'MODERATE_EVENTS',
        'name' => 'Moderate events',
        'type' => 'TEXT',
        'display_value' => 'Set to 1 to show only confirmed events on the frontend.',
        'value' => '0',
        'published' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'key_cd' => 'MODERATE_COMMUNITIES',
        'name' => 'Moderate communities',
        'type' => 'TEXT',
        'display_value' => 'Set to 1 to show only confirmed groups and teams on the frontend.',
        'value' => '0',
        'published' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ],
];
