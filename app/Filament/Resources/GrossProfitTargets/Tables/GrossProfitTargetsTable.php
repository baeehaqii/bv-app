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
                    ->beforeStateDehydrated(fn($state) => (int) str_replace(['.', ',', 'Rp', ' '], '', $state ?? '0'))
                    ->getStateUsing(fn($record) => (int) $record->target_deal_revenue)
                    ->sortable(),

                TextInputColumn::make('target_amount')
                    ->label('Target Gross Profit (Rp)')
                    ->rules(['numeric', 'min:0'])
                    ->extraInputAttributes(['style' => 'min-width:180px;text-align:right;'])
                    ->beforeStateDehydrated(fn($state) => (int) str_replace(['.', ',', 'Rp', ' '], '', $state ?? '0'))
                    ->getStateUsing(fn($record) => (int) $record->target_amount)
                    ->sortable(),

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
