<?php

namespace App\Enums;

enum ClientStatus: string
{
    case WON = 'won';
    case LOST = 'lost';
    case REVISION = 'revision';
    case MEDIAPLAN = 'mediaplan';
    case AWAITING = 'awaiting';
    case INVOICING = 'invoicing';

    public function getLabel(): string
    {
        return match ($this) {
            self::WON => 'Won',
            self::LOST => 'Lost',
            self::REVISION => 'Revision',
            self::MEDIAPLAN => 'Media Plan',
            self::AWAITING => 'Awaiting',
            self::INVOICING => 'Invoicing',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::WON => 'success',
            self::LOST => 'danger',
            self::REVISION => 'warning',
            self::MEDIAPLAN => 'info',
            self::AWAITING => 'gray',
            self::INVOICING => 'primary',
        };
    }

    /** @return array<string, string> value => label untuk dipakai di Select options */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->getLabel()])
            ->all();
    }
}
