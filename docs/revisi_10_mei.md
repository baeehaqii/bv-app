# Revisi BV Tech — 10 Mei 2026

> **Meeting Date:** 10 Mei 2026
> **Dibuat:** 13 Mei 2026
> **Referensi Sebelumnya:** [revisi_27_april.md](revisi_27_april.md)

---

## Modul 1: Sales Activity Tracker

### 1.1 Popup Detail Campaign di Klik Card Kanban

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- Halaman Sales Activity Tracker (`SalesKanban.php`) punya dua mode: List dan Kanban.
- Di mode Kanban, klik pada card campaign tidak menampilkan popup detail.
- Data seperti meeting progress dan komentar tidak terekspos dari tampilan kanban.

**Permintaan:**

- Ketika card campaign di-klik pada mode Kanban, tampilkan popup (modal) berisi detail campaign: meeting progress, komentar (jika ada), dan informasi kampanye lainnya.

**To-Do:**

- [x] Tambah `Action::make('view_detail')` sebagai card action di `board()` di `SalesKanban.php`
- [x] Modal infolist menampilkan: status (badge berwarna), client/brand, budget propose, deal value, campaign period, close date, meeting notes, dan komentar (max 5 terbaru)
- [x] Komentar diambil dari relasi `salesComments` (`BvSalesComment`) — ditampilkan sebagai RepeatableEntry dengan nama user + waktu relatif
- [x] `->cardAction('view_detail')` — klik card langsung buka popup detail, tombol "Edit" tersedia di footer modal

---

### 1.2 Ganti Label "Pipeline Saya" → "Pipeline"

**Status:** 🟢 Selesai
**Prioritas:** Low

**Kondisi saat ini:**

- Di `resources/views/filament/pages/sales-dashboard.blade.php:317`, terdapat label **"Pipeline Saya"**.
- Fungsi backend `getMyPipeline()` di `SalesDashboard.php:237` mengambil pipeline milik user yang login.

**Permintaan:**

- Ganti label "Pipeline Saya" menjadi **"Pipeline"** di Dashboard Sales.

**To-Do:**

- [x] Ubah teks `Pipeline Saya` → `Pipeline` di `sales-dashboard.blade.php:317`

---

## Modul 2: Form Brief

### 2.1 Format Mata Uang (Currency Mask) di Section Budget

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `FormBriefForm.php:114–130` — Section Budget memiliki beberapa field: `budget_main_kol`, `budget_macro_kol`, dll.
- Field-field budget sudah menggunakan `->prefix('Rp')` dan `->mask(RawJs::make('$money(...)'))`, namun format pemisah ribuan **belum konsisten** atau belum tampil dengan benar di UI.

**Permintaan:**

- Pastikan semua field budget di Form Brief menampilkan format mata uang Indonesia (Rp 1.000.000) dengan benar.

**To-Do:**

- [x] Field `budget` di `FormBriefForm.php` menggunakan `mask(RawJs::make('$money($input, \',\', \'.\', 0)'))` konsisten
- [x] `dehydrateStateUsing` strip karakter non-numerik (`preg_replace('/[^0-9]/', '', ...)`) sebelum simpan ke DB
- [x] `formatStateUsing` pakai `number_format((int) $state, 0, ',', '.')` untuk tampilan edit

---

### 2.2 Section Budget Dijadikan 1 Field Saja

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Section Budget (`FormBriefForm.php:109–135`) memiliki beberapa field terpisah: `budget_main_kol`, `budget_macro_kol`, dan kemungkinan field budget lainnya.

**Permintaan:**

- Sederhanakan section Budget menjadi **1 field saja**: `budget` (total budget campaign).

**To-Do:**

- [x] Ganti `budget_main_kol` + `budget_macro_kol` dengan satu `TextInput::make('budget')` berlabel "Budget Campaign" di `FormBriefForm.php`
- [x] Migration `2026_05_18_200000` — tambah kolom `budget` (unsignedBigInteger), migrasi data, drop kolom lama
- [x] Model `FormBrief.php` — tambah cast `budget` sebagai `integer`
- [x] `FormBriefInfolist.php` — tampilkan `budget` dengan format `Rp x.xxx.xxx` + `FontWeight::Bold`

---

### 2.3 Deadline Menggunakan Date Picker (Dropdown Tanggal)

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- `FormBriefForm.php:139` — field `deadline` berupa `TextInput` (input teks bebas).

