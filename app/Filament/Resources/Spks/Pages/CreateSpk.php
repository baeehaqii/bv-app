<?php

namespace App\Filament\Resources\Spks\Pages;

use App\Filament\Resources\Spks\Concerns\ConvertsClauseState;
use App\Filament\Resources\Spks\SpkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpk extends CreateRecord
{
    use ConvertsClauseState;

    protected static string $resource = SpkResource::class;
}
