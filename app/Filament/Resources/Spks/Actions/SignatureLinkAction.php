<?php

namespace App\Filament\Resources\Spks\Actions;

use App\Models\BvSPK;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;

/**
 * Modal "Link Tanda Tangan SPK" — dipakai di header EditSpk maupun row action tabel,
 * jadi satu definisi saja. Pola sama dengan "Link Review Client" di Internal Budget.
 */
class SignatureLinkAction
{
    public static function make(string $name = 'signature_link'): Action
    {
        return Action::make($name)
            ->label('Link Tanda Tangan')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            // SPK yang sudah ditandatangani tidak perlu link lagi — tombolnya jadi "Lihat Hasil".
            ->visible(fn(BvSPK $record) => $record->status !== 'cancelled')
            ->modalHeading('Link Tanda Tangan SPK')
            ->modalDescription('Salin link tanda tangan atau kirim pesannya langsung ke WhatsApp KOL.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->fillForm(function (BvSPK $record) {
                $record->generatePublicToken();

                return [
                    'sign_url' => $record->public_url,
                    'wa_message' => $record->whatsappMessage(),
                ];
            })
            ->schema([
                Placeholder::make('recipient')
                    ->label('Data Penerima SPK')
                    ->columnSpanFull()
                    ->content(fn(BvSPK $record) => new HtmlString(self::recipientTable($record))),

                TextInput::make('sign_url')
                    ->label('Sign Link')
                    ->readOnly()
                    ->columnSpanFull()
                    ->suffixAction(
                        Action::make('open_sign_link')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->label('Buka')
                            ->url(fn(BvSPK $record) => $record->public_url, shouldOpenInNewTab: true)
                    )
                    ->helperText('KOL wajib melewati verifikasi (No. SPK + nama lengkap + platform) sebelum bisa tanda tangan.'),

                Textarea::make('wa_message')
                    ->label('Pesan WhatsApp')
                    ->readOnly()
                    ->rows(9)
                    ->columnSpanFull(),

                Placeholder::make('send')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->content(fn(BvSPK $record) => new HtmlString(self::sendButton($record))),
            ]);
    }

    private static function recipientTable(BvSPK $record): string
    {
        $rows = [
            'Campaign' => $record->nama_campaign ?: '—',
            'Nama KOL' => $record->pihak_kedua_nama_lengkap ?: '—',
            'Akun / Platform' => $record->pihak_kedua_nama_akun ?: '—',
            'No. SPK' => $record->spk_number,
            'Nominal' => $record->formatted_nominal . ' (di luar pajak)',
            'Status' => $record->isSigned()
                ? 'Signed — ' . $record->signed_at->translatedFormat('d M Y H.i')
                : 'Menunggu tanda tangan KOL',
        ];

        $html = '<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">';

        foreach ($rows as $label => $value) {
            $html .= '<div><dt class="text-xs text-gray-500 dark:text-gray-400">' . e($label) . '</dt>'
                . '<dd class="font-medium text-gray-950 dark:text-white">' . e($value) . '</dd></div>';
        }

        return $html . '</dl>';
    }

    private static function sendButton(BvSPK $record): string
    {
        $url = $record->whatsappUrl();

        if (! $url) {
            return '<p class="text-sm text-amber-600 dark:text-amber-400">Nomor WhatsApp KOL belum ada. '
                . 'Isi <strong>No WhatsApp</strong> di Data KOL, atau salin Sign Link di atas dan kirim manual.</p>';
        }

        return '<a href="' . e($url) . '" target="_blank" rel="noopener"'
            . ' class="inline-flex items-center justify-center gap-2 w-full rounded-lg bg-green-600 px-4 py-2.5'
            . ' text-sm font-semibold text-white shadow-sm hover:bg-green-500 transition">'
            . 'Kirim WhatsApp ke ' . e($record->dataKol?->wa_number) . '</a>';
    }
}
