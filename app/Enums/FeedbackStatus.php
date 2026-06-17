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
            self::New => 'New',
            self::Processing => 'In progress',
            self::Closed => 'Closed',
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
     * Returns signature statusа обращения по numeric value.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Returns CSS-класс statusа обращения по numeric value.
     */
    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    /**
     * Returns options statusов для форм admin panel.
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
