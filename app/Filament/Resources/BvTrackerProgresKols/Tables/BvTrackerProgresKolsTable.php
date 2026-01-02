<?php

namespace App\Filament\Resources\BvTrackerProgresKols\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BvTrackerProgresKolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_brand')
                    ->label('Brand')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('timeline')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('kol_approve')
                    ->label('KOL Approved')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),
                TextColumn::make('link')
                    ->label('Link')
                    ->limit(30)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
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
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'waiting_draft' => 'gray',
                        'waiting_storyline', 'waiting_caption', 'waiting_revisi_draft', 'waiting_revisi_storyline', 'waiting_revisi_caption' => 'warning',
                        'need_approval_storyline', 'need_approval_draft_video', 'need_approval_caption', 'kol_cancel' => 'danger',
                        'storyline_approved', 'draft_approved', 'video_approved', 'caption_approved' => 'success',
                        'waiting_kol_posting', 'kol_done_posting', 'kol_done_visit' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),

                // Draft Storyline
                TextColumn::make('draft_storyline')
                    ->label('Draft Storyline')
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('feedback_draft_storyline')
                    ->label('Feedback Client')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Draft Video
                TextColumn::make('draft_video')
                    ->label('Draft Video')
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('feedback_draft_video')
                    ->label('Feedback Client')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Draft Revisi 1
                TextColumn::make('draft_revisi_1')
                    ->label('Draft Revisi 1')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('feedback_draft_revisi_1')
                    ->label('Feedback Client')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Draft Revisi 2
                TextColumn::make('draft_revisi_2')
                    ->label('Draft Revisi 2')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('feedback_draft_revisi_2')
                    ->label('Feedback Client')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Caption
                TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('feedback_caption')
                    ->label('Feedback Client')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Posting
                TextColumn::make('link_post')
                    ->label('Link Post')
                    ->limit(20)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('tanggal_posting')
                    ->label('Tanggal Posting')
                    ->date()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
