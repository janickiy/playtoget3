<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Translate the extended sport catalogs that already exist in production data.
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
     * Restore Russian values when rolling the migration back.
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
            'Авиамодельный спорт' => 'Model Aircraft Sports',
            'Авиационный спорт' => 'Air Sports',
            'Автомодельный спорт' => 'Model Car Racing',
            'Айсшток' => 'Ice Stock Sport',
            'Аквабайк' => 'Aquabike',
            'Акватлон' => 'Aquathlon',
            'акетбол' => 'Racketball',
            'Акробатика' => 'Acrobatics',
            'Акробатический рок-н-ролл' => 'Acrobatic Rock and Roll',
            'Американский футбол' => 'American Football',
            'Апноэ (Фридайвинг)' => 'Apnea (Freediving)',
            'Арбалетный спорт' => 'Crossbow Shooting',
            'Армспорт' => 'Armwrestling',
            'Аэробика спортивная' => 'Sport Aerobics',
            'Баскская пелота' => 'Basque Pelota',
            'Бейсджампинг' => 'BASE Jumping',
            'Биатлон' => 'Biathlon',
            'Блицспринт' => 'Blitz Sprint',
            'Бобслей' => 'Bobsleigh',
            'Бодибилдинг' => 'Bodybuilding',
            'Бокс французский (сават)' => 'Savate',
            'Борьба вольная' => 'Freestyle Wrestling',
            'Борьба греко-римская' => 'Greco-Roman Wrestling',
            'Борьба на поясах' => 'Belt Wrestling',
            'Боулинг' => 'Bowling',
            'Боулспорт' => 'Bowls',
            'Бочче' => 'Bocce',
            'Бридж спортивный' => 'Bridge',
            'Вейкборд' => 'Wakeboarding',
            'Вертолетный спорт' => 'Helicopter Sport',
            'Виндсерфинг' => 'Windsurfing',
            'ВМХ' => 'BMX',
            'Водно-моторный спорт' => 'Powerboating',
            'Воднолыжный спорт' => 'Water Skiing',
            'Воздухоплавание' => 'Ballooning',
            'Гимнастика спортивная' => 'Artistic Gymnastics',
            'Гимнастика художественная' => 'Rhythmic Gymnastics',
            'Гимнастика эстетическая' => 'Aesthetic Group Gymnastics',
            'Гиревой спорт' => 'Kettlebell Sport',
            'Го' => 'Go',
            'Годзю-рю' => 'Goju-Ryu Karate',
            'Голбол' => 'Goalball',
            'Горнолыжный спорт' => 'Alpine Skiing',
            'Городошный спорт' => 'Gorodki',
            'Гребля академическая' => 'Rowing',
            'Гребля на байдарках и каноэ' => 'Canoe Sprint',
            'Гребля на лодках Дракон' => 'Dragon Boat Racing',
            'Гребной слалом' => 'Canoe Slalom',
            'Грэпплинг' => 'Grappling',
            'Дартс' => 'Darts',
            'Дельтапланерный спорт' => 'Hang Gliding',
            'Джиу-джитсу' => 'Jiu-Jitsu',
            'Дуатлон' => 'Duathlon',
            'Ездовой спорт' => 'Sled Dog Racing',
            'Здоровый образ жизни' => 'Healthy Lifestyle',
            'Зимнее плавание (моржевание)' => 'Winter Swimming',
            'Индейский биатлон' => 'Indian Biathlon',
            'Индорхоккей' => 'Indoor Hockey',
            'Кайтинг' => 'Kiting',
            'Каноэ поло' => 'Canoe Polo',
            'Капоэйра' => 'Capoeira',
            'Каратэ' => 'Karate',
            'Картинг' => 'Karting',
            'Каякинг' => 'Kayaking',
            'Кендо' => 'Kendo',
            'Керлинг' => 'Curling',
            'Кинологический спорт' => 'Dog Sport',
            'Комплексное единоборство' => 'Combined Martial Arts',
            'Компьютерный спорт' => 'Esports',
            'Конькобежный спорт' => 'Speed Skating',
            'Корпоративный спорт' => 'Corporate Sports',
            'Корфбол' => 'Korfball',
            'Крикет' => 'Cricket',
            'Крокет' => 'Croquet',
            'Кудо' => 'Kudo',
            'Лапта русская' => 'Russian Lapta',
            'Лаун-боулинг' => 'Lawn Bowls',
            'Лыжное двоеборье' => 'Nordic Combined',
            'Лыжные гонки' => 'Cross-Country Skiing',
            'Лякросс' => 'Lacrosse',
            'Маунтинбайк' => 'Mountain Biking',
            'Маунтинборд' => 'Mountainboarding',
            'Метание лески' => 'Casting Sport',
            'Микс-файт М1' => 'M-1 Mixed Fight',
            'Минигольф' => 'Minigolf',
            'Мотобол' => 'Motoball',
            'Мотоциклетный спорт' => 'Motorcycle Sport',
            'Муай тай' => 'Muay Thai',
            'Настольный футбол' => 'Table Football',
            'Настольный хоккей' => 'Table Hockey',
            'Национальная борьба Татарча корэш' => 'Tatar Koresh Wrestling',
            'Новус' => 'Novuss',
            'Ножевой бой' => 'Knife Fighting',
            'Нэтбол' => 'Netball',
            'Ойна' => 'Oina',
            'Ориентирование спортивное' => 'Orienteering',
            'Панкратион' => 'Pankration',
            'Параглайдинг' => 'Paragliding',
            'Парашютный спорт' => 'Parachuting',
            'Пейнтбол' => 'Paintball',
            'Перетягивание каната' => 'Tug of War',
            'Петанк' => 'Petanque',
            'Плавание в ластах' => 'Finswimming',
            'Планерный спорт' => 'Gliding',
            'Пляжный гандбол' => 'Beach Handball',
            'Пляжный футбол' => 'Beach Soccer',
            'Подводная охота' => 'Spearfishing',
            'Подводное ориентирование' => 'Underwater Orienteering',
            'Подводное регби' => 'Underwater Rugby',
            'Подводное фотографирование' => 'Underwater Photography',
            'Подводный спорт' => 'Underwater Sports',
            'Подводный хоккей' => 'Underwater Hockey',
            'Пожарно-прикладной спорт' => 'Fire and Rescue Sport',
            'Покер спортивный' => 'Sports Poker',
            'Полиатлон' => 'Polyathlon',
            'Поло (конное поло)' => 'Polo',
            'Прыжки в воду' => 'Diving',
            'Прыжки на батуте' => 'Trampoline Gymnastics',
            'Прыжки на лыжах с трамплина' => 'Ski Jumping',
            'Рафтинг' => 'Rafting',
            'Регбилиг' => 'Rugby League',
            'Регбол' => 'Regball',
            'Роллер-спорт' => 'Roller Sports',
            'Рукопашный бой' => 'Hand-to-Hand Combat',
            'Рыболовство спортивное' => 'Sport Fishing',
            'Рэндзю' => 'Renju',
            'Санный спорт' => 'Luge',
            'Северное многоборье' => 'Northern Combined',
            'Силовой экстрим' => 'Strongman',
            'Синхронное плавание' => 'Artistic Swimming',
            'Скайсерфинг' => 'Skysurfing',
            'Скалолазание спортивное' => 'Sport Climbing',
            'Сквош' => 'Squash',
            'Скелетон' => 'Skeleton',
            'Современное пятиборье' => 'Modern Pentathlon',
            'Софтбол' => 'Softball',
            'Спасание жизни' => 'Lifesaving',
            'Спорт для всех' => 'Sport for All',
            'Спорт Чанбара (Спочан)' => 'Sports Chanbara',
            'Спортинг' => 'Sporting Clays',
            'Стрелковый спорт' => 'Shooting Sports',
            'Стритбол' => 'Streetball',
            'Судомодельный спорт' => 'Model Boat Racing',
            'Сумо' => 'Sumo',
            'Танцевальный спорт' => 'DanceSport',
            'Теннис настольный' => 'Table Tennis',
            'Туризм спортивный' => 'Sport Tourism',
            'Укадо' => 'Ukado',
            'Ушу' => 'Wushu',
            'Фехтование' => 'Fencing',
            'Фистбол' => 'Fistball',
            'Фитбол' => 'Fitball',
            'Флаинг диск' => 'Flying Disc',
            'Флорбол' => 'Floorball',
            'Формула 1' => 'Formula 1',
            'Формула 1 на воде' => 'Formula 1 Powerboat Racing',
            'Фристайл' => 'Freestyle Skiing',
            'Хапкидо' => 'Hapkido',
            'Хапсагай' => 'Khapsagay',
            'Ходьба скандинавская' => 'Nordic Walking',
            'Хоккей на траве' => 'Field Hockey',
            'Хоккей с мячом (бенди)' => 'Bandy',
            'Черлидинг' => 'Cheerleading',
            'Шаффлборд' => 'Shuffleboard',
            'Шашки' => 'Draughts',
            'Шорт-трек' => 'Short Track Speed Skating',
            'Экстремальный спорт' => 'Extreme Sports',
            'Этноспорт' => 'Ethnosport',
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
            'Полупрофессионал' => 'Semi-professional',
            'Тренер' => 'Coach',
        ];
    }
};
