<?php

namespace App\Enums;

/**
 * Empat status yang dipakai sheet OKR (dropdown di kolom F).
 *
 * "Not Done" bukan sinonim "To Do": To Do belum digarap, Not Done sudah lewat
 * periodenya dan tidak tercapai. Itu bedanya laporan jujur dan laporan yang
 * menunda-nunda.
 */
enum OkrStatus: string
{
    case TO_DO = 'to_do';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case NOT_DONE = 'not_done';

    public function getLabel(): string
    {
        return match ($this) {
            self::TO_DO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
            self::NOT_DONE => 'Not Done',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TO_DO => 'gray',
            self::IN_PROGRESS => 'warning',
            self::DONE => 'success',
            self::NOT_DONE => 'danger',
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
