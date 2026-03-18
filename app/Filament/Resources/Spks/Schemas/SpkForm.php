<?php

namespace App\Filament\Resources\Spks\Schemas;

use App\Models\DataClient;
use App\Models\FormBrief;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SpkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi SPK')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('spk_number')
                                ->label('Nomor SPK')
                                ->required()
                                ->default(fn() => 'SPK-' . now()->format('Ymd-His'))
                                ->maxLength(255),

                            DatePicker::make('tanggal_perjanjian')
                                ->label('Tanggal Perjanjian')
                                ->native(false)
                                ->default(now()),

                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'signed' => 'Signed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('draft')
                                ->required()
                                ->native(false),
                        ]),
                    ]),

                Section::make('Referensi Data')
                    ->description('Field merah SPK akan diisi otomatis dari Data Client dan Form Brief setelah dipilih.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('client_id')
                                ->label('Data Client')
                                ->relationship('client', 'nama_brand')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $client = DataClient::find($state);

                                    if (!$client) {
                                        return;
                                    }

                                    $namaAkun = $client->instagram ?: ($client->tiktok ?: null);

                                    $set('pihak_kedua_nama_lengkap', $client->nama_pic ?: null);
                                    $set('pihak_kedua_nama_akun', $namaAkun);
                                    $set('pihak_kedua_alamat', $client->notes ?: null);
                                }),

                            Select::make('form_brief_id')
                                ->label('Form Brief')
                                ->relationship('formBrief', 'title')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $brief = FormBrief::find($state);

                                    if (!$brief) {
                                        return;
                                    }

                                    $set('nama_campaign', $brief->campaign_name ?: null);
                                    $set('sow_disepakati', $brief->sow ?: null);
                                    $set('timeline_kerja_sama', $brief->timeline ?: null);
                                }),
                        ]),
                    ]),

                Section::make('PIHAK KEDUA (Auto dari Data Client)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('pihak_kedua_nama_lengkap')
                                ->label('Nama Lengkap')
                                ->placeholder('Auto dari Data Client > nama_pic')
                                ->required(),

                            TextInput::make('pihak_kedua_nama_akun')
                                ->label('Nama Akun')
                                ->placeholder('Auto dari Data Client > instagram/tiktok')
                                ->required(),

                            TextInput::make('pihak_kedua_nik')
                                ->label('NIK')
                                ->placeholder('NIK belum tersedia di Data Client, isi manual')
                                ->required(),

                            Textarea::make('pihak_kedua_alamat')
                                ->label('Alamat')
                                ->placeholder('Auto dari Data Client (sementara dari notes, bisa diubah manual)')
                                ->required()
                                ->rows(3),
                        ]),
                    ]),

                Section::make('Campaign & Scope (Auto dari Form Brief)')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nama_campaign')
                                ->label('Nama Campaign')
                                ->placeholder('Auto dari Form Brief > campaign_name')
                                ->required(),

                            TextInput::make('timeline_kerja_sama')
                                ->label('Timeline')
                                ->placeholder('Auto dari Form Brief > timeline')
                                ->required(),

                            Textarea::make('sow_disepakati')
                                ->label('SOW yang Disepakati')
                                ->placeholder('Auto dari Form Brief > sow')
                                ->rows(4)
                                ->columnSpanFull()
                                ->required(),
                        ]),
                    ]),

                Section::make('Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nominal_kesepakatan')
                                ->label('Nominal Kesepakatan')
                                ->numeric()
                                ->prefix('Rp'),

                            TextInput::make('nominal_terbilang')
                                ->label('Terbilang')
                                ->placeholder('Contoh: Seratus Juta Rupiah'),

                            TextInput::make('atas_nama_rekening')
                                ->label('Atas Nama Rekening'),

                            TextInput::make('nomor_rekening')
                                ->label('Nomor Rekening'),

                            TextInput::make('nama_bank')
                                ->label('Bank'),

                            TextInput::make('kantor_cabang_bank')
                                ->label('Kantor Cabang'),

                            TextInput::make('termin_pembayaran_1')
                                ->label('Termin Pembayaran 1'),

                            TextInput::make('termin_pembayaran_2')
                                ->label('Termin Pembayaran 2'),
                        ]),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),

                        Placeholder::make('doc_hint')
                            ->label('Preview Dokumen')
                            ->content(fn($record) => $record
                                ? 'Setelah simpan, buka aksi "Dokumen" untuk melihat blade SPK siap print.'
                                : 'Simpan data dulu untuk membuka dokumen SPK.'),
                    ]),
            ]);
    }
}
