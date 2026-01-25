<?php

namespace App\Filament\Resources\BvCampignUpcomings\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BvCampignUpcomingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('client.id')
                    ->label('Client')
                    ->placeholder('-'),
                TextEntry::make('campaign_name'),
                ImageEntry::make('campaign_image')
                    ->placeholder('-'),
                TextEntry::make('budget_allocated')
                    ->numeric(),
                TextEntry::make('pot_cpv')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('pot_cpe')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('pot_views')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
