<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInternalBudget extends EditRecord
{
    protected static string $resource = InternalBudgetResource::class;

    /**
     * Persist Status KOL (field non-dehydrated di repeater items) ke MediaPlanKol.
     * Satu KOL bisa punya banyak item — cukup update sekali per KOL.
     */
    protected function afterSave(): void
    {
        $applied = [];

        foreach ($this->data['items'] ?? [] as $row) {
            $status = $row['kol_status'] ?? null;
            $kolId = $row['media_plan_kol_id'] ?? null;

            // Fallback: ambil media_plan_kol_id dari item bila tidak ada di state (field disabled).
            if (! $kolId && ! empty($row['id'])) {
                $kolId = \App\Models\InternalBudgetItem::find($row['id'])?->media_plan_kol_id;
            }

            if (! $kolId || blank($status) || isset($applied[$kolId])) {
                continue;
            }

            \App\Models\MediaPlanKol::where('id', $kolId)->update(['status' => $status]);
            $applied[$kolId] = true;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_quotation')
                ->label('Generate Quotation')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                // Hanya muncul setelah client approve (Approve Client / Approve AM) & belum ada quotation
                ->visible(fn($record) => $record->quotation === null
                    && in_array($record->status, \App\Models\InternalBudget::STATUS_FINAL, true))
                ->requiresConfirmation()
                ->modalHeading('Generate Quotation')
                ->modalDescription('Generate quotation baru dari data budget ini. Quotation akan dibuat dengan status Draft.')
                ->modalSubmitActionLabel('Generate')
                ->action(function ($record) {
                    if ($record->total_rounded <= 0) {
                        Notification::make()
                            ->title('Tidak Dapat Generate Quotation')
                            ->body('Total budget masih 0. Pastikan budget items sudah diisi.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $quotation = $record->generateQuotation();

                    Notification::make()
                        ->title('Quotation Berhasil Dibuat')
                        ->body("Quotation #{$quotation->quotation_number} berhasil di-generate.")
                        ->success()
                        ->send();

                    return redirect()->route(
                        'filament.office.resources.quotation.edit',
                        ['record' => $quotation->id]
                    );
                }),

            // SPK = kontrak ke KOL (uang keluar), lawan dari Quotation/Invoice (uang masuk).
            // Baru muncul setelah client approve supaya BV tidak terikat ke KOL sebelum
            // harga ke client deal. Idempoten: klik ulang hanya membuat SPK yang belum ada.
            Actions\Action::make('create_spk')
                ->label('Terbitkan SPK')
                ->icon('heroicon-m-document-check')
                ->color('warning')
                ->visible(fn($record) => in_array($record->status, \App\Models\InternalBudget::STATUS_FINAL, true)
                    && $record->items()->where('status', 'approved')->exists())
                ->requiresConfirmation()
                ->modalHeading('Terbitkan SPK ke KOL')
                ->modalDescription(function ($record) {
                    $approved = $record->items()->where('status', 'approved')
                        ->whereNotNull('media_plan_kol_id')
                        ->distinct()->count('media_plan_kol_id');
                    $sudah = \App\Models\BvSPK::where('internal_budget_id', $record->id)->count();

                    return "KOL approved: {$approved}. SPK sudah terbit: {$sudah}. "
                        . 'SPK dibuat satu per KOL (semua SOW-nya digabung) dengan status Draft. '
                        . 'NIK & rekening diambil dari Data KOL — lengkapi di sana agar tidak kosong.';
                })
                ->modalSubmitActionLabel('Terbitkan')
                ->action(function ($record) {
                    $created = \App\Models\BvSPK::createFromBudget($record);

                    if ($created->isEmpty()) {
                        Notification::make()
                            ->title('Tidak Ada SPK Baru')
                            ->body('Semua KOL approved sudah punya SPK.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $kosong = $created->filter(fn($spk) => blank($spk->pihak_kedua_nik)
                        || blank($spk->nomor_rekening))->count();

                    Notification::make()
                        ->title($created->count() . ' SPK Berhasil Dibuat')
                        ->body($kosong > 0
                            ? "{$kosong} SPK masih kosong NIK/rekening — lengkapi Data KOL atau isi manual di SPK."
                            : 'Cek di menu Campaign Area → Contract.')
                        ->success()
                        ->send();

                    if ($created->count() === 1) {
                        return redirect()->route(
                            'filament.office.resources.spk.edit',
                            ['record' => $created->first()->id]
                        );
                    }

                    return redirect()->route('filament.office.resources.spk.index');
                }),

            Actions\Action::make('view_quotation')
                ->label('View Quotation')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->url(fn($record) => $record->quotation
                    ? route('filament.office.resources.quotation.edit', ['record' => $record->quotation->id])
                    : null)
                ->visible(fn($record) => $record->quotation !== null),

            Actions\Action::make('sync_from_media_plan')
                ->label('Sync from Media Plan')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn($record) => $record->mediaPlan !== null && $record->status !== 'approved')
                ->requiresConfirmation()
                ->modalHeading('Sync Budget Items dari Media Plan Internal')
                ->modalDescription('Tombol ini menarik ulang data KOL dan scope item (SOW) dari Media Plan Internal ke halaman ini. Berguna ketika ada perubahan KOL atau scope di Media Plan Internal setelah budget external sudah dibuat. Semua items saat ini akan dihapus dan diganti data terbaru. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Sync Sekarang')
                ->action(function ($record) {
                    $record->mediaPlan->syncInternalBudgetItems();

                    Notification::make()
                        ->title('Budget Items Diperbarui')
                        ->body('Items berhasil disinkronkan dari Media Plan Internal.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['items']);
                }),

            Actions\Action::make('link_review_client')
                ->label('Link Review Client')
                ->icon('heroicon-m-link')
                ->color('info')
                // Muncul saat status "Review ke Client"
                ->visible(fn($record) => $record->status === 'review_client')
                ->modalHeading('Link Review Client')
                ->modalDescription('Bagikan tautan berikut ke client. Client dapat menandai SOW mana yang dipakai (✓ / ✗) dan memberi feedback per item.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->fillForm(function ($record) {
                    $record->generateReviewToken();

                    return ['review_url' => $record->review_url];
                })
                ->form([
                    \Filament\Forms\Components\TextInput::make('review_url')
                        ->label('Tautan Review Client')
                        ->readOnly()
                        ->suffixAction(
                            Actions\Action::make('open')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->label('Buka')
                                ->url(fn($record) => $record->review_url, shouldOpenInNewTab: true)
                        )
                        ->helperText('Salin tautan ini dan kirim ke client.'),
                ]),

            Actions\Action::make('view_media_plan')
                ->label('View Media Plan Internal')
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->url(
                    fn($record) => $record->mediaPlan
                    ? route('filament.office.resources.media-plan-internal.edit', ['record' => $record->mediaPlan->id])
                    : null
                )
                ->visible(fn($record) => $record->mediaPlan !== null),
        ];
    }
}
