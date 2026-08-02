<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

use App\Filament\Resources\Spks\SpkResource;
use App\Models\CampaignKolPayment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Pembayaran KOL (Campaign Ongoing Internal) — acuan sheet "OFERO".
 * Baris di-generate dari daftar KOL via aksi "Sync dari KOL"; data bayar tahan re-sync.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pembayaran KOL';

    protected static ?string $recordTitleAttribute = 'kol_name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make($this->paymentSteps())->columnSpanFull(),
        ]);
    }

    /**
     * Langkah wizard Edit Pembayaran. Empat section lama jadi empat step —
     * di modal, section bersebelahan bikin field menyempit jadi seperempat lebar.
     *
     * @return array<int, Step>
     */
    protected function paymentSteps(): array
    {
        return [
            Step::make('Identitas KOL')
                ->description('Nama, akun, alamat')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    TextInput::make('kol_name')->label('KOL Name')->placeholder('Nama KOL sesuai Media Plan')->required()->maxLength(255),
                    TextInput::make('pic')->label('PIC')->placeholder('Nama PIC internal yang menangani KOL ini')->maxLength(255),
                    TextInput::make('username')->label('Username')->placeholder('@username — terisi otomatis dari Database KOL')->maxLength(255),
                    TextInput::make('platform')->label('Platform')->placeholder('instagram / tiktok / youtube')->maxLength(255),
                    Textarea::make('alamat')->label('Alamat')->placeholder('Alamat sesuai KTP — dipakai untuk SPK & dokumen pajak')->rows(2)->columnSpanFull(),
                ]),

            Step::make('Rekening & Pajak')
                ->description('Tujuan transfer & identitas pajak')
                ->icon('heroicon-o-building-library')
                ->columns(2)
                ->schema([
                    TextInput::make('ktp')->label('KTP / NIK')->placeholder('16 digit NIK')->maxLength(255),
                    TextInput::make('npwp')->label('NPWP')->placeholder('Kosongkan bila KOL tidak ber-NPWP (tarif PPh lebih tinggi)')->maxLength(255),
                    TextInput::make('nama_bank')->label('Nama Bank')->placeholder('mis. BCA, Mandiri, SeaBank')->maxLength(255),
                    TextInput::make('nomor_rekening')->label('Nomor Rekening')->placeholder('Tanpa spasi atau tanda hubung')->maxLength(255),
                    TextInput::make('nama_rekening')->label('Nama Rekening')->placeholder('Nama pemilik rekening, harus sama persis dengan buku tabungan')->columnSpanFull()->maxLength(255),
                ]),

            Step::make('SOW & Dokumen')
                ->description('Lingkup kerja, SPK, invoice')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([
                    Textarea::make('detail_sow')->label('Detail SOW')->placeholder('mis. 1x TikTok Video, 2x IG Story')->rows(2)->columnSpanFull(),
                    TextInput::make('est_timeline')->label('Est. Timeline')->placeholder('mis. 1 - 30 Juli 2026')->maxLength(255),
                    TextInput::make('paying_agreement')->label('Paying Agreement')->placeholder('mis. Full payment setelah posting')->maxLength(255),
                    // SPK dari modul SPK KOL dicocokkan otomatis; field ini cuma cadangan manual.
                    TextInput::make('link_spk')->label('Link SPK (manual)')->placeholder('https://drive.google.com/... — hanya bila SPK dibuat di luar sistem')->url()->maxLength(2048)
                        ->helperText(fn($record) => ($spk = $record?->resolveSpk())
                            ? "SPK {$spk->spk_number} sudah tersambung otomatis dari modul SPK KOL — field ini boleh dikosongkan."
                            : 'Belum ada SPK di modul SPK KOL untuk KOL ini.'),
                    TextInput::make('link_invoice')->label('Link Invoice')->placeholder('https://... — invoice yang dikirim KOL ke BV')->url()->maxLength(2048),
                ]),

            Step::make('Pembayaran')
                ->description('Nominal, status, bukti transfer')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    TextInput::make('real_cost')->label('Real Cost')->placeholder('Rate dasar KOL, sebelum gross-up pajak')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('cost_tax')->label('Cost + Tax')->placeholder('Total kas keluar setelah gross-up PPh/PPN')->numeric()->prefix('Rp')->default(0),
                    Select::make('payment_status')
                        ->label('Status Pembayaran')
                        ->options(CampaignKolPayment::PAYMENT_STATUSES)
                        ->placeholder('Pilih status pembayaran')
                        ->default('waiting_payment')
                        ->native(false)
                        ->required()
                        ->helperText(fn($record) => $record?->isPaymentLocked()
                            ? 'Terkunci — arus kas sudah diposting untuk pembayaran ini.'
                            : 'Begitu disimpan sebagai Paid, arus kas langsung diposting dan status tidak bisa dikembalikan.'),
                    TextInput::make('payment_schedule')->label('Payment Schedule')->placeholder('mis. H+14 setelah invoice diterima')->maxLength(255),
                    DatePicker::make('invoice_date_received')->label('Invoice Date Received')->placeholder('Tanggal invoice KOL diterima')->native(false),
                    TextInput::make('link_bukti_transfer')->label('Link Bukti Transfer')->placeholder('https://drive.google.com/... bukti transfer')->url()->maxLength(2048),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kol_name')->label('KOL')->searchable()->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('pic')->label('PIC')->placeholder('—')->toggleable(),
                TextColumn::make('platform')->label('Platform')->placeholder('—')->toggleable(),
                TextColumn::make('nama_bank')->label('Bank')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nomor_rekening')->label('No. Rekening')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_rekening')->label('Nama Rekening')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                // SPK dari modul SPK KOL; kalau belum ada, fallback ke link manual.
                TextColumn::make('spk')->label('SPK')
                    ->state(fn($record) => $record->resolveSpk()?->spk_number ?: ($record->link_spk ? 'Buka' : '—'))
                    ->url(fn($record) => ($spk = $record->resolveSpk())
                        ? SpkResource::getUrl('document', ['record' => $spk])
                        : $record->link_spk, true)
                    ->badge(fn($record) => (bool) $record->resolveSpk())
                    ->color(fn($record) => $record->resolveSpk() || $record->link_spk ? 'primary' : 'gray')
                    ->tooltip(fn($record) => $record->resolveSpk()?->status)
                    ->toggleable(),
                TextColumn::make('link_invoice')->label('Invoice')->url(fn($record) => $record->link_invoice, true)
                    ->formatStateUsing(fn($state) => $state ? 'Buka' : '—')->color('primary')->toggleable(),
                TextColumn::make('real_cost')->label('Real Cost')->money('IDR')->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR')),
                TextColumn::make('cost_tax')->label('Cost + Tax')->money('IDR')->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR')),
                TextColumn::make('payment_status')->label('Status')->badge()
                    ->color(fn($state) => $state === 'paid' ? 'success' : 'warning')
                    ->formatStateUsing(fn($state) => CampaignKolPayment::PAYMENT_STATUSES[$state] ?? ucfirst((string) $state)),
                TextColumn::make('invoice_date_received')->label('Invoice Diterima')->date('d M Y')
                    ->placeholder('—')->toggleable(),
                TextColumn::make('link_bukti_transfer')->label('Bukti Transfer')
                    ->url(fn($record) => $record->link_bukti_transfer, true)
                    ->formatStateUsing(fn($state) => $state ? 'Lihat' : '—')->color('primary'),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(CampaignKolPayment::PAYMENT_STATUSES),
            ])
            ->headerActions([
                Action::make('sync_from_kols')
                    ->label('Sync dari KOL')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        $campaign = $this->getOwnerRecord();
                        $campaign->load('kols');
                        $campaign->syncPaymentRowsFromKols();

                        Notification::make()
                            ->success()
                            ->title('Baris pembayaran disinkronkan dari daftar KOL')
                            ->body('Identitas, alamat & rekening diambil dari Database KOL untuk field yang masih kosong; isian manual tidak ditimpa.')
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->steps($this->paymentSteps())
                    ->skippableSteps()
                    // Field kosong langsung diisi dari Database KOL saat modal dibuka,
                    // jadi tidak bergantung pada "Sync dari KOL" pernah diklik atau tidak.
                    ->fillForm(fn(CampaignKolPayment $record) => $record->formDefaults())
                    // Model sudah menolak perubahannya; di sini cuma memberi tahu kenapa.
                    ->after(function (CampaignKolPayment $record, array $data) {
                        if ($record->isPaymentLocked() && ($data['payment_status'] ?? 'paid') !== 'paid') {
                            Notification::make()
                                ->warning()
                                ->title('Status pembayaran terkunci')
                                ->body("Pembayaran {$record->kol_name} sudah PAID dan arus kasnya sudah diposting, jadi statusnya tidak bisa dikembalikan. Kalau memang salah input, buat jurnal koreksi di modul Cashflow.")
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kol_name', 'asc')
            ->emptyStateHeading('Belum ada baris pembayaran')
            ->emptyStateDescription(new HtmlString('Klik <strong>Sync dari KOL</strong> untuk membuat baris pembayaran dari daftar KOL campaign.'))
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
