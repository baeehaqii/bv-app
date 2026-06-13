<?php

namespace App\Http\Controllers;

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\CampaignStoryline;
use App\Models\InternalBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Link Approval Konten (internal) — halaman publik untuk client menyetujui /
 * meminta revisi draft konten (campaign_storylines berstatus waiting_approval).
 *
 * Saat client approve sebuah draft → storyline.status = approved dan dipastikan
 * ada baris bv_campaign_kols (brief_status = approved) agar masuk KOL Performance.
 */
class CampaignContentReviewController extends Controller
{
    /**
     * Storyline yang ditampilkan ke client: yang sedang menunggu approval atau
     * sudah pernah diputuskan (approved/revision) — bukan draft mentah.
     */
    protected function reviewableStorylines(BvCampign $campaign)
    {
        return $campaign->storylines()
            ->whereIn('status', ['waiting_approval', 'revision', 'approved'])
            ->orderBy('posting_deadline')
            ->get();
    }

    public function show(string $token)
    {
        $campaign = BvCampign::where('content_review_token', $token)
            ->where('content_review_is_public', true)
            ->with('client')
            ->firstOrFail();

        $storylines = $this->reviewableStorylines($campaign);

        $campaignName = $campaign->campaign_name ?? '-';
        $clientName = $campaign->client?->nama_brand ?? 'Client';
        $alreadySubmitted = $campaign->content_review_submitted_at !== null;

        return view('campaign.content-review', compact(
            'campaign',
            'storylines',
            'campaignName',
            'clientName',
            'alreadySubmitted',
        ));
    }

    public function submit(Request $request, string $token)
    {
        $campaign = BvCampign::where('content_review_token', $token)
            ->where('content_review_is_public', true)
            ->firstOrFail();

        $data = Validator::make($request->all(), [
            'choices'    => ['array'],
            'choices.*'  => ['nullable', 'in:approved,revision'],
            'feedback'   => ['array'],
            'feedback.*' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $choices  = $data['choices'] ?? [];
        $feedback = $data['feedback'] ?? [];

        $storylines = $this->reviewableStorylines($campaign);

        foreach ($storylines as $storyline) {
            $choice = $choices[$storyline->id] ?? null;
            $fb     = $feedback[$storyline->id] ?? null;

            if ($choice === null) {
                continue;
            }

            $storyline->update([
                'client_choice'   => $choice,
                'client_feedback' => $fb,
                // approve → status approved; revision → status revision (PIC perbaiki lagi)
                'status'          => $choice === 'approved' ? 'approved' : 'revision',
            ]);

            if ($choice === 'approved') {
                $this->ensurePerformanceKol($campaign, $storyline);
            }
        }

        $campaign->forceFill(['content_review_submitted_at' => now()])->saveQuietly();

        return redirect()
            ->route('campaign-internal.content-review', ['token' => $token])
            ->with('submitted', true);
    }

    /**
     * Pastikan ada baris bv_campaign_kols (approved) untuk KOL pada storyline ini,
     * sehingga muncul di tab KOL Performance.
     */
    protected function ensurePerformanceKol(BvCampign $campaign, CampaignStoryline $storyline): void
    {
        ['platform' => $platform, 'content_type' => $contentType] =
            InternalBudget::parseScopeItemToChannel($storyline->sow ?? '');

        $kol = $campaign->kols()
            ->where('creator_name', $storyline->kol_name)
            ->where('content_type', $contentType)
            ->first();

        if ($kol) {
            $kol->update(['brief_status' => 'approved', 'status' => 'posted']);
            return;
        }

        BvCampaignKol::create([
            'campaign_id'  => $campaign->id,
            'creator_name' => $storyline->kol_name,
            'platform'     => $platform ?: ($storyline->platform ?? 'instagram'),
            'content_type' => $contentType,
            'brief_status' => 'approved',
            'status'       => 'posted',
            'posted_at'    => now(),
        ]);
    }
}