**Permintaan:**

- Ubah field `deadline` menjadi `DatePicker` agar user memilih tanggal via dropdown kalender.
- Di tab Brief (tampilan list/infolist), tanggal deadline dibuat **bold dan font-size lebih besar**.

**To-Do:**

- [x] Tambah `DatePicker::make('deadline_date')` di `FormBriefForm.php` (kolom date baru, bukan string lama)
- [x] Migration `2026_05_18_200000` menambahkan kolom `deadline_date` (date, nullable)
- [x] Model `FormBrief.php` — cast `deadline_date` sebagai `date`
- [x] `FormBriefInfolist.php` — `deadline_date` dengan `->weight(FontWeight::Bold)->size(TextEntry\TextEntrySize::Large)`

---

### 2.4 Kirim Email ke Client Setelah Form Brief Disubmit + Form Bisa Diedit

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `CreateFormBrief.php` — Tidak ada mekanisme pengiriman email saat brief disimpan.
- `EditFormBrief.php` — Form edit sudah ada.
- `FormBriefPublicController.php:29` — Ada referensi `$employee->whatsapp` dan service WA (`WhatsAppService.php`), namun pengiriman email ke client belum ada.

**Permintaan:**

- Setelah form brief berhasil di-submit (create), kirimkan notifikasi email ke client yang bersangkutan.
- Form brief tetap bisa diedit setelah submit (tidak di-lock).

**To-Do:**

- [x] Tambah `afterCreate()` di `CreateFormBrief.php` — kirim email ke `submitted_by_email` → `bvSales.client.email` → `client.email`
- [x] Buat `FormBriefSubmittedMailable` di `app/Mail/` + blade view `emails/form-brief-submitted.blade.php` — berisi campaign name, brand, budget, deadline, PIC
- [x] Email gagal dikirim ditangani gracefully (tidak crash, muncul warning notification)
- [x] `EditFormBrief.php` sudah bersih — tidak ada `canEdit()` atau `authorizeAccess()` yang memblokir

---

### 2.5 Bug: Error di View Brief

**Status:** 🟢 Selesai
**Prioritas:** Critical (Bug)

**Kondisi saat ini:**

- `ViewFormBrief.php` menggunakan custom blade view (`filament.resources.form-briefs.pages.view-form-brief`).
- Terdapat error saat membuka halaman view brief.

**Permintaan:**

- Investigasi dan perbaiki error di halaman View Brief.

**To-Do:**

- [x] Root cause: custom blade memanggil `$record->bvSales?->status->getLabel()` (Enum, error jika null/string) dan field `budget_main_kol`/`budget_macro_kol` yang sudah tidak ada
- [x] Fix: hapus `protected string $view = ...` dari `ViewFormBrief.php` — Filament kini render infolist standar yang sudah diupdate
- [x] `FormBriefInfolist.php` diupdate selaras dengan schema DB terbaru (field `budget`, `deadline_date`, hapus field lama)

---

## Modul 3: Media Plan Internal

### 3.1 Restrukturisasi PIC Campaign

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `MediaPlanForm.php:247–254` — Satu field `pic_campaign_id` berlabel **"PIC Utama (Assign Tugas Brief)"** berupa Select single.
- Revisi 27 April menambah `sub_pic_campaign_ids` (di-comment out di baris 254) untuk sub-PIC, belum aktif.

**Permintaan:**

- Ganti struktur PIC menjadi **section PIC tersendiri** dengan 4 role PIC per campaign:
  - **PIC Sales/BD** (menggantikan "PIC Utama")
  - **PIC Leads Project** (Manager)
  - **PIC Project Internal** (bisa lebih dari 1 — KOL Specialist)
  - **PIC Account Manager (AM)**
- Rename label "PIC Utama (Assign Tugas Brief)" → **"PIC Sales/BD"**.

**To-Do:**

