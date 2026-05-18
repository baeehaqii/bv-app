<?php

namespace App\Filament\Resources\FormBriefs\Schemas;

use App\Models\BvSales;
use App\Models\DataClient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class FormBriefForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            // -------------------------------------------------------
            // Section 1: KOL Needs — Info Utama
            // -------------------------------------------------------
            Section::make('KOL Needs')
                ->description('Informasi utama kebutuhan KOL campaign')
                ->icon('heroicon-o-star')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('brand')
                            ->label('Brand')
                            ->placeholder('e.g. Tigesnus')
                            ->maxLength(255),

                        Select::make('client_status')
                            ->label('Client Status')
                            ->options([
                                'direct'         => 'Direct',
                                'agency'         => 'Agency',
                                'another_agency' => 'Another Agency',
                            ])
                            ->native(false)
                            ->placeholder('Pilih status client'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('pic')
                            ->label('PIC')
                            ->placeholder('e.g. Karina')
                            ->maxLength(255),

                        TextInput::make('campaign_name')
                            ->label('Campaign Name')
                            ->placeholder('e.g. Tigersnus Drama')
                            ->maxLength(255),
                    ]),

                    TextInput::make('timeline')
                        ->label('Timeline')
                        ->placeholder('e.g. January - February 2026')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('campaign_objective')
                        ->label('Campaign Objective')
                        ->placeholder('Apa tujuan utama campaign ini?')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // -------------------------------------------------------
            // Section 2: Criteria of KOL
            // -------------------------------------------------------
            Section::make('Criteria of KOL')
                ->description('Kriteria KOL yang dibutuhkan (Main KOL, Opsi Macro, dll)')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->schema([
                    RichEditor::make('criteria_of_kol')
                        ->label('')
                        ->toolbarButtons(['bold', 'bulletList', 'orderedList', 'h3', 'italic', 'underline', 'redo', 'undo'])
                        ->columnSpanFull(),
                ]),

            // -------------------------------------------------------
            // Section 3: SOW (Scope of Work)
            // -------------------------------------------------------
            Section::make('SOW (Scope of Work)')
                ->description('Ruang lingkup pekerjaan KOL')
                ->icon('heroicon-o-clipboard-document-list')
                ->collapsible()
                ->schema([
                    RichEditor::make('sow')
                        ->label('')
                        ->toolbarButtons(['bold', 'bulletList', 'orderedList', 'italic', 'underline', 'redo', 'undo'])
                        ->columnSpanFull(),
                ]),

            // -------------------------------------------------------
            // Section 4: Budget — 1 field total campaign budget
            // -------------------------------------------------------
            Section::make('Budget')
                ->description('Total budget campaign')
                ->icon('heroicon-o-banknotes')
                ->collapsible()
                ->schema([
                    TextInput::make('budget')
                        ->label('Budget Campaign')
                        ->prefix('Rp')
                        ->placeholder('0')
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                        ->formatStateUsing(
                            fn($state) => $state !== null && $state !== ''
                                ? number_format((int) $state, 0, ',', '.')
                                : null
                        )
                        ->dehydrateStateUsing(
                            fn($state) => $state !== null && $state !== ''
                                ? (int) preg_replace('/[^0-9]/', '', (string) $state)
                                : null
                        )
                        ->helperText('Masukkan total budget campaign dalam Rupiah'),
                ]),

            // -------------------------------------------------------
            // Section 5: Deadline, Status & Sheet Links
            // -------------------------------------------------------
            Section::make('Detail & Status')
                ->description('Deadline, status, dan link spreadsheet')
                ->icon('heroicon-o-link')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('deadline_date')
                            ->label('Deadline')
                            ->placeholder('Pilih tanggal deadline')
                            ->native(false)
                            ->displayFormat('d M Y'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft'     => 'Draft',
                                'submitted' => 'Submitted',
                                'reviewed'  => 'Reviewed',
                                'approved'  => 'Approved',
                                'revision'  => 'Perlu Revisi',
                            ])
                            ->default('draft')
                            ->native(false)
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('sheet_link_internal')
                            ->label('Sheet Link Internal')
                            ->placeholder('https://docs.google.com/spreadsheets/...')
                            ->url()
                            ->maxLength(1000),

                        TextInput::make('sheet_link_external')
                            ->label('Sheet Link External')
                            ->placeholder('https://docs.google.com/spreadsheets/...')
                            ->url()
                            ->maxLength(1000),
                    ]),
                ]),

            // -------------------------------------------------------
            // Section 6: Judul & Catatan (internal)
            // -------------------------------------------------------
            Section::make('Informasi Internal')
                ->description('Judul brief, link brief client, dan catatan tambahan')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Brief')
                        ->placeholder('e.g. Brief KOL Ramadan 2026 — Tigesnus')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('additional_notes')
                        ->label('Catatan Tambahan')
                        ->placeholder('Catatan lainnya...')
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('attachments')
                        ->label('Lampiran')
                        ->multiple()
                        ->directory('form-briefs')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/png',
                            'image/jpeg',
                            'image/webp',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        ])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable()
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
