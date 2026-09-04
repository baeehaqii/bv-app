<?php

namespace App\Filament\Resources\MediaPlans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use Carbon\CarbonImmutable;


class MediaPlansTable
{
    /** Kolom periode disimpan sebagai string bebas; tampilkan rapi kalau terbaca. */
    private static function tanggal(?string $nilai): ?string
    {
        if (blank($nilai)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($nilai)->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $nilai;
        }
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Quotation number copied'),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->state(function ($record) {
                        $brand = $record->brand;
                        if (!$brand || trim($brand) === '-') {
                            return $record->bvSales?->company_name;
                        }
                        return $brand;
                    })
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('-'),

                TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('kols_count')
                    ->label('Total KOLs')
                    ->counts('kols')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                // KOL yang sudah di-approve client di Media Plan External.
                // is_selected cuma "dicentang di shortlist", bukan keputusan client —
                // itu sebabnya kolom lama selalu 0 padahal di external sudah approve.
                TextColumn::make('approved_kols_count')
                    ->label('Approved')
                    ->state(fn($record) => (int) ($record->internalBudget
                        ?->items()
                        ->where('status', 'approved')
                        ->whereNotNull('media_plan_kol_id')
                        ->distinct()
                        ->count('media_plan_kol_id') ?? 0))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->tooltip('KOL yang di-approve client lewat Media Plan External'),

                TextColumn::make('kols_list_count')
                    ->label('KOL(s)')
                    ->state(fn($record) => $record->kols_count)
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->action(
                        Action::make('view_kols')
                            ->modalHeading(fn($record) => 'KOL List — ' . $record->campaign_name)
                            ->modalContent(fn($record) => view('filament.modals.media-plan-kol-list', [
                                'kols' => $record->kols()->orderByDesc('is_selected')->orderBy('row_number')->get(),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('4xl')
                    ),

                TextColumn::make('picSalesBd.nama_sales')
                    ->label('Sales')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('pic_project_internal_ids')
                    ->label('PIC KOL')
                    ->state(function ($record) {
                        $daftar = MediaPlanForm::kolSpecialists();

                        return collect($record->pic_project_internal_ids ?? [])
                            ->map(fn($id) => $daftar[$id] ?? null)
                            ->filter()
                            ->values()
                            ->all();
                    })
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('picAm.nama_sales')
                    ->label('PIC AM')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('picLeadsProject.nama_sales')
                    ->label('Lead Project')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('quotations_count')
                    ->label('Quotation?')
                    ->counts('quotations')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Ada' : 'Belum')
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('campaign_period_start')
                    ->label('Period Start')
                    ->formatStateUsing(fn($state) => self::tanggal($state))
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('campaign_period_end')
                    ->label('Period End')
                    ->formatStateUsing(fn($state) => self::tanggal($state))
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                // Summary from Internal Budget
                TextColumn::make('internalBudget.total_rounded')
                    ->label('Total Budget')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('internalBudget.average_margin_percent')
                    ->label('Avg Margin')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn($state) => ($state ?? 0) < 30 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bvSales.status')
                    ->label('Campaign Status')
                    ->badge()
                    ->state(fn($record) => $record->bvSales?->status?->getLabel())
                    ->color(fn($record): string => match ($record->bvSales?->status?->getColor()) {
                        'warning' => 'warning',
                        'success' => 'success',
                        'info' => 'info',
                        'danger' => 'danger',
                        'purple' => 'purple',
                        default => 'gray',
                    })
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Plan Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Planning' => 'warning',
                        'To Client' => 'info',
                        'Ongoing' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('internalBudget.status')
                    ->label('Budget Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Plan Status')
                    ->options([
                        'Planning' => 'Planning',
                        'To Client' => 'To Client',
                        'Ongoing' => 'Ongoing',
                    ]),

                SelectFilter::make('channel')
                    ->label('Filter by Channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Tiktok' => 'Tiktok',
                        'Youtube Channels' => 'Youtube Channels',
                        'Youtube Shorts' => 'Youtube Shorts',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value']) {
                            $query->whereHas('kols', function ($q) use ($state) {
                                $q->where('channel', $state['value']);
                            });
                        }
                    }),

                SelectFilter::make('budget_status')
                    ->label('Budget Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value']) {
                            $query->whereHas('internalBudget', function ($q) use ($state) {
                                $q->where('status', $state['value']);
                            });
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('markOngoing')
                    ->label('Mark as Ongoing')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => in_array($record->status, ['Planning', 'To Client']) && $record->internalBudget?->status === 'approved')
                    ->action(fn($record) => $record->update(['status' => 'Ongoing'])),
                Action::make('markPlanning')
                    ->label('Revert to Planning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn($record) => in_array($record->status, ['To Client', 'Ongoing']))
                    ->action(fn($record) => $record->update(['status' => 'Planning'])),
                Action::make('quotation')
                    ->label('Quotation')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn($record) => route('quotation.preview', ['mediaPlan' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->kols()->where('is_selected', true)->count() > 0),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}


