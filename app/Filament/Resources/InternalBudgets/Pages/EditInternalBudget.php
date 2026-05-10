<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInternalBudget extends EditRecord
{
    protected static string $resource = InternalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_quotation')
                ->label('Generate Quotation')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->visible(fn($record) => $record->quotation === null)
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
