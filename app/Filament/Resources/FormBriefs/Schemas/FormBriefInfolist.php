<?php

namespace App\Filament\Resources\FormBriefs\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormBriefInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Brief')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title')
                                ->label('Judul Brief'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn($record) => $record->status_color),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('client.nama_brand')
                                ->label('Client / Brand')
                                ->placeholder('-'),

                            TextEntry::make('campaign.campaign_name')
                                ->label('Campaign')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('brand_name')
                                ->label('Nama Brand')
                                ->placeholder('-'),

                            TextEntry::make('product_name')
                                ->label('Nama Produk')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Detail Campaign Brief')
                    ->icon('heroicon-o-megaphone')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('campaign_objective')
                            ->label('Campaign Objective')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('target_audience')
                            ->label('Target Audience')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('key_message')
                            ->label('Key Message')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Content Guidelines')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('mandatory_content')
                            ->label('Mandatory Content')
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('do_and_dont')
                            ->label("Do's and Don'ts")
                            ->html()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('reference_links')
                            ->label('Reference Links')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextEntry::make('hashtags')
                                ->label('Hashtags')
                                ->placeholder('-'),

                            TextEntry::make('mentions')
                                ->label('Mentions')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Timeline & Budget')
                    ->icon('heroicon-o-calendar')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('content_deadline')
                                ->label('Content Deadline')
                                ->date('d M Y')
                                ->placeholder('-'),

                            TextEntry::make('posting_date')
                                ->label('Posting Date')
                                ->date('d M Y')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('budget')
                                ->label('Budget')
                                ->money('IDR')
                                ->placeholder('-'),

                            TextEntry::make('budget_notes')
                                ->label('Catatan Budget')
                                ->placeholder('-'),
                        ]),
                    ]),

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
