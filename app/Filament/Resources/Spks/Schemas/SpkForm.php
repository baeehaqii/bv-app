<?php

namespace App\Filament\Resources\Spks\Schemas;

use App\Models\BvSPK;
use App\Models\DataKol;
use App\Models\FormBrief;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SpkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Satu kolom: tiap section melebar penuh dan berurutan dari atas ke bawah.
            // Section berdampingan bikin select & helper text terjepit di kolom
            // separuh lebar dan tingginya tidak pernah sejajar.
            ->columns(1)
            // Cermin dari penjaga di BvSPK::booted() — supaya user melihat form
            // terkunci, bukan exception saat menekan Simpan.
            ->disabled(fn(?BvSPK $record) => (bool) $record?->isSigned())
            ->components([
                self::lockedNotice(),
                self::sectionInformasi(),
                self::sectionReferensi(),
                self::sectionPihakKedua(),
                self::sectionCampaign(),
                self::sectionPembayaran(),
                self::sectionKlausul(),
                self::sectionAddons(),
                self::sectionCatatan(),
            ]);
    }

    private static function lockedNotice(): Placeholder
    {
        return Placeholder::make('locked_notice')
            ->hiddenLabel()
            ->visible(fn(?BvSPK $record) => (bool) $record?->isSigned())
            ->content(fn(BvSPK $record) => new HtmlString(
                '<div class="rounded-lg bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">'
                . '<strong>SPK terkunci.</strong> Sudah ditandatangani KOL pada '
                . e($record->signed_at->translatedFormat('d M Y H.i'))
                . '. Isi perjanjian tidak bisa diubah lagi — batalkan SPK ini lalu terbitkan yang baru bila ada perubahan.'
                . '</div>'
            ));
    }

    private static function sectionInformasi(): Section
    {
        return Section::make('Informasi SPK')
            ->columns(3)
            ->schema([
                TextInput::make('spk_number')
                    ->label('Nomor SPK')
                    ->required()
                    ->default(fn() => BvSPK::generateNumber())
                    ->maxLength(255),

                DatePicker::make('tanggal_perjanjian')
                    ->label('Tanggal Perjanjian')
                    ->native(false)
                    ->default(now()),

                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Menunggu TTD KOL',
                        'signed' => 'Signed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required()
                    ->native(false),

                FileUpload::make('materai_path')
                    ->label('e-Meterai')
                    ->image()
                    ->disk('public')
                    ->directory('spk-materai')
                    ->imagePreviewHeight('80')
                    ->helperText('Unggah gambar e-meterai yang sudah dibubuhkan. Belum terhubung ke Peruri — '
                        . 'pembelian dan pembubuhannya masih manual. Kosongkan bila SPK belum bermeterai; '
                        . 'PDF akan mencetak kotak kosong di sisi Pihak Pertama.'),
            ]);
    }

    private static function sectionReferensi(): Section
    {
        return Section::make('Referensi Data')
            ->description('PIHAK KEDUA diisi dari Data KOL; campaign & SOW dari Form Brief. Client hanya referensi — dia klien BV, bukan pihak di SPK ini.')
            ->columns(3)
            ->schema([
                Placeholder::make('sumber_approved')
                    ->label('Sumber')
                    ->columnSpanFull()
                    ->visible(fn(?BvSPK $record) => $record !== null)
                    ->content(fn(BvSPK $record) => $record->internal_budget_id
                        ? 'Diterbitkan dari KOL approved pada Media Plan External #'
                            . $record->internal_budget_id
                            . ($record->mediaPlanKol ? ' — KOL: ' . $record->mediaPlanKol->name : '')
                        : 'Dibuat manual (tidak terhubung ke KOL approved). Untuk otomatis: buka Media Plan External → tombol "Terbitkan SPK".'),

                Select::make('data_kol_id')
                    ->label('Data KOL (PIHAK KEDUA)')
                    ->relationship('dataKol', 'username')
                    ->getOptionLabelFromRecordUsing(fn(DataKol $r) => trim("{$r->username} ({$r->channel})"))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $kol = DataKol::find($state);

                        if (! $kol) {
                            return;
                        }

                        $set('pihak_kedua_nama_lengkap', $kol->full_name);
                        $set('pihak_kedua_nama_akun', $kol->channel
                            ? "{$kol->username} ({$kol->channel})"
                            : $kol->username);
                        $set('pihak_kedua_nik', $kol->nik);
                        $set('pihak_kedua_alamat', $kol->address);
                        $set('atas_nama_rekening', $kol->bank_account_name ?: $kol->full_name);
                        $set('nomor_rekening', $kol->bank_account_number);
                        $set('nama_bank', $kol->bank_name);
                        $set('kantor_cabang_bank', $kol->bank_branch);
                    })
                    ->helperText('NIK & rekening ikut terisi otomatis.'),

                Select::make('client_id')
                    ->label('Data Client (referensi)')
                    ->relationship('client', 'nama_brand')
                    ->searchable()
                    ->preload(),

                Select::make('form_brief_id')
                    ->label('Form Brief')
                    ->relationship('formBrief', 'title')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $brief = FormBrief::find($state);

                        if (! $brief) {
                            return;
                        }

                        $set('nama_campaign', $brief->campaign_name ?: null);
                        $set('sow_disepakati', $brief->sow ?: null);
                        $set('timeline_kerja_sama', $brief->timeline ?: null);
                    }),
            ]);
    }

    private static function sectionPihakKedua(): Section
    {
        return Section::make('PIHAK KEDUA')
            ->description('Identitas KOL yang menandatangani. Terisi dari Data KOL, boleh dikoreksi untuk kebutuhan legal.')
            ->columns(3)
            ->schema([
                TextInput::make('pihak_kedua_nama_lengkap')
                    ->label('Nama Lengkap')
                    ->placeholder('Sesuai KTP')
                    ->required(),

                TextInput::make('pihak_kedua_nama_akun')
                    ->label('Nama Akun')
                    ->placeholder('mis. justeenff (TikTok)')
                    ->required(),

                TextInput::make('pihak_kedua_nik')
                    ->label('NIK')
                    ->placeholder('16 digit')
                    ->required(),

                Textarea::make('pihak_kedua_alamat')
                    ->label('Alamat')
                    ->placeholder('Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi')
                    ->rows(2)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    private static function sectionCampaign(): Section
    {
        return Section::make('Campaign & Scope')
            ->description('Dicetak di Pasal 1 — Maksud dan Tujuan. Satu baris SOW = satu poin di dokumen.')
            ->columns(2)
            ->schema([
                TextInput::make('nama_campaign')
                    ->label('Nama Campaign')
                    ->required(),

                TextInput::make('timeline_kerja_sama')
                    ->label('Timeline')
                    ->placeholder('mis. Juli 2026')
                    ->required(),

                Textarea::make('sow_disepakati')
                    ->label('SOW yang Disepakati')
                    ->placeholder("1x TikTok Video\n2x Instagram Story")
                    ->helperText('Satu SOW per baris.')
                    ->rows(4)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    private static function sectionPembayaran(): Section
    {
        return Section::make('Pembayaran')
            ->description('Dicetak di Pasal 3. Nominal harus sama dengan Real Cost di Pembayaran KOL.')
            ->columns(2)
            ->schema([
                TextInput::make('nominal_kesepakatan')
                    ->label('Nominal Kesepakatan')
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, callable $set) => $set(
                        'nominal_terbilang',
                        BvSPK::terbilang((float) $state)
                    ))
                    ->helperText('Netto jasa KOL, di luar pajak.'),

                TextInput::make('nominal_terbilang')
                    ->label('Terbilang')
                    ->placeholder('Terisi otomatis dari nominal'),

                Grid::make(4)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('atas_nama_rekening')->label('Atas Nama Rekening'),
                        TextInput::make('nomor_rekening')->label('Nomor Rekening'),
                        TextInput::make('nama_bank')->label('Bank'),
                        TextInput::make('kantor_cabang_bank')->label('Kantor Cabang'),
                    ]),

                TextInput::make('termin_pembayaran_1')
                    ->label('Termin Pembayaran 1')
                    ->placeholder(BvSPK::TERMIN_1_DEFAULT),

                TextInput::make('termin_pembayaran_2')
                    ->label('Termin Pembayaran 2')
                    ->placeholder('Kosongkan bila pembayaran satu termin'),
            ]);
    }

    /**
     * Klausul opsional sebagai Repeater dengan baris tetap — tidak bisa ditambah,
     * dihapus, atau diurut ulang: daftarnya ditentukan BvSPK::CLAUSES karena tiap
     * kunci punya pasangan ayat di blade PDF.
     *
     * Penyimpanan tetap map ber-kunci (clauses.eksklusivitas.enabled), konversi
     * ke/dari list Repeater lewat BvSPK::clausesToForm/clausesFromForm.
     */
    private static function sectionKlausul(): Section
    {
        return Section::make('Klausul Opsional')
            ->description('Nyalakan/matikan pasal yang ikut dicetak. Ayat yang dimatikan hilang dari dokumen dan nomor ayat sisanya merapat otomatis.')
            ->collapsible()
            ->schema([
                Repeater::make('clauses')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    // Konversi map <-> list ditangani ConvertsClauseState di level page;
                    // default() ini hanya untuk halaman Create yang belum punya data.
                    ->default(fn() => BvSPK::clausesToForm(null))
                    ->itemLabel(fn(array $state) => self::labelKlausul($state))
                    ->schema([
                        Hidden::make('key'),

                        Toggle::make('enabled')
                            ->label('Cetak klausul ini di SPK')
                            ->live(),

                        Textarea::make('text')
                            ->label('Redaksi Klausul')
                            ->rows(4)
                            ->helperText('Kosongkan untuk kembali ke redaksi bawaan.')
                            ->visible(fn(callable $get) => (bool) $get('enabled')),
                    ]),
            ]);
    }

    /** "Eksklusivitas · Pasal 2 · Jangka Waktu Perjanjian — dicetak" */
    private static function labelKlausul(array $state): string
    {
        $c = BvSPK::CLAUSES[$state['key'] ?? ''] ?? null;

        if (! $c) {
            return 'Klausul';
        }

        return $c['label'] . ' · ' . $c['pasal']
            . ' — ' . (($state['enabled'] ?? false) ? 'dicetak' : 'tidak dicetak');
    }

    private static function sectionAddons(): Section
    {
        return Section::make('Add Ons')
            ->description('Kesepakatan tambahan di luar pasal baku. Dicetak sebagai Pasal 9 — Ketentuan Tambahan, dan tidak muncul sama sekali bila kosong.')
            ->collapsible()
            ->schema([
                Repeater::make('addons')
                    ->hiddenLabel()
                    ->addActionLabel('Tambah Add On')
                    ->reorderable()
                    ->collapsed()
                    ->itemLabel(fn(array $state) => $state['title'] ?: 'Add On baru')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->placeholder('mis. Perpanjangan Hak Pakai Konten')
                            ->maxLength(191),

                        Textarea::make('text')
                            ->label('Isi Klausul')
                            ->rows(3)
                            ->required()
                            ->placeholder('Tuliskan kesepakatan tambahannya.'),
                    ]),
            ]);
    }

    private static function sectionCatatan(): Section
    {
        return Section::make('Catatan Internal')
            ->description('Tidak dicetak di dokumen SPK.')
            ->collapsible()
            ->collapsed()
            ->schema([
                Textarea::make('notes')
                    ->hiddenLabel()
                    ->rows(3),
            ]);
    }
}
