# Laporan E2E — Skenario "Masters of the Universe / Sony Pictures"

Tanggal: 2026-06-14
Acuan data: `[EXT] Masters of the Universe - Sony Pictures - KOL List - BV Network .xlsx`
(sheet **MacroMicro**, **Approval**, **Tracker** — ketiganya dipakai).

Tujuan: memverifikasi alur sistem dari **Sales Tracker → Form Brief → Media Plan Internal →
Media Plan External → Campaign Ongoing (Storyline + Tracker)** berjalan ujung-ke-ujung
sebelum naik ke server produksi, lewat transisi status & boot hooks **produksi** (bukan insert manual).

---

## Hasil akhir: ✅ alur jalan ujung-ke-ujung

`php artisan test` → **45 passed (131 assertions)**, lint bersih.

Skenario (BD **Gerry**, PIC **Baehaqi**, client **Sony Pictures** direct/rebrand) menghasilkan:
- Form Brief otomatis (draft → submitted) + Sales maju ke Proposal Building.
- Media Plan Internal: 15 KOL (8 longlist + 7 shortlist) dari sheet.
- Media Plan External: 7 item budget, **Sub Total Cost = Rp 15.100.000 persis** sesuai sheet Approval.
- Campaign Ongoing internal: **8 KOL** (7 approved + `kadin5s` Cancel), **7 draft storyline**, **17 baris revisi**
  bertingkat (storyline/video, feedback client persis sheet), event_attendance & posting link sesuai Tracker.

Reproduksi data UI: `php artisan db:seed --class=SonyPicturesScenarioSeeder` (idempotent).

---

## 🐞 Bug produksi yang DITEMUKAN & DIPERBAIKI

### 1. Kondisi mati membuat KOL & storyline tak pernah ter-sync (KRITIS)
`BvSales::ensureCampaignOngoingExists()` mengecek `$internalBudget->status === 'approved'`,
padahal status valid InternalBudget adalah `approve_am` (tak pernah `'approved'`).
**Akibat:** saat Sales → Campaign Live, campaign dibuat tapi **0 KOL & 0 storyline** —
modul Campaign Ongoing kosong walau budget sudah disetujui.
**Fix:** `=== 'approve_am'`. → `app/Models/BvSales.php`

### 2. Sync gagal karena cache relasi basi (KRITIS)
`InternalBudget::syncCampaignKolsFromApprovedBudget()` mengambil campaign lewat properti
relasi `$this->mediaPlan?->bvSales?->campaign`. Relasi `campaign` sudah ter-cache **null**
(diakses sebelum campaign dibuat), sehingga sync menganggap campaign tidak ada → no-op,
walau barisnya nyata ada di DB.
**Fix:** resolusi via query segar `$bvSales?->campaign()->first()`. → `app/Models/InternalBudget.php`

### 3. Sync tidak order-independent
Seeding hanya terpicu dari satu jalur (Campaign Live). Bila Approve AM terjadi **setelah**
campaign dibuat, KOL/storyline tak ter-seed.
**Fix:** boot hook `approve_am` di InternalBudget kini juga memanggil sync (idempotent, no-op
bila campaign belum ada). Test membuktikan **kedua urutan** menghasilkan 3 KOL + 3 storyline. → `app/Models/InternalBudget.php`

---

### 4. `BvSales` tak meng-cast `start_date`/`end_date` → Edit Media Plan Internal 500
`MediaPlanForm` memanggil `$record->bvSales->start_date->format()`, tapi model `BvSales` hanya
meng-cast `close_date`/`campaign_date`/`brief_submit_date` — `start_date`/`end_date` masih string.
**Fix:** tambah cast `'start_date' => 'date'`, `'end_date' => 'date'`. → `app/Models/BvSales.php`

### 5. Namespace Filament v5 salah → Edit Media Plan External 500
`EditInternalBudget` memakai `\Filament\Forms\Components\Actions\Action` (tidak ada di v5).
**Fix:** `Filament\Actions\Action`. → `app/Filament/Resources/InternalBudgets/Pages/EditInternalBudget.php`

### 6. Channel log `whatsapp` tak terdefinisi → 500 saat notifikasi WA
`WhatsAppService`/`BvNotificationService` memanggil `Log::stack(['single','whatsapp'])` tapi
channel `whatsapp` tak ada di `config/logging.php` → `Log [whatsapp] is not defined` pada jalur
WA yang tak ter-guard (mis. N8N gagal/tak diset).
**Fix:** definisikan channel `whatsapp` (file `storage/logs/whatsapp.log`). → `config/logging.php`

### 7. `match($state)` status usang → List Media Plan External 500
`InternalBudgetsTable` memetakan warna status dengan `match` tanpa `default` & masih pakai nilai
lama (`pending`/`approved`). Status `approve_am` → `UnhandledMatchError`.
**Fix:** selaraskan ke `STATUS_OPTIONS` + tambah `default`; filter status juga diselaraskan. → `app/Filament/Resources/InternalBudgets/Tables/InternalBudgetsTable.php`

> Temuan 4–7 lolos dari test model/alur karena bug saat **render halaman**. Ditangkap oleh
> `tests/Feature/FilamentPagesSmokeTest.php` (mount List + Edit tiap modul dengan data skenario).

## ⚠️ Catatan / batasan yang masih perlu diperhatikan (belum diubah)

- **Jalur MediaPlan manual (tanpa `bv_sales_id`)**: fallback di `InternalBudget::booted()` masih
  membuat KOL via `detectPlatformFromScope` dan **tidak** seed storyline / tak set `campaign_type=internal`.
  Tidak terpakai di alur Sales-driven (skenario ini), tapi perlu diselaraskan bila modul itu dipakai.
- **Grand Total sheet (Rp 15.175.879)**: PPN manual (75.879) di sheet tidak direplikasi sistem —
  sistem menghitung gross-up pajak per-item (default Pribadi 0,975). Yang dijamin sama adalah
  **Sub Total Cost (rate KOL) = Rp 15.100.000**. Harga client = `total_rounded` (setelah margin 40–80%).
- **Event Attendance**: di sistem disimpan sebagai boolean `bv_campaign_kols.event_attendance`
  (tahap Tracker), bukan sebagai item rate terpisah di budget (sesuai desain saat ini).
- **Notifikasi WA** saat approve budget dibungkus try/catch (gagal → hanya log warning).

---

## File yang ditambah/diubah

Ditambah:
- `app/Support/MotuScenarioData.php` — sumber data tunggal dari 3 sheet Excel.
- `database/seeders/SonyPicturesScenarioSeeder.php` — bangun skenario penuh lewat alur produksi (idempotent).
- `tests/Feature/SonyPicturesEndToEndTest.php` — 5 test E2E per stage.
- `tests/Feature/CampaignSyncOrderTest.php` — 3 test order-independence & idempotency.
- `tests/Unit/ScopeItemChannelTest.php` — pemetaan SOW → platform/content_type.

Diubah (fix):
- `app/Models/BvSales.php` — kondisi `approve_am`.
- `app/Models/InternalBudget.php` — query segar campaign + sync order-independent.
