<?php

namespace App\Filament\Resources\BvTrackerProgresKols\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BvTrackerProgresKolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section: Info Umum
                Section::make('Info Umum')
                    ->columns(2)
                    ->schema([
                        Select::make('media_plan_kol_id')
                            ->relationship('mediaPlanKol', 'name')
                            ->label('Media Plan KOL')
                            ->required(),
                        TextInput::make('nama_brand')
                            ->label('Nama Brand') ->required()
                            ->placeholder('Nama Brand'),
                        TextInput::make('timeline')
                            ->label('Timeline') ->required()
                            ->placeholder('Timeline'),
                        TextInput::make('kol_approve')
                            ->label('KOL Approved') ->required()
                            ->placeholder('KOL Approved'),
                        TextInput::make('username')
                            ->label('Username') ->required()
                            ->placeholder('Username'),
                        TextInput::make('link')
                            ->label('Link') ->required()
                            ->url()
                            ->placeholder('Link'),
                        Select::make('status')
                            ->label('Status') ->native(false) ->columnSpanFull()
                            ->placeholder('Pilih Status') ->required()
                            ->options([
                                'waiting_draft' => 'Waiting Draft',
                                'waiting_storyline' => 'Waiting Storyline',
                                'waiting_caption' => 'Waiting Caption',
                                'waiting_revisi_draft' => 'Waiting Revisi Draft',
                                'waiting_revisi_storyline' => 'Waiting Revisi Storyline',
                                'waiting_revisi_caption' => 'Waiting Revisi Caption',
                                'need_approval_storyline' => 'Need Approval Storyline',
                                'need_approval_draft_video' => 'Need Approval Draft Video',
                                'need_approval_caption' => 'Need Approval Caption',
                                'storyline_approved' => 'Storyline Approved',
                                'draft_approved' => 'Draft Approved',
                                'video_approved' => 'Video Approved',
                                'caption_approved' => 'Caption Approved',
                                'waiting_kol_posting' => 'Waiting KOL Posting',
                                'kol_done_posting' => 'KOL Done Posting',
                                'kol_done_visit' => 'KOL Done Visit',
                                'kol_cancel' => 'KOL Cancel',
                            ]),
                    ]),

                // Section: Draft Storyline
                Section::make('Draft Storyline')
                    ->columns(2)
                    ->schema([
                        TextInput::make('draft_storyline')
                            ->label('Draft Storyline') ->placeholder('Draft Storyline') ->required()
                            ->columnSpanFull(),
                        Textarea::make('feedback_draft_storyline')
                            ->label('Feedback Client') ->placeholder('Feedback Client') ->required()
                            ->columnSpanFull(),
                    ]),

                // Section: Draft Video
                Section::make('Draft Video')
                    ->columns(2)
                    ->schema([
                        TextInput::make('draft_video')
                            ->label('Draft Video') ->placeholder('Draft Video') ->required()
                            ->columnSpanFull(),
                        Textarea::make('feedback_draft_video')
                            ->label('Feedback Client') ->placeholder('Feedback Client') ->required()
                            ->columnSpanFull(),
                    ]),

                // Section: Draft Revisi 1
                Section::make('Draft Revisi 1')
                    ->columns(2)
                    ->schema([
                        TextInput::make('draft_revisi_1')
                            ->label('Draft Revisi 1') ->placeholder('Draft Revisi 1') ->required()
                            ->columnSpanFull(),
                        Textarea::make('feedback_draft_revisi_1')
                            ->label('Feedback Client') ->placeholder('Feedback Client') ->required()
                            ->columnSpanFull(),
                    ]),

                // Section: Draft Revisi 2
                Section::make('Draft Revisi 2')
                    ->columns(2)
                    ->schema([
                        TextInput::make('draft_revisi_2')
                            ->label('Draft Revisi 2') ->placeholder('Draft Revisi 2') ->required()
                            ->columnSpanFull(),
                        Textarea::make('feedback_draft_revisi_2')
                            ->label('Feedback Client') ->placeholder('Feedback Client') ->required()
                            ->columnSpanFull(),
                    ]),

                // Section: Caption
                Section::make('Caption')
                    ->columns(2)
                    ->schema([
                        Textarea::make('caption')
                            ->label('Caption') ->placeholder('Caption') ->required()
                            ->columnSpanFull(),
                        Textarea::make('feedback_caption')
                            ->label('Feedback Client') ->placeholder('Feedback Client') ->required()
                            ->columnSpanFull(),
                    ]),

                // Section: Posting
                Section::make('Posting')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('link_post')
                            ->label('Link Post') ->placeholder('Link Post') ->required()
                            ->url(),
                        DatePicker::make('tanggal_posting')
                            ->label('Tanggal Posting') ->placeholder('Tanggal Posting') ->required(),
                    ]),
            ]);
    }
}
