<?php

namespace App\Filament\Resources\DataKols\Actions;

use App\Filament\Resources\DataKols\DataKolResource;
use App\Models\DataKol;
use App\Service\KolProfileImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Tombol "Tambah Channel" di section Social Media Data (halaman edit KOL).
 *
 * Dua mode, karena keduanya benar-benar beda sasaran:
 *  - satu : 1 channel, username DIPAKSA sama dengan KOL yang sedang dibuka supaya
 *           barisnya mengelompok ke orang itu (grouping memakai kolom `username`).
 *  - bulk : upload CSV (kolom A channel, kolom B link), masing-masing baris jadi
 *           KOL BARU dengan username aslinya. Tidak boleh dipaksa ke username KOL
 *           ini — beberapa akun dengan username sama pada satu channel akan saling
 *           menimpa.
 */
class AddChannelAction
{
    /**
     * Hasil impor terakhir, dibaca kembali oleh Placeholder saat modal di-render
     * ulang setelah halt(). Sengaja static & se-request: begitu user menutup modal
     * atau memuat ulang halaman, hasilnya memang tidak perlu bertahan.
     */
    private static array $hasilTerakhir = [];

    public static function make(string $name = 'add_channel'): Action
    {
        return Action::make($name)
            ->label('Tambah Channel')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->modalHeading('Tambah Channel')
            ->modalSubmitActionLabel('Fetch & Simpan')
            ->modalWidth('2xl')
            ->modalDescription('Data diambil otomatis dari platform saat disimpan. Perubahan form yang belum di-Save Changes akan hilang.')
            // Setiap kali modal dibuka, mulai dari bersih — jangan tampilkan hasil impor lama.
            // mountUsing MENGGANTI perilaku bawaan yang memanggil $schema->fill(), jadi
            // fill() wajib dipanggil manual di sini. Tanpa itu default 'satu' pada radio
            // mode tidak pernah terisi, dan field Channel/URL yang bergantung padanya
            // ikut tersembunyi sampai user meng-klik radio.
            ->mountUsing(function (?Schema $schema = null): void {
                self::$hasilTerakhir = [];
                $schema?->fill();
            })
            ->schema([
                Radio::make('mode')
                    ->hiddenLabel()
                    ->options(fn(?DataKol $record) => [
                        'satu' => 'Satu channel — digabung ke @' . ($record?->username ?? 'KOL ini'),
                        'bulk' => 'Banyak sekaligus via CSV (maks ' . KolProfileImporter::MAX_BULK . ') — masing-masing jadi KOL baru',
                    ])
                    ->default('satu')
                    ->live()
                    ->required(),

                Select::make('channel')
                    ->label('Channel')
                    ->options(KolProfileImporter::channelOptions())
                    ->native(false)
                    ->default('Instagram')
                    ->visible(fn(callable $get) => $get('mode') === 'satu')
                    ->required(fn(callable $get) => $get('mode') === 'satu'),

                TextInput::make('link_userprofile')
                    ->label('URL / Username')
                    ->placeholder('https://www.instagram.com/username/ atau username saja')
                    ->visible(fn(callable $get) => $get('mode') === 'satu')
                    ->required(fn(callable $get) => $get('mode') === 'satu'),

                // Closure, bukan HtmlString langsung: template-nya digenerate
                // on-demand di route, jadi jangan ada kerja saat schema dibangun.
                Placeholder::make('template')
                    ->hiddenLabel()
                    ->visible(fn(callable $get) => $get('mode') === 'bulk')
                    ->content(fn() => new HtmlString(
                        '<a href="' . route('data-kol.import-template') . '"'
                        . ' class="text-primary-600 underline hover:text-primary-500 text-sm font-medium">'
                        . '⬇ Unduh template Excel</a>'
                        . '<p class="mt-1 text-xs text-gray-500">Kolom A: channel (pilih dari dropdown).'
                        . ' Kolom B: URL atau username. Maks ' . KolProfileImporter::MAX_BULK . ' baris.</p>'
                    )),

                FileUpload::make('csv')
                    ->label('File Excel / CSV')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->maxSize(1024)
                    // storeFiles(false): file cuma dibaca sekali saat submit, tidak perlu
                    // disimpan permanen lalu dibersihkan belakangan.
                    ->storeFiles(false)
                    ->visible(fn(callable $get) => $get('mode') === 'bulk')
                    ->required(fn(callable $get) => $get('mode') === 'bulk'),

                // Target wire:stream — progres per baris ditulis ke sini selagi
                // request berjalan, jadi user tidak menatap spinner kosong.
                Placeholder::make('progress')
                    ->hiddenLabel()
                    ->visible(fn(callable $get) => $get('mode') === 'bulk')
                    ->content(new HtmlString(
                        '<div wire:stream="scrape-progress" class="text-xs text-gray-500"></div>'
                    )),

                Placeholder::make('hasil')
                    ->hiddenLabel()
                    ->visible(fn() => self::$hasilTerakhir !== [])
                    ->content(fn(DataKol $record) => view('filament.data-kols.import-result', [
                        'hasil' => self::$hasilTerakhir,
                        'reloadUrl' => DataKolResource::getUrl('edit', ['record' => $record]),
                    ])),
            ])
            ->action(function (array $data, DataKol $record, $livewire, Action $action): void {
                $bulk = ($data['mode'] ?? 'satu') === 'bulk';

                [$baris, $errors] = $bulk
                    ? self::bacaBerkas($data['csv'] ?? null)
                    : [[['channel' => $data['channel'], 'link_userprofile' => $data['link_userprofile']]], []];

                if (! $baris) {
                    self::$hasilTerakhir = ['rows' => [], 'errors' => $errors ?: ['Tidak ada baris yang bisa diimpor.']];
                    Notification::make()->title('Tidak ada yang diimpor')->warning()->send();
                    $action->halt();
                }

                $hasil = app(KolProfileImporter::class)->importMany(
                    $baris,
                    $bulk ? null : $record->username,
                    fn(int $ke, int $total, string $channel, string $url) => $livewire->stream(
                        to: 'scrape-progress',
                        content: "⏳ Mengambil {$ke}/{$total} — {$channel}: {$url}",
                        replace: true,
                    ),
                );

                $hasil['errors'] = $errors;

                // Semua mulus → tidak ada yang perlu dibaca, langsung muat ulang
                // supaya tabel per-channel & agregat ikut terbarui.
                if (! $hasil['failed'] && ! $errors) {
                    Notification::make()
                        ->title('Channel tersimpan')
                        ->body("{$hasil['created']} dibuat, {$hasil['updated']} diperbarui.")
                        ->success()
                        ->send();

                    $livewire->redirect(DataKolResource::getUrl('edit', ['record' => $record]), navigate: true);

                    return;
                }

                // Ada yang gagal → modal ditahan terbuka supaya hasilnya bisa dibaca.
                self::$hasilTerakhir = $hasil;

                Notification::make()
                    ->title('Sebagian gagal di-scrape')
                    ->body(count($hasil['failed']) . ' baris gagal. Rinciannya ada di modal.')
                    ->warning()
                    ->send();

                $action->halt();
            });
    }

    /**
     * @return array{list<array{channel: string, link_userprofile: string}>, list<string>}
     */
    private static function bacaBerkas(mixed $upload): array
    {
        // FileUpload selalu memberi array (multiple atau tidak).
        $file = is_array($upload) ? reset($upload) : $upload;

        if (! $file || ! is_object($file) || ! method_exists($file, 'getRealPath')) {
            return [[], ['File tidak terbaca.']];
        }

        $parsed = KolProfileImporter::parseFile($file->getRealPath());

        return [$parsed['rows'], $parsed['errors']];
    }
}
