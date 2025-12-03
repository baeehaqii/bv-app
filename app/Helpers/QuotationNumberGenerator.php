<?php

namespace App\Helpers;

use App\Models\MediaPlan;
use Carbon\Carbon;

class QuotationNumberGenerator
{
    /**
     * Generate quotation number with format: Qty/DD/MM/YYYY/urut
     * Example: 1/03/12/2025/0001
     */
    public static function generate(): string
    {
        $now = Carbon::now();
        $date = $now->format('d');
        $month = $now->format('m');
        $year = $now->format('Y');

        // Get sequence number for today
        $sequence = self::getSequenceForToday();

        return sprintf('1/%s/%s/%s/%04d', $date, $month, $year, $sequence);
    }

    /**
     * Get the next sequence number for today
     */
    private static function getSequenceForToday(): int
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        // Count media plans created today with similar date pattern
        $todayCount = MediaPlan::whereDate('created_at', $today)->count();

        return $todayCount + 1;
    }

    /**
     * Validate quotation number format
     */
    public static function validate(string $quotationNumber): bool
    {
        $pattern = '/^\d+\/\d{2}\/\d{2}\/\d{4}\/\d{4}$/';
        return preg_match($pattern, $quotationNumber) === 1;
    }
}
