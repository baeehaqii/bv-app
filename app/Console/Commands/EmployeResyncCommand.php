<?php

namespace App\Console\Commands;

use App\Models\BvEmploye;
use Illuminate\Console\Command;

class EmployeResyncCommand extends Command
{
    protected $signature = 'bv:employe-resync {--id= : ID karyawan tertentu}';

    protected $description = 'Re-sync karyawan ke Sales List / Business Director berdasarkan sync_type divisi dan departemen';

    public function handle(): int
    {
        $query = BvEmploye::with('position.department.division');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $employes = $query->get();

        $bar = $this->output->createProgressBar($employes->count());
        $bar->start();

        foreach ($employes as $employe) {
            $employe->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Re-sync selesai untuk {$employes->count()} karyawan.");

        return self::SUCCESS;
    }
}