- [x] Buat `Section::make('PIC Campaign')` baru di `MediaPlanForm.php` yang berisi 4 field PIC
- [x] Field `Select::make('pic_sales_bd_id')` — label "PIC Sales/BD" (single) — rename dari `pic_campaign_id`
- [x] Field `Select::make('pic_leads_project_id')` — label "PIC Leads Project (Manager)" (single)
- [x] Field `Select::make('pic_project_internal_ids')` — label "PIC Project Internal" (multiple, untuk KOL Specialist)
- [x] Field `Select::make('pic_am_id')` — label "PIC Account Manager" (single)
- [x] Tambah kolom `pic_leads_project_id`, `pic_project_internal_ids` (JSON), `pic_am_id` di migration `media_plans`
- [x] Rename kolom `pic_campaign_id` → `pic_sales_bd_id` via migration `2026_05_13_100000`
- [x] Update model `MediaPlan.php` — tambah cast `pic_project_internal_ids` sebagai array, relasi `picSalesBd()`, `picLeadsProject()`, `picAm()`
- [x] Field `sub_pic_campaign_ids` lama digantikan sepenuhnya oleh struktur 4 role baru

---

### 3.2 Rate Card Per Channel — Dibuat Per SOW, Ada yang Custom

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- Revisi 27 April (Modul 5.3) membuat tabel `kol_rate_cards` dengan kolom `channel`, `sow`, `rate`, dll.
- `MasterServices/` resource sudah ada — berisi master data SOW/layanan.
- Saat ini rate card input manual per channel, tapi belum terhubung ke master data SOW.

**Permintaan:**

- Rate card **per SOW** (bukan hanya per channel) — setiap SOW punya rate tersendiri.
- Ada opsi SOW **custom** selain pilihan dari master data.
- Buat **master data SOW** yang bisa dikelola admin, termasuk opsi custom.

**To-Do:**

- [x] Tambah tabel `master_sows` via migration `2026_05_13_100001`
- [x] Buat model `MasterSow` dan resource Filament `MasterSows/MasterSowResource.php` (CRUD lengkap di Master Data sidebar)
- [x] Di `kol_rate_cards` — tambah kolom `master_sow_id` (FK) dan `custom_sow_name` (nullable)
- [x] Update repeater rate card di `DataKolForm.php` — field SOW menjadi Select dari `MasterSow::active()->ordered()`
- [x] Jika SOW = Custom (`is_custom = true`), tampilkan `TextInput::make('custom_sow_name')` otomatis
- [x] Model `KolRateCard` — relasi `masterSow()` + accessor `sow_label`

---

### 3.3 Auto-Select PIC Saat Tambah KOL (User Baehaqi)

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Di `MediaPlanForm.php` — tab "Select KOL", ada field `pic_kol_id` di repeater KOL.
- Saat user menambahkan KOL, PIC tidak otomatis terisi.

**Permintaan:**

- Ketika user yang sedang login (contoh: akun Baehaqi) menambahkan KOL di media plan, field **PIC KOL otomatis terisi dengan user yang sedang login**.

**To-Do:**

- [x] Tambah `->default(fn() => auth()->id())` pada field `pic_kol_id` di repeater KOL di `MediaPlanForm.php`
- [x] Options diubah dari hardcoded ke `BvSalesList` (konsisten dengan PIC Campaign)

---

### 3.4 Default Checkbox Quotation — Jangan Dicentang

**Status:** 🟢 Selesai
**Prioritas:** Low

**Kondisi saat ini:**

- Di form media plan atau quotation, ada checkbox yang defaultnya **tercentang** — seharusnya default **tidak tercentang**.

**Permintaan:**

- Default select/checkbox di quotation jangan dicentang (false by default).

**To-Do:**

- [x] Verifikasi semua instance `Checkbox::make('is_selected')` — sudah `->default(false)` di semua tempat
- [x] Auto-set `true` saat pilih KOL dari database adalah behavior yang disengaja, tidak diubah

---

### 3.5 Edit Status Budget Items — Approve, Reject, Nego (dengan Komentar)

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `InternalBudgets/` — Ada tombol Approve dan Reject dari revisi 27 April.
- Belum ada opsi status **Nego** dan mekanisme komentar untuk budget items individual.

**Permintaan:**

- Setiap budget item bisa di-set status: **Approve**, **Reject**, atau **Nego**.
- Jika status = **Nego**, user bisa meninggalkan komentar/catatan negosiasi.

**To-Do:**

