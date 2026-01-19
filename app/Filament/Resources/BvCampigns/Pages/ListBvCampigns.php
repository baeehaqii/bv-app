<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Filament\Widgets\CampaignSummaryWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvCampigns extends ListRecords
{
    protected static string $resource = BvCampignResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignSummaryWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
