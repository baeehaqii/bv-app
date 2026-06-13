# Rencana Implementasi — Alur Media Plan External → Campaign On Going Internal

Tanggal: 2026-06-12
Status keputusan (dari user):
- Status: map lama→baru, **pertahankan opsi Rejected**.
- Link Review Client: client isi ✓/✗/feedback per item (disimpan), lalu **BV manual** ubah status → "Approve Client".
- Campaign On Going Internal: **model & tabel baru terpisah**.
- Pengerjaan: **bertahap** — Part A + Part B dulu (jalan & terverifikasi), Part C menyusul.

---

## Konsep alur (4 status linear + Rejected)

```
Draft  →  Review ke Client  →  Approve Client  →  Approve AM
  (default)      │                    │                 │
                 │                    │                 └─→ auto-create "Campaign On Going Internal"
                 │                    └─→ tombol "Generate Quotation" muncul
                 └─→ tombol "Link Review Client" muncul (public link utk client)
  (Rejected = jalur penolakan, tetap tersedia)
```

Makna status:
- **Draft**: BV masih menyusun Budget Items (hasil sync dari Media Plan Internal).
- **Review ke Client**: budget item sudah disesuaikan BV, dikirim ke client lewat Link Review Client.
- **Approve Client**: BV sudah memfinalisasi hasil feedback client (manual). Tombol Generate Quotation muncul.
- **Approve AM**: Account Manager menyetujui → auto-create Campaign On Going Internal.
- **Rejected**: ditolak (dengan rejection_notes).

---

## PART A — Redesign status di Media Plan External (InternalBudget)

### A1. Migrasi DB
File: `database/migrations/xxxx_update_internal_budget_status_options.php`
- Ubah enum/string `internal_budgets.status` agar memuat: `draft`, `review_client`, `approve_client`, `approve_am`, `rejected`.
  (Catatan: kolom saat ini `enum`. Aman-nya ubah jadi `string` agar fleksibel, atau redefinisi enum. Pilih `string` + default `draft`.)
- Data lama: `pending` → `review_client`, `approved` → `approve_am`. `draft`/`rejected` tetap.

### A2. Model `InternalBudget`
- Update `STATUS_OPTIONS` ke 5 status baru.
- Sesuaikan `approve()` / `booted()` updated-hook: trigger aktivasi sekarang pada `approve_am` (bukan `approved`).
- Tambah konstanta label.

### A3. Form `InternalBudgetForm`
- Update opsi Select status → 5 status baru.
- `afterStateUpdated`: pindahkan logika campaign-live/sync ke saat `approve_am`.

### A4. Header actions `EditInternalBudget`
- `generate_quotation`: `visible` hanya saat `status === 'approve_client'` (atau lebih, sesuai bisnis) DAN belum ada quotation.
- Tambah action **Link Review Client** (Part B).

> Catatan penting: ada auto-approve budget di `approve_item` action (form) yang sekarang memanggil `$budget->approve()` + auto-generate quotation saat semua item approved. Perlu disesuaikan agar tidak bentrok dengan alur status baru — akan ditinjau saat implementasi (kemungkinan: auto-approve item tetap, tapi tidak otomatis lompat status budget).

---

## PART B — Link Review Client (public, token-based)

Mengikuti pola existing: `QuotationPublicController` + route `quotation.public` + blade `quotation/public.blade.php` + kolom `public_token`/`is_public` di model.

### B1. Migrasi DB
- Tambah ke `internal_budgets`: `review_token` (string 64, nullable, unique), `review_is_public` (bool default false), `review_submitted_at` (timestamp nullable).
- Tambah ke `internal_budget_items`: `client_choice` (string nullable: `approved`|`rejected`|null), `client_feedback` (text nullable).
  (Dipisah dari `status`/`rejection_notes`/`nego_notes` internal supaya keputusan client tidak menimpa keputusan internal BV.)

### B2. Model `InternalBudget`
- `generateReviewToken()` / `revokeReviewToken()` / `getReviewUrlAttribute()` → route `media-plan-external.review`.

### B3. Controller `InternalBudgetReviewController`
- `show($token)`: tampilkan budget items + form ✓/✗/feedback.
- `submit($token, Request)`: simpan `client_choice` + `client_feedback` per item, set `review_submitted_at = now()`. **Tidak** mengubah status budget (BV finalisasi manual).

### B4. Routes (`routes/web.php`, tanpa auth)
- `GET /media-plan-review/{token}` → `review.show` (name: `media-plan-external.review`)
- `POST /media-plan-review/{token}` → `review.submit`

