<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Helpers\QuotationNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaPlan extends CreateRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clean up username_manual field
        unset($data['username_manual']);

        // Auto-generate quotation number if not provided
        if (empty($data['quotation_number'])) {
            $data['quotation_number'] = QuotationNumberGenerator::generate();
        }

        return $data;
    }
}
