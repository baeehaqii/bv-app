<?php

namespace App\Filament\Resources\BvQuotations\Pages;

use App\Filament\Resources\BvQuotations\BvQuotationResource;
use App\Models\BvQuotation;
use App\Service\BvNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditBvQuotation extends EditRecord
{
    protected static string $resource = BvQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // PDF (download & preview) dihapus: quotation dibagikan lewat Link Quotation,
            // pengesahannya urut CEO → Business Development → Client.
            self::signAction('ceo'),
            self::signAction('bd'),

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

    /**
     * Aksi tanda tangan internal (CEO / Business Development).
     * Hanya muncul saat memang urutannya — client tanda tangan sendiri lewat link.
     * Gambar TTD opsional; tanpa gambar pun tetap sah (nama + waktu tercatat).
     */
    private static function signAction(string $role): Action
    {
        $label = BvQuotation::SIGN_FLOW[$role];

        return Action::make("sign_{$role}")
            ->label("Tanda Tangani ({$label})")
            ->icon('heroicon-m-pencil-square')
            ->color('success')
            ->visible(fn($record) => $record->canSign($role))
            ->modalHeading("Tanda Tangan {$label}")
            ->modalDescription('Nama & waktu tanda tangan dicatat sistem. Gambar tanda tangan opsional.')
            ->modalSubmitActionLabel('Tanda Tangani')
            ->fillForm(fn() => [
                'name' => auth()->user()?->name,
                'job_title' => $label,
            ])
            ->form([
                TextInput::make('name')
                    ->label('Nama Penanda Tangan')
                    ->required(),

                TextInput::make('job_title')
                    ->label('Jabatan')
                    ->required(),

                FileUpload::make('image')
                    ->label('Gambar Tanda Tangan (opsional)')
                    ->image()
                    ->directory('signatures')
                    ->disk('public')
                    ->imagePreviewHeight('120'),
            ])
            ->action(function ($record, array $data) use ($role, $label) {
                try {
                    $record->sign($role, $data['name'], $data['job_title'] ?? null, $data['image'] ?? null);
                } catch (\RuntimeException $e) {
                    Notification::make()->title('Gagal Tanda Tangan')->body($e->getMessage())->danger()->send();
                    return;
                }

                $next = $record->nextSigner();

                Notification::make()
                    ->title("Ditandatangani oleh {$label}")
                    ->body($next
                        ? 'Selanjutnya: tanda tangan ' . BvQuotation::SIGN_FLOW[$next] . '.'
                        : 'Semua pihak sudah tanda tangan.')
                    ->success()
                    ->send();
            });
    }
}
