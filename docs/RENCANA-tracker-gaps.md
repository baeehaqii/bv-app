# Rencana Implementasi — Menutup Gap "Tracker" di Campaign Ongoing Internal

Tanggal: 2026-06-13
Acuan data nyata: `[EXT] Masters of the Universe - Sony Pictures - KOL List - BV Network.xlsx`, sheet **Tracker**.
Status keputusan (dari user):
- Revisi bertingkat: **tabel revisi terpisah & dinamis** (bukan kolom fixed). Tak terbatas jumlah ronde.
- Pengerjaan: **dokumen rencana dulu** (file ini), implementasi menyusul setelah di-review.

---

## Latar belakang — hasil verifikasi

Modul **Media Plan Internal** (`media_plan_kols`) dan **Media Plan External**
(`internal_budget_items`) sudah menampung seluruh kolom sheet MacroMicro & Approval — **tidak ada gap**.

Modul **Campaign Ongoing Internal** (`bv_campaigns` + `campaign_storylines` + `bv_campaign_kols`)
**belum** menampung sebagian kolom sheet Tracker. Gap yang dikonfirmasi:

| # | Gap | Bukti di Tracker | Kondisi sistem sekarang |
|---|-----|------------------|--------------------------|
| 1 | Revisi konten **lebih dari 2 ronde** | `Draft Revisi 1/2/3` + `Final Revisi`, tiap ronde ada `Feedback Client` | Hanya 2 ronde fixed: `bv_campaign_kols.feedback` / `revision_link` / `feedback_2` |
| 2 | **Revisi tahap storyline** punya draft + feedback sendiri | `Draft Storyline → Feedback → Draft Revisi Storyline` | Hanya status `revision` di `campaign_storylines`, tak ada link/draft revisinya |
| 3 | **Caption final + feedback caption** | kolom `Caption` lalu `Feedback Client` | Hanya `campaign_storylines.caption_draft` (draft), tak ada caption final + feedback |
| 4 | Status **"KOL Cancel"** | baris `kadin5s` = `KOL Cancel` | `bv_campaign_kols.status` / `brief_status` tak punya nilai cancel |
| 5 | **Event Attendance** sebagai item SOW terpisah | kolom boolean `Event Attendance` (TRUE / –) | Ada `visit_date` + `visit_status` (tanggal+status), bukan boolean kehadiran event |

---

## Desain solusi

### Inti: tabel revisi dinamis `campaign_kol_revisions`

Satu baris = satu **ronde revisi** pada satu **tahap** (storyline / video / caption) untuk satu KOL.
Menggantikan kolom fixed `feedback` / `revision_link` / `feedback_2`. Anchor ke `bv_campaign_kols`
(entitas eksekusi/tracker per-KOL), karena Tracker = 1 baris per KOL melintasi semua tahap.

```
campaign_kol_revisions
  id
  bv_campaign_kol_id   FK → bv_campaign_kols (cascadeOnDelete)
  stage                string  : 'storyline' | 'video' | 'caption'
  round                unsigned int : 1, 2, 3, ...  (urutan revisi dalam stage)
  asset_link           string  nullable  : link Google Docs/Drive draft revisi
  asset_text           text    nullable  : isi storyline/caption bila disimpan sbg teks
  client_feedback      text    nullable  : feedback client utk ronde ini
  is_final             boolean default false : tandai "Final Revisi" (versi terkunci)
  status               string  default 'waiting_review' : waiting_review | approved | revision
  submitted_at         timestamp nullable
  timestamps
  index (bv_campaign_kol_id, stage, round)
```

Pemetaan kolom Tracker → tabel:
- `Draft Storyline` → row (stage=storyline, round=1)
- `Draft Revisi Storyline` → row (stage=storyline, round=2)
- `Draft Video` → row (stage=video, round=1)
- `Draft Revisi 1/2/3` → row (stage=video, round=2/3/4)
- `Final Revisi` → row (stage=video, is_final=true)
- `Caption` + `Feedback Client` → row (stage=caption, round=1)
- tiap `Feedback Client` → `client_feedback` di row ronde sebelumnya

### Gap 4 — Status "Cancel"
Tambah nilai `canceled` ke daftar status `bv_campaign_kols.status` (dan/atau `brief_status`).
Pemetaan istilah Tracker → sistem:
- `KOL Done Posting` → `status = posted` (atau `completed`)
- `Waiting KOL Posting` → `brief_status = approved`, `status = pending`
- `KOL Cancel` → `status = canceled` (baru)

> Kolom `status` sudah `string`, jadi tinggal tambah opsi di Select/badge di Filament. Tak perlu migrasi
> kalau hanya menambah nilai; cukup update `BvCampign`/form. (Cek dulu apakah ada enum constraint.)

### Gap 5 — Event Attendance eksplisit
Tambah `bv_campaign_kols.event_attendance` (boolean, default false), terpisah dari `visit_date`/`visit_status`.
`visit_date` tetap dipakai untuk tanggal kunjungan; `event_attendance` untuk centang item SOW "Event Attendance".

---

## Langkah implementasi (bertahap, tiap langkah diverifikasi)

