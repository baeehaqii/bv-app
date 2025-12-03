<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Models\InternalBudget;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMediaPlan extends EditRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_internal_budget')
                ->label('Create Internal Budget')
                ->icon('heroicon-m-plus')
                ->color('success')
                ->action(function ($record) {
                    // Check if internal budget already exists
                    if ($record->internalBudget) {
                        Notification::make()
                            ->warning()
                            ->title('Internal Budget Already Exists')
                            ->body('This Media Plan already has an Internal Budget.')
                            ->send();
                        return;
                    }

                    // Create Internal Budget
                    $internalBudget = InternalBudget::create([
                        'media_plan_id' => $record->id,
                        'scopeofwork_item' => $record->scopeofwork,
                        'qty' => 1,
                        'rate' => null,
                        'subtotal' => 0,
                        'gross_up_coeff' => 0.97,
                        'tax' => 0.05,
                        'mu_pph' => null,
                        'mu_target' => null,
                        'published_rate' => null,
                        'rounded' => null,
                        'margin_percent' => null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Internal Budget Created')
                        ->body('Internal Budget has been created for this Media Plan.')
                        ->send();
                })
                ->visible(fn($record) => !$record->internalBudget),

            Actions\DeleteAction::make(),
        ];
    }
}
