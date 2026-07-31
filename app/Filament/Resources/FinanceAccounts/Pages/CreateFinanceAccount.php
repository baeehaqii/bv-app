<?php

namespace App\Filament\Resources\FinanceAccounts\Pages;

use App\Filament\Resources\FinanceAccounts\FinanceAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinanceAccount extends CreateRecord
{
    protected static string $resource = FinanceAccountResource::class;
}
