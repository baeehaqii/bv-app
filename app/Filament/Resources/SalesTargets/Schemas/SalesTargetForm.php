<?php

namespace App\Filament\Resources\SalesTargets\Schemas;

use App\Models\BvSalesList;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class SalesTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sales Target')
                ->description('Set the monthly deal value target for each sales person. Quarterly and Annual targets are automatically calculated from accumulated monthly data.')
                ->schema([
                    Select::make('bv_sales_list_id')
                        ->label('Sales Person')
                        ->options(function () {
                            $candidateRoles = ['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development'];

                            // Only query roles that actually exist in the DB
                            $existingRoles = Role::whereIn('name', $candidateRoles)->pluck('name')->toArray();

                            $options = collect();

                            if (!empty($existingRoles)) {
                                User::role($existingRoles)
                                    ->orderBy('name')
                                    ->get()
                                    ->each(function ($user) use ($options) {
                                        $salesList = BvSalesList::firstOrCreate(
                                            ['user_id' => $user->id],
                                            ['nama_sales' => $user->name]
                                        );
                                        $options->put($salesList->id, $user->name);
                                    });
                            }

                            // Also include BvSalesList entries not linked to any user account
                            BvSalesList::whereNull('user_id')
                                ->orderBy('nama_sales')
                                ->each(function ($sl) use ($options) {
                                    $options->put($sl->id, $sl->nama_sales);
                                });

                            return $options->sortBy(fn($v) => $v)->all();
                        })
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->columnSpan(2),

                    Select::make('year')
                        ->label('Year')
                        ->options(function () {
                            $current = now()->year;
                            $years = [];
                            for ($i = $current - 1; $i <= $current + 2; $i++) {
                                $years[$i] = (string) $i;
                            }
                            return $years;
                        })
                        ->default(now()->year)
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    Select::make('month')
                        ->label('Month')
                        ->options(function () {
                            $months = [];
                            for ($i = 1; $i <= 12; $i++) {
                                $months[$i] = Carbon::createFromDate(null, $i, 1)->translatedFormat('F');
                            }
                            return $months;
                        })
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    TextInput::make('target_amount')
                        ->label('Target Deal Value (Rp)')
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Target deal value nominal for this sales person in the selected month.')
                        ->columnSpan(2),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Write optional notes here...')
                        ->rows(2)
                        ->columnSpan(2),
                ])
                ->columns(2),
        ]);
    }
}
