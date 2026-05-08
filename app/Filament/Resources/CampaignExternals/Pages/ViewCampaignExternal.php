<?php

namespace App\Filament\Resources\CampaignExternals\Pages;

use App\Filament\Resources\CampaignExternals\CampaignExternalResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaignExternal extends ViewRecord
{
    protected static string $resource = CampaignExternalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_link')
                ->label('Copy Link External')
                ->icon('heroicon-o-clipboard-document')
                ->color('success')
                ->visible(fn() => $this->record->is_public)
                ->action(function () {
                    Notification::make()
                        ->title('Link External')
                        ->body($this->record->public_url)
                        ->info()
                        ->persistent()
                        ->send();
                }),

            Action::make('generate_link')
                ->label('Buat Link External')
                ->icon('heroicon-o-share')
                ->color('warning')
                ->visible(fn() => !$this->record->is_public)
                ->requiresConfirmation()
                ->modalHeading('Buat Link External?')
                ->modalDescription('Link publik akan dibuat. Client dapat melihat progress campaign tanpa login.')
                ->action(function () {
                    $this->record->generatePublicToken();
                    Notification::make()
                        ->title('Link berhasil dibuat!')
                        ->body($this->record->fresh()->public_url)
                        ->success()
                        ->persistent()
                        ->send();
                    $this->refreshFormData(['is_public', 'public_token']);
                }),

            Action::make('view_external')
                ->label('Buka Halaman External')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->visible(fn() => $this->record->is_public)
                ->url(fn() => $this->record->public_url)
                ->openUrlInNewTab(),

            Action::make('revoke_link')
                ->label('Cabut Akses External')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn() => $this->record->is_public)
                ->requiresConfirmation()
                ->modalHeading('Cabut Akses External?')
                ->modalDescription('Link publik akan dinonaktifkan. Client tidak bisa lagi mengakses halaman ini.')
                ->action(function () {
                    $this->record->revokePublicToken();
                    Notification::make()
                        ->title('Akses external dicabut')
                        ->success()
                        ->send();
                    $this->refreshFormData(['is_public', 'public_token']);
                }),
        ];
    }
}
