<?php

namespace App\Filament\Resources\Spks\Pages;

use App\Filament\Resources\Spks\Actions\SignatureLinkAction;
use App\Filament\Resources\Spks\Concerns\ConvertsClauseState;
use App\Filament\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpk extends EditRecord
{
    use ConvertsClauseState;

    protected static string $resource = SpkResource::class;

    /**
     * Setelah simpan, langsung buka modal Link Tanda Tangan — begitu klausul
     * disesuaikan, langkah berikutnya selalu "kirim ke KOL", jadi jangan
     * paksa user mencari tombolnya sendiri.
     * SPK yang sudah ditandatangani dilewati: tidak ada yang perlu dikirim lagi.
     */
    protected function afterSave(): void
    {
        if (! $this->record->isSigned()) {
            $this->mountAction('signature_link');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            SignatureLinkAction::make(),

            Action::make('document')
                ->label('Lihat Dokumen')
                ->icon('heroicon-o-document-text')
                ->url(fn() => SpkResource::getUrl('document', ['record' => $this->record]))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }
}
