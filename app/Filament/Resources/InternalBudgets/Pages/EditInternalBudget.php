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
                ->color('white')
                ->visible(fn($record) => $record->status !== 'approved')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'approved']);

                    Notification::make()
                        ->title('Budget Approved')
                        ->success()
                        ->send();
                }),

            // Actions\DeleteAction::make(),
        ];
    }
}
