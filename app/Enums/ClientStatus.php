<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Status Client — mengikuti dropdown kolom STATUS di sheet PIPELINE BD.
 *
 * Ini status hubungan/tahap deal dari sisi BD, BUKAN status campaign internal
 * (itu SalesStatus). Nilainya sengaja sama dengan sheet supaya migrasi tidak
 * perlu menerjemahkan apa pun.
 */
enum ClientStatus: string
{
    case ON_PROGRESS = 'on_progress';
    case SENT_PARALLEL = 'sent_parallel';
    case HOLD = 'hold';
    case COMPLETE_SENT_TO_CLIENT = 'complete_sent_to_client';
    case REVISION = 'revision';
    case AWAITING_FEEDBACK = 'awaiting_feedback';
    case LOST = 'lost';
    case WON_ON_GOING = 'won_on_going';
    case FINISH = 'finish';

    public function getLabel(): string
    {
        return match ($this) {
            self::ON_PROGRESS => 'ON PROGRESS',
            self::SENT_PARALLEL => 'SENT PARALLEL',
            self::HOLD => 'HOLD',
            self::COMPLETE_SENT_TO_CLIENT => 'COMPLETE - SENT TO CLIENT',
            self::REVISION => 'REVISION',
            self::AWAITING_FEEDBACK => 'AWAITING FEEDBACK',
            self::LOST => 'LOST',
            self::WON_ON_GOING => 'WON - ON GOING',
            self::FINISH => 'FINISH',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ON_PROGRESS, self::REVISION => 'warning',
            self::SENT_PARALLEL, self::WON_ON_GOING => 'info',
            self::HOLD, self::LOST => 'danger',
            self::COMPLETE_SENT_TO_CLIENT => 'success',
            self::AWAITING_FEEDBACK, self::FINISH => 'primary',
        };
    }

    /** @return array<string, string> value => label, untuk options Select */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    /**
     * Teks apa pun dari sheet → case yang cocok, atau null kalau tidak dikenali.
     *
     * Toleran terhadap variasi penulisan: tanda hubung/en dash, spasi ganda,
     * huruf besar-kecil, dan singkatan yang lazim dipakai BD ("WON", "COMPLETE").
     */
    public static function fromSheet(mixed $nilai): ?self
    {
        $teks = trim(preg_replace('/\s+/', ' ', (string) $nilai) ?? '');

        if ($teks === '') {
            return null;
        }

        // Samakan bentuknya: huruf kecil, hanya huruf/angka/spasi.
        $normal = trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower($teks)) ?? '');

        foreach (self::cases() as $case) {
            $label = trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower($case->getLabel())) ?? '');

            if ($normal === $label || $normal === str_replace(' ', '', $label)) {
                return $case;
            }
        }

        // Penulisan pendek yang sering dipakai di sheet lama.
        return match (true) {
            str_contains($normal, 'awaiting') => self::AWAITING_FEEDBACK,
            str_contains($normal, 'complete') => self::COMPLETE_SENT_TO_CLIENT,
            str_contains($normal, 'parallel') => self::SENT_PARALLEL,
            str_contains($normal, 'progress') => self::ON_PROGRESS,
            str_contains($normal, 'revisi') => self::REVISION,
            str_contains($normal, 'won') => self::WON_ON_GOING,
            str_contains($normal, 'lost') => self::LOST,
            str_contains($normal, 'hold') => self::HOLD,
            str_contains($normal, 'finish') => self::FINISH,
            default => null,
        };
    }

    /** Label untuk nilai yang tersimpan di DB; nilai lama/asing dikembalikan apa adanya. */
    public static function labelFor(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return self::tryFrom($value)?->getLabel() ?? $value;
    }
}
