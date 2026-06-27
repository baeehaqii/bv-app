<?php

namespace App\Helpers;

use App\Models\BvQuotation;
use Carbon\Carbon;

class QuotationNumberGenerator
{
    /**
     * Generate quotation number: BVN/QUOT/YYYY/MM/NNN
     * Sequence per bulan dari tabel bv_quotations.
     */
    public static function generate(): string
    {
        $now = Carbon::now();

        $count = BvQuotation::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        return sprintf(
            'BVN/QUOT/%s/%s/%03d',
            $now->format('Y'),
            $now->format('m'),
            $count + 1
        );
    }
}
