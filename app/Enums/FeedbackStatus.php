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

    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    public static function options(): array
    {
        return [
            self::New->value => self::New->label(),
            self::Processing->value => self::Processing->label(),
            self::Closed->value => self::Closed->label(),
        ];
    }
}
