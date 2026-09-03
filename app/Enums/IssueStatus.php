<?php

namespace App\Enums;

/**
 * Status isu rapat. "Dibahas" bukan "Selesai": banyak isu yang keluar dari rapat
 * dengan keputusan, tapi keputusannya baru jalan minggu berikutnya.
 */
enum IssueStatus: string
{
    case OPEN = 'open';
    case DISCUSSED = 'discussed';
    case RESOLVED = 'resolved';
    case DROPPED = 'dropped';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Belum Dibahas',
            self::DISCUSSED => 'Sudah Dibahas',
            self::RESOLVED => 'Selesai',
            self::DROPPED => 'Tidak Dilanjutkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::DISCUSSED => 'info',
            self::RESOLVED => 'success',
            self::DROPPED => 'gray',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}
