<?php

namespace App\Filament\Resources\BvQuotations\Pages;

use App\Filament\Resources\BvQuotations\BvQuotationResource;
use App\Service\BvNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditBvQuotation extends EditRecord
{
    protected static string $resource = BvQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_public_link')
                ->label('Generate Link Client')
                ->icon('heroicon-m-link')
                ->color('success')
                ->visible(fn($record) => !$record->is_public)
                ->requiresConfirmation()
                ->modalHeading('Generate Link Public untuk Client')
                ->modalDescription('Link ini memungkinkan client melihat quotation tanpa perlu login. Pastikan data quotation sudah final sebelum dibagikan.')
                ->modalSubmitActionLabel('Generate Link')
                ->action(function ($record) {
                    $record->generatePublicToken();
                    $record->refresh();

                    Notification::make()
                        ->title('Link Berhasil Dibuat')
                        ->body('Link public quotation berhasil digenerate. Gunakan tombol "Preview Client" untuk melihat tampilan client.')
                        ->success()
                        ->send();

                    try {
                        app(BvNotificationService::class)->quotationLinkGenerated($record);
                    } catch (\Throwable $e) {
                        Log::warning('[EditBvQuotation] Notifikasi WA generate link gagal: ' . $e->getMessage());
                    }

                    $this->refreshFormData([]);
                }),

            Action::make('open_public_link')
                ->label('Preview Client')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('info')
                ->visible(fn($record) => $record->is_public)
                ->url(fn($record) => $record->public_url)
                ->openUrlInNewTab(),

            Action::make('copy_public_link')
                ->label('Salin Link')
                ->icon('heroicon-m-clipboard-document')
                ->color('gray')
                ->visible(fn($record) => $record->is_public)
                ->action(function ($record) {
                    $url = $record->public_url;

                    Notification::make()
                        ->title('Link Public Quotation')
                        ->body($url)
                        ->info()
                        ->persistent()
                        ->send();
                }),

            Action::make('revoke_public_link')
                ->label('Cabut Link')
                ->icon('heroicon-m-lock-closed')
                ->color('danger')
                ->visible(fn($record) => $record->is_public)
                ->requiresConfirmation()
                ->modalHeading('Cabut Akses Link Client')
                ->modalDescription('Client tidak akan bisa lagi mengakses halaman quotation ini. Anda bisa generate link baru kapan saja.')
                ->modalSubmitActionLabel('Ya, Cabut Link')
                ->action(function ($record) {
                    $record->revokePublicToken();

                    Notification::make()
                        ->title('Link Dicabut')
                        ->body('Akses public quotation telah dicabut.')
                        ->warning()
                        ->send();

                    $this->refreshFormData([]);
                }),

            DeleteAction::make(),
        ];
    }
}
