<?php

namespace App\Http\Controllers;

use App\Models\FormBrief;
use Illuminate\Http\Request;

class FormBriefPublicController extends Controller
{
    /**
     * Tampilkan form brief public untuk client berdasarkan token.
     */
    public function show(string $token)
    {
        $brief = FormBrief::where('token', $token)->with('client')->firstOrFail();

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
            'criteria_of_kol' => 'nullable|string',
            'sow' => 'nullable|string',
            'budget_main_kol' => 'nullable|string|max:255',
            'budget_macro_kol' => 'nullable|string|max:255',
            'deadline' => 'nullable|string|max:255',
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
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('form-brief.public', $token)
            ->with('success', 'Brief berhasil disubmit! Tim kami akan segera meninjau.');
    }
}
