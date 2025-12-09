<?php

namespace App\Enums;

enum MediaPlanKolStatus: string
{
    case NEW_LIST = 'New List';
    case APPROACHING = 'Approaching';
    case LOCKED = 'Locked';
    case CANCELED = 'Canceled';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW_LIST => 'gray',
            self::APPROACHING => 'warning',
            self::LOCKED => 'success',
            self::CANCELED => 'danger',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
