<?php

namespace App\Filament\Resources\FormBriefs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;

class FormBriefInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // -----------------------------------------------
                // Section 1: KOL Needs — Info Utama
                // -----------------------------------------------
                Section::make('KOL Needs')
                    ->icon('heroicon-o-star')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('brand')
                                ->label('Brand')
                                ->placeholder('-'),

                            TextEntry::make('client_status')
                                ->label('Client Status')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'direct'         => 'info',
                                    'agency'         => 'warning',
                                    'another_agency' => 'danger',
                                    default          => 'gray',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'direct'         => 'Direct',
                                    'agency'         => 'Agency',
                                    'another_agency' => 'Another Agency',
                                    default          => ucfirst($state ?? '-'),
                                })
                                ->placeholder('-'),

                            TextEntry::make('pic')
                                ->label('PIC')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('campaign_name')
                                ->label('Campaign Name')
                                ->placeholder('-'),

                            TextEntry::make('timeline')
                                ->label('Timeline')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('campaign_objective')
                            ->label('Campaign Objective')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                // -----------------------------------------------
                // Section 2: Criteria of KOL
                // -----------------------------------------------
                Section::make('Criteria of KOL')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('criteria_of_kol')
                            ->label('')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                // -----------------------------------------------
                // Section 3: SOW
                // -----------------------------------------------
                Section::make('SOW (Scope of Work)')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('sow')
                            ->label('')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                // -----------------------------------------------
                // Section 4: Budget — 1 field
                // -----------------------------------------------
                Section::make('Budget')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('budget')
                            ->label('Budget Campaign')
                            ->formatStateUsing(
                                fn($state) => $state !== null && $state !== ''
                                    ? 'Rp ' . number_format((int) $state, 0, ',', '.')
                                    : '-'
                            )
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),
                    ]),

                // -----------------------------------------------
                // Section 5: Status & Links
                // -----------------------------------------------
                Section::make('Status & Detail')
                    ->icon('heroicon-o-link')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            // Deadline — bold & size large sesuai permintaan
                            TextEntry::make('deadline_date')
                                ->label('Deadline')
                                ->date('d M Y')
                                ->weight(FontWeight::Bold)
                                ->size(TextEntry\TextEntrySize::Large)
                                ->placeholder('-'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn($record) => $record->status_color),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('sheet_link_internal')
                                ->label('Sheet Link Internal')
                                ->placeholder('-')
                                ->copyable(),

                            TextEntry::make('sheet_link_external')
                                ->label('Sheet Link External')
                                ->placeholder('-')
                                ->copyable(),
                        ]),
                    ]),

                // -----------------------------------------------
                // Section 6: Submission Info
                // -----------------------------------------------
                Section::make('Submission Info')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('submitted_by_name')
                                ->label('Disubmit Oleh')
                                ->placeholder('-'),

                            TextEntry::make('submitted_by_email')
                                ->label('Email')
                                ->placeholder('-'),

                            TextEntry::make('submitted_at')
                                ->label('Tanggal Submit')
                                ->dateTime('d M Y H:i')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('review_notes')
                            ->label('Catatan Review')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
