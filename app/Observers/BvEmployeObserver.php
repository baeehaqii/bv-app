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
        $employe->load('position.department.division');

        $department = $employe->position?->department;
        $division   = $department?->division;

        // Kumpulkan sync targets dari departemen DAN divisi — keduanya bisa aktif sekaligus
        $targets = array_values(array_filter(array_unique([
            $department?->sync_type?->value,
            $division?->sync_type?->value,
        ])));

        if (empty($targets)) {
            $this->detachFromLists($employe);
            return;
        }

        foreach ($targets as $type) {
            match (DivisionSyncType::from($type)) {
                DivisionSyncType::Sales            => $this->syncToSalesList($employe),
                DivisionSyncType::BusinessDirector => $this->syncToBusinessDirector($employe),
            };
        }

        // Lepaskan dari list yang tidak termasuk target saat ini
        if (!in_array(DivisionSyncType::Sales->value, $targets)) {
            BvSalesList::where('bv_employe_id', $employe->id)
                ->update(['bv_employe_id' => null]);
        }
        if (!in_array(DivisionSyncType::BusinessDirector->value, $targets)) {
            BvBussinesDirector::where('bv_employe_id', $employe->id)
                ->update(['bv_employe_id' => null]);
        }
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