### B5. Blade `resources/views/media-plan/review.blade.php`
- Tabel Budget Items (KOL, Scope Item, Client Price) + kolom aksi: radio/toggle ✓ Pakai / ✗ Tidak + textarea feedback per baris.
- Tombol Submit. Setelah submit → halaman thank-you / read-only.

### B6. Header action `EditInternalBudget`
- Action **Link Review Client**: `visible` saat `status === 'review_client'`. Generate token bila belum ada, lalu copy/redirect URL. (Pola sama seperti quotation share.)

### B7. Deskripsi section dinamis (`InternalBudgetForm`)
- `Section::make('💰 Budget Items')->description(...)`: ubah jadi closure yang membaca `$record->review_submitted_at`:
  - belum submit → teks lama ("Items otomatis dari Media Plan Internal...").
  - sudah submit → "✅ Telah disubmit oleh client pada {tanggal}. Lihat kolom feedback client di tiap item."
- Tampilkan `client_choice` + `client_feedback` (read-only) di repeater item agar BV lihat keputusan client sebelum finalisasi.

---

## PART C — Campaign On Going Internal (REVISI: reuse BvCampign)

Keputusan user (revisi setelah pemetaan kode): **REUSE** `BvCampign` existing, bukan model baru.
Alasan: `bv_campaigns.campaign_type` sudah ada; `campaign_storylines` = content planning
(draft/waiting_approval/revision/approved/posted); `bv_campaign_kols` sudah punya SEMUA field
screenshot 4 (username, tier, visit_date, content_drive_link, feedback, revision_link, feedback_2,
posting_date, post_url) + `brief_status` gerbang KOL Performance. `approve_am` sudah auto-sync KOL.

Konvensi: **campaign internal = `campaign_type = 'internal'`**.

### C1. Migrasi DB
- `bv_campaigns`: tambah `content_review_token` (string 64 nullable unique), `content_review_is_public` (bool),
  `content_review_submitted_at` (timestamp nullable). (Token TERPISAH dari public_token external performance.)
- `campaign_storylines`: tambah `client_choice` (string nullable: approved|revision), `client_feedback` (text nullable).

### C2. Wiring approve_am
- Di `InternalBudget::syncCampaignKolsFromApprovedBudget()` (dan fallback create di `booted()`),
  set `campaign_type = 'internal'` pada BvCampign yang dipakai untuk media plan internal.

### C3. Exclude internal dari Campaign Ongoing External
- `CampaignExternalsTable`: tambah `->where('campaign_type', '!=', 'internal')` agar internal tidak bocor.

### C4. Resource "Campaign Ongoing Internal" (REUSE yang SUDAH ADA)
TEMUAN: resource `BvCampignResource` (nav 'Campaign Ongoing Internal', slug `campaign-ongoing-internal`)
SUDAH ADA & sudah punya Content Planning (StorylinesRelationManager editable, status waiting_approval),
KolBrief (gerbang), Kols + halaman KolPerformance. Membuat resource baru → BENTROK slug.
KEPUTUSAN user: hapus resource duplikat, ENHANCE resource lama:
- `EditBvCampign`: tambah header action "Buat Link Approval Konten" (+ revoke) — tampil saat ada storyline `waiting_approval`.
- `StorylinesRelationManager`: tambah kolom "Pilihan Client" + "Feedback Client", dan action "Kirim ke Client" (set waiting_approval).

### C5. Public approval link konten (pola Part B)
- Controller `CampaignContentReviewController` (show/submit), route `campaign-internal.content-review(.submit)`, blade
  `resources/views/campaign/content-review.blade.php`.
- Tampilkan storylines status `waiting_approval` (atau semua non-draft): KOL, platform, SOW, angle, caption draft,
  key message, deadline + aksi ✓ Approve / ↻ Revisi + feedback per draft.
- Submit: simpan `client_choice`/`client_feedback` per storyline, set `content_review_submitted_at`.
  - choice `approved` → storyline.status = `approved`, dan PASTIKAN ada `bv_campaign_kols` (brief_status flow)
    untuk KOL itu agar masuk KOL Performance.
  - choice `revision` → storyline.status = `revision`.
- Submit TIDAK mengubah status campaign secara paksa (PIC finalisasi).

---

## Verifikasi tiap langkah
- Setelah migrasi: `php artisan migrate` di local, cek kolom.
- Setelah tiap perubahan UI: load halaman Edit Media Plan External, pastikan tidak ada error & layout Filament tidak rusak (sesuai feedback: jangan rusak layout).
- Test Link Review: buka URL token sebagai "client", submit, cek data tersimpan & deskripsi berubah.
