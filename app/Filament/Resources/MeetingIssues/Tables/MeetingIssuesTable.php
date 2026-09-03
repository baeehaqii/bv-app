<?php

namespace App\Filament\Resources\MeetingIssues\Tables;

use App\Enums\IssueStatus;
use App\Models\MeetingIssue;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class MeetingIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority_score')
                    ->label('Priority')
                    // formatStateUsing tidak dipanggil untuk nilai null — yang
                    // mengisi selnya placeholder, bukan format.
                    ->formatStateUsing(fn(string $state) => rtrim(rtrim(number_format((float) $state, 2), '0'), '.'))
                    ->placeholder('—')
                    ->alignCenter()
                    ->sortable()
                    ->width(80),

                TextColumn::make('pic')
                    ->label('PIC')
                    ->placeholder('—')
                    ->searchable()
                    ->width(120),

                TextColumn::make('issue')
                    ->label('Issues To Discuss')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('resolution')
                    ->label('Resolution')
                    ->placeholder('Belum ada keputusan')
                    ->wrap()
                    ->searchable(),

                SelectColumn::make('status')
                    ->label('Resolution Status')
                    ->options(IssueStatus::toArray())
                    ->selectablePlaceholder(false)
                    ->width(170),

                TextColumn::make('meeting_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('meeting_date')
                    ->label('Tanggal Rapat')
                    ->date()
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('meeting_date')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(IssueStatus::toArray())
                    ->multiple(),

                SelectFilter::make('pic')
                    ->label('PIC')
                    ->options(fn() => MeetingIssue::query()
                        ->whereNotNull('pic')
                        ->distinct()
                        ->orderBy('pic')
                        ->pluck('pic', 'pic')
                        ->all())
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            // 1 = prioritas tertinggi, jadi menaik. Yang belum diberi skor jatuh
            // ke bawah, bukan ke atas bersama prioritas nomor satu.
            ->defaultSort(fn($query) => $query
                ->orderByDesc('meeting_date')
                ->orderByRaw('priority_score is null, priority_score'))
            ->emptyStateHeading('Belum ada issue')
            ->emptyStateDescription('Isu ditulis sebelum rapat mulai, bukan saat rapat berjalan.');
    }
}
