<?php

namespace App\Filament\Pages;

use App\Service\CampaignSheetMigration;
use App\Service\ClientSheetMigration;
use App\Service\GoogleSheetReader;
use App\Service\MediaPlanSheetMigration;
use App\Service\PipelineSheetMigration;
use App\Service\SheetMigration;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

/**
 * Migrasi data dari Google Spreadsheet — pull, bukan push.
 *
 * Laravel membaca sheet-nya sendiri lewat service account, jadi tidak perlu
 * Apps Script yang ditempel di tiap file. Alurnya: tempel link → Preview →
 * Migrasi per-chunk.
 *
 * TANPA QUEUE. Item hasil parse ditaruh di cache, lalu Alpine memanggil
 * processChunk() berulang — tiap panggilan satu request pendek yang menyimpan
 * sebagian baris. Server produksi tidak menjalankan worker, dan satu request
 * panjang untuk ratusan baris pasti kena timeout.
 *
 * Diporting dari service migrasi SOP Siproper.
 */
class MigrasiData extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Migrasi Data';
    protected static ?string $title = 'Migrasi Data (Spreadsheet → BV App)';
    protected static ?string $slug = 'migrasi-data';
    protected static ?int $navigationSort = 90;
    protected string $view = 'filament.pages.migrasi-data';

    /**
     * Profil yang tersedia. Ditaruh di sini, bukan kelas registry tersendiri:
     * satu-satunya yang perlu tahu daftarnya memang halaman ini.
     *
     * @var array<string, class-string<SheetMigration>>
     */
    public const PROFIL = [
        'client' => ClientSheetMigration::class,
        'pipeline' => PipelineSheetMigration::class,
        'campaign' => CampaignSheetMigration::class,
        'mediaplan' => MediaPlanSheetMigration::class,
    ];

    /** @var array<string, mixed> state form: jenis, sheetLink, sheetName */
    public ?array $data = [];

    /** @var array<int, string> nama tab yang ada di spreadsheet itu */
    public array $sheetNames = [];

    /** @var array<string, string> [id => judul] spreadsheet yang bisa diakses service account */
    public array $daftarSpreadsheet = [];

    // ---- Preview ----
    public bool $previewed = false;
    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];
    /** @var array<int, string> judul kolom sheet yang tidak dikenali */
    public array $unmapped = [];
    public int $totalItems = 0;
    public int $warnCount = 0;
    public ?string $errorMessage = null;

    // ---- Migrasi ----
    public bool $migrating = false;
    public bool $finished = false;
    public int $chunkSize = 25;
    public int $processed = 0;
    public int $success = 0;
    public int $skipped = 0;
    public int $failed = 0;
    /** @var array<int, string> */
    public array $notes = [];
    public ?string $cacheKey = null;

    private const PREVIEW_LIMIT = 200;
    private const CACHE_TTL = 1800;

    /**
     * Daftar spreadsheet dari Drive di-cache: isinya jarang berubah, dan tiap
     * render form tidak boleh memanggil API.
     */
    private const CACHE_DAFTAR = 600;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Tanpa kredensial service account halamannya cuma bisa menampilkan error,
        // jadi jangan muncul di sidebar sama sekali.
        return GoogleSheetReader::configured();
    }

    public function mount(): void
    {
        // Tombol "Migrasi dari Spreadsheet" di halaman lain membuka halaman ini
        // dengan jenisnya sudah terpilih.
        $jenis = array_key_exists((string) request('jenis'), self::PROFIL) ? (string) request('jenis') : 'client';

        $this->form->fill([
            'jenis' => $jenis,
            'sumber' => 'paste',
            'bvSalesId' => request('sales'),
            'sheetName' => app(self::PROFIL[$jenis])->defaultSheetName(),
        ]);
    }

    /**
     * Semua spreadsheet yang di-share ke service account. Dipanggil hanya saat
     * user memilih mode "daftar" atau menekan Muat Ulang — bukan tiap render.
     */
    public function muatDaftarSpreadsheet(bool $paksa = false): void
    {
        $this->errorMessage = null;

        if ($paksa) {
            Cache::forget('migrasi:daftar-spreadsheet');
        }

        try {
            $this->daftarSpreadsheet = Cache::remember(
                'migrasi:daftar-spreadsheet',
                self::CACHE_DAFTAR,
                fn() => app(GoogleSheetReader::class)->listSpreadsheets(),
            );
        } catch (\Throwable $e) {
            $this->daftarSpreadsheet = [];
            $this->errorMessage = 'Daftar spreadsheet tidak bisa diambil: ' . $e->getMessage();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Radio::make('sumber')
                    ->label('Sumber spreadsheet')
                    ->options([
                        'paste' => 'Tempel link',
                        'daftar' => 'Pilih dari yang di-share',
                    ])
                    ->default('paste')
                    ->inline()
                    ->inlineLabel(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state) {
                        $this->previewed = false;

                        if ($state === 'daftar' && $this->daftarSpreadsheet === []) {
                            $this->muatDaftarSpreadsheet();
                        }
                    }),

                Select::make('spreadsheetId')
                    ->label('Spreadsheet')
                    ->options(fn() => $this->daftarSpreadsheet)
                    ->searchable()
                    ->native(false)
                    ->visible(fn(callable $get) => $get('sumber') === 'daftar')
                    ->helperText(fn() => count($this->daftarSpreadsheet) . ' spreadsheet terbaca. '
                        . 'Yang muncul di sini hanya yang di-share ke email service account.')
                    ->live()
                    // Link dan daftar memakai jalur yang sama: id yang dipilih
                    // ditulis ke sheetLink, karena extractId() menerima id mentah.
                    ->afterStateUpdated(function (?string $state, callable $set) {
                        $set('sheetLink', $state);
                        $this->muatNamaTab();
                    }),

                Select::make('jenis')
                    ->label('Jenis data')
                    ->options(collect(self::PROFIL)->map(fn(string $kelas) => app($kelas)->label())->all())
                    ->default('client')
                    ->required()
                    ->live()
                    ->native(false)
                    // Tab bawaan tiap profil berbeda, dan preview lama tidak lagi
                    // berlaku begitu jenisnya diganti.
                    ->afterStateUpdated(function (callable $set) {
                        $set('sheetName', $this->profil()->defaultSheetName());
                        $this->previewed = false;
                    }),

                TextInput::make('sheetLink')
                    ->label('Link Google Sheets')
                    ->visible(fn(callable $get) => $get('sumber') !== 'daftar')
                    ->placeholder('https://docs.google.com/spreadsheets/d/.../edit')
                    ->helperText('Sheet-nya harus di-share ke email service account (minimal Viewer).')
                    ->required(fn(callable $get) => $get('sumber') !== 'daftar')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn() => $this->muatNamaTab()),

                Select::make('bvSalesId')
                    ->label('Deal di Sales Activity Tracker')
                    ->helperText('KOL dari sheet masuk ke Media Plan milik deal ini. '
                        . 'Kalau Media Plan-nya belum ada, dibuatkan otomatis.')
                    ->options(fn() => \App\Models\BvSales::orderByDesc('id')->limit(200)->get()
                        ->mapWithKeys(fn($s) => [$s->id => $s->event_name
                            . ($s->company_name ? ' — ' . $s->company_name : '')])
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->required(fn(callable $get) => $get('jenis') === 'mediaplan')
                    ->visible(fn(callable $get) => $get('jenis') === 'mediaplan'),

                Select::make('sheetName')
                    ->label('Tab')
                    ->options(fn() => array_combine($this->sheetNames, $this->sheetNames))
                    ->helperText(fn() => 'Kosong = tab pertama. Bawaan untuk jenis ini: '
                        . ($this->profil()->defaultSheetName() ?? 'tab pertama') . '.')
                    ->native(false)
                    ->visible(fn() => $this->sheetNames !== []),
            ]);
    }

    public function profil(): SheetMigration
    {
        $profil = app(self::PROFIL[$this->data['jenis'] ?? 'client'] ?? ClientSheetMigration::class);

        // Profil KOL List perlu tahu Media Plan tujuannya; profil lain tidak.
        return $profil instanceof MediaPlanSheetMigration
            ? $profil->untukSales($this->data['bvSalesId'] ?? null)
            : $profil;
    }

    /** Isi dropdown tab begitu link ditempel, sekalian uji akses lebih awal. */
    public function muatNamaTab(): void
    {
        $this->reset(['sheetNames', 'errorMessage']);

        $id = GoogleSheetReader::extractId((string) ($this->data['sheetLink'] ?? ''));

        if (! $id) {
            return;
        }

        try {
            $this->sheetNames = app(GoogleSheetReader::class)->sheetNames($id);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Sheet tidak bisa dibuka: ' . $e->getMessage();
        }
    }

    public function preview(): void
    {
        $this->reset(['previewed', 'previewRows', 'unmapped', 'totalItems', 'warnCount',
            'errorMessage', 'finished', 'processed', 'success', 'skipped', 'failed', 'notes']);

        $id = GoogleSheetReader::extractId((string) ($this->data['sheetLink'] ?? ''));

        if (! $id) {
            $this->errorMessage = 'Link Google Sheets tidak dikenali.';

            return;
        }

        if (($this->data['jenis'] ?? null) === 'mediaplan' && blank($this->data['bvSalesId'] ?? null)) {
            $this->errorMessage = 'Pilih dulu deal di Sales Activity Tracker.';

            return;
        }

        try {
            $rows = app(GoogleSheetReader::class)->readRows($id, $this->data['sheetName'] ?? null);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Gagal membaca sheet: ' . $e->getMessage();

            return;
        }

        $migrasi = $this->profil();

        if ($rows === []) {
            $this->errorMessage = 'Tab-nya kosong.';

            return;
        }

        if ($migrasi->mapHeaders($migrasi->headerRow($rows)) === []) {
            $this->errorMessage = 'Tidak ada judul kolom yang dikenali di baris pertama untuk jenis "'
                . $migrasi->label() . '". Pastikan tab dan jenis datanya cocok.';

            return;
        }

        $items = $migrasi->parseRows($rows);
        $this->unmapped = $migrasi->unmappedHeaders($migrasi->headerRow($rows));
        $this->totalItems = count($items);
        $this->warnCount = collect($items)->whereNotNull('_note')->count();

        $this->previewRows = collect($items)->take(self::PREVIEW_LIMIT)->values()->all();

        $this->cacheKey = 'migrasi:' . ($this->data['jenis'] ?? 'client') . ':' . auth()->id() . ':' . md5($id . ($this->data['sheetName'] ?? ''));
        Cache::put($this->cacheKey, $items, self::CACHE_TTL);

        $this->previewed = true;
    }

    public function startMigration(): void
    {
        if (! $this->previewed || ! $this->cacheKey || ! Cache::has($this->cacheKey)) {
            $this->errorMessage = 'Preview belum siap atau sudah kedaluwarsa — klik Preview lagi.';

            return;
        }

        $this->processed = $this->success = $this->skipped = $this->failed = 0;
        $this->notes = [];
        $this->finished = false;
        $this->migrating = true;

        $this->dispatch('migrasi-client-run');
    }

    /** @return bool true = selesai, hentikan loop Alpine. */
    public function processChunk(): bool
    {
        if (! $this->migrating || ! $this->cacheKey) {
            return true;
        }

        $items = Cache::get($this->cacheKey);

        if (! is_array($items)) {
            $this->errorMessage = 'Data preview kedaluwarsa. Klik Preview lagi.';
            $this->migrating = false;

            return true;
        }

        $potongan = array_slice($items, $this->processed, $this->chunkSize);

        if ($potongan === []) {
            return $this->selesai();
        }

        try {
            $hasil = $this->profil()->persist($potongan);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Chunk gagal: ' . $e->getMessage();
            $this->migrating = false;

            return true;
        }

        $this->success += $hasil['success'];
        $this->skipped += $hasil['skipped'];
        $this->failed += $hasil['failed'];
        $this->notes = array_slice(array_merge($this->notes, $hasil['notes']), -200);
        $this->processed += count($potongan);

        return $this->processed >= $this->totalItems ? $this->selesai() : false;
    }

    private function selesai(): bool
    {
        $this->migrating = false;
        $this->finished = true;

        Notification::make()
            ->title('Migrasi selesai')
            ->body("{$this->success} tersimpan, {$this->skipped} dilewati, {$this->failed} gagal.")
            ->{$this->failed > 0 ? 'warning' : 'success'}()
            ->send();

        return true;
    }
}
