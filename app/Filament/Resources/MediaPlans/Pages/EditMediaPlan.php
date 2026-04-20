<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Models\InternalBudgetItem;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMediaPlan extends EditRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected array $kolsData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load KOLs relationship data
        $data['kols'] = $this->record->kols->map(function ($kol) {
            return [
                'id' => $kol->id,
                'is_selected' => $kol->is_selected,
                'row_number' => $kol->row_number,
                'pic' => $kol->pic,
                'status' => $kol->status,
                'channel' => $kol->channel,
                'categories' => $kol->categories,
                'domisili' => $kol->domisili,
                'tipe_pajak_kol' => $kol->tipe_pajak_kol,
                'data_kol_id' => $kol->data_kol_id,
                'name' => $kol->name,
                'links' => $kol->links ?? [],
                'followers' => $kol->followers,
                'tier' => $kol->tier,
                'er_percent' => $kol->er_percent,
                'impression' => $kol->impression,
                'engagement' => $kol->engagement,
                'scope_items' => $kol->scope_items ?? [],
                'rate' => $kol->rate,
                'cpi_cpv' => $kol->cpi_cpv,
                'cpe' => $kol->cpe,
                'notes' => $kol->notes,
            ];
        })->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store kols data temporarily and remove from main data
        $this->kolsData = $data['kols'] ?? [];
        unset($data['kols']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Ensure internal budget exists
        $internalBudget = $this->record->internalBudget;
        if (!$internalBudget) {
            $internalBudget = $this->record->internalBudget()->create([
                'status' => 'draft',
            ]);
        }

        // Get existing KOL IDs
        $existingKolIds = collect($this->kolsData)
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete KOLs that are not in the form anymore
        $deletedKols = $this->record->kols()
            ->whereNotIn('id', $existingKolIds)
            ->get();

        foreach ($deletedKols as $kol) {
            // Delete related internal budget items
            $kol->internalBudgetItems()->delete();
            $kol->delete();
        }

        $sortOrder = $internalBudget->items()->max('sort_order') ?? 0;
        $maxRowNumber = $this->record->kols()->max('row_number') ?? 0;

        // Update or create KOLs
        foreach ($this->kolsData as $kolData) {
            // Remove temporary fields
            unset($kolData['search_link']);
            unset($kolData['categories']);

            $kolId = $kolData['id'] ?? null;
            unset($kolData['id']);

            // Ensure links is array
            if (isset($kolData['links']) && is_string($kolData['links'])) {
                $kolData['links'] = [$kolData['links']];
            }

            if ($kolId) {
                // Update existing KOL
                $mediaPlanKol = $this->record->kols()->find($kolId);
                if ($mediaPlanKol) {
                    $oldScopeItems = $mediaPlanKol->scope_items ?? [];
                    $newScopeItems = $kolData['scope_items'] ?? [];

                    $mediaPlanKol->update($kolData);

                    // If scope items changed, update internal budget items
                    if ($oldScopeItems !== $newScopeItems) {
                        // Delete existing budget items for this KOL
                        $mediaPlanKol->internalBudgetItems()->delete();

                        // Create new budget items for each scope item
                        foreach ($newScopeItems as $scopeItem) {
                            $internalBudget->items()->create([
                                'media_plan_kol_id' => $mediaPlanKol->id,
                                'scope_item' => $scopeItem,
                                'qty' => 1,
                                'rate_base' => 0,
                                'master_pph_id' => $mediaPlanKol->tipe_pajak_kol ?? \App\Models\MasterPph::where('name', 'Pribadi')->value('id'),
                                'sort_order' => ++$sortOrder,
                            ]);
                        }
                    } else {
                        // Update master_pph_id for existing budget items if tipe_pajak_kol changed
                        if (isset($kolData['tipe_pajak_kol']) && $kolData['tipe_pajak_kol'] != $mediaPlanKol->getOriginal('tipe_pajak_kol')) {
                            $mediaPlanKol->internalBudgetItems()->update([
                                'master_pph_id' => $kolData['tipe_pajak_kol']
                            ]);
                        }
                    }
                }
            } else {
                // Create new KOL
                $kolData['row_number'] = ++$maxRowNumber;
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
        }

        // Update CPI/CPV and CPE for each KOL
        foreach ($this->record->kols as $kol) {
            $kol->syncRateFromBudget();
        }

        // Recalculate budget totals
        $internalBudget->refresh();
        $internalBudget->recalculateTotals();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_internal_budget')
                ->label('View Media Plan External')
                ->icon('heroicon-m-eye')
                ->url(
                    fn($record) => $record->internalBudget
                    ? route('filament.office.resources.media-plan-external.edit', ['record' => $record->internalBudget->id])
                    : null
                )
                ->visible(fn($record) => $record->internalBudget !== null),

            Actions\Action::make('generate_quotation')
                ->label('Generate Quotation')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Quotation PDF')
                ->modalDescription('This will generate a quotation PDF for selected KOLs only. Make sure you have selected the KOLs you want to include.')
                ->modalSubmitActionLabel('Generate PDF')
                ->action(function ($record) {
                    $selectedKols = $record->selectedKols()->get();

                    if ($selectedKols->isEmpty()) {
                        Notification::make()
                            ->title('No KOLs Selected')
                            ->body('Please select at least one KOL before generating quotation.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Redirect to quotation download route
                    return redirect()->route('quotation.download', ['mediaPlan' => $record->id]);
                }),

            // Actions\Action::make('preview_quotation')
            //     ->label('Preview Quotation')
            //     ->icon('heroicon-m-eye')
            //     ->color('gray')
            //     ->url(fn($record) => route('quotation.preview', ['mediaPlan' => $record->id]))
            //     ->openUrlInNewTab()
            //     ->visible(fn($record) => $record->selectedKols()->count() > 0)
            //     ->tooltip('Preview Quotation in browser'),

            Actions\Action::make('preview_pdf')
                ->label('Preview Media Plan')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->url(fn($record) => route('media-plan.pdf.preview', ['mediaPlan' => $record->id]))
                ->openUrlInNewTab()
                ->visible(fn($record) => $record->internalBudget?->status === 'approved')
                ->tooltip('Preview Media Plan PDF in browser'),

            Actions\Action::make('export_google_sheets')
                ->label('Export Google Sheets')
                ->icon('heroicon-m-table-cells')
                ->color('success')
                ->url(fn($record) => route('media-plan.google-sheets', ['mediaPlan' => $record->id]))
                ->openUrlInNewTab()
                ->visible(fn($record) => $record->internalBudget?->status === 'approved')
                ->tooltip('Create new Google Spreadsheet from Media Plan'),
        ];
    }
}

