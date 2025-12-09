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
                ->label('View Media Plan')
                ->icon('heroicon-m-chart-bar-square')
                ->color('info')
                ->url(
                    fn($record) => $record->mediaPlan
                    ? route('filament.office.resources.media-plan.edit', ['record' => $record->mediaPlan->id])
                    : null
                )
                ->visible(fn($record) => $record->mediaPlan !== null),

            Actions\Action::make('recalculate')
                ->label('Recalculate Totals')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->action(function ($record) {
                    $record->recalculateTotals();

                    // Sync rates back to KOLs
                    foreach ($record->items as $item) {
                        if ($item->mediaPlanKol) {
                            $item->mediaPlanKol->syncRateFromBudget();
                        }
                    }

                    Notification::make()
                        ->title('Totals Recalculated')
                        ->body('Budget totals and KOL rates have been updated.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['total_rate', 'total_subtotal', 'total_mu_pph', 'total_published_rate', 'total_rounded', 'average_margin_percent', 'warnings']);
                }),

            Actions\Action::make('approve')
                ->label('Approve Budget')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn($record) => $record->status !== 'approved')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'approved']);

                    Notification::make()
                        ->title('Budget Approved')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
