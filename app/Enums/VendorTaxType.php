<?php

namespace App\Enums;

enum VendorTaxType: string
{
    case PRIBADI = 'Pribadi';
    case PT_NON_PKP = 'PT Non PKP';
    case PT_PKP = 'PT PKP';
    case CV = 'CV';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRIBADI => 'Pribadi (PPh 2.5%/21)',
            self::PT_NON_PKP => 'PT Non PKP (PPh 23 2%)',
            self::PT_PKP => 'PT PKP (PPh 23 + PPN 11%)',
            self::CV => 'CV (PPh Final 0.5%)',
        };
    }

    public function getGrossUpCoefficient(): float
    {
        return match ($this) {
            self::PRIBADI => 0.975,
            self::PT_NON_PKP => 0.98,
            self::PT_PKP => 0.98,
            self::CV => 0.995,
        };
    }

    public function getTaxValueDisplay(): string
    {
        return match ($this) {
            self::PRIBADI => 'PPh 2.5%',
            self::PT_NON_PKP => 'PPh 23 2%',
            self::PT_PKP => 'PPh 23 2% + PPN 11%',
            self::CV => 'PPh Final 0.5%',
        };
    }

    /**
     * Calculate real cost (MU PPh) based on base rate
     */
    public function calculateRealCost(float $baseRate): float
    {
        if ($baseRate <= 0) {
            return 0;
        }

        return match ($this) {
            self::PRIBADI => $baseRate / 0.975,
            self::PT_NON_PKP => $baseRate / 0.98,
            self::PT_PKP => ($baseRate / 0.98) + ($baseRate * 0.11),
            self::CV => $baseRate / 0.995,
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
