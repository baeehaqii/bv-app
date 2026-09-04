<?php

namespace App\Filament\Pages;

use App\Models\MasterMargin;
use App\Models\MasterPph;
use App\Models\MediaPlanCalcSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Angka rumus Media Plan Internal yang tidak punya rumahnya sendiri:
 * pembulatan harga jual dan ambang Tier KOL. Koefisien pajak diatur di
 * Master PPH, tingkatan margin di Master Margin — sengaja TIDAK diduplikasi
 * di sini supaya tidak ada dua tempat mengubah hal yang sama.
 *
 * Halaman (bukan Resource) karena tabelnya singleton — satu baris config,
 * tidak ada daftar untuk di-CRUD.
 */
class MasterdataMediaPlanInternal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-variable';

    protected static ?string $navigationLabel = 'Masterdata Media Plan Internal';

    protected static ?string $title = 'Masterdata Media Plan Internal';

    protected ?string $subheading = 'Ubah rumus perhitungan Media Plan Internal dari sini — tidak ada angka yang ditulis di kode.';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.masterdata-media-plan-internal';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = MediaPlanCalcSetting::current();

        $this->form->fill([
            'rounding_step' => (float) $setting->rounding_step,
            'rounding_mode' => $setting->rounding_mode,
            'tier_thresholds' => $setting->tiers(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Pembulatan harga jual')
                    ->description('Harga jual ke client dibulatkan sebelum ditampilkan.')
                    ->schema([
                        TextInput::make('rounding_step')
                            ->label('Kelipatan pembulatan')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Rp')
                            ->helperText('Isi 0 untuk mematikan pembulatan.'),

                        Select::make('rounding_mode')
                            ->label('Arah pembulatan')
                            ->options(MediaPlanCalcSetting::ROUNDING_MODES)
                            ->required()
                            ->native(false),
                    ])->columns(2),

                Section::make('Tier KOL')
                    ->description('Berlaku global: Media Plan Internal, KOL Data, dan semua hasil scraping (Instagram/TikTok/YouTube/Threads). Tier dihitung dari jumlah follower; ambang terbesar dicek lebih dulu, apa pun urutannya di sini. Batas atas tiap band diturunkan otomatis dari ambang band di bawahnya.')
                    ->schema([
                        Repeater::make('tier_thresholds')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')
                                    ->label('Nama tier')
                                    ->required(),

                                TextInput::make('min_followers')
                                    ->label('Follower minimum')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(count(MediaPlanCalcSetting::DEFAULT_TIERS))
                            ->minItems(1)
                            ->reorderable(false)
                            ->itemLabel(fn(array $state): ?string => filled($state['label'] ?? null)
                                ? $state['label'].' — ≥ '.number_format((float) ($state['min_followers'] ?? 0), 0, ',', '.')
                                : null),
                    ]),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Simpan')
                ->submit('simpan'),

            Action::make('kembalikan_sheet')
                ->label('Kembalikan ke sheet KOL List')
                ->color('gray')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->modalDescription('Mengembalikan pembulatan ke kelipatan 100.000 ke atas dan tier ke ambang bawaan.')
                ->action(function () {
                    $this->form->fill([
                        'rounding_step' => 100000,
                        'rounding_mode' => 'up',
                        'tier_thresholds' => MediaPlanCalcSetting::DEFAULT_TIERS,
                    ]);
                }),
        ];
    }

    public function simpan(): void
    {
        $data = $this->form->getState();

        $setting = MediaPlanCalcSetting::query()->first() ?? new MediaPlanCalcSetting;
        $setting->fill($data)->save();

        MediaPlanCalcSetting::forgetCached();

        Notification::make()
            ->success()
            ->title('Rumus Media Plan Internal disimpan')
            ->body('Berlaku untuk perhitungan berikutnya. Baris budget yang sudah tersimpan baru ikut berubah setelah media plan-nya disimpan ulang.')
            ->send();
    }

    /** Contoh hidup: satu baris rate dihitung dengan setelan yang sedang diisi. */
    public function getPratinjauProperty(): array
    {
        $data = $this->form->getRawState();
        $rate = 2_000_000.0;

        $setting = new MediaPlanCalcSetting([
            'rounding_step' => (float) ($data['rounding_step'] ?? 100000),
            'rounding_mode' => $data['rounding_mode'] ?? 'up',
        ]);

        $coeff = MasterPph::defaultCalculatedCoefficient();
        $cost = $coeff > 0 ? $rate / $coeff : $rate;
        $muTarget = $setting->applyMargin($cost, MasterMargin::getMarginForAmount($rate));
        $rounded = $setting->roundPrice($muTarget);

        return [
            'rate' => $rate,
            'cost' => $cost,
            'mu_target' => $muTarget,
            'rounded' => $rounded,
            'margin' => $rounded > 0 ? (($rounded - $cost) / $rounded) * 100 : 0,
        ];
    }
}
