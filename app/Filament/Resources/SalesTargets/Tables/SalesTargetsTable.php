<?php

namespace App\Filament\Resources\SalesTargets\Tables;

use App\Models\BvSalesList;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salesList.nama_sales')
                    ->label('Nama Sales')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable()
                    ->alignCenter()
                    ->width(80),

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
                    ->getStateUsing(fn($record) => 'Q' . SalesTarget::quarterFromMonth($record->month))
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->width(80),

                TextInputColumn::make('target_amount')
                    ->label('Target Bulanan (Rp)')
                    ->rules(['numeric', 'min:0'])
                    ->extraInputAttributes(['style' => 'min-width:160px;text-align:right;'])
                    ->beforeStateDehydrated(fn($state) => (int) str_replace(['.', ',', 'Rp', ' '], '', $state ?? '0'))
                    ->getStateUsing(fn($record) => (int) $record->target_amount)
                    ->sortable(),

                TextColumn::make('quarter_target')
                    ->label('Target Quarter')
                    ->getStateUsing(
                        fn($record) =>
                        SalesTarget::totalForSalesQuarter(
                            $record->bv_sales_list_id,
                            $record->year,
                            SalesTarget::quarterFromMonth($record->month)
                        )
                    )
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('warning')
                    ->alignRight(),

                TextColumn::make('year_target')
                    ->label('Target Tahunan')
                    ->getStateUsing(
                        fn($record) =>
                        SalesTarget::totalForSalesYear($record->bv_sales_list_id, $record->year)
                    )
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->alignRight(),

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
                SelectFilter::make('bv_sales_list_id')
                    ->label('Sales')
                    ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                    ->searchable(),

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
                            $months = SalesTarget::quarterMonths((int) $data['value']);
                            $query->whereIn('month', $months);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn() => auth()->user()?->can('Update:SalesTarget')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('Delete:SalesTarget')),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('salesList.nama_sales', 'asc');
    }
}
