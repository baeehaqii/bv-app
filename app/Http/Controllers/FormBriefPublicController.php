<?php

namespace App\Http\Controllers;

use App\Models\BvEmploye;
use App\Models\FormBrief;
use Illuminate\Http\Request;

class FormBriefPublicController extends Controller
{
    /**
     * Tampilkan form brief public untuk client berdasarkan token.
     */
    public function show(string $token)
    {
        $brief = FormBrief::where('token', $token)->with('client', 'bvSales.salesList')->firstOrFail();

        $picName = $brief->bvSales?->salesList?->nama_sales
            ?: $brief->bvSales?->pic_media_plan;
        $salesName = $picName ?: 'Tim Beyond Viral';
        $salesWhatsapp = null;

        if ($picName) {
            $employee = BvEmploye::query()
                ->where('nama_lengkap', $picName)
                ->orWhere('nama_lengkap', 'like', '%'.$picName.'%')
                ->first();

            $salesWhatsapp = $employee?->whatsapp;
        }

        $salesWhatsapp = preg_replace('/[^0-9]/', '', (string) $salesWhatsapp);
        if ($salesWhatsapp !== '') {
            if (str_starts_with($salesWhatsapp, '0')) {
                $salesWhatsapp = '62'.substr($salesWhatsapp, 1);
            } elseif (! str_starts_with($salesWhatsapp, '62')) {
                $salesWhatsapp = '62'.$salesWhatsapp;
            }
        }

        $salesWhatsappUrl = $salesWhatsapp !== '' ? 'https://wa.me/'.$salesWhatsapp : null;

        // Jika sudah disubmit, tampilkan halaman thank you
        if ($brief->isSubmitted()) {
            return view('form-brief.submitted', compact('brief'));
        }

        return view('form-brief.public', compact('brief', 'salesName', 'salesWhatsapp', 'salesWhatsappUrl'));
    }

    /**
     * Submit brief dari client portal.
     */
    public function submit(Request $request, string $token)
    {
        $brief = FormBrief::where('token', $token)->firstOrFail();

        // Kalau sudah submitted, jangan bisa submit lagi
        if ($brief->isSubmitted()) {
            return redirect()->route('form-brief.public', $token)
                ->with('error', 'Brief ini sudah disubmit sebelumnya.');
        }

        $validated = $request->validate([
            'submitted_by_name' => 'required|string|max:255',
            'submitted_by_email' => 'required|email|max:255',
            'product' => 'nullable|string|max:255',
            'background' => 'nullable|string',
            'campaign_objective' => 'nullable|string',
            'cta' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'key_messages' => 'nullable|string',
            'request_kol' => 'nullable|string',
            'persona_kol' => 'nullable|string',
            'criteria_of_kol' => 'nullable|string',
            'sow' => 'nullable|string',
            'brief_do' => 'nullable|string',
            'brief_dont' => 'nullable|string',
            'kpi' => 'nullable|string',
            'budget' => 'nullable|numeric|min:0',
            'timeline' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'deadline' => 'nullable|string|max:255',
            'supporting_doc' => 'nullable|url|max:255',
            'additional_notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('form-briefs', 'public');
                $attachments[] = $path;
            }
        }

        $brief->update([
            ...$validated,
            'attachments' => ! empty($attachments) ? $attachments : $brief->attachments,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Brief sudah terisi client → majukan status campaign ke Proposal Building
        $brief->bvSales?->syncBriefStatus();

        return redirect()->route('form-brief.public', $token)
            ->with('success', 'Brief berhasil disubmit! Tim kami akan segera meninjau.');
    }
}
