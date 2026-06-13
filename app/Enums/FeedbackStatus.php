<?php

namespace App\Enums;

enum FeedbackStatus: int
{
    case New = 0;
    case Processing = 1;
    case Closed = 2;

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новое',
            self::Processing => 'В обработке',
            self::Closed => 'Закрыт',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::New => 'bg-warning',
            self::Processing => 'bg-success',
            self::Closed => '',
        };
    }

    /**
     * Возвращает подпись статуса обращения по числовому значению.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Возвращает CSS-класс статуса обращения по числовому значению.
     */
    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    /**
     * Возвращает варианты статусов для форм админки.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::New->value => self::New->label(),
            self::Processing->value => self::Processing->label(),
            self::Closed->value => self::Closed->label(),
        ];
    }
}
