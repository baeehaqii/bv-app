<?php

namespace App\Enums;

enum MediaPlanKolStatus: string
{
    case MOVE_TO_CLIENT = 'Move to Client';
    case APPROVED_BY_CLIENT = 'Approved by Client';
    case UNAVAIL = 'Unavail';
    case NEW_LIST = 'New List';
    case HOLD = 'HOLD';
    case REJECTED = 'Rejected';
    case AVAILABLE = 'AVAILABLE';
    case APPROACHING = 'Approaching';
    case REQ_CLIENT = 'Req Client';
    case NEED_CONFIRMATION = 'Need Confirmation';
    case NEED_RATE_NEGO = 'Need Rate Nego';
    case PAYMENT_GATEWAY = 'Payment Gateway';
    case REFERENSI = 'Referensi';
    case REPLIED = 'Replied';

    /** Status yang hanya dipakai internal (tidak tampil di Media Plan External). */
    public const INTERNAL_ONLY = ['Payment Gateway'];

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::APPROVED_BY_CLIENT, self::AVAILABLE => 'success',
            self::REJECTED, self::UNAVAIL => 'danger',
            self::HOLD, self::APPROACHING, self::NEED_CONFIRMATION, self::NEED_RATE_NEGO => 'warning',
            self::MOVE_TO_CLIENT, self::REQ_CLIENT, self::REPLIED => 'info',
            self::PAYMENT_GATEWAY => 'primary',
            self::NEW_LIST, self::REFERENSI => 'gray',
        };
    }

    /**
     * Semua opsi (Internal) — value => label.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }

    /**
     * Opsi untuk Media Plan External — tanpa status internal-only (Payment Gateway).
     *
     * @return array<string, string>
     */
    public static function toArrayExternal(): array
    {
        return collect(self::cases())
            ->reject(fn (self $s) => in_array($s->value, self::INTERNAL_ONLY, true))
            ->mapWithKeys(fn (self $s) => [$s->value => $s->value])
            ->all();
    }
}
