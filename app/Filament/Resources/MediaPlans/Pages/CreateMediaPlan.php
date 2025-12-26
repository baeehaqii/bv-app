<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Helpers\QuotationNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaPlan extends CreateRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected array $kolsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store kols data temporarily and remove from main data
        $this->kolsData = $data['kols'] ?? [];
        unset($data['kols']);

        // Auto-generate quotation number if not provided
        if (empty($data['quotation_number'])) {
            $data['quotation_number'] = QuotationNumberGenerator::generate();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create Internal Budget for this Media Plan (1:1 relationship)
        $internalBudget = $this->record->internalBudget()->create([
            'status' => 'draft',
        ]);

        $rowNumber = 0;
        $sortOrder = 0;

        // Save each KOL and create budget items
        foreach ($this->kolsData as $kolData) {
            // Remove temporary fields
            unset($kolData['search_link']);
            unset($kolData['categories']);

            // Set row number
            $kolData['row_number'] = ++$rowNumber;

            // Ensure links is array
            if (isset($kolData['links']) && is_string($kolData['links'])) {
                $kolData['links'] = [$kolData['links']];
            }

            // Create the MediaPlanKol record
            $mediaPlanKol = $this->record->kols()->create($kolData);

            // Create internal budget items for each scope item
            $scopeItems = $kolData['scope_items'] ?? ['Deliverable'];
            foreach ($scopeItems as $scopeItem) {
                $internalBudget->items()->create([
                    'media_plan_kol_id' => $mediaPlanKol->id,
                    'scope_item' => $scopeItem,
                    'qty' => 1,
                    'rate_base' => 0,
                    'master_pph_id' => $mediaPlanKol->tipe_pajak_kol ?? \App\Models\MasterPph::where('name', 'Pribadi')->value('id'),
                    'sort_order' => ++$sortOrder,
                ]);
            }
        }

        // Recalculate budget totals
        $internalBudget->refresh();
        $internalBudget->recalculateTotals();
    }
}
