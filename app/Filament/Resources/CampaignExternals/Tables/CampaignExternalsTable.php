<?php

namespace App\Filament\Resources\CampaignExternals\Tables;

use App\Filament\Resources\CampaignExternals\CampaignExternalResource;
use App\Models\BvCampign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignExternalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                // Tampilkan SEMUA campaign ongoing (internal & external). Campaign internal
                // dari pipeline Media Plan juga muncul di sini agar client bisa preview/revisi
                // lewat modul External (Internal = workspace tim, External = sisi client).
                BvCampign::query()
                    ->where('status', 'ongoing')
                    ->with(['client', 'kols'])
            )
            ->columns([
                ImageColumn::make('campaign_image')
                    ->label('')
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->campaign_name ?? 'C') . '&color=059669&background=d1fae5'),

                TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->client?->nama_brand ?? '-'),

                TextColumn::make('campaign_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn($state) => $state === BvCampign::TYPE_INTERNAL ? 'info' : 'success')
                    ->formatStateUsing(fn($state) => $state === BvCampign::TYPE_INTERNAL ? 'Internal' : 'External'),

                TextColumn::make('start_date')
                    ->label('Timeline')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        ($record->start_date?->format('d M') ?? '-') . ' — ' . ($record->end_date?->format('d M Y') ?? '-')
                    )
                    ->sortable(),

                TextColumn::make('kols_count')
                    ->label('KOLs')
                    ->counts('kols')
                    ->badge()
                    ->color('info')
                    ->suffix(' creators'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'draft'     => 'gray',
                        'upcoming'  => 'info',
                        'ongoing'   => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucfirst($state ?? 'draft')),

                IconColumn::make('is_public')
                    ->label('External Link')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'upcoming'  => 'Upcoming',
                        'ongoing'   => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Brand')
                    ->relationship('client', 'nama_brand')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('success')
                    ->visible(fn($record) => $record->is_public)
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Link External')
                            ->body($record->public_url)
                            ->info()
                            ->persistent()
                            ->send();
                    }),

                Action::make('generate_link')
                    ->label('Buat Link')
                    ->icon('heroicon-o-share')
                    ->color('warning')
                    ->visible(fn($record) => !$record->is_public)
                    ->requiresConfirmation()
                    ->modalHeading('Buat Link External?')
                    ->modalDescription('Link publik akan dibuat. Client dapat melihat progress campaign tanpa login.')
                    ->action(function ($record) {
                        $record->generatePublicToken();
                        Notification::make()
                            ->title('Link berhasil dibuat!')
                            ->body($record->fresh()->public_url)
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Action::make('view_external')
                    ->label('View Page')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn($record) => $record->is_public)
                    ->url(fn($record) => $record->public_url)
                    ->openUrlInNewTab(),

                Action::make('revoke_link')
                    ->label('Cabut')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn($record) => $record->is_public)
                    ->requiresConfirmation()
                    ->modalHeading('Cabut Akses External?')
                    ->modalDescription('Link publik akan dinonaktifkan.')
                    ->action(function ($record) {
                        $record->revokePublicToken();
                        Notification::make()
                            ->title('Akses external dicabut')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
