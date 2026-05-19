<?php

namespace App\Enums;

enum DivisionSyncType: string
{
    case Sales = 'sales';
    case BusinessDirector = 'business_director';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales List',
            self::BusinessDirector => 'Business Director',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->all();
    }
}
