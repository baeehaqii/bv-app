<?php

namespace App\Http\Controllers;

use App\Models\FormBrief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormBriefPublicController extends Controller
{
    /**
     * Tampilkan form brief public untuk client berdasarkan token.
     */
    public function show(string $token)
    {
        $brief = FormBrief::where('token', $token)->firstOrFail();

        // Jika sudah disubmit, tampilkan halaman thank you
        if ($brief->isSubmitted()) {
            return view('form-brief.submitted', compact('brief'));
        }

        return view('form-brief.public', compact('brief'));
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
            'campaign_objective' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'key_message' => 'nullable|string',
            'mandatory_content' => 'nullable|string',
            'do_and_dont' => 'nullable|string',
            'reference_links' => 'nullable|string',
            'hashtags' => 'nullable|string|max:500',
            'mentions' => 'nullable|string|max:500',
            'content_deadline' => 'nullable|date',
            'posting_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'budget_notes' => 'nullable|string',
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
            'attachments' => !empty($attachments) ? $attachments : $brief->attachments,
            'budget' => $validated['budget'] ?? 0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('form-brief.public', $token)
            ->with('success', 'Brief berhasil disubmit! Tim kami akan segera meninjau.');
    }
}
