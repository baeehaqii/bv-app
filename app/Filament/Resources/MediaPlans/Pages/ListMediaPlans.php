<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMediaPlans extends ListRecords
{
    protected static string $resource = MediaPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Media Plan'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => \Filament\Schemas\Components\Tabs\Tab::make(),
            'Planning' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'Planning')),
            'Ongoing' => \Filament\Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('status', 'Ongoing')),
        ];
    }
}
