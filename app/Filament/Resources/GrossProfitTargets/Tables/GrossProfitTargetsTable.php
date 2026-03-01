<?php

namespace App\Filament\Resources\GrossProfitTargets\Tables;

use App\Models\GrossProfitTarget;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
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

                TextColumn::make('target_amount')
                    ->label('Target Bulanan')
                    ->money('IDR')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('quarter_target')
                    ->label('Target Quarter')
                    ->getStateUsing(
                        fn($record) =>
                        GrossProfitTarget::totalForQuarter(
                            $record->year,
                            GrossProfitTarget::quarterFromMonth($record->month)
                        )
                    )
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('warning')
                    ->alignRight(),

                TextColumn::make('year_target')
                    ->label('Target Tahunan')
                    ->getStateUsing(fn($record) => GrossProfitTarget::totalForYear($record->year))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->alignRight(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('-')
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