- [x] Tambah kolom `nego_notes` (text, nullable) di tabel `internal_budget_items` via migration `2026_05_13_100002` — kolom `status` sudah ada sebagai string
- [x] Tambah action `nego_item` di repeater budget items — modal dengan `Textarea::make('nego_notes')` wajib diisi
- [x] Status badge diupdate: `approved` → hijau, `rejected` → merah, `nego` → kuning (+ tooltip catatan nego)
- [x] Model `InternalBudgetItem` — method `nego(string $notes)` ditambah
- [x] Action Nego muncul untuk status `pending` dan `nego`; status lain hanya bisa diubah oleh super_admin/CEO/COO

---

### 3.6 Page View Quotation untuk Review ke Client (Media Plan External)

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `CampaignExternals/` resource sudah ada (dari revisi 27 April) untuk Campaign Ongoing External.
- Quotation sudah bisa di-generate dari `BvQuotations/` resource.
- Belum ada halaman publik/review khusus untuk quotation yang bisa dilihat client.

**Permintaan:**

- Buat halaman view/review quotation yang bisa dikirimkan ke client sebagai **Media Plan External / Excel-style view**.
- Serupa dengan public campaign page yang sudah ada (`/campaign/{token}`).

**To-Do:**

- [x] Tambah kolom `public_token`, `is_public`, `media_plan_id` di `bv_quotations` via migration `2026_05_13_100003`
- [x] Model `BvQuotation` — method `generatePublicToken()`, `revokePublicToken()`, accessor `public_url`, `public_items`
- [x] `EditBvQuotation.php` — 4 action buttons: Generate Link Client, Preview Client, Salin Link, Cabut Link
- [x] Buat `QuotationPublicController.php` — grouping items per KOL (KOL sama = 1 baris, SOW digabung)
- [x] Route `GET /quotation-review/{token}` → `quotation.public` (no auth required)
- [x] Buat `resources/views/quotation/public.blade.php` — tampilan clean dengan logo BV, info client & campaign, tabel KOL+SOW+rate, signature block 3 pihak, print-ready

---

### 3.7 Konten Dokumen Quotation (Logo, Alamat, TTD)

**Status:** ✅ Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Quotation yang di-generate belum memiliki elemen dokumen resmi.

**Permintaan:**

- Dokumen quotation/proposal harus memuat:
  - Logo BV
  - Alamat BV
  - Nama client
  - Nama campaign
  - Tanda tangan PIC Client
  - Tanda tangan Sales BV
  - Tanda tangan BD Sales BV

**To-Do:**

- [x] Tambah section "Pengesahan" di `public.blade.php` — 3 kolom (Client, Sales BV, BD BV)
- [x] Nama dinamis dari relasi: `picSalesBd` dan `picLeadsProject`
- [x] Alamat PT Beyond Viral Indonesia dilengkapi (jalan, kota, email)
- [x] Print CSS diperbaiki (shadow & rounded hilang saat print)
- [x] Tambah `FileUpload` TTD digital di `bv_quotations` — migration `2026_05_18_300000`, kolom `ttd_pic_client`, `ttd_sales_bv`, `ttd_bd_sales`
- [x] Section "✍️ Tanda Tangan Digital" di `BvQuotationForm.php` — 3 FileUpload PNG/JPEG, disimpan di `storage/public/ttd/quotations`
- [x] `public.blade.php` — TTD ditampilkan sebagai gambar jika sudah di-upload, fallback ke placeholder dashed box

---

### 3.8 Approved Item Table — KOL Sama Dijadikan 1 Baris, 2 SOW

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Di tabel Approved Items (hasil approve budget), jika KOL yang sama punya 2 SOW berbeda, muncul sebagai 2 baris terpisah.

**Permintaan:**

- Jika ada nama KOL yang sama, jadikan **1 baris** saja dengan **2 kolom SOW** (atau SOW ditampilkan gabung dalam 1 sel).

**To-Do:**

- [x] Group by `mediaPlanKol->id` di `BvQuotationForm.php` — KOL sama = 1 baris
- [x] SOW ditampilkan sebagai badge inline (contoh: `IG Reels` `Story ×2`)
- [x] Total rate per KOL = sum `rounded` semua SOW KOL tersebut

---

## Modul 4: Campaign Ongoing

### 4.1 Storyline di Campaign Ongoing External

**Status:** ✅ Selesai (termasuk public view)
**Prioritas:** High

**Kondisi saat ini:**

