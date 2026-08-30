<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Helpers\QuotationNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaPlan extends CreateRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected static bool $canCreateAnother = false;

    protected array $kolsData = [];
    protected array $kolMargins = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store kols data temporarily and remove from main data
        $this->kolsData = $data['kols'] ?? [];
        $this->kolMargins = $data['kol_margins'] ?? [];
        unset($data['kols']);
        unset($data['kol_margins']);

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

            // Determine margin for this KOL
            $useGlobalMargin = $data['use_global_margin'] ?? true;
            $globalMargin = $data['margin_percent'] ?? \App\Models\MasterMargin::getMarginForAmount(0);

            // Default to global
            $useFlexible = false;
            $overridenMargin = null;

            if (!$useGlobalMargin) {
                // Find custom margin from kol_margins based on index
                // Note: kol_margins is indexed array matching kolsData order hopefully
                // $kolData['row_number'] is 1-based index we just set
                $marginIndex = $kolData['row_number'] - 1; // 0-based index
                $customMargin = $this->kolMargins[$marginIndex]['margin'] ?? $globalMargin;

                $useFlexible = true;
                $overridenMargin = $customMargin;
            }

            foreach ($scopeItems as $scopeItem) {
                $internalBudget->items()->create([
                    'media_plan_kol_id' => $mediaPlanKol->id,
                    'scope_item' => $scopeItem,
                    'qty' => 1,
                    'rate_base' => 0,
                    'master_pph_id' => $mediaPlanKol->tipe_pajak_kol ?? \App\Models\MasterPph::where('name', 'Pribadi')->value('id'),
                    'sort_order' => ++$sortOrder,
                    'use_flexible_margin' => $useFlexible,
                    'margin_percent_override' => $overridenMargin,
                ]);
            }
        }

        // Recalculate budget totals
        $internalBudget->refresh();
        $internalBudget->recalculateTotals();
    }
}
