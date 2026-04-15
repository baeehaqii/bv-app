<?php

namespace App\Helpers;

use Carbon\Carbon;

class PaymentScheduleHelper
{
    /**
     * Kembalikan jadwal payment Jumat Week 1 & Week 3
     * untuk bulan berjalan dan 2 bulan ke depan.
     * Format: ['Y-m-d' => 'Jumat, DD MMM YYYY (Week X)']
     */
    public static function getUpcomingSchedules(int $monthsAhead = 2): array
    {
        $schedules = [];
        $now = Carbon::now();

        for ($i = 0; $i <= $monthsAhead; $i++) {
            $month = $now->copy()->addMonths($i)->startOfMonth();

            // Jumat pertama (Week 1) & Jumat ketiga (Week 3)
            foreach ([1, 3] as $weekNumber) {
                $friday = self::getNthFridayOfMonth($month->year, $month->month, $weekNumber);
                $label = $friday->translatedFormat('l, d M Y') . " (Week {$weekNumber})";
                $schedules[$friday->format('Y-m-d')] = $label;
            }
        }

        // Urutkan berdasarkan tanggal
        ksort($schedules);

        return $schedules;
    }

    /**
     * Dapatkan Jumat ke-N dalam bulan tertentu.
     */
    private static function getNthFridayOfMonth(int $year, int $month, int $n): Carbon
    {
        $firstDayOfMonth = Carbon::create($year, $month, 1);

        // Hari Jumat = 5 (Carbon/ISO: Monday=1 ... Friday=5 ... Sunday=7)
        $daysUntilFriday = (5 - $firstDayOfMonth->dayOfWeek + 7) % 7;
        $firstFriday = $firstDayOfMonth->copy()->addDays($daysUntilFriday);

        return $firstFriday->copy()->addWeeks($n - 1);
    }
}
