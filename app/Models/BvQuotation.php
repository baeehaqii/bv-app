<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BvQuotation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quotation_date' => 'date',
        'expiry_date'    => 'date',
        'is_public'      => 'boolean',
        'signatories'    => 'array',
        'signatures'     => 'array',
    ];

    /**
     * Alur tanda tangan quotation — urutannya wajib: CEO dulu, lalu Business
     * Development, terakhir Client (lewat link public). Key = key di kolom
     * `signatures`, value = label yang tampil di halaman client.
     */
    public const SIGN_FLOW = [
        'ceo' => 'CEO',
        'bd' => 'Business Development',
        'client' => 'Client',
    ];

    /** Sudah ditandatangani pihak $role? */
    public function isSignedBy(string $role): bool
    {
        return filled(data_get($this->signatures, "{$role}.at"));
    }

    /** Pihak berikutnya yang harus tanda tangan (null bila sudah lengkap). */
    public function nextSigner(): ?string
    {
        foreach (array_keys(self::SIGN_FLOW) as $role) {
            if (! $this->isSignedBy($role)) {
                return $role;
            }
        }

        return null;
    }

    /** Urutan dijaga: hanya pihak berikutnya yang boleh tanda tangan. */
    public function canSign(string $role): bool
    {
        return $this->nextSigner() === $role;
    }

    public function isFullySigned(): bool
    {
        return $this->nextSigner() === null;
    }

    /**
     * Simpan tanda tangan satu pihak. $image = path relatif di disk public (opsional).
     * Client yang tanda tangan = quotation disetujui → status accepted.
     */
    public function sign(string $role, string $name, ?string $jobTitle = null, ?string $image = null): void
    {
        if (! isset(self::SIGN_FLOW[$role])) {
            throw new \InvalidArgumentException("Pihak penanda tangan tidak dikenal: {$role}");
        }

        if (! $this->canSign($role)) {
            $next = $this->nextSigner();
            throw new \RuntimeException($next
                ? 'Belum urutannya. Menunggu tanda tangan ' . self::SIGN_FLOW[$next] . '.'
                : 'Quotation sudah ditandatangani semua pihak.');
        }

        $signatures = $this->signatures ?? [];
        $signatures[$role] = [
            'name' => $name,
            'job_title' => $jobTitle,
            'image' => $image,
            'at' => now()->toDateTimeString(),
        ];

        $this->update([
            'signatures' => $signatures,
            'status' => $role === 'client' ? 'accepted' : $this->status,
        ]);
    }

    public function internalBudget(): BelongsTo
    {
        return $this->belongsTo(InternalBudget::class);
    }

    public function mediaPlan(): BelongsTo
    {
        return $this->belongsTo(MediaPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatePublicToken(): string
    {
        $token = Str::random(48);

        $this->update([
            'public_token' => $token,
            'is_public'    => true,
        ]);

        return $token;
    }

    public function revokePublicToken(): void
    {
        $this->update([
            'public_token' => null,
            'is_public'    => false,
        ]);
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return route('quotation.public', ['token' => $this->public_token]);
    }

    /**
     * Ambil semua approved budget items untuk ditampilkan di public view.
     * Fallback ke semua items jika tidak ada yang approved.
     */
    public function getPublicItemsAttribute(): \Illuminate\Support\Collection
    {
        $budget = $this->internalBudget;
        if (!$budget) {
            return collect();
        }

        $approved = $budget->items()
            ->with('mediaPlanKol')
            ->where('status', 'approved')
            ->orderBy('sort_order')
            ->get();

        if ($approved->isNotEmpty()) {
            return $approved;
        }

        return $budget->items()
            ->with('mediaPlanKol')
            ->orderBy('sort_order')
            ->get();
    }
}
