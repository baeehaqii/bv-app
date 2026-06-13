<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

use App\Models\BvCampaignKol;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KolBriefRelationManager extends RelationManager
{
    protected static string $relationship = 'kols';

    protected static ?string $title = 'KOL Brief';

    protected static ?string $recordTitleAttribute = 'creator_name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identitas KOL')
                ->columns(2)
                ->schema([
                    TextInput::make('creator_name')
                        ->label('Nama KOL / Creator')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('username')
                        ->label('Username')
                        ->placeholder('@username')
                        ->maxLength(255),

                    TextInput::make('kol_profile_url')
                        ->label('Link Profil (TikTok / IG)')
                        ->url()
                        ->placeholder('https://www.tiktok.com/@username')
                        ->columnSpan(1),

                    Select::make('tier')
                        ->label('Tier')
                        ->options(BvCampaignKol::TIERS)
                        ->native(false),

                    Select::make('platform')
                        ->label('Platform')
                        ->options(BvCampaignKol::PLATFORMS)
                        ->required()
                        ->native(false)
                        ->reactive(),

                    Select::make('content_type')
                        ->label('Content Type')
                        ->options(fn($get) => BvCampaignKol::CONTENT_TYPES[$get('platform')] ?? [])
                        ->required()
                        ->native(false),
                ]),

            Section::make('Visit & Briefing')
                ->columns(2)
                ->schema([
                    DatePicker::make('visit_date')
                        ->label('Tanggal Visit')
                        ->native(false)
                        ->displayFormat('d M Y'),

                    Select::make('visit_status')
                        ->label('Status Visit')
                        ->options(BvCampaignKol::VISIT_STATUSES)
                        ->native(false),

                    Toggle::make('event_attendance')
                        ->label('Hadir Event')
                        ->helperText('Item SOW "Event Attendance" (acuan sheet Tracker).')
                        ->inline(false),
                ]),

            Section::make('Konten & Review')
                ->columns(2)
                ->schema([
                    TextInput::make('content_drive_link')
                        ->label('Link Drive Konten')
                        ->url()
                        ->placeholder('https://drive.google.com/...')
                        ->columnSpanFull(),

                    Select::make('brief_status')
                        ->label('Status Brief')
                        ->options(BvCampaignKol::BRIEF_STATUSES)
                        ->default('draft')
                        ->required()
                        ->native(false)
                        ->helperText('Approved = konten otomatis masuk KOL Performance'),

                    DatePicker::make('posting_date')
                        ->label('Tanggal Posting (Rencana)')
                        ->native(false)
                        ->displayFormat('d M Y'),

                    Textarea::make('feedback')
                        ->label('Feedback Round 1')
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('revision_link')
                        ->label('Link Revisi')
                        ->url()
                        ->placeholder('https://drive.google.com/...')
                        ->columnSpanFull(),

                    Textarea::make('feedback_2')
                        ->label('Feedback Round 2')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('brief_status', '!=', 'approved'))
            ->columns([
                TextColumn::make('creator_name')
                    ->label('KOL / Creator')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn($record) => $record->username ? '@' . $record->username : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kol_profile_url')
                    ->label('Link Profil')
                    ->url(fn($record) => $record->kol_profile_url)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($state) => $state ? 'Buka ↗' : '-')
                    ->color('primary'),

                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'mega'  => 'danger',
                        'macro' => 'warning',
                        'micro' => 'info',
                        'nano'  => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'mega'  => 'Mega',
                        'macro' => 'Macro',
                        'micro' => 'Micro',
                        'nano'  => 'Nano',
                        default => ucfirst($state ?? '-'),
                    }),

                TextColumn::make('visit_date')
                    ->label('Tanggal Visit')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('visit_status')
                    ->label('Visit')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'done'      => 'success',
                        'scheduled' => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => BvCampaignKol::VISIT_STATUSES[$state] ?? '-'),

                IconColumn::make('event_attendance')
                    ->label('Event')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('content_drive_link')
                    ->label('Drive Konten')
                    ->url(fn($record) => $record->content_drive_link)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($state) => $state ? 'Buka ↗' : '-')
                    ->color(fn($state) => $state ? 'primary' : 'gray'),

                TextColumn::make('brief_status')
                    ->label('Status Brief')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'draft'          => 'gray',
                        'waiting_review' => 'warning',
                        'revision'       => 'danger',
                        'approved'       => 'success',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn($state) => BvCampaignKol::BRIEF_STATUSES[$state] ?? ucfirst($state)),

                TextColumn::make('feedback')
                    ->label('Feedback')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('revision_link')
                    ->label('Link Revisi')
                    ->url(fn($record) => $record->revision_link)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($state) => $state ? 'Buka ↗' : '-')
                    ->color(fn($state) => $state ? 'primary' : 'gray'),

                TextColumn::make('feedback_2')
                    ->label('Feedback 2')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('posting_date')
                    ->label('Tgl Posting (Plan)')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tier')
                    ->label('Tier')
                    ->options(BvCampaignKol::TIERS),

                SelectFilter::make('visit_status')
                    ->label('Status Visit')
                    ->options(BvCampaignKol::VISIT_STATUSES),

                SelectFilter::make('brief_status')
                    ->label('Status Brief')
                    ->options(array_filter(BvCampaignKol::BRIEF_STATUSES, fn($k) => $k !== 'approved', ARRAY_FILTER_USE_KEY)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah KOL'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Konten KOL?')
                    ->modalDescription('Konten KOL akan ditandai APPROVED dan otomatis masuk ke tab KOL Performance.')
                    ->modalSubmitActionLabel('Ya, Approve')
                    ->visible(fn($record) => $record->brief_status !== 'approved')
                    ->action(function ($record) {
                        $record->approveBrief();

                        Notification::make()
                            ->title('KOL Approved!')
                            ->body("{$record->creator_name} sudah masuk ke KOL Performance.")
                            ->success()
                            ->send();
                    }),

                Action::make('set_revision')
                    ->label('Minta Revisi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn($record) => in_array($record->brief_status, ['waiting_review', 'draft']))
                    ->action(function ($record) {
                        $record->update(['brief_status' => 'revision']);

                        Notification::make()
                            ->title('Status diubah ke Revision')
                            ->warning()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada KOL')
            ->emptyStateDescription('Tambahkan KOL yang akan di-brief untuk campaign ini.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