### Langkah 1 — Migrasi DB
File baru: `database/migrations/xxxx_create_campaign_kol_revisions_table.php` (skema di atas).
File baru: `database/migrations/xxxx_add_event_attendance_to_bv_campaign_kols.php`
  - `boolean('event_attendance')->default(false)->after('visit_status')`.
- Pakai pola lintas-driver (MySQL + sqlite test). Jangan raw ALTER.

### Langkah 2 — Model
- `app/Models/CampaignKolRevision.php` (baru): `belongsTo(BvCampignKol)`, casts `is_final` bool, `submitted_at` datetime.
- `app/Models/BvCampignKol.php` (atau nama model existing utk `bv_campaign_kols`):
  tambah `hasMany(CampaignKolRevision::class)` + helper `latestRevision($stage)`, `finalVideo()`.
- Konstanta: `STAGE_OPTIONS`, `REVISION_STATUS_OPTIONS`; tambah `canceled` ke status KOL.

### Langkah 3 — Migrasi data lama (opsional tapi disarankan)
Backfill `feedback`/`revision_link`/`feedback_2` existing → baris `campaign_kol_revisions`
(stage=video). Setelah yakin, kolom lama bisa di-deprecate (jangan langsung drop — sisakan 1 rilis).

### Langkah 4 — UI Filament (resource `BvCampignResource`, slug `campaign-ongoing-internal`)
- **RelationManager baru "Revisi Konten"** di `EditBvCampign` (atau nested di halaman KOL):
  kolom `stage`, `round`, `asset_link`, `client_feedback`, `is_final`, `status`; aksi tambah ronde.
- Kolom/aksi `event_attendance` (toggle) + status `canceled` di tabel KOL.
- Pastikan layout Filament existing tidak rusak (sesuai feedback user sebelumnya).

### Langkah 5 — Link Approval Konten publik (selaras Part C existing)
- `CampaignContentReviewController`: saat client pilih `revision`, **buat baris revisi baru** (stage sesuai)
  alih-alih hanya set status. Saat `approved` pada stage=caption/video terakhir → tandai `is_final`.
- Blade `resources/views/campaign/content-review.blade.php`: tampilkan riwayat ronde revisi (read-only)
  + form feedback ronde berjalan.

### Langkah 6 — Test
- `tests/Feature/` : tambah test alur multi-ronde (storyline r1→r2, video r1→r3→final, caption) +
  status `canceled` + `event_attendance`. Jaga suite tetap hijau (existing 23 pass).

---

## Verifikasi tiap langkah
- Setelah migrasi: `php artisan migrate` di local + cek kolom/tabel.
- Setelah UI: load Edit Campaign Ongoing Internal, pastikan tidak error & layout utuh.
- Uji ulang dengan data sheet Tracker (mis. baris `Felix Sudjiman` yang punya storyline-revisi + video-revisi)
  untuk memastikan semua ronde tersimpan.

---

## STATUS IMPLEMENTASI — SELESAI (2026-06-14)

Keputusan arsitektur tambahan dari user: **Modul Campaign Ongoing Internal = workspace tim internal SAJA.
Sisi client (preview & revisi) lewat modul Campaign Ongoing External.** Ini mengubah Langkah 4 & 5 dari rencana awal.

Yang dikerjakan & terverifikasi (19 test hijau, lint bersih, route:list & optimize:clear OK):
1. **DB** — `campaign_kol_revisions` dibuat dengan DUA FK (`bv_campaign_id` + `bv_campaign_kol_id` nullable) + `kol_name`
   (refinement dari rencana yang hanya anchor ke `bv_campaign_kols`, agar bisa jadi RelationManager di halaman campaign).
   `bv_campaign_kols.event_attendance` (boolean).
2. **Model** — `CampaignKolRevision` (STAGES/STATUSES). Relasi `BvCampign::revisions()`, `BvCampaignKol::revisions()`.
   `canceled` ditambah ke `BvCampaignKol::STATUSES`; `event_attendance` cast boolean.
3. **UI Internal** — `RevisionsRelationManager` ("Revisi Konten") didaftarkan di `BvCampignResource`.
   `event_attendance` Toggle + IconColumn di `KolBriefRelationManager`. `KolPerformance` status pakai const.
   `EditBvCampign` dibersihkan → hanya `DeleteAction` (semua tombol client dihapus).
4. **UI External (sisi client)** — `CampaignExternalsTable` tak lagi exclude internal + kolom badge "Tipe".
   Aksi "Buat/Cabut Link Approval Konten" dipindah ke `ViewCampaignExternal`.
5. **Test** — `CampaignContentReviewTest` disesuaikan; baru `CampaignKolRevisionTest`.

Catatan: route name publik tetap `campaign-internal.content-review` (tak diganti agar token existing tak rusak),
walau link-nya kini digenerate dari modul External. Langkah 5 (auto-buat baris revisi dari submit client publik)
BELUM dikerjakan — saat ini revisi dicatat tim internal; client approve/revisi storyline lewat link publik seperti Part C.
Belum di-commit (menunggu user test manual UI).
