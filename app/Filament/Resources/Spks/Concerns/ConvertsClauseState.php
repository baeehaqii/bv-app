<?php

namespace App\Filament\Resources\Spks\Concerns;

use App\Models\BvSPK;

/**
 * Konversi kolom `clauses` antara map ber-kunci (DB) dan list (Repeater Filament).
 *
 * Dilakukan di level PAGE, bukan lewat dehydrateStateUsing() di Repeater:
 * Repeater punya logika dehidrasi sendiri yang menimpa callback itu, sehingga
 * klausul tersimpan sebagai list bernomor 0..5 dan clauses.eksklusivitas.enabled
 * berhenti bisa dibaca. Terbukti lewat test Livewire SpkEditFormTest.
 */
trait ConvertsClauseState
{
    /** Edit: map dari DB → list untuk Repeater. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['clauses'] = BvSPK::clausesToForm($data['clauses'] ?? null);

        return $data;
    }

    /** Edit: list dari Repeater → map untuk DB. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->clausesKeMap($data);
    }

    /** Create: sama, hook-nya saja yang berbeda nama. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->clausesKeMap($data);
    }

    private function clausesKeMap(array $data): array
    {
        if (array_key_exists('clauses', $data)) {
            $data['clauses'] = BvSPK::clausesFromForm($data['clauses']);
        }

        return $data;
    }
}
