<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

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
use Filament\Schemas\Components\Section;
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
        return $schema
            ->components([
                Section::make('Identitas KOL')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kol_name')->label('KOL Name')->required()->maxLength(255),
                        TextInput::make('pic')->label('PIC')->maxLength(255),
                        TextInput::make('username')->label('Username')->maxLength(255),
                        TextInput::make('platform')->label('Platform')->maxLength(255),
                        Textarea::make('alamat')->label('Alamat')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Rekening & Pajak')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ktp')->label('KTP / NIK')->maxLength(255),
                        TextInput::make('npwp')->label('NPWP')->maxLength(255),
                        TextInput::make('nama_bank')->label('Nama Bank')->maxLength(255),
                        TextInput::make('nomor_rekening')->label('Nomor Rekening')->maxLength(255),
                        TextInput::make('nama_rekening')->label('Nama Rekening')->maxLength(255),
                    ]),

                Section::make('SOW & Dokumen')
                    ->columns(2)
                    ->schema([
                        Textarea::make('detail_sow')->label('Detail SOW')->rows(2)->columnSpanFull(),
                        TextInput::make('est_timeline')->label('Est. Timeline')->maxLength(255),
                        TextInput::make('paying_agreement')->label('Paying Agreement')->maxLength(255),
                        TextInput::make('link_spk')->label('Link SPK')->url()->maxLength(2048),
                        TextInput::make('link_invoice')->label('Link Invoice')->url()->maxLength(2048),
                    ]),

                Section::make('Pembayaran')
                    ->columns(2)
                    ->schema([
                        TextInput::make('real_cost')->label('Real Cost')->numeric()->prefix('Rp')->default(0),
                        TextInput::make('cost_tax')->label('Cost + Tax')->numeric()->prefix('Rp')->default(0),
                        Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options(CampaignKolPayment::PAYMENT_STATUSES)
                            ->default('waiting_payment')
                            ->native(false)
                            ->required(),
                        TextInput::make('payment_schedule')->label('Payment Schedule')->maxLength(255),
                        DatePicker::make('invoice_date_received')->label('Invoice Date Received')->native(false),
                        TextInput::make('link_bukti_transfer')->label('Link Bukti Transfer')->url()->maxLength(2048),
                    ]),
            ]);
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
                TextColumn::make('link_spk')->label('SPK')->url(fn($record) => $record->link_spk, true)
                    ->formatStateUsing(fn($state) => $state ? 'Buka' : '—')->color('primary')->toggleable(),
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
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),
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
