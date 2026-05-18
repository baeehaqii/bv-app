<?php

namespace App\Mail;

use App\Models\FormBrief;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormBriefSubmittedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly FormBrief $brief) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Form Brief Diterima — ' . ($this->brief->campaign_name ?: $this->brief->title),
        );
    }

    public function content(): Content
    {
        $brief = $this->brief;

        return new Content(
            view: 'emails.form-brief-submitted',
            with: [
                'pic'          => $brief->pic ?: $brief->submitted_by_name ?: 'Client',
                'campaignName' => $brief->campaign_name ?: $brief->title,
                'brand'        => $brief->brand,
                'budget'       => $brief->budget,
                'deadline'     => $brief->deadline_date?->translatedFormat('d M Y'),
                'submittedAt'  => now()->translatedFormat('d M Y, H:i'),
            ],
        );
    }
}
