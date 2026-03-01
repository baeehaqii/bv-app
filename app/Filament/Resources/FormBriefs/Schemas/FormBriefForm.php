<?php

namespace App\Filament\Resources\FormBriefs\Schemas;

use App\Models\BvCampign;
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
            Section::make('Informasi Brief')
                ->description('Informasi dasar brief campaign')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Brief')
                        ->placeholder('e.g. Brief Campaign Ramadan 2026')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('client_id')
                            ->label('Client / Brand')
                            ->relationship('client', 'nama_brand')
                            ->searchable()
                            ->preload()
                            ->createOptionForm(\App\Filament\Resources\DataClients\Schemas\DataClientForm::getFormSchema()),

                        Select::make('campaign_id')
                            ->label('Campaign Terkait')
                            ->relationship('campaign', 'campaign_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih campaign (opsional)'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('brand_name')
                            ->label('Nama Brand / Produk')
                            ->placeholder('Brand yang akan dipromosikan'),

                        TextInput::make('product_name')
                            ->label('Nama Produk')
                            ->placeholder('Produk spesifik'),
                    ]),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'reviewed' => 'Reviewed',
                            'approved' => 'Approved',
                            'revision' => 'Perlu Revisi',
                        ])
                        ->default('draft')
                        ->native(false)
                        ->required(),
                ]),

            Section::make('Detail Campaign Brief')
                ->description('Objective, target audience, dan key message')
                ->icon('heroicon-o-megaphone')
                ->collapsible()
                ->schema([
                    RichEditor::make('campaign_objective')
                        ->label('Campaign Objective')
                        ->placeholder('Apa tujuan utama campaign ini?')
                        ->columnSpanFull(),

                    RichEditor::make('target_audience')
                        ->label('Target Audience')
                        ->placeholder('Siapa target audience yang dituju?')
                        ->columnSpanFull(),

                    RichEditor::make('key_message')
                        ->label('Key Message')
                        ->placeholder('Pesan utama yang ingin disampaikan')
                        ->columnSpanFull(),
                ]),

            Section::make('Content Guidelines')
                ->description('Panduan konten, do & dont, hashtag')
                ->icon('heroicon-o-clipboard-document-list')
                ->collapsible()
                ->collapsed()
                ->schema([
                    RichEditor::make('mandatory_content')
                        ->label('Mandatory Content')
                        ->placeholder('Konten yang wajib ada dalam brief')
                        ->columnSpanFull(),

                    RichEditor::make('do_and_dont')
                        ->label("Do's and Don'ts")
                        ->placeholder("Yang boleh dan tidak boleh dilakukan")
                        ->columnSpanFull(),

                    Textarea::make('reference_links')
                        ->label('Reference Links')
                        ->placeholder('Link referensi (satu per baris)')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('hashtags')
                            ->label('Hashtags')
                            ->placeholder('#brand #campaign'),

                        TextInput::make('mentions')
                            ->label('Mentions')
                            ->placeholder('@brand @account'),
                    ]),
                ]),

            Section::make('Timeline & Budget')
                ->description('Deadline dan anggaran')
                ->icon('heroicon-o-calendar')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('content_deadline')
                            ->label('Content Deadline')
                            ->native(false)
                            ->displayFormat('d M Y'),

                        DatePicker::make('posting_date')
                            ->label('Posting Date')
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('budget')
                            ->label('Budget')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),

                        Textarea::make('budget_notes')
                            ->label('Catatan Budget')
                            ->placeholder('Detail alokasi budget')
                            ->rows(2),
                    ]),
                ])->columns(2),

            Section::make('Lampiran & Catatan')
                ->description('File pendukung dan catatan tambahan')
                ->icon('heroicon-o-paper-clip')
                ->collapsible()
                ->collapsed()
                ->schema([
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

                    Textarea::make('additional_notes')
                        ->label('Catatan Tambahan')
                        ->placeholder('Catatan lainnya...')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

        ];
    }
}
