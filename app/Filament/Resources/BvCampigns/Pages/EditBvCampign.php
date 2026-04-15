<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Jobs\ScrapeKolMetricsJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvCampign extends EditRecord
{
    protected static string $resource = BvCampignResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['kol_entries'] = $this->record->kols
            ->map(fn($kol) => [
                'creator_name' => $kol->creator_name,
                'channel' => $kol->platform . '_' . $kol->content_type,
                'url' => $kol->post_url,
                'price' => number_format($kol->price, 0, ',', '.'),
            ])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $entries = $data['kol_entries'] ?? [];

        $data['media_platforms'] = collect($entries)
            ->pluck('channel')
            ->filter()
            ->map(fn($ch) => explode('_', $ch, 2)[0])
            ->unique()
            ->values()
            ->toArray();

        $totalCost = collect($entries)->sum(
            fn($e) => (float) str_replace(['.', ','], '', $e['price'] ?? '0')
        );

        if ($totalCost > 0) {
            $data['total_cost'] = $totalCost;
        }

        unset($data['kol_entries']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->kols()->delete();

        foreach ($this->data['kol_entries'] ?? [] as $entry) {
            $channel = $entry['channel'] ?? '';
            [$platform, $contentType] = array_pad(explode('_', $channel, 2), 2, '');

            if (!$platform || !$contentType) {
                continue;
            }

            \App\Models\BvCampaignKol::create([
                'campaign_id' => $this->record->id,
                'creator_name' => $entry['creator_name'] ?? '',
                'post_url' => $entry['url'] ?? null,
                'price' => (float) str_replace(['.', ','], '', $entry['price'] ?? '0'),
                'platform' => $platform,
                'content_type' => $contentType,
                'status' => 'pending',
            ]);
        }

        // Antrekan scraping metrics untuk setiap KOL yang memiliki URL
        $this->record->kols()
            ->whereNotNull('post_url')
            ->where('post_url', '!=', '')
            ->get()
            ->each(fn($kol) => ScrapeKolMetricsJob::dispatch($kol->id)->onQueue('scraping'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
