<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use App\Models\InternalBudgetItem;
use App\Service\BvNotificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditMediaPlan extends EditRecord
{
    protected static string $resource = MediaPlanResource::class;

    protected array $kolsData = [];
    protected array $budgetItemsData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load KOLs relationship data
        $data['kols'] = $this->record->kols()->with('dataKol')->get()->map(function ($kol) {
            // Backfill ER & rate dari database KOL jika belum tersimpan di baris
            $erPercent = $kol->er_percent;
            if (!(float) $erPercent && $kol->dataKol) {
                $erPercent = (float) $kol->dataKol->engagement_rate;
            }

            $rate = (float) $kol->rate;
            if (!$rate && !empty($kol->scope_items)) {
                $rate = round(\App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::computeRateFromSow(
                    $kol->data_kol_id,
                    $kol->name,
                    $kol->channel,
                    $kol->scope_items ?? []
                ));
            }

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
                'er_percent' => $erPercent,
                'impression' => $kol->impression,
                'engagement' => $kol->engagement,
                'scope_items' => $kol->scope_items ?? [],
                'rate' => $rate,
                'cpi_cpv' => $kol->cpi_cpv,
                'cpe' => $kol->cpe,
                'notes' => $kol->notes,
            ];
        })->toArray();

        // Load budget items dari InternalBudget untuk tab Budget Items
        if ($this->record->internalBudget) {
            $data['budget_items'] = $this->record->internalBudget
                ->items()
                ->with('mediaPlanKol')
                ->orderBy('sort_order')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'kol_name' => $item->mediaPlanKol?->name ?? '—',
                    'kol_status' => $item->mediaPlanKol?->status ?? '—',
                    'scope_item' => $item->scope_item,
                    'qty' => $item->qty,
                    'rate_base' => $item->rate_base,
                    'master_pph_id' => $item->master_pph_id,
                    'mu_pph' => $item->mu_pph ? number_format(round($item->mu_pph), 0, '.', ',') : null,
                    'published_rate' => $item->published_rate,
                    'rounded' => $item->rounded ? number_format(round($item->rounded), 0, '.', ',') : null,
                    'actual_margin_percent' => $item->actual_margin_percent,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                    'subtotal' => $item->subtotal,
                    'mu_target' => $item->mu_target,
                    'status' => $item->status ?? 'pending',
                    'rejection_notes' => $item->rejection_notes,
                ])
                ->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store kols data temporarily and remove from main data
        $this->kolsData = $data['kols'] ?? [];
        $this->budgetItemsData = $data['budget_items'] ?? [];
        unset($data['kols']);
        unset($data['kol_margins']);
        unset($data['budget_items']); // handled in afterSave

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

            // Ensure name is never null — fallback to DataKol username
            if (empty($kolData['name']) && !empty($kolData['data_kol_id'])) {
                $kolData['name'] = \App\Models\DataKol::find($kolData['data_kol_id'])?->username;
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
                                'rate_base' => $this->rateForScope($mediaPlanKol, $scopeItem),
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
                        'rate_base' => $this->rateForScope($mediaPlanKol, $scopeItem),
                        'master_pph_id' => $mediaPlanKol->tipe_pajak_kol ?? \App\Models\MasterPph::where('name', 'Pribadi')->value('id'),
                        'sort_order' => ++$sortOrder,
                    ]);
                }
            }
        }

        // Kalkulasi Cost/Client Price/Margin tiap budget item dari rate_base + koef PPh + Margin% KOL.
        // Dipindah dari step "Budget Items" (kini disembunyikan) agar nilai tetap konsisten & tidak dobel.
        foreach ($internalBudget->items()->with(['mediaPlanKol', 'masterPph'])->get() as $item) {
            $coeff = $item->masterPph?->getCalculatedCoefficient() ?? 0.975;
            $kolMargin = $item->mediaPlanKol?->margin_percent;
            $subtotal = (float) $item->rate_base * (int) ($item->qty ?: 1);

            $figs = \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::computeBudgetFigures(
                $subtotal,
                $coeff,
                $kolMargin !== null ? (float) $kolMargin : null,
            );

            $item->update([
                'subtotal' => $figs['subtotal'],
                'mu_pph' => $figs['mu_pph'],
                'mu_target' => $figs['mu_target'],
                'published_rate' => $figs['mu_target'],
                'rounded' => $figs['rounded'],
                'actual_margin_percent' => $figs['actual_margin'],
                // Simpan override agar margin KOL tetap konsisten bila halaman External recalculate.
                'use_flexible_margin' => $kolMargin !== null,
                'margin_percent_override' => $kolMargin !== null ? (float) $kolMargin : null,
            ]);
        }

        // Update CPI/CPV and CPE for each KOL
        foreach ($this->record->kols as $kol) {
            $kol->syncRateFromBudget();
        }

        // Simpan perubahan budget items dari tab Budget Items (rate, qty, notes, pph)
        if (!empty($this->budgetItemsData)) {
            foreach ($this->budgetItemsData as $itemData) {
                $itemId = $itemData['id'] ?? null;
                if (!$itemId) {
                    continue;
                }

                $item = InternalBudgetItem::find($itemId);
                if (!$item || $item->internalBudget?->media_plan_id !== $this->record->id) {
                    continue;
                }

                $item->update([
                    'qty' => (int) ($itemData['qty'] ?? 1),
                    'rate_base' => is_numeric($itemData['rate_base'] ?? null) ? $itemData['rate_base'] : 0,
                    'master_pph_id' => $itemData['master_pph_id'] ?? $item->master_pph_id,
                    'mu_pph' => is_numeric($itemData['mu_pph'] ?? null) ? $itemData['mu_pph'] : (float) str_replace(',', '', $itemData['mu_pph'] ?? '0'),
                    'published_rate' => is_numeric($itemData['published_rate'] ?? null) ? $itemData['published_rate'] : (float) str_replace(',', '', $itemData['published_rate'] ?? '0'),
                    'rounded' => is_numeric($itemData['rounded'] ?? null) ? $itemData['rounded'] : (float) str_replace(',', '', $itemData['rounded'] ?? '0'),
                    'actual_margin_percent' => (float) ($itemData['actual_margin_percent'] ?? 0),
                    'subtotal' => (float) ($itemData['subtotal'] ?? 0),
                    'mu_target' => (float) ($itemData['mu_target'] ?? 0),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        }

        // Recalculate budget totals
        $internalBudget->refresh();
        $internalBudget->recalculateTotals();

        // Notifikasi WA jika ada perubahan PIC
        $picFields = ['pic_sales_bd_id', 'pic_leads_project_id', 'pic_am_id', 'pic_project_internal_ids'];
        $picChanged = collect($picFields)->contains(fn($f) => $this->record->wasChanged($f));

        if ($picChanged) {
            try {
                $this->record->loadMissing(['picSalesBd.user', 'picLeadsProject.user', 'picAm.user']);
                app(BvNotificationService::class)->picAssigned($this->record);
            } catch (\Throwable $e) {
                Log::warning('[EditMediaPlan] Notifikasi WA PIC assigned gagal: ' . $e->getMessage());
            }
        }
    }

    /**
     * Rate dasar 1 scope item, diambil dari rate card SOW milik KOL tersebut.
     */
    private function rateForScope(\App\Models\MediaPlanKol $kol, string $scopeItem): float
    {
        return \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::computeRateFromSow(
            $kol->data_kol_id,
            $kol->name,
            $kol->channel,
            [$scopeItem],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_internal_budget')
                ->label('View Media Plan External')
                ->icon('heroicon-m-eye')
                ->color('white')
                ->url(
                    fn($record) => $record->internalBudget
                    ? route('filament.office.resources.media-plan-external.edit', ['record' => $record->internalBudget->id])
                    : null
                )
                ->visible(fn($record) => $record->internalBudget !== null),

            // Actions\Action::make('generate_quotation')
            //     ->label('Generate Quotation')
            //     ->icon('heroicon-m-document-arrow-down')
            //     ->color('white')
            //     ->requiresConfirmation()
            //     ->modalHeading('Generate Quotation PDF')
            //     ->modalDescription('This will generate a quotation PDF for selected KOLs only. Make sure you have selected the KOLs you want to include.')
            //     ->modalSubmitActionLabel('Generate PDF')
            //     ->action(function ($record) {
            //         $selectedKols = $record->selectedKols()->get();
            //
            //         if ($selectedKols->isEmpty()) {
            //             Notification::make()
            //                 ->title('No KOLs Selected')
            //                 ->body('Please select at least one KOL before generating quotation.')
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }
            //
            //         // Redirect to quotation download route
            //         return redirect()->route('quotation.download', ['mediaPlan' => $record->id]);
            //     }),

            // Actions\Action::make('preview_quotation')
            //     ->label('Preview Quotation')
            //     ->icon('heroicon-m-eye')
            //     ->color('gray')
            //     ->url(fn($record) => route('quotation.preview', ['mediaPlan' => $record->id]))
            //     ->openUrlInNewTab()
            //     ->visible(fn($record) => $record->selectedKols()->count() > 0)
            //     ->tooltip('Preview Quotation in browser'),

            // Actions\Action::make('preview_pdf')
            //     ->label('Preview PDF')
            //     ->icon('heroicon-m-document-text')
            //     ->color('gray')
            //     ->url(fn($record) => $record->internalBudget
            //         ? route('internal-budget.pdf.preview', ['internalBudget' => $record->internalBudget->id])
            //         : null)
            //     ->openUrlInNewTab()
            //     ->visible(fn($record) => $record->internalBudget !== null)
            //     ->tooltip('Preview Internal Budget PDF in browser'),

            // Actions\Action::make('preview_media_plan_pdf')
            //     ->label('Preview Media Plan')
            //     ->icon('heroicon-m-document-text')
            //     ->color('info')
            //     ->url(fn($record) => route('media-plan.pdf.preview', ['mediaPlan' => $record->id]))
            //     ->openUrlInNewTab()
            //     ->visible(fn($record) => $record->internalBudget?->status === 'approved')
            //     ->tooltip('Preview Media Plan PDF in browser'),

            // Actions\Action::make('export_google_sheets')
            //     ->label('Export Google Sheets')
            //     ->icon('heroicon-m-table-cells')
            //     ->color('success')
            //     ->url(fn($record) => route('media-plan.google-sheets', ['mediaPlan' => $record->id]))
            //     ->openUrlInNewTab()
            //     ->visible(fn($record) => $record->internalBudget?->status === 'approved')
            //     ->tooltip('Create new Google Spreadsheet from Media Plan'),
        ];
    }
}

