<?php

namespace App\Filament\Resources\GrossProfitTargets\Tables;

use App\Models\GrossProfitTarget;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GrossProfitTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable()
                    ->width(80)
                    ->alignCenter(),

                TextColumn::make('month')
                    ->label('Bulan')
                    ->formatStateUsing(
                        fn(int $state, $record) =>
                        Carbon::createFromDate($record->year, $state, 1)->translatedFormat('F')
                    )
                    ->sortable()
                    ->width(120),

                TextColumn::make('quarter_label')
                    ->label('Quarter')
                    ->getStateUsing(fn($record) => 'Q' . GrossProfitTarget::quarterFromMonth($record->month))
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->width(80),

                TextInputColumn::make('target_deal_revenue')
                    ->label('Target Deal Revenue (Rp)')
                    ->rules(['numeric', 'min:0'])
                    ->extraInputAttributes(['style' => 'min-width:180px;text-align:right;'])
                    ->afterStateUpdated(function ($record, $state) {
                        $record->update(['target_deal_revenue' => (int) str_replace(['.', ',', 'Rp', ' '], '', $state ?? '0')]);
                    })
                    ->getStateUsing(fn($record) => (int) $record->target_deal_revenue)
                    ->sortable(),

                TextInputColumn::make('margin_benchmark_percent')
                    ->label('Benchmark Margin (%)')
                    ->rules(['numeric', 'min:0', 'max:100'])
                    ->extraInputAttributes(['style' => 'min-width:110px;text-align:right;'])
                    ->getStateUsing(fn($record) => (float) $record->margin_benchmark_percent)
                    ->sortable(),

                TextColumn::make('target_amount')
                    ->label('Target Gross Profit (Rp)')
                    ->description('Otomatis: revenue x benchmark')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->color('success')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('actual_gp')
                    ->label('Actual Booked GP')
                    ->getStateUsing(fn($record) => $record->actual_gp)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->description(fn($record) => 'Revenue: Rp ' . number_format($record->actual_revenue, 0, ',', '.'))
                    ->alignRight(),

                TextColumn::make('gp_achievement_percent')
                    ->label('% Achievement GP')
                    ->getStateUsing(fn($record) => $record->gp_achievement_percent)
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', '.') . '%')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 100 => 'success',
                        $state > 0    => 'warning',
                        default       => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('profit_margin_percent')
                    ->label('% Profit Margin')
                    ->getStateUsing(fn($record) => $record->profit_margin_percent)
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', '.') . '%')
                    ->description(fn($record) => 'Benchmark: ' . number_format((float) $record->margin_benchmark_percent, 0, ',', '.') . '%')
                    ->badge()
                    ->color(fn($state, $record) => match (true) {
                        $state >= (float) $record->margin_benchmark_percent => 'success',
                        $state > 0                                          => 'danger',
                        default                                             => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('gp_quarter_target')
                    ->label('GP Quarter')
                    ->getStateUsing(
                        fn($record) =>
                        GrossProfitTarget::totalForQuarter(
                            $record->year,
                            GrossProfitTarget::quarterFromMonth($record->month)
                        )
                    )
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('warning')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('gp_year_target')
                    ->label('GP Tahunan')
                    ->getStateUsing(fn($record) => GrossProfitTarget::totalForYear($record->year))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('primary')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextInputColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('Tulis catatan...')
                    ->extraInputAttributes(['style' => 'min-width:180px;'])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updatedBy.name')
                    ->label('Diupdate Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $current = now()->year;
                        $years = [];
                        for ($i = $current - 2; $i <= $current + 2; $i++) {
                            $years[$i] = (string) $i;
                        }
                        return $years;
                    })
                    ->default(now()->year),

                SelectFilter::make('quarter')
                    ->label('Quarter')
                    ->options([
                        '1' => 'Q1 (Jan–Mar)',
                        '2' => 'Q2 (Apr–Jun)',
                        '3' => 'Q3 (Jul–Sep)',
                        '4' => 'Q4 (Okt–Des)',
                    ])
                    ->query(function ($query, array $data) {
                        if (!blank($data['value'])) {
                            $months = GrossProfitTarget::quarterMonths((int) $data['value']);
                            $query->whereIn('month', $months);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'asc');
    }
}
