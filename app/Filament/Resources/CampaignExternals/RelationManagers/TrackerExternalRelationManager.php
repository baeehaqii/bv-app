<?php

namespace App\Filament\Resources\CampaignExternals\RelationManagers;

use App\Models\BvCampaignKol;
use App\Models\CampaignKolRevision;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Tracker progres konten per-KOL untuk Campaign Ongoing External (acuan sheet "EXT Tracker").
 * Read-only: data berasal dari modul Internal; client/tim memantau di sini.
 * Status workflow + draft/feedback/posting diambil dari kolom BvCampaignKol yang sudah ada;
 * progres revisi bertingkat ditampilkan via modal (load on-demand, hindari N+1).
 */
class TrackerExternalRelationManager extends RelationManager
{
    protected static string $relationship = 'kols';

    protected static ?string $title = 'Tracker';

    protected static ?string $recordTitleAttribute = 'creator_name';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('creator_name')
                    ->label('Username')
                    ->description(fn($record) => $record->username ? '@' . $record->username : null)
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('content_type')
                    ->label('SOW')
                    ->formatStateUsing(fn($record) => $record->content_type_label)
                    ->toggleable(),

                IconColumn::make('event_attendance')
                    ->label('Event')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('kol_profile_url')
                    ->label('Link')
                    ->url(fn($record) => $record->kol_profile_url, true)
                    ->formatStateUsing(fn($state) => $state ? 'Profil' : '—')
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'posted', 'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn($state) => BvCampaignKol::STATUSES[$state] ?? ucfirst((string) $state)),

                TextColumn::make('brief_status')
                    ->label('Brief')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'approved' => 'success',
                        'revision' => 'danger',
                        'waiting_review' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => BvCampaignKol::BRIEF_STATUSES[$state] ?? ucfirst((string) $state))
                    ->toggleable(),

                TextColumn::make('content_drive_link')
                    ->label('Draft Konten')
                    ->url(fn($record) => $record->content_drive_link, true)
                    ->formatStateUsing(fn($state) => $state ? 'Buka' : '—')
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('feedback')
                    ->label('Feedback')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->feedback)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('post_url')
                    ->label('Posting Link')
                    ->url(fn($record) => $record->post_url, true)
                    ->formatStateUsing(fn($state) => $state ? 'Lihat' : '—')
                    ->color('primary'),

                TextColumn::make('posting_date')
                    ->label('Posting Date')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BvCampaignKol::STATUSES),
            ])
            ->actions([
                Action::make('progres_revisi')
                    ->label('Progres Revisi')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn($record) => 'Progres Revisi: ' . $record->creator_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn($record) => new HtmlString(self::renderRevisions($record))),
            ])
            ->defaultSort('creator_name', 'asc')
            ->emptyStateHeading('Belum ada KOL')
            ->emptyStateDescription('KOL akan muncul setelah budget di-approve AM & campaign berjalan.')
            ->emptyStateIcon('heroicon-o-presentation-chart-line');
    }

    /** Render timeline revisi (stage/ronde/feedback/final) untuk satu KOL. */
    protected static function renderRevisions(BvCampaignKol $record): string
    {
        $revisions = $record->revisions()
            ->orderBy('stage')
            ->orderBy('round')
            ->get();

        if ($revisions->isEmpty()) {
            return '<p style="color:#6b7280;padding:12px;">Belum ada revisi konten.</p>';
        }

        $rows = '';
        foreach ($revisions as $rev) {
            $stage = CampaignKolRevision::STAGES[$rev->stage] ?? ucfirst((string) $rev->stage);
            $status = CampaignKolRevision::STATUSES[$rev->status] ?? ucfirst((string) $rev->status);
            $link = $rev->asset_link
                ? '<a href="' . e($rev->asset_link) . '" target="_blank" style="color:#4f46e5;">Buka</a>'
                : '—';
            $final = $rev->is_final ? ' ✅' : '';
            $feedback = $rev->client_feedback ? e($rev->client_feedback) : '—';

            $rows .= '<tr>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">' . e($stage) . $final . '</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;text-align:center;">' . (int) $rev->round . '</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">' . $link . '</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">' . e($status) . '</td>
                <td style="padding:6px 10px;border:1px solid #e5e7eb;">' . $feedback . '</td>
            </tr>';
        }

        return '<table style="border-collapse:collapse;width:100%;font-size:13px;">
            <thead><tr style="background:#f9fafb;">
                <th style="padding:6px 10px;border:1px solid #e5e7eb;text-align:left;">Tahap</th>
                <th style="padding:6px 10px;border:1px solid #e5e7eb;">Ronde</th>
                <th style="padding:6px 10px;border:1px solid #e5e7eb;text-align:left;">Draft</th>
                <th style="padding:6px 10px;border:1px solid #e5e7eb;text-align:left;">Status</th>
                <th style="padding:6px 10px;border:1px solid #e5e7eb;text-align:left;">Feedback Client</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';
    }
}
