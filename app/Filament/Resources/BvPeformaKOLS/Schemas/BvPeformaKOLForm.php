<?php

namespace App\Filament\Resources\BvPeformaKOLS\Schemas;

use App\Service\InstagramService;
use App\Service\TiktokService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BvPeformaKOLForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Informasi Umum - Full Width
                Section::make('Informasi Umum')
                    ->description('Informasi dasar tracking performa KOL')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('pic')
                                    ->label('PIC')
                                    ->placeholder('Nama PIC')
                                    ->maxLength(255),
                                TextInput::make('username')
                                    ->label('Username')
                                    ->placeholder('@username')
                                    ->maxLength(255),
                                DatePicker::make('tanggal_posting')
                                    ->label('Tanggal Posting')
                                    ->native(false)
                                    ->displayFormat('d M Y'),
                            ]),
                        TextInput::make('link_insight_postingan')
                            ->label('Link Insight Postingan (TikTok + IG)')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // TikTok - Full Width
                Section::make('TikTok')
                    ->description('Masukkan link posting TikTok untuk mengambil data secara otomatis')
                    ->icon('heroicon-o-play')
                    ->collapsible()
                    ->schema([
                        TextInput::make('link_posting_tiktok')
                            ->label('Link Posting TikTok')
                            ->placeholder('https://www.tiktok.com/@username/video/...')
                            ->url()
                            ->suffixAction(
                                Action::make('fetch_tiktok')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Ambil data dari TikTok')
                                    ->action(function (callable $set, $state) {
                                        if (empty($state)) {
                                            Notification::make()
                                                ->title('Link TikTok kosong')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        try {
                                            $service = new TiktokService();
                                            $stats = $service->getPostStats($state);

                                            $set('username', $stats['username'] ?? '');
                                            $set('tiktok_views', $stats['views']);
                                            $set('tiktok_likes', $stats['likes']);
                                            $set('tiktok_comments', $stats['comments']);
                                            $set('tiktok_saves', $stats['saves']);
                                            $set('tiktok_shares', $stats['shares']);
                                            $set('tiktok_total_engagement', $stats['total_engagement']);

                                            Notification::make()
                                                ->title('Data TikTok berhasil diambil')
                                                ->success()
                                                ->send();
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Gagal mengambil data TikTok')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            )
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('tiktok_views')
                                    ->label('Views')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('👁️'),
                                TextInput::make('tiktok_likes')
                                    ->label('Likes')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('❤️'),
                                TextInput::make('tiktok_comments')
                                    ->label('Comments')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('💬'),
                                TextInput::make('tiktok_saves')
                                    ->label('Saves')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('🔖'),
                                TextInput::make('tiktok_shares')
                                    ->label('Shares')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('🔗'),
                                TextInput::make('tiktok_total_engagement')
                                    ->label('Total Engagement')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('📊')
                                    ->helperText('Likes + Comments + Saves + Shares'),
                            ]),
                    ])
                    ->columnSpan(1),

                // Instagram - Full Width
                Section::make('Instagram')
                    ->description('Masukkan link posting Instagram untuk mengambil data secara otomatis')
                    ->icon('heroicon-o-camera')
                    ->collapsible()
                    ->schema([
                        TextInput::make('link_posting_instagram')
                            ->label('Link Posting Instagram')
                            ->placeholder('https://www.instagram.com/p/... atau /reel/...')
                            ->url()
                            ->suffixAction(
                                Action::make('fetch_instagram')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Ambil data dari Instagram')
                                    ->action(function (callable $set, $state) {
                                        if (empty($state)) {
                                            Notification::make()
                                                ->title('Link Instagram kosong')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        try {
                                            $service = new InstagramService();
                                            $stats = $service->getPostStats($state);

                                            if (!empty($stats['username'])) {
                                                $set('username', $stats['username']);
                                            }
                                            $set('instagram_views', $stats['views']);
                                            $set('instagram_likes', $stats['likes']);
                                            $set('instagram_comments', $stats['comments']);
                                            $set('instagram_saves', $stats['saves']);
                                            $set('instagram_shares', $stats['shares']);
                                            $set('instagram_total_engagement', $stats['total_engagement']);

                                            Notification::make()
                                                ->title('Data Instagram berhasil diambil')
                                                ->success()
                                                ->send();
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Gagal mengambil data Instagram')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            )
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('instagram_views')
                                    ->label('Views')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('👁️'),
                                TextInput::make('instagram_likes')
                                    ->label('Likes')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('❤️'),
                                TextInput::make('instagram_comments')
                                    ->label('Comments')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('💬'),
                                TextInput::make('instagram_saves')
                                    ->label('Saves')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('🔖'),
                                TextInput::make('instagram_shares')
                                    ->label('Shares')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('🔗'),
                                TextInput::make('instagram_total_engagement')
                                    ->label('Total Engagement')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('📊')
                                    ->helperText('Likes + Comments + Saves + Shares'),
                            ]),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(2);
    }
}
