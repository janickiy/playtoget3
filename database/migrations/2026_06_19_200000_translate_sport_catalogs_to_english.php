<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Translate existing sport catalog values to English.
     */
    public function up(): void
    {
        foreach ($this->sportTypes() as $russian => $english) {
            DB::table('sport_types')
                ->where('name', $russian)
                ->update(['name' => $english]);
        }

        foreach ($this->sportLevels() as $russian => $english) {
            DB::table('sport_level')
                ->where('name', $russian)
                ->update(['name' => $english]);
        }
    }

    /**
     * Restore Russian catalog values when rolling the migration back.
     */
    public function down(): void
    {
        foreach ($this->sportTypes() as $russian => $english) {
            DB::table('sport_types')
                ->where('name', $english)
                ->update(['name' => $russian]);
        }

        foreach ($this->sportLevels() as $russian => $english) {
            DB::table('sport_level')
                ->where('name', $english)
                ->update(['name' => $russian]);
        }
    }

    /**
     * Returns Russian to English sport type translations.
     *
     * @return array<string, string>
     */
    private function sportTypes(): array
    {
        return [
            'Футбол' => 'Football',
            'Мини-футбол' => 'Futsal',
            'Футзал' => 'Futsal',
            'Баскетбол' => 'Basketball',
            'Волейбол' => 'Volleyball',
            'Пляжный волейбол' => 'Beach Volleyball',
            'Хоккей' => 'Hockey',
            'Хоккей с шайбой' => 'Ice Hockey',
            'Теннис' => 'Tennis',
            'Настольный теннис' => 'Table Tennis',
            'Бадминтон' => 'Badminton',
            'Бег' => 'Running',
            'Легкая атлетика' => 'Athletics',
            'Лёгкая атлетика' => 'Athletics',
            'Плавание' => 'Swimming',
            'Велоспорт' => 'Cycling',
            'Велосипедный спорт' => 'Cycling',
            'Фитнес' => 'Fitness',
            'Йога' => 'Yoga',
            'Бокс' => 'Boxing',
            'Единоборства' => 'Martial Arts',
            'Боевые искусства' => 'Martial Arts',
            'Борьба' => 'Wrestling',
            'Карате' => 'Karate',
            'Дзюдо' => 'Judo',
            'Автомобильный спорт' => 'Motorsport',
            'Автоспорт' => 'Motorsport',
            'Радиоспорт' => 'Radio Sport',
            'Конный спорт' => 'Equestrian',
            'Гольф' => 'Golf',
            'Регби' => 'Rugby',
            'Бейсбол' => 'Baseball',
            'Гандбол' => 'Handball',
            'Танцы' => 'Dance',
            'Лыжи' => 'Skiing',
            'Лыжный спорт' => 'Skiing',
            'Сноуборд' => 'Snowboarding',
            'Фигурное катание' => 'Figure Skating',
            'Шахматы' => 'Chess',
            'Киберспорт' => 'Esports',
            'Пилатес' => 'Pilates',
            'Скалолазание' => 'Climbing',
            'Триатлон' => 'Triathlon',
            'Гонки' => 'Racing',
            'Бильярд' => 'Billiards',
            'Скейтбординг' => 'Skateboarding',
            'Серфинг' => 'Surfing',
            'Гребля' => 'Rowing',
            'Гимнастика' => 'Gymnastics',
            'Художественная гимнастика' => 'Rhythmic Gymnastics',
            'Спортивная гимнастика' => 'Artistic Gymnastics',
            'Аэробика' => 'Aerobics',
            'Кроссфит' => 'CrossFit',
            'Пауэрлифтинг' => 'Powerlifting',
            'Тяжелая атлетика' => 'Weightlifting',
            'Тяжёлая атлетика' => 'Weightlifting',
            'Стрельба' => 'Shooting',
            'Стрельба из лука' => 'Archery',
            'Альпинизм' => 'Mountaineering',
            'Туризм' => 'Hiking',
            'Спортивное ориентирование' => 'Orienteering',
            'Парусный спорт' => 'Sailing',
            'Дайвинг' => 'Diving',
            'Водное поло' => 'Water Polo',
            'Самбо' => 'Sambo',
            'Айкидо' => 'Aikido',
            'Тхэквондо' => 'Taekwondo',
            'Кикбоксинг' => 'Kickboxing',
            'Смешанные единоборства' => 'Mixed Martial Arts',
            'MMA' => 'Mixed Martial Arts',
        ];
    }

    /**
     * Returns Russian to English sport level translations.
     *
     * @return array<string, string>
     */
    private function sportLevels(): array
    {
        return [
            'Новичок' => 'Beginner',
            'Начинающий' => 'Beginner',
            'Любитель' => 'Amateur',
            'Средний' => 'Intermediate',
            'Средний уровень' => 'Intermediate',
            'Продвинутый' => 'Advanced',
            'Профессионал' => 'Professional',
            'Профи' => 'Professional',
            'Эксперт' => 'Expert',
            'Мастер' => 'Master',
            'Кандидат в мастера спорта' => 'Candidate Master of Sports',
            'КМС' => 'Candidate Master of Sports',
            'Мастер спорта' => 'Master of Sports',
            'МС' => 'Master of Sports',
        ];
    }
};
