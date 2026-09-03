<?php

namespace App\Filament\Resources\Okrs\Schemas;

use App\Enums\OkrStatus;
use App\Models\Okr;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OkrForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pemilik & Periode')
                ->columns(4)
                ->schema([
                    Select::make('user_id')
                        ->label('Akun (opsional)')
                        ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        // Mengisi nama sekaligus, tapi owner_name tetap bisa diubah:
                        // di sheet namanya ditulis "Andhini - Creative Director",
                        // dan tidak semua orang di sheet punya akun.
                        ->afterStateUpdated(fn($state, callable $set) => $set(
                            'owner_name',
                            $state ? User::find($state)?->name : null,
                        ))
                        ->live()
                        ->columnSpan(2),

                    TextInput::make('owner_name')
                        ->label('Nama Pemilik OKR')
                        ->helperText('Boleh disertai peran, seperti "Andhini - Creative Director".')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('year')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->default(fn() => now()->year)
                        ->minValue(2020)
                        ->maxValue(2100),

                    Select::make('quarter')
                        ->label('Kuartal')
                        ->options([1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'])
                        ->required()
                        ->native(false)
                        ->default(fn() => Okr::quarterFromMonth(now()->month)),

                    Select::make('month')
                        ->label('Bulan')
                        ->helperText('Kosongkan kalau targetnya berlaku sekuartal.')
                        ->options(fn() => collect(range(1, 12))
                            ->mapWithKeys(fn($m) => [$m => Carbon::create(null, $m, 1)->translatedFormat('F')])
                            ->all())
                        ->native(false)
                        ->columnSpan(2),
                ]),

            Section::make('Objective & Key Results')
                ->schema([
                    Textarea::make('objective')
                        ->label('Objective (apa yang harus dicapai?)')
                        ->required()
                        ->rows(2),

                    Textarea::make('key_results')
                        ->label('Key Results (bagaimana cara mencapainya?)')
                        ->helperText('Satu baris per Key Result. Sebutkan angkanya — "xx %" tidak bisa dinilai saat rapat.')
                        ->required()
                        ->rows(4),

                    TextInput::make('partner_with')
                        ->label('Partner with')
                        ->helperText('Siapa yang harus dilibatkan supaya ini jalan.')
                        ->maxLength(255),

                    Select::make('status')
                        ->label('Status')
                        ->options(OkrStatus::toArray())
                        ->required()
                        ->native(false)
                        ->default(OkrStatus::TO_DO->value),
                ]),

            Section::make('Skor')
                ->description('Skala 0.0-1.0. 0.7 sudah dianggap tercapai; 1.0 berarti targetnya kurang menantang.')
                ->columns(2)
                ->schema([
                    TextInput::make('expected_score')
                        ->label('Expected EoQ key result score')
                        ->helperText('Perkiraan capaian Key Result di akhir kuartal.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.1),

                    TextInput::make('objective_score')
                        ->label('End-of-quarter objective score')
                        ->helperText('Diisi setelah kuartalnya selesai.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.1),
                ]),

            Section::make('Current status')
                ->description('Perkembangan per bulan, diisi sebelum weekly meeting — bukan saat rapat berlangsung.')
                ->schema([
                    Textarea::make('status_month_1')->label('Bulan 1')->rows(3),
                    Textarea::make('status_month_2')->label('Bulan 2')->rows(3),
                    Textarea::make('status_month_3')->label('Bulan 3')->rows(3),
                ]),
        ]);
    }
}
