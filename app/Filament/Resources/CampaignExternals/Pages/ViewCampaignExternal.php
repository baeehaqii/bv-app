<?php

namespace App\Filament\Resources\CampaignExternals\Pages;

use App\Filament\Resources\CampaignExternals\CampaignExternalResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaignExternal extends ViewRecord
{
    protected static string $resource = CampaignExternalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Dropdown 1 — Link External (halaman progress publik untuk client).
            ActionGroup::make([
                Action::make('generate_link')
                    ->label('Buat Link External')
                    ->icon('heroicon-o-share')
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

                Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-clipboard-document')
                    ->visible(fn() => $this->record->is_public)
                    ->action(function () {
                        Notification::make()
                            ->title('Link External')
                            ->body($this->record->public_url)
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                Action::make('view_external')
                    ->label('Buka Halaman')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn() => $this->record->is_public)
                    ->url(fn() => $this->record->public_url)
                    ->openUrlInNewTab(),

                Action::make('revoke_link')
                    ->label('Cabut Akses')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn() => $this->record->is_public)
                    ->requiresConfirmation()
                    ->modalHeading('Cabut Akses External?')
                    ->modalDescription('Link publik akan dinonaktifkan. Client tidak bisa lagi mengakses halaman ini.')
                    ->action(function () {
                        $this->record->revokePublicToken();
                        Notification::make()->title('Akses external dicabut')->success()->send();
                        $this->refreshFormData(['is_public', 'public_token']);
                    }),
            ])
                ->label('Link External')
                ->icon('heroicon-o-globe-alt')
                ->color($this->record->is_public ? 'success' : 'gray')
                ->button(),

            // Dropdown 2 — Approval Konten (client menyetujui / minta revisi draft).
            // Tampil bila ada draft "waiting_approval" atau link approval sedang aktif.
            ActionGroup::make([
                Action::make('content_review_link')
                    ->label(fn() => $this->record->content_review_is_public ? 'Copy Link Approval' : 'Buat Link Approval')
                    ->icon('heroicon-o-document-check')
                    ->requiresConfirmation(fn() => !$this->record->content_review_is_public)
                    ->modalHeading('Link Approval Konten')
                    ->modalDescription('Bagikan tautan ini ke client untuk menyetujui / meminta revisi draft konten yang berstatus "Waiting Approval".')
                    ->modalSubmitActionLabel('Buat / Tampilkan Link')
                    ->action(function () {
                        $this->record->generateContentReviewToken();
                        Notification::make()
                            ->title($this->record->content_review_is_public ? 'Link Approval Konten' : 'Link approval konten dibuat!')
                            ->body($this->record->fresh()->content_review_url)
                            ->success()
                            ->persistent()
                            ->send();
                        $this->refreshFormData(['content_review_is_public', 'content_review_token']);
                    }),

                Action::make('revoke_content_review')
                    ->label('Cabut Link Approval')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn() => $this->record->content_review_is_public)
                    ->requiresConfirmation()
                    ->modalHeading('Cabut Link Approval Konten?')
                    ->modalDescription('Client tidak bisa lagi mengakses halaman approval konten.')
                    ->action(function () {
                        $this->record->revokeContentReviewToken();
                        Notification::make()->title('Link approval konten dicabut')->success()->send();
                        $this->refreshFormData(['content_review_is_public', 'content_review_token']);
                    }),
            ])
                ->label('Approval Konten')
                ->icon('heroicon-o-document-check')
                ->color($this->record->content_review_is_public ? 'success' : 'warning')
                // ponytail: selalu tampil — PIC bisa generate link approval kapan saja.
                // Halaman client tampilkan storyline waiting_approval/revision/approved.
                ->button(),
        ];
    }
}
