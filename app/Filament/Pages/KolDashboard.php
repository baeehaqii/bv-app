<?php

namespace App\Filament\Pages;

use App\Models\BvCampign;
use App\Models\BvSalesList;
use App\Models\MediaPlan;
use Carbon\Carbon;
use Filament\Pages\Page;

class KolDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Dashboard KOL';
    protected static ?string $title = 'KOL & Creative Dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'Campaign';
    protected static ?int $navigationSort = 0;
    protected static ?string $slug = 'kol-dashboard';
    protected string $view = 'filament.pages.kol-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole([
            'Operation KOL & Creative',
            'operation_kol',
            'kol_specialist',
            'creative',
        ]);
    }

    public function getDisplayName(): string
    {
        $salesList = BvSalesList::where('user_id', auth()->id())->first();
        return $salesList?->nama_sales ?? auth()->user()->name;
    }

    public function getCurrentDateLabel(): string
    {
        return Carbon::now()->translatedFormat('l, d M Y');
    }

    /**
     * Ambil BvSalesList ID untuk user yang login (dipakai di pic_project_internal_ids)
     */
    private function getSalesListId(): ?int
    {
        return BvSalesList::where('user_id', auth()->id())->value('id');
    }

    /**
     * Media Plans dimana user ini adalah PIC Project Internal (KOL Specialist)
     */
    private function myMediaPlans()
    {
        $salesListId = $this->getSalesListId();
        if (!$salesListId) {
            return MediaPlan::whereRaw('0=1'); // empty query
        }

        return MediaPlan::whereJsonContains('pic_project_internal_ids', $salesListId);
    }

    /**
     * Campaign Ongoing (BvCampign) dimana user ini adalah PIC AM — via mediaPlan
     */
    private function myOngoingCampaigns()
    {
        $salesListId = $this->getSalesListId();
        if (!$salesListId) {
            return BvCampign::whereRaw('0=1');
        }

        return BvCampign::whereHas('mediaPlan', function ($q) use ($salesListId) {
            $q->whereJsonContains('pic_project_internal_ids', $salesListId)
              ->orWhere('pic_am_id', $salesListId);
        });
    }

    public function getQuickStats(): array
    {
        $salesListId = $this->getSalesListId();

        if (!$salesListId) {
            return [
                ['label' => 'Campaign Assigned', 'value' => 0, 'color' => 'primary',  'icon' => 'heroicon-m-megaphone'],
                ['label' => 'Sedang Berjalan',   'value' => 0, 'color' => 'success',  'icon' => 'heroicon-m-play-circle'],
                ['label' => 'Selesai Bulan Ini', 'value' => 0, 'color' => 'warning',  'icon' => 'heroicon-m-check-circle'],
                ['label' => 'Media Plan Aktif',  'value' => 0, 'color' => 'primary',  'icon' => 'heroicon-m-document-text'],
            ];
        }

        $totalAssigned  = $this->myOngoingCampaigns()->count();
        $ongoing        = $this->myOngoingCampaigns()->where('status', 'ongoing')->count();
        $doneThisMonth  = $this->myOngoingCampaigns()
            ->where('status', 'completed')
            ->whereMonth('end_date', now()->month)
            ->whereYear('end_date', now()->year)
            ->count();
        $activeMediaPlan = $this->myMediaPlans()
            ->whereIn('status', ['Planning', 'To Client', 'Ongoing'])
            ->count();

        return [
            ['label' => 'Campaign Assigned', 'value' => $totalAssigned,  'color' => 'primary', 'icon' => 'heroicon-m-megaphone'],
            ['label' => 'Sedang Berjalan',   'value' => $ongoing,        'color' => 'success', 'icon' => 'heroicon-m-play-circle'],
            ['label' => 'Selesai Bulan Ini', 'value' => $doneThisMonth,  'color' => 'warning', 'icon' => 'heroicon-m-check-circle'],
            ['label' => 'Media Plan Aktif',  'value' => $activeMediaPlan,'color' => 'primary', 'icon' => 'heroicon-m-document-text'],
        ];
    }

    /**
     * Daftar Campaign Ongoing yang di-assign ke user ini
     */
    public function getAssignedCampaigns(): array
    {
        return $this->myOngoingCampaigns()
            ->with('client')
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw("FIELD(status, 'ongoing','upcoming','completed','draft')")
            ->limit(8)
            ->get()
            ->map(function (BvCampign $campaign) {
                $kolCount    = $campaign->kols()->count();
                $kolPosted   = $campaign->kols()->where('status', 'posted')->count();
                $progress    = $campaign->progress;

                $statusColors = [
                    'draft'     => ['bg' => '#f3f4f6', 'text' => '#374151'],
                    'upcoming'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                    'ongoing'   => ['bg' => '#dcfce7', 'text' => '#14532d'],
                    'completed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                    'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                ];
                [$bg, $text] = $statusColors[$campaign->status] ?? ['#f3f4f6', '#374151'];

                return [
                    'id'           => $campaign->id,
                    'name'         => $campaign->campaign_name,
                    'client'       => $campaign->client?->nama_brand ?? '—',
                    'status'       => $campaign->status,
                    'status_label' => ucfirst($campaign->status),
                    'status_bg'    => $bg,
                    'status_text'  => $text,
                    'start_date'   => $campaign->start_date?->translatedFormat('d M Y'),
                    'end_date'     => $campaign->end_date?->translatedFormat('d M Y'),
                    'kol_count'    => $kolCount,
                    'kol_posted'   => $kolPosted,
                    'progress'     => $progress,
                    'platforms'    => $campaign->media_platforms ?? [],
                ];
            })
            ->toArray();
    }

    /**
     * Daftar Media Plan Internal yang di-assign ke user ini
     */
    public function getAssignedMediaPlans(): array
    {
        return $this->myMediaPlans()
            ->whereIn('status', ['Planning', 'To Client', 'Ongoing'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(function (MediaPlan $plan) {
                $selectedKols = $plan->selectedKols()->count();
                $totalKols    = $plan->kols()->count();

                $statusColors = [
                    'Planning'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                    'To Client' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                    'Ongoing'   => ['bg' => '#dcfce7', 'text' => '#14532d'],
                    'Done'      => ['bg' => '#d1fae5', 'text' => '#065f46'],
                ];
                [$bg, $text] = $statusColors[$plan->status] ?? ['#f3f4f6', '#374151'];

                return [
                    'id'            => $plan->id,
                    'brand'         => $plan->brand ?? '—',
                    'status'        => $plan->status,
                    'status_bg'     => $bg,
                    'status_text'   => $text,
                    'period_start'  => $plan->campaign_period_start
                                        ? Carbon::parse($plan->campaign_period_start)->translatedFormat('d M Y')
                                        : null,
                    'period_end'    => $plan->campaign_period_end
                                        ? Carbon::parse($plan->campaign_period_end)->translatedFormat('d M Y')
                                        : null,
                    'total_kols'    => $totalKols,
                    'selected_kols' => $selectedKols,
                ];
            })
            ->toArray();
    }

    /**
     * Aktivitas terbaru — campaign yang baru di-update
     */
    public function getRecentActivity(): array
    {
        return $this->myOngoingCampaigns()
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get(['id', 'campaign_name', 'status', 'updated_at'])
            ->map(fn(BvCampign $c) => [
                'name'    => $c->campaign_name,
                'status'  => ucfirst($c->status),
                'time'    => $c->updated_at?->diffForHumans(),
                'is_new'  => $c->updated_at && $c->updated_at->gt(now()->subDay()),
            ])
            ->toArray();
    }
}
