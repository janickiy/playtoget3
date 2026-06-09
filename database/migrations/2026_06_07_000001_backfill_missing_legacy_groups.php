<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            [
                'id' => 12,
                'type' => 'group',
                'banned' => false,
                'name' => 'Стильные ЗОЖ-сутенеры',
                'about' => 'Здесь собираются стильные сутенеры повернутые на ЗОЖ, а также их черные рабы',
                'created_at' => '2016-04-22 14:41:05',
                'updated_at' => '2016-04-22 14:47:42',
                'avatar' => '1dcf1240f236636400c7ee8a2feb5d69.jpg',
                'cover_page' => '98769cef9a258f0363e5f26f29d86888.jpg',
                'place' => 'Тверь',
                'sport_type' => 'Лапта русская',
                'moderate' => true,
            ],
            [
                'id' => 13,
                'type' => 'group',
                'banned' => false,
                'name' => 'Все о конном виде спорта',
                'about' => "Вы сможете узнать много нового и интересного о лошадях, тонкостях этого спорта, особенностях экипировки, истории появления и многом другом! \r\n\r\nДобро пожаловать!",
                'created_at' => '2016-04-27 15:57:24',
                'updated_at' => null,
                'avatar' => '41eb56e646167ca6f744a23e07137422.jpg',
                'cover_page' => 'eae86db0fca1f6db673664743af005fd.jpg',
                'place' => 'Москва',
                'sport_type' => 'Конный спорт',
                'moderate' => true,
            ],
            [
                'id' => 14,
                'type' => 'group',
                'banned' => false,
                'name' => 'Весенние виды спорта',
                'about' => "Здесь вы сможете узнать все о скейтбординге, роллер спорте, бокинге и роликовых лыжах, а также о мероприятиях, посвященных данным видам спорта, известных спортсменах и многом другом!\r\n\r\nВступай в группу, мы ждем именно тебя!",
                'created_at' => '2016-04-27 16:25:17',
                'updated_at' => null,
                'avatar' => '23c85070c322ea9fd79f1415754026f8.png',
                'cover_page' => '12643b6c0e987180b6c4f2ec4946ed2d.jpg',
                'place' => 'Москва',
                'sport_type' => 'Скейтбординг',
                'moderate' => true,
            ],
            [
                'id' => 15,
                'type' => 'group',
                'banned' => false,
                'name' => '&quot;Я хотел играть против Великих и обыгрывать их&quot; - Аллен Айверсон. Группа о баскетболе',
                'about' => 'Вы сможете узнать нечто новое об этом виде спорта -  от мировых чемпионатов до дворовых соревнований, об особенностях игры, ролях спортсменов и многом другом!',
                'created_at' => '2016-04-27 17:58:44',
                'updated_at' => '2016-04-27 17:59:23',
                'avatar' => '077f6daf32eb573ec741f6c19622bb06.jpg',
                'cover_page' => '129a8ffc1ac3e1cff6d077c98ec6756a.jpg',
                'place' => 'Москва',
                'sport_type' => 'Баскетбол',
                'moderate' => true,
            ],
            [
                'id' => 16,
                'type' => 'group',
                'banned' => false,
                'name' => 'Гонки - это метафора всей жизни',
                'about' => 'Вы сможете почувствовать море драйва, скорости и неподдельных эмоций! Погрузитесь с нами в мир гонок и узнайте больше об истории данного вида спорта, о его разновидностях, особенностях, мировых спортсменах, а главное - следите и болейте вместе с нами за наших ребят на Формуле - 1!',
                'created_at' => '2016-04-28 12:21:27',
                'updated_at' => null,
                'avatar' => '5ac001793cc825748842747c3eb1886c.jpeg',
                'cover_page' => '7201689acf0eec6c14b348aab104cf4c.jpg',
                'place' => 'Москва',
                'sport_type' => 'Автомобильный спорт',
                'moderate' => true,
            ],
            [
                'id' => 17,
                'type' => 'group',
                'banned' => false,
                'name' => 'Вкусные и полезные рецепты',
                'about' => 'Все самое вкусненькое и полезное мы собрали для вас в этой группе. Без вреда для фигуры - проверено командой Playtoget =)',
                'created_at' => '2016-04-28 18:21:44',
                'updated_at' => null,
                'avatar' => '194c67fad6fdbf381aff75523db90f71.jpg',
                'cover_page' => '4630ecf3d5e7e4354e99b74f08fa48f5.jpg',
                'place' => 'Москва',
                'sport_type' => 'Здоровый образ жизни',
                'moderate' => true,
            ],
        ];

        foreach ($groups as $group) {
            DB::table('communities')->updateOrInsert(
                ['id' => $group['id']],
                $group,
            );
        }

        DB::table('communities_settings')->updateOrInsert(
            ['community_id' => 15],
            [
                'permission_wall' => false,
                'permission_photo' => false,
                'permission_video' => false,
                'type' => false,
            ],
        );

        $roles = [
            ['user_id' => 2, 'community_id' => 13, 'role' => 3],
            ['user_id' => 1, 'community_id' => 13, 'role' => 2],
            ['user_id' => 10, 'community_id' => 13, 'role' => 5],
            ['user_id' => 8, 'community_id' => 13, 'role' => 3],
            ['user_id' => 20, 'community_id' => 13, 'role' => 2],
            ['user_id' => 24, 'community_id' => 13, 'role' => 2],
            ['user_id' => 194, 'community_id' => 13, 'role' => 3],
            ['user_id' => 189, 'community_id' => 13, 'role' => 5],
            ['user_id' => 229, 'community_id' => 13, 'role' => 2],
            ['user_id' => 11, 'community_id' => 13, 'role' => 5],
            ['user_id' => 21, 'community_id' => 13, 'role' => 5],
            ['user_id' => 9, 'community_id' => 13, 'role' => 5],
            ['user_id' => 13, 'community_id' => 13, 'role' => 5],
            ['user_id' => 23, 'community_id' => 13, 'role' => 5],
            ['user_id' => 30, 'community_id' => 13, 'role' => 5],
            ['user_id' => 36, 'community_id' => 13, 'role' => 5],
            ['user_id' => 184, 'community_id' => 13, 'role' => 1],
            ['user_id' => 186, 'community_id' => 13, 'role' => 5],
            ['user_id' => 12, 'community_id' => 13, 'role' => 5],
            ['user_id' => 184, 'community_id' => 14, 'role' => 1],
            ['user_id' => 2, 'community_id' => 14, 'role' => 2],
            ['user_id' => 1, 'community_id' => 14, 'role' => 2],
            ['user_id' => 10, 'community_id' => 14, 'role' => 5],
            ['user_id' => 8, 'community_id' => 14, 'role' => 3],
            ['user_id' => 12, 'community_id' => 14, 'role' => 5],
            ['user_id' => 20, 'community_id' => 14, 'role' => 2],
            ['user_id' => 24, 'community_id' => 14, 'role' => 2],
            ['user_id' => 194, 'community_id' => 14, 'role' => 5],
            ['user_id' => 189, 'community_id' => 14, 'role' => 5],
            ['user_id' => 229, 'community_id' => 14, 'role' => 5],
            ['user_id' => 184, 'community_id' => 15, 'role' => 1],
            ['user_id' => 2, 'community_id' => 15, 'role' => 2],
            ['user_id' => 1, 'community_id' => 15, 'role' => 2],
            ['user_id' => 10, 'community_id' => 15, 'role' => 5],
            ['user_id' => 8, 'community_id' => 15, 'role' => 2],
            ['user_id' => 12, 'community_id' => 15, 'role' => 5],
            ['user_id' => 23, 'community_id' => 15, 'role' => 5],
            ['user_id' => 20, 'community_id' => 15, 'role' => 5],
            ['user_id' => 24, 'community_id' => 15, 'role' => 2],
            ['user_id' => 194, 'community_id' => 15, 'role' => 5],
            ['user_id' => 189, 'community_id' => 15, 'role' => 5],
            ['user_id' => 229, 'community_id' => 15, 'role' => 5],
            ['user_id' => 184, 'community_id' => 16, 'role' => 1],
            ['user_id' => 186, 'community_id' => 16, 'role' => 3],
            ['user_id' => 2, 'community_id' => 16, 'role' => 3],
            ['user_id' => 184, 'community_id' => 17, 'role' => 1],
            ['user_id' => 2, 'community_id' => 17, 'role' => 2],
            ['user_id' => 10, 'community_id' => 17, 'role' => 5],
            ['user_id' => 8, 'community_id' => 17, 'role' => 2],
            ['user_id' => 12, 'community_id' => 17, 'role' => 5],
            ['user_id' => 23, 'community_id' => 17, 'role' => 5],
            ['user_id' => 20, 'community_id' => 17, 'role' => 5],
            ['user_id' => 24, 'community_id' => 17, 'role' => 2],
            ['user_id' => 186, 'community_id' => 17, 'role' => 5],
            ['user_id' => 194, 'community_id' => 17, 'role' => 5],
            ['user_id' => 189, 'community_id' => 17, 'role' => 5],
            ['user_id' => 229, 'community_id' => 17, 'role' => 5],
        ];

        $existingUserIds = DB::table('users')
            ->whereIn('id', collect($roles)->pluck('user_id')->unique()->values()->all())
            ->pluck('id')
            ->all();

        foreach ($roles as $role) {
            if (!in_array($role['user_id'], $existingUserIds, true)) {
                continue;
            }

            DB::table('community_roles')->updateOrInsert(
                [
                    'user_id' => $role['user_id'],
                    'community_id' => $role['community_id'],
                ],
                [
                    'role' => $role['role'],
                    'role_description' => null,
                ],
            );
        }
    }

    public function down(): void
    {
        // Data backfill only. Do not delete user content on rollback.
    }
};