- `BvCampignForm.php` — Campaign Ongoing sudah ada beberapa section.
- Belum ada section/field untuk **Storyline** per KOL.
- Referensi format storyline: [Google Sheets Storyline](https://docs.google.com/spreadsheets/d/1yIBYzM3pL4gdnv42Og10M-WzeLEQ7ggA9KgalaazTJw/edit?usp=sharing)

**Permintaan:**

- Tambahkan section **Storyline** di halaman Campaign Ongoing External, mengacu pada format spreadsheet yang diberikan.

**To-Do:**

- [x] Pelajari struktur spreadsheet storyline (kolom: KOL name, platform, SOW, content angle, caption draft, key message, deadline posting, dll)
- [x] Tambah tabel/relasi `campaign_storylines` via migration `2026_05_18_100001` (id, bv_campaign_id, kol_name, platform, sow, content_angle, caption_draft, key_message, posting_deadline, status, notes, timestamps)
- [x] Buat model `CampaignStoryline.php` dengan constants `PLATFORMS`, `STATUSES` dan relasi `campaign()`
- [x] Buat `StorylinesRelationManager.php` di `BvCampigns/RelationManagers/` — form lengkap + tabel dengan filter platform & status
- [x] Daftarkan `StorylinesRelationManager` di `BvCampignResource::getRelations()` — tab "Storyline" otomatis muncul di halaman edit
- [x] Di Campaign Ongoing External (`CampaignExternalResource`), tampilkan storyline yang sudah di-approve via `StorylinesExternalRelationManager` (read-only, filter `status = approved`)

---

### 4.2 Upload Quotation Bertanda Tangan Sebelum Campaign Live

**Status:** 🟢 Selesai
**Prioritas:** High

**Kondisi saat ini:**

- Alur campaign: Quotation di-generate → status berubah → campaign live.
- Tidak ada gate/syarat upload quotation signed sebelum campaign bisa diaktifkan.

**Permintaan:**

- **Sebelum campaign live**, wajib upload **quotation yang sudah ditandatangani**.
- Setelah upload quotation signed, baru bisa **assign PIC AM**.

**To-Do:**

- [x] Tambah kolom `quotation_signed_path` (string, nullable) dan `quotation_signed_at` (timestamp, nullable) di tabel `media_plans` via migration `2026_05_18_100000`
- [x] Tambah field `FileUpload::make('quotation_signed_path')` di Step "Campaign Information" `MediaPlanForm.php` — Section "Quotation Bertanda Tangan", dengan status indicator
- [x] Tambah validasi di `tryActivateCampaign()` — early return jika `quotation_signed_path` kosong
- [x] `quotation_signed_at` diisi otomatis saat file diupload via `afterStateUpdated`

---

### 4.3 Semua PIC Tampil di Campaign Tracker Setelah Upload Quotation Signed

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Campaign tracker belum menampilkan semua PIC (BD, KOL Specialist, AM) dalam satu section.

**Permintaan:**

- Di Campaign Tracker, setelah section upload quotation signed, tampilkan **semua PIC** yang terlibat: PIC BD, PIC KOL, PIC AM.

**To-Do:**

- [x] Tambah Section "PIC Campaign" di `BvCampignForm.php` Step 3 (Campaign Settings) — setelah section Campaign Settings
- [x] Ambil data PIC dari relasi `mediaPlan` via `HasOneThrough` (BvCampign → BvSales → MediaPlan) — relasi baru ditambah ke `BvCampign.php`
- [x] Section hidden jika `$record->mediaPlan->quotation_signed_path` null/kosong
- [x] Menampilkan 4 role: PIC Sales/BD, PIC Leads Project, PIC Project Internal (multi), PIC AM dalam tabel read-only

---

### 4.4 Deal Value = Budget Akhir Quotation

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Field `deal_value` di model `BvSales.php` diisi manual saat create/edit Sales Activity.
- `SalesDashboard.php:117,123` menggunakan `->sum('deal_value')` untuk kalkulasi pipeline.

**Permintaan:**

- **Deal Value di Sales Activity otomatis disamakan** dengan nilai budget akhir dari quotation yang sudah di-approve.

**To-Do:**

- [x] Sync `deal_value` di `InternalBudget::approve()` — update `bv_sales.deal_value = total_rounded` saat budget di-approve via per-item flow
- [x] Sync `deal_value` juga di `afterStateUpdated` dropdown status `InternalBudgetForm.php` — untuk path approve langsung via dropdown
- [x] Relasi via `mediaPlan->bvSales` sudah tersedia — tidak perlu relasi baru

---

### 4.5 Bug: Pagination KOL Performance

**Status:** 🟢 Selesai
**Prioritas:** Medium (Bug)

**Kondisi saat ini:**

- Terdapat bug di halaman **KOL Performance** — pagination tidak berfungsi dengan benar.

**To-Do:**

- [x] Identifikasi root cause: konflik query string `page` karena `KolPerformance` custom Page tidak punya identifier unik
- [x] Fix: tambah `->queryStringIdentifier('kol')` pada `table()` di `KolPerformance.php` — pagination kini pakai `?kolPage=N` yang tidak bentrok dengan halaman lain

---

## Modul 5: Dashboard Tim KOL & Kreatif

### 5.1 Dashboard Tim KOL dan Tim Kreatif Nempel dengan Project yang Di-assign

**Status:** 🟢 Selesai
**Prioritas:** Medium

**Kondisi saat ini:**

- Dashboard Sales (`SalesDashboard.php`) sudah ada untuk tim Sales.
- Belum ada dashboard khusus untuk user **Tim KOL** dan **Tim Kreatif** yang menampilkan project yang di-assign ke mereka.

**Permintaan:**

- Tim KOL dan Tim Kreatif memiliki tampilan dashboard yang menampilkan campaign/project yang di-assign kepada mereka secara langsung.
- Style kurang lebih seperti sales dashboard

**To-Do:**

- [x] Buat `KolDashboard.php` di `app/Filament/Pages/` — Filament Page dengan `shouldRegisterNavigation()` terbatas ke role `Operation KOL & Creative`
- [x] Dashboard menampilkan 4 quick stats: Campaign Assigned, Sedang Berjalan, Selesai Bulan Ini, Media Plan Aktif
- [x] Section "Campaign Saya" — list BvCampign dimana user ada di `pic_project_internal_ids` atau `pic_am_id` (via `whereHas mediaPlan`), dengan progress bar waktu + KOL posted progress
- [x] Section "Media Plan Internal Saya" — list MediaPlan dengan `whereJsonContains('pic_project_internal_ids', $salesListId)`, card grid 3 kolom
- [x] Section "Aktivitas Terbaru" — campaign terbaru yang di-update, dengan indikator is_new
- [x] Filter data by `BvSalesList.user_id = auth()->id()` — data strictly scoped ke user yang login
- [x] Style konsisten dengan SalesDashboard (grid 12 kolom, card rounded-2xl, dark mode support)
- [x] Route terdaftar: `GET /office/kol-dashboard`

---

## Modul 6: Notifikasi

### 6.1 Semua Notifikasi ke WhatsApp Grup + Email Backup

**Status:** ✅ Selesai
**Prioritas:** High

**Kondisi saat ini:**

- `WhatsAppService.php` sudah ada tapi implementasi pengiriman masih di-comment out (`WhatsAppService.php:29–30`).
- `CampaignNotificationService.php` — ada `sendNotification()` untuk beberapa event campaign.
- Email notifikasi sudah berjalan untuk beberapa event (via `GoogleSheetsService.php`).

**Permintaan:**

- **Semua notifikasi sistem dikirim ke WhatsApp grup utama** (sebagai notifikasi utama).
- **Email** tetap dikirim sebagai **backup/cadangan**.
- Event notifikasi mencakup: brief baru masuk, campaign status berubah, quotation approved/rejected, PIC di-assign, dll.

**To-Do:**

- [x] Aktifkan implementasi `WhatsAppService.php` — HTTP POST ke n8n webhook (Waha), normalize phone, double-channel log
- [x] Tambah `N8N_WEBHOOK_URL` dan `NOTIFY_WA_GROUP_MAIN` di `.env.example` dan `config/services.php`
- [x] Buat `BvNotificationService.php` — service pusat semua event: `briefSubmitted`, `quotationLinkGenerated`, `budgetApproved`, `budgetRejected`, `picAssigned`, `campaignCreated`
- [x] Update `CampaignNotificationService.php` — slim down jadi adapter, delegate ke `BvNotificationService`
- [x] `CreateFormBrief.php` — notifikasi WA setelah brief submit
- [x] `EditBvQuotation.php` — notifikasi WA saat generate link quotation
- [x] `InternalBudget::approve()` dan `reject()` — notifikasi WA budget approved/rejected
- [x] `EditMediaPlan::afterSave()` — notifikasi WA jika PIC berubah
- [ ] Test pengiriman ke WA grup sebelum deploy (perlu isi `N8N_WEBHOOK_URL` dan `NOTIFY_WA_GROUP_MAIN` di .env)

---

## Modul 7: `@bvnetwork`

### 7.1 Implementasi @bvnetwork

**Status:** 🟢 Selesai
**Prioritas:** Low

**Kondisi saat ini:**

- domain menggunakan dummy bv.com bukan `@bvnetwork` di sistem.

**Permintaan:**

- Implementasi fitur login menggunakan akhiran domain **`@bvnetwork`**

**To-Do:**

- [x] Ubah semua email di `UserSeeder.php` dari `@bv.com` → `@bvnetwork`
- [x] Tambah logika migrasi otomatis: email lama `@bv.com` di DB di-update ke `@bvnetwork` saat seeder dijalankan
- [x] Seeder dijalankan — semua 7 user berhasil dimigrasi ke domain `@bvnetwork`

---

## Ringkasan Todo per Prioritas

### 🔴 Critical (Bug Fix)

| # | Modul       | Deskripsi                     | Status | File Terkait                                                     |
|---|-------------|-------------------------------|--------|------------------------------------------------------------------|
| 1 | Form Brief  | Bug error di halaman View Brief | ✅   | `ViewFormBrief.php` — hapus custom blade, render infolist standar |

### 🟠 High Priority

| # | Modul              | Deskripsi                                               | Status | File Terkait                                                                   |
|---|--------------------|---------------------------------------------------------|--------|--------------------------------------------------------------------------------|
| 2 | Sales Activity     | Popup detail campaign saat card kanban diklik           | ✅     | `SalesKanban.php` — `Action::make('view_detail')` + `->cardAction('view_detail')` |
| 3 | Form Brief         | Format mata uang di section budget                      | ✅     | `FormBriefForm.php` — currency mask konsisten                                  |
| 4 | Form Brief         | Kirim email ke client setelah submit + form bisa diedit | ✅     | `CreateFormBrief.php`, `Mail/FormBriefSubmittedMailable.php`                   |
| 5 | Media Plan         | Restrukturisasi PIC menjadi 4 role (BD, Manager, KOL, AM) | ✅   | `MediaPlanForm.php`, migration `media_plans`                                 |
| 6 | Media Plan         | Rate card per SOW + master data SOW + custom            | ✅     | `DataKolForm.php`, migration `kol_rate_cards`, `MasterSows/`                   |
| 7 | Media Plan Ext     | Edit status budget items (approve/reject/nego + komentar) | ✅   | `EditInternalBudget.php`, migration `internal_budget_items`                  |
| 8 | Media Plan Ext     | Page view quotation untuk review client                 | ✅     | `BvQuotationResource.php`, `QuotationPublicController.php`, blade view         |
| 9 | Campaign Ongoing   | Storyline di Campaign Ongoing External                  | ✅     | `StorylinesRelationManager.php` (internal) + `StorylinesExternalRelationManager.php` (external, read-only) |
| 10| Campaign Ongoing   | Upload quotation signed sebelum campaign live           | ✅     | `MediaPlan.php`, `MediaPlanForm.php`, migration `2026_05_18_100000`             |
| 11| Notifikasi         | Semua notifikasi ke WhatsApp grup + email backup        | ✅     | `WhatsAppService.php`, `BvNotificationService.php`, `CampaignNotificationService.php` |

### 🟡 Medium Priority

| # | Modul              | Deskripsi                                               | Status | File Terkait                                                                   |
|---|--------------------|---------------------------------------------------------|--------|--------------------------------------------------------------------------------|
| 12| Form Brief         | Section budget dijadikan 1 field                        | ✅     | `FormBriefForm.php`, migration `2026_05_18_200000`                             |
| 13| Form Brief         | Deadline pakai DatePicker + bold di tab brief           | ✅     | `FormBriefForm.php`, `FormBriefInfolist.php`                                   |
| 14| Media Plan         | Auto-select PIC saat user login menambahkan KOL         | ✅     | `MediaPlanForm.php`                                                            |
| 15| Media Plan Ext     | Konten dokumen quotation (logo, alamat, TTD)            | ✅     | blade view quotation + `BvQuotationForm.php`, migration `2026_05_18_300000`    |
| 16| Media Plan Ext     | Approved item table — KOL sama jadi 1 baris, 2 SOW      | ✅     | `BvQuotationForm.php`                                                         |
| 17| Campaign Ongoing   | Semua PIC tampil di campaign tracker setelah upload signed | ✅  | `BvCampignForm.php`, `BvCampign.php`                                         |
| 18| Campaign Ongoing   | Deal value = budget akhir quotation                     | ✅     | `InternalBudget.php`, `InternalBudgetForm.php`                                |
| 19| Campaign Ongoing   | Bug pagination KOL Performance                          | ✅     | `KolPerformance.php` — `->queryStringIdentifier('kol')`                        |
| 20| Dashboard          | Dashboard Tim KOL & Kreatif nempel project yang di-assign | ✅  | `KolDashboard.php`, `kol-dashboard.blade.php`                                |

### 🟢 Low Priority

| # | Modul              | Deskripsi                                               | Status | File Terkait                                                                   |
|---|--------------------|---------------------------------------------------------|--------|--------------------------------------------------------------------------------|
| 21| Sales Dashboard    | Ganti "Pipeline Saya" → "Pipeline"                      | ✅     | `sales-dashboard.blade.php:317`                                                |
| 22| Media Plan         | Default checkbox quotation jangan dicentang             | ✅     | `MediaPlanForm.php`                                                            |
| 23| Misc               | Implementasi @bvnetwork                                 | ✅     | `UserSeeder.php` — semua email migrasi ke `@bvnetwork`                         |

---

## Catatan Teknis

### File yang Paling Banyak Terdampak

1. **`MediaPlanForm.php`** — Modul 3 (4 perubahan: PIC, auto-select, rate card SOW, checkbox)
2. **`FormBriefForm.php`** — Modul 2 (3 perubahan: budget format, 1 field, deadline)
3. **`BvCampignForm.php`** — Modul 4 (storyline, upload signed, PIC section)
4. **`WhatsAppService.php` + `CampaignNotificationService.php`** — Modul 6 (notifikasi WA)
5. **`EditInternalBudget.php`** — Modul 3 (status nego + view quotation client)

### Migration Status

| Migration | Deskripsi | Status |
|-----------|-----------|--------|
| `2026_05_13_100000_add_pic_roles_to_media_plans` | Rename `pic_campaign_id` → `pic_sales_bd_id`, tambah 3 kolom PIC baru | ✅ Done |
| `2026_05_13_100001_create_master_sows_table` | Tabel `master_sows` + kolom `master_sow_id`, `custom_sow_name` di `kol_rate_cards` | ✅ Done |
| `2026_05_13_100002_add_nego_notes_to_internal_budget_items` | Kolom `nego_notes` di `internal_budget_items` | ✅ Done |
| `2026_05_13_100003_add_public_token_to_bv_quotations` | Kolom `public_token`, `is_public`, `media_plan_id` di `bv_quotations` | ✅ Done |
| `2026_05_18_100000_add_quotation_signed_to_media_plans` | Kolom `quotation_signed_path`, `quotation_signed_at` di `media_plans` | ✅ Done |
| `2026_05_18_200000_alter_form_briefs_table` | Tambah `budget` (bigInt) + `deadline_date` (date), drop `budget_main_kol` & `budget_macro_kol` | ✅ Done |
| `2026_05_18_300000_add_ttd_to_bv_quotations` | Kolom `ttd_pic_client`, `ttd_sales_bv`, `ttd_bd_sales` di `bv_quotations` | ✅ Done |
| `2026_05_18_100001_create_campaign_storylines_table` | Tabel `campaign_storylines` — storyline per KOL per campaign | ✅ Done |

### Dependensi Antar Item

- **Modul 4.2** (upload quotation signed) harus selesai sebelum **Modul 4.3** (PIC AM tampil)
- ~~**Modul 3.1** (restrukturisasi PIC) harus selesai sebelum **Modul 4.3**~~ — ✅ 3.1 sudah selesai
- ~~**Modul 3.2** (master data SOW) harus selesai sebelum **Modul 3.6**~~ — ✅ 3.2 dan 3.6 sudah selesai
- **Modul 6.1** (WhatsApp aktif) bisa dikerjakan paralel dengan modul lain
