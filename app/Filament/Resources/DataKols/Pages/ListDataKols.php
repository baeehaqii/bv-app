<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use App\Filament\Resources\DataKols\Widgets\KolStatsWidget;
use App\Models\DataKol;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Service\KolProfileImporter;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;

class ListDataKols extends ListRecords
{
    protected static string $resource = DataKolResource::class;

    public $dateFilter = 'all';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            KolStatsWidget::class,
        ];
    }

    protected function getWidgetsData(): array
    {
        return [
            'dateFilter' => $this->dateFilter,
        ];
    }

    public function updatedDateFilter()
    {
        // This will trigger widget refresh when filter changes
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dateFilter')
                ->label(fn() => match ($this->dateFilter) {
                    'today' => 'Filter: Today',
                    '7days' => 'Filter: 7 Days',
                    '14days' => 'Filter: 14 Days',
                    '30days' => 'Filter: 30 Days',
                    '60days' => 'Filter: 60 Days',
                    '90days' => 'Filter: 90 Days',
                    default => 'Filter: All Time',
                })
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->form([
                    Select::make('filter')
                        ->label('Select Date Range')
                        ->options([
                            'today' => 'Today',
                            '7days' => '7 Days',
                            '14days' => '14 Days',
                            '30days' => '30 Days',
                            '60days' => '60 Days',
                            '90days' => '90 Days',
                            'all' => 'All Time',
                        ])
                        ->default($this->dateFilter)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $this->dateFilter = $data['filter'];
                })
                ->modalHeading('Filter by Date Range')
                ->modalSubmitActionLabel('Apply Filter')
                ->modalWidth('sm'),

            CreateAction::make()
                ->label('New Data KOL')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Database KOL')
                ->modalSubmitActionLabel('Create & Fetch Data')
                ->form([
                    // Indikator loading saat tombol Create ditekan (fetch API bisa beberapa detik)
                    Placeholder::make('fetch_loading_indicator')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString(
                            '<span wire:loading.delay.flex wire:target="callMountedAction" style="display:none;" class="items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Menyimpan & mengambil data dari API… mohon tunggu.
                            </span>'
                        ))
                        ->columnSpanFull(),

                    Repeater::make('channels')
                        ->label('Platform / Channel')
                        ->helperText('Satu KOL bisa punya banyak platform. Tambahkan tiap channel — masing-masing di-fetch & disimpan terpisah.')
                        ->schema([
                            Select::make('channel')
                                ->label('Channel')
                                ->options(KolProfileImporter::channelOptions())
                                ->required()
                                ->default('Instagram'),

                            TextInput::make('link_userprofile')
                                ->label('Profile URL / Username')
                                ->required()
                                ->placeholder(fn($get) => match ($get('channel')) {
                                    'Tiktok' => 'Contoh: @stoolpresidente atau https://tiktok.com/@stoolpresidente',
                                    'Youtube Channels', 'Youtube Shorts' => 'Contoh: @ThePatMcAfeeShow atau URL channel',
                                    default => 'Contoh: adrianhorning atau https://instagram.com/adrianhorning',
                                })
                                ->suffixIcon('heroicon-m-magnifying-glass'),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->maxItems(KolProfileImporter::MAX_BULK)
                        ->addActionLabel('+ Tambah Channel')
                        ->reorderable(false),
                ])
                ->using(function (array $data): Model {
                    // Tiap baris di-fetch & disimpan sebagai record terpisah, jadi
                    // pembuatannya diambil alih dari CreateAction bawaan.
                    $hasil = (app(KolProfileImporter::class))->importMany($data['channels'] ?? []);

                    if (! $hasil['first']) {
                        throw new \Exception('Semua channel gagal di-fetch. ' . implode(' | ', $hasil['failed']));
                    }

                    $summary = array_filter([
                        $hasil['created'] ? "{$hasil['created']} dibuat" : null,
                        $hasil['updated'] ? "{$hasil['updated']} diperbarui" : null,
                        $hasil['failed'] ? count($hasil['failed']) . ' gagal' : null,
                    ]);

                    Notification::make()
                        ->title('Data KOL tersimpan')
                        ->body(implode(', ', $summary) . ($hasil['failed'] ? ' — ' . implode(' | ', $hasil['failed']) : ''))
                        ->success()
                        ->send();

                    return $hasil['first'];
                })
                ->successNotification(null)
                ->createAnother(false)
                ->modalWidth('lg'),
        ];
    }

}
