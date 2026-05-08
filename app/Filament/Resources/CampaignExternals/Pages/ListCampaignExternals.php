<?php

namespace App\Filament\Resources\CampaignExternals\Pages;

use App\Filament\Resources\CampaignExternals\CampaignExternalResource;
use Filament\Resources\Pages\ListRecords;

class ListCampaignExternals extends ListRecords
{
    protected static string $resource = CampaignExternalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
