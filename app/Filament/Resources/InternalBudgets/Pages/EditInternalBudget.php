<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInternalBudget extends EditRecord
{
    protected static string $resource = InternalBudgetResource::class;

    /**
     * Daftar Budget Items: satu baris per KOL, dengan drill-down ke SOW-nya.
     *
     * Satu media plan bisa punya 590 item milik 98 KOL. Dua alasan barisnya
     * diringkas per KOL: Filament Repeater membangun komponen seluruh baris
     * sekaligus (590 baris = kehabisan memori), dan membaca 7 baris berturut-turut
     * dengan nama KOL yang sama itu tidak ada gunanya. Baris KOL menampilkan SOW
     * pertama + sisanya sebagai "+6"; tombol Detail SOW membuka rinciannya.
     *
     * Yang dimuat ke form tetap satu halaman saja — sekarang halaman KOL, bukan
     * halaman item, jadi 20 halaman alih-alih 118.
     */
    public const ITEM_PER_PAGE = [5, 10, 15];

    public int $itemPage = 1;

    public int $itemPerPage = 5;

    /** KOL yang sedang dibuka rincian SOW-nya; null = daftar ringkas per KOL. */
    public ?int $kolFokus = null;

    /**
     * Repeater items tidak lagi ber-relationship(): isinya bentukan sendiri
     * (baris ringkas per KOL) dan tidak selalu satu-lawan-satu dengan record.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->barisItemUntukForm();

        return $data;
    }

    public function muatUlangItems(): void
    {
        // Status item bisa saja baru berubah; peringatan "belum masuk Campaign
        // Ongoing" harus ikut angka terbaru, bukan hitungan awal request.
        \App\Filament\Resources\InternalBudgets\Schemas\InternalBudgetForm::lupakanTertahan();

        $this->data['items'] = $this->barisItemUntukForm();
    }

    public function bukaDetailKol(int $kolId): void
    {
        $this->kolFokus = $kolId;
        $this->muatUlangItems();
    }

    public function tutupDetailKol(): void
    {
        $this->kolFokus = null;
        $this->muatUlangItems();
    }

    public function namaKolFokus(): string
    {
        return \App\Models\MediaPlanKol::find($this->kolFokus)?->name ?? 'KOL ini';
    }

    public function totalItem(): int
    {
        return $this->urutanKol()->count();
    }

    public function totalHalamanItem(): int
    {
        return max(1, (int) ceil($this->totalItem() / $this->itemPerPage));
    }

    public function gantiHalamanItem(int $halaman): void
    {
        $this->itemPage = max(1, min($halaman, $this->totalHalamanItem()));
        $this->muatUlangItems();
    }

    public function aturItemPerPage(int $jumlah): void
    {
        $this->itemPerPage = in_array($jumlah, self::ITEM_PER_PAGE, true) ? $jumlah : self::ITEM_PER_PAGE[0];
        $this->itemPage = 1;
        $this->muatUlangItems();
    }

    /**
     * Urutan KOL di daftar, mengikuti urutan item pertamanya.
     *
     * Bisa memuat null: item yang belum terhubung ke KOL mana pun tetap harus
     * kelihatan, dikelompokkan jadi satu baris tersendiri.
     *
     * @return \Illuminate\Support\Collection<int, int|null>
     */
    private function urutanKol(): \Illuminate\Support\Collection
    {
        return $this->record->items()
            // reorder() wajib: relasi items() sudah membawa orderBy('sort_order'),
            // dan kolom itu tidak ada di GROUP BY — MySQL only_full_group_by
            // menolaknya (error 1055). SQLite membiarkannya, jadi test saja tidak
            // cukup untuk menangkap ini.
            ->reorder()
            ->selectRaw('media_plan_kol_id, MIN(sort_order) as urut, MIN(id) as urut_id')
            ->groupBy('media_plan_kol_id')
            ->orderBy('urut')
            ->orderBy('urut_id')
            ->pluck('media_plan_kol_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function barisItemUntukForm(): array
    {
        // Mode rincian: satu KOL, semua SOW-nya. Jumlahnya sedikit (paling
        // banyak belasan), jadi tidak perlu dipaginasi lagi.
        if ($this->kolFokus !== null) {
            return $this->record->items()
                ->with('mediaPlanKol')
                ->where('media_plan_kol_id', $this->kolFokus)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn($item) => $this->barisSow($item))
                ->all();
        }

        $idHalaman = $this->urutanKol()->forPage($this->itemPage, $this->itemPerPage)->values();
        $adaTanpaKol = $idHalaman->contains(null);
        $ids = $idHalaman->filter()->all();

        $items = $this->record->items()
            ->with('mediaPlanKol')
            ->where(function ($query) use ($ids, $adaTanpaKol) {
                $query->whereIn('media_plan_kol_id', $ids);

                if ($adaTanpaKol) {
                    $query->orWhereNull('media_plan_kol_id');
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($item) => (string) $item->media_plan_kol_id);

        // Dipetakan lewat $idHalaman supaya urutan barisnya ikut urutan KOL di
        // halaman, bukan urutan kunci hasil groupBy.
        return $idHalaman
            ->map(fn($id) => $items->get((string) $id))
            ->filter()
            ->map(fn($grup) => $this->barisKol($grup))
            ->values()
            ->all();
    }

    /**
     * Satu baris = satu SOW (mode rincian). Bentuknya sama dengan baris KOL
     * supaya kolom repeater-nya tidak perlu berubah antar mode.
     *
     * @return array<string, mixed>
     */
    private function barisSow(\App\Models\InternalBudgetItem $item): array
    {
        return [
            // id terisi = baris ini record sungguhan; dipakai aksi yang memang
            // hanya masuk akal per SOW (Ganti KOL).
            'id' => $item->id,
            // Sasaran Approve / Reject / Nego. Di mode rincian isinya satu SOW;
            // di baris ringkas seluruh SOW milik KOL itu — approval diputuskan
            // per KOL, sama seperti di Link Review Client.
            'item_ids' => [$item->id],
            'media_plan_kol_id' => $item->media_plan_kol_id,
            'kol_status' => $item->mediaPlanKol?->status,
            'scope_item' => $item->scope_item,
            'qty' => $item->qty,
            'rate_base' => $item->rate_base,
            'master_pph_id' => $item->master_pph_id,
            'mu_pph' => $item->mu_pph,
            'published_rate' => $item->published_rate,
            'rounded' => $item->rounded,
            'actual_margin_percent' => $item->actual_margin_percent,
            'notes' => $item->notes,
            'status' => $item->status ?: 'pending',
            'rejection_notes' => $item->rejection_notes,
            'nego_notes' => $item->nego_notes,
            'client_choice' => $item->client_choice,
            'client_feedback' => $item->client_feedback,
            'jumlah_sow' => 1,
        ];
    }

    /**
     * Satu baris = satu KOL, angka-angkanya dijumlah dari seluruh SOW miliknya.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\InternalBudgetItem>  $items
     * @return array<string, mixed>
     */
    private function barisKol(\Illuminate\Support\Collection $items): array
    {
        $pertama = $items->first();

        $muPph = (float) $items->sum('mu_pph');
        $rounded = (float) $items->sum('rounded');

        return [
            // Sengaja null: baris ini gabungan, bukan satu record — aksi yang
            // hanya masuk akal per SOW menyembunyikan diri kalau id kosong.
            'id' => null,
            'item_ids' => $items->pluck('id')->all(),
            'media_plan_kol_id' => $pertama->media_plan_kol_id,
            'kol_status' => $pertama->mediaPlanKol?->status,
            'scope_item' => $pertama->scope_item,
            'qty' => $items->sum('qty'),
            'rate_base' => $items->sum('rate_base'),
            'master_pph_id' => $pertama->master_pph_id,
            'mu_pph' => $muPph,
            'published_rate' => $items->sum('published_rate'),
            'rounded' => $rounded,
            // Dihitung ulang dari total, bukan rata-rata persen per item —
            // rata-rata persen salah kalau nominal antar SOW timpang.
            'actual_margin_percent' => $rounded > 0 ? round(($rounded - $muPph) / $rounded * 100, 2) : 0,
            'notes' => $items->pluck('notes')->filter()->implode(' • '),
            'status' => self::ringkas($items->pluck('status'), 'pending'),
            'rejection_notes' => null,
            'nego_notes' => $items->pluck('nego_notes')->filter()->implode(' • '),
            'client_choice' => self::ringkas($items->pluck('client_choice'), ''),
            'client_feedback' => $items->pluck('client_feedback')->filter()->implode(' • '),
            'jumlah_sow' => $items->count(),
        ];
    }

    /**
     * Nilai gabungan beberapa SOW: seragam → nilainya, beda-beda → "campuran".
     *
     * @param  \Illuminate\Support\Collection<int, string|null>  $nilai
     */
    private static function ringkas(\Illuminate\Support\Collection $nilai, string $bawaan): string
    {
        $unik = $nilai->map(fn($v) => filled($v) ? $v : $bawaan)->unique();

        return $unik->count() === 1 ? (string) $unik->first() : 'campuran';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_quotation')
                ->label('Generate Quotation')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                // Hanya muncul setelah client approve (Approve Client / Approve AM) & belum ada quotation
                ->visible(fn($record) => $record->quotation === null
                    && in_array($record->status, \App\Models\InternalBudget::STATUS_FINAL, true))
                ->requiresConfirmation()
                ->modalHeading('Generate Quotation')
                ->modalDescription('Generate quotation baru dari data budget ini. Quotation akan dibuat dengan status Draft.')
                ->modalSubmitActionLabel('Generate')
                ->action(function ($record) {
                    if ($record->total_rounded <= 0) {
                        Notification::make()
                            ->title('Tidak Dapat Generate Quotation')
                            ->body('Total budget masih 0. Pastikan budget items sudah diisi.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $quotation = $record->generateQuotation();

                    Notification::make()
                        ->title('Quotation Berhasil Dibuat')
                        ->body("Quotation #{$quotation->quotation_number} berhasil di-generate.")
                        ->success()
                        ->send();

                    return redirect()->route(
                        'filament.office.resources.quotation.edit',
                        ['record' => $quotation->id]
                    );
                }),

            // SPK = kontrak ke KOL (uang keluar), lawan dari Quotation/Invoice (uang masuk).
            // Baru muncul setelah client approve supaya BV tidak terikat ke KOL sebelum
            // harga ke client deal. Idempoten: klik ulang hanya membuat SPK yang belum ada.
            Actions\Action::make('create_spk')
                ->label('Terbitkan SPK')
                ->icon('heroicon-m-document-check')
                ->color('warning')
                ->visible(fn($record) => in_array($record->status, \App\Models\InternalBudget::STATUS_FINAL, true)
                    && $record->items()->where('status', 'approved')->exists())
                ->requiresConfirmation()
                ->modalHeading('Terbitkan SPK ke KOL')
                ->modalDescription(function ($record) {
                    $approved = $record->items()->where('status', 'approved')
                        ->whereNotNull('media_plan_kol_id')
                        ->distinct()->count('media_plan_kol_id');
                    $sudah = \App\Models\BvSPK::where('internal_budget_id', $record->id)->count();

                    return "KOL approved: {$approved}. SPK sudah terbit: {$sudah}. "
                        . 'SPK dibuat satu per KOL (semua SOW-nya digabung) dengan status Draft. '
                        . 'NIK & rekening diambil dari Data KOL — lengkapi di sana agar tidak kosong.';
                })
                ->modalSubmitActionLabel('Terbitkan')
                ->action(function ($record) {
                    $created = \App\Models\BvSPK::createFromBudget($record);

                    if ($created->isEmpty()) {
                        Notification::make()
                            ->title('Tidak Ada SPK Baru')
                            ->body('Semua KOL approved sudah punya SPK.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $kosong = $created->filter(fn($spk) => blank($spk->pihak_kedua_nik)
                        || blank($spk->nomor_rekening))->count();

                    Notification::make()
                        ->title($created->count() . ' SPK Berhasil Dibuat')
                        ->body($kosong > 0
                            ? "{$kosong} SPK masih kosong NIK/rekening — lengkapi Data KOL atau isi manual di SPK."
                            : 'Cek di menu Campaign Area → Contract.')
                        ->success()
                        ->send();

                    if ($created->count() === 1) {
                        return redirect()->route(
                            'filament.office.resources.spk.edit',
                            ['record' => $created->first()->id]
                        );
                    }

                    return redirect()->route('filament.office.resources.spk.index');
                }),

            Actions\Action::make('view_quotation')
                ->label('View Quotation')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->url(fn($record) => $record->quotation
                    ? route('filament.office.resources.quotation.edit', ['record' => $record->quotation->id])
                    : null)
                ->visible(fn($record) => $record->quotation !== null),

            Actions\Action::make('sync_from_media_plan')
                ->label('Sync from Media Plan')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn($record) => $record->mediaPlan !== null && $record->status !== 'approved')
                ->requiresConfirmation()
                ->modalHeading('Sync Budget Items dari Media Plan Internal')
                ->modalDescription('Tombol ini menarik ulang data KOL dan scope item (SOW) dari Media Plan Internal ke halaman ini. Berguna ketika ada perubahan KOL atau scope di Media Plan Internal setelah budget external sudah dibuat. Semua items saat ini akan dihapus dan diganti data terbaru. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Sync Sekarang')
                ->action(function ($record) {
                    $record->mediaPlan->syncInternalBudgetItems();

                    Notification::make()
                        ->title('Budget Items Diperbarui')
                        ->body('Items berhasil disinkronkan dari Media Plan Internal.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['items']);
                }),

            Actions\Action::make('link_review_client')
                ->label('Link Review Client')
                ->icon('heroicon-m-link')
                ->color('info')
                // Muncul saat status "Review ke Client"
                ->visible(fn($record) => $record->status === 'review_client')
                ->modalHeading('Link Review Client')
                ->modalDescription('Bagikan tautan berikut ke client. Client dapat menandai SOW mana yang dipakai (✓ / ✗) dan memberi feedback per item.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->fillForm(function ($record) {
                    $record->generateReviewToken();

                    return ['review_url' => $record->review_url];
                })
                ->form([
                    \Filament\Forms\Components\TextInput::make('review_url')
                        ->label('Tautan Review Client')
                        ->readOnly()
                        ->suffixAction(
                            Actions\Action::make('open')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->label('Buka')
                                ->url(fn($record) => $record->review_url, shouldOpenInNewTab: true)
                        )
                        ->helperText('Salin tautan ini dan kirim ke client.'),
                ]),

            Actions\Action::make('view_media_plan')
                ->label('View Media Plan Internal')
                ->icon('heroicon-m-eye')
                ->color('gray')
                ->url(
                    fn($record) => $record->mediaPlan
                    ? route('filament.office.resources.media-plan-internal.edit', ['record' => $record->mediaPlan->id])
                    : null
                )
                ->visible(fn($record) => $record->mediaPlan !== null),
        ];
    }
}
