<?php

namespace App\Observers;

use App\Enums\DivisionSyncType;
use App\Models\BvBussinesDirector;
use App\Models\BvEmploye;
use App\Models\BvSalesList;

class BvEmployeObserver
{
    public function saved(BvEmploye $employe): void
    {
        $employe->loadMissing('position.department.division');

        $division = $employe->position?->department?->division;

        if (!$division?->sync_type) {
            $this->detachFromLists($employe);
            return;
        }

        match ($division->sync_type) {
            DivisionSyncType::Sales => $this->syncToSalesList($employe),
            DivisionSyncType::BusinessDirector => $this->syncToBusinessDirector($employe),
        };
    }

    public function deleted(BvEmploye $employe): void
    {
        // Hanya nullify bv_employe_id agar data historis (pipeline, dll) tidak ikut terhapus
        BvSalesList::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);

        BvBussinesDirector::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);
    }

    private function syncToSalesList(BvEmploye $employe): void
    {
        // Jika sebelumnya di-sync ke Business Director, lepaskan tautan (jangan delete)
        BvBussinesDirector::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);

        BvSalesList::updateOrCreate(
            ['bv_employe_id' => $employe->id],
            [
                'nama_sales' => $employe->nama_lengkap,
                'user_id' => $employe->user_id,
                'alamat' => $employe->alamat,
            ]
        );
    }

    private function syncToBusinessDirector(BvEmploye $employe): void
    {
        // Jika sebelumnya di-sync ke Sales List, lepaskan tautan (jangan delete)
        BvSalesList::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);

        BvBussinesDirector::updateOrCreate(
            ['bv_employe_id' => $employe->id],
            [
                'nama_lengkap' => $employe->nama_lengkap,
                'alamat_email' => $employe->email,
                'no_wa' => $employe->whatsapp,
                'status' => 'aktif',
            ]
        );
    }

    private function detachFromLists(BvEmploye $employe): void
    {
        BvSalesList::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);

        BvBussinesDirector::where('bv_employe_id', $employe->id)
            ->update(['bv_employe_id' => null]);
    }
}
