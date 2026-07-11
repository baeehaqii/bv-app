<?php

namespace App\Filament\Actions;

use App\Models\MediaPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GenerateQuotationAction
{
    public static function make(): Action
    {
        return Action::make('generateQuotation')
            ->label('Generate Quotation')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Generate Quotation PDF')
            ->modalDescription('This will generate a quotation PDF for selected KOLs only. Make sure you have selected the KOLs you want to include.')
            ->modalSubmitActionLabel('Generate PDF')
            ->action(function (MediaPlan $record) {
                // Check if there are selected KOLs
                $selectedKols = $record->selectedKols()->get();

                if ($selectedKols->isEmpty()) {
                    Notification::make()
                        ->title('No KOLs Selected')
                        ->body('Please select at least one KOL before generating quotation.')
                        ->danger()
                        ->send();
                    return;
                }

                // Calculate totals
                $subTotal = $selectedKols->sum('rate');
                $ppnPercent = 11;
                $ppnAmount = $subTotal * ($ppnPercent / 100);
                $grandTotal = $subTotal + $ppnAmount;

                // Prepare data
                $data = [
                    'mediaPlan' => $record,
                    'selectedKols' => $selectedKols,
                    'subTotal' => $subTotal,
                    'ppnPercent' => $ppnPercent,
                    'ppnAmount' => $ppnAmount,
                    'grandTotal' => $grandTotal,
                    'quotationDate' => Carbon::now()->format('d M Y'),
                    'preparedBy' => auth()->user()->name ?? 'Beyond Viral Team',
                ];

                // Generate PDF
                $pdf = Pdf::loadView('pdf.quotation', $data);
                $pdf->setPaper('a4', 'landscape');

                // Generate filename
                $filename = 'Quotation_' . str_replace(' ', '_', $record->campaign_name ?? 'Campaign') . '_' . Carbon::now()->format('Ymd') . '.pdf';

                Notification::make()
                    ->title('Quotation Generated')
                    ->body("Quotation for {$selectedKols->count()} KOL(s) has been generated.")
                    ->success()
                    ->send();

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename);
            });
    }
}
