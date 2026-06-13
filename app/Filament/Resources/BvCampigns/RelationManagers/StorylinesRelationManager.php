<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

use App\Models\CampaignStoryline;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StorylinesRelationManager extends RelationManager
{
    protected static string $relationship = 'storylines';

    protected static ?string $title = 'Storyline';

    protected static ?string $recordTitleAttribute = 'kol_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('kol_name')
                        ->label('Nama KOL / Creator')
                        ->required()
                        ->maxLength(255),

                    Select::make('platform')
                        ->label('Platform')
                        ->options(CampaignStoryline::PLATFORMS)
                        ->native(false),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('sow')
                        ->label('SOW (Scope of Work)')
                        ->placeholder('e.g. IG Reels 1x, Story 2x')
                        ->maxLength(255),

                    DatePicker::make('posting_deadline')
                        ->label('Deadline Posting')
                        ->native(false)
                        ->displayFormat('d M Y'),
                ]),

                TextInput::make('content_angle')
                    ->label('Content Angle')
                    ->placeholder('e.g. Lifestyle, Review, Tutorial')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('key_message')
                    ->label('Key Message')
                    ->placeholder('Pesan utama yang harus disampaikan...')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('caption_draft')
                    ->label('Caption Draft')
                    ->placeholder('Draft caption untuk posting...')
                    ->rows(4)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(CampaignStoryline::STATUSES)
                        ->default('draft')
                        ->native(false)
                        ->required(),

                    Textarea::make('notes')
                        ->label('Catatan / Feedback')
                        ->placeholder('Catatan revisi atau feedback client...')
                        ->rows(2),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kol_name')
                    ->label('KOL / Creator')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'instagram' => 'pink',
                        'tiktok'    => 'gray',
                        'youtube'   => 'danger',
                        'threads'   => 'info',
                        default     => 'primary',
                    })
                    ->formatStateUsing(fn($state) => CampaignStoryline::PLATFORMS[$state] ?? ucfirst($state)),

                TextColumn::make('sow')
                    ->label('SOW')
                    ->searchable(),

                TextColumn::make('content_angle')
                    ->label('Content Angle')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('posting_deadline')
                    ->label('Deadline Posting')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'draft'            => 'gray',
                        'waiting_approval' => 'warning',
                        'revision'         => 'danger',
                        'approved'         => 'success',
                        'posted'           => 'primary',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn($state) => CampaignStoryline::STATUSES[$state] ?? ucfirst($state)),

                TextColumn::make('client_choice')
                    ->label('Pilihan Client')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'approved' => 'success',
                        'revision' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'approved' => '✓ Approve',
                        'revision' => '↻ Revisi',
                        default    => '—',
                    }),

                TextColumn::make('client_feedback')
                    ->label('Feedback Client')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->client_feedback)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options(CampaignStoryline::PLATFORMS),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CampaignStoryline::STATUSES),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Storyline'),
            ])
            ->actions([
                Action::make('send_to_client')
                    ->label('Kirim ke Client')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('warning')
                    ->tooltip('Tandai draft ini "Waiting Approval" agar muncul di Link Approval Konten')
                    ->visible(fn($record) => in_array($record->status, ['draft', 'revision'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Draft ke Client')
                    ->modalDescription('Draft ini akan ditandai "Waiting Approval" dan muncul di Link Approval Konten. Buat/Buka link approval dari tombol header. Lanjutkan?')
                    ->action(function ($record) {
                        $record->update(['status' => 'waiting_approval']);
                        Notification::make()
                            ->title('Draft siap di-review client')
                            ->body('Gunakan tombol "Buat Link Approval Konten" di header untuk membagikan tautannya.')
                            ->success()
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
            ->defaultSort('posting_deadline', 'asc')
            ->emptyStateHeading('Belum ada storyline')
            ->emptyStateDescription('Tambahkan storyline untuk setiap KOL yang terlibat dalam campaign ini.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
