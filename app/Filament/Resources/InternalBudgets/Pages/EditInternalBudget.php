<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInternalBudget extends EditRecord
{
    protected static string $resource = InternalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_media_plan')
                ->label('View Media Plan Internal')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->url(
                    fn($record) => $record->mediaPlan
                    ? route('filament.office.resources.media-plan-internal.edit', ['record' => $record->mediaPlan->id])
                    : null
                )
                ->visible(fn($record) => $record->mediaPlan !== null),

            // Actions\Action::make('download_pdf')
            //     ->label('Download PDF')
            //     ->icon('heroicon-m-arrow-down-tray')
            //     ->color('success')
            //     ->url(fn($record) => route('internal-budget.pdf', ['internalBudget' => $record->id]))
            //     ->openUrlInNewTab()
            //     ->tooltip('Download Internal Budget as PDF'),

            Actions\Action::make('approve')
                ->label('Approve Budget')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn($record) => !in_array($record->status, ['approved']))
                ->requiresConfirmation()
                ->modalHeading('Approve Budget')
                ->modalDescription('Apakah Anda yakin ingin meng-approve budget ini? Campaign akan diaktifkan jika Media Plan sudah berstatus Ongoing.')
                ->modalSubmitActionLabel('Ya, Approve')
                ->action(function ($record) {
                    $record->approve();

                    Notification::make()
                        ->title('Budget Approved')
                        ->body('Internal Budget berhasil di-approve.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'rejection_notes']);
                }),

            Actions\Action::make('reject')
                ->label('Reject Budget')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn($record) => !in_array($record->status, ['rejected']))
                ->form([
                    Textarea::make('rejection_notes')
                        ->label('Alasan Penolakan')
                        ->placeholder('Tuliskan alasan penolakan budget ini...')
                        ->required()
                        ->rows(4)
                        ->minLength(10),
                ])
                ->modalHeading('Reject Budget')
                ->modalSubmitActionLabel('Reject')
                ->action(function ($record, array $data) {
                    $record->reject($data['rejection_notes']);

                    Notification::make()
                        ->title('Budget Rejected')
                        ->body('Internal Budget ditolak dengan catatan: ' . $data['rejection_notes'])
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'rejection_notes']);
                }),

            // Actions\DeleteAction::make(),
        ];
    }
}
