<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

use App\Models\CampaignKolRevision;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Kelola revisi konten dinamis (storyline/video/caption, tak terbatas ronde + Final Revisi).
 * Dikelola tim internal. Feedback client (kolom client_feedback) read-only di sini —
 * diisi client lewat modul External / Link Approval Konten.
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Revisi Konten';

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

                    Select::make('stage')
                        ->label('Tahap')
                        ->options(CampaignKolRevision::STAGES)
                        ->default('video')
                        ->native(false)
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('round')
                        ->label('Ronde ke-')
                        ->helperText('1 = draft awal, 2 = revisi 1, dst.')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),

                    Select::make('status')
                        ->label('Status')
                        ->options(CampaignKolRevision::STATUSES)
                        ->default('waiting_review')
                        ->native(false)
                        ->required(),
                ]),

                TextInput::make('asset_link')
                    ->label('Link Draft / Revisi (Google Docs/Drive)')
                    ->url()
                    ->maxLength(2048)
                    ->columnSpanFull(),

                Textarea::make('asset_text')
                    ->label('Isi Storyline / Caption (opsional)')
                    ->placeholder('Bila draft berupa teks, bukan link...')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('client_feedback')
                    ->label('Feedback Client')
                    ->helperText('Biasanya diisi client lewat Link Approval Konten (modul External).')
                    ->rows(2)
                    ->columnSpanFull(),

                Toggle::make('is_final')
                    ->label('Tandai sebagai Final Revisi')
                    ->helperText('Versi terkunci yang siap posting.'),
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

                TextColumn::make('stage')
                    ->label('Tahap')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'storyline' => 'info',
                        'video'     => 'primary',
                        'caption'   => 'warning',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => CampaignKolRevision::STAGES[$state] ?? ucfirst((string) $state)),

                TextColumn::make('round')
                    ->label('Ronde')
                    ->badge()
                    ->formatStateUsing(fn($state) => 'Ronde ' . $state)
                    ->sortable(),

                TextColumn::make('asset_link')
                    ->label('Link Draft')
                    ->url(fn($record) => $record->asset_link, true)
                    ->formatStateUsing(fn($state) => $state ? 'Buka' : '—')
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'waiting_review' => 'warning',
                        'revision'       => 'danger',
                        'approved'       => 'success',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn($state) => CampaignKolRevision::STATUSES[$state] ?? ucfirst((string) $state)),

                IconColumn::make('is_final')
                    ->label('Final')
                    ->boolean(),

                TextColumn::make('client_feedback')
                    ->label('Feedback Client')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->client_feedback)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->label('Tahap')
                    ->options(CampaignKolRevision::STAGES),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CampaignKolRevision::STATUSES),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Revisi')
                    ->mutateDataUsing(function (array $data) {
                        // Tautkan ke baris KOL Performance bila nama cocok.
                        $kol = $this->getOwnerRecord()->kols()
                            ->where('creator_name', $data['kol_name'] ?? '')
                            ->first();
                        $data['bv_campaign_kol_id'] = $kol?->id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('round', 'asc')
            ->emptyStateHeading('Belum ada revisi')
            ->emptyStateDescription('Tambahkan ronde revisi (storyline / video / caption) untuk tiap KOL.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }
}
