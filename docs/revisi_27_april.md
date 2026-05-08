# Revisi BV — 27 April 2026

> **Meeting Date:** 27 April 2026  
> **Dibuat:** 30 April 2026  
> **Referensi Sebelumnya:** [revisi_5_april.md](file:///Volumes/DATA/Dev/bv-app/docs/revisi_5_april.md)

---

## Modul 1: Data Client (Client Information)

### 1.1 Hapus Field Kategori di Client Information (KETIKA CLIENT TIPE AGENCY)

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Field `category` (Select) ada di section "Client Information" (`DataClientForm.php:48–81`) dan wajib diisi (`->required()`).
- Berisi opsi seperti FMCG, E-Commerce & Tech, Fintech & Banking, dll.

**Permintaan:**

- Hapus field `category` dari section Client Information. Kategori tidak lagi diperlukan di level client.

**To-Do:**

- [x] Hapus/sembunyikan field `Select::make('category')` di `DataClientForm.php` ketika client type agency
- [x] Pastikan tidak ada referensi `category` di tempat lain yang menjadi error (cek `DataClientResource`, table, filter)

---

### 1.2 Agency: Tambah Field Brand yang Di-handle (Multi Brand)

**Status:** 🟢 Selesai  
**Prioritas:** High

**Kondisi saat ini:**

- Ketika `type === 'agency'`, muncul section "Detail Agency" berisi repeater `pics` untuk daftar agency PIC (nama agency, PIC, email, WA, deskripsi).
- Saat ini repeater `pics` **mewakili** daftar agency, bukan daftar brand yang di-handle.
- Field `nama_brand` hanya muncul ketika `type === 'direct'`.

**Permintaan:**

- Jika `type === 'agency'`, field `parent_brand` digunakan sebagai label "Brand apa yang di-handle" dan bisa lebih dari 1 brand.
- Muncul field-field khusus untuk input brand yang di-handle oleh agency (nama brand, PIC brand, email brand, dst) — **field-nya samakan dengan field Direct Brand**.

**To-Do:**

- [x] Tambah repeater baru `agency_brands` (atau ubah repeater `pics`) untuk menampung daftar brand yang di-handle agency
- [x] Setiap item repeater berisi: `nama_brand`, `nama_pic`, `email`, `wa_number`, `description` — menyerupai field Direct Brand
- [x] Simpan data repeater sebagai JSON di kolom baru atau kolom `pics` yang sudah ada
- [x] Tambah kolom `agency_brands` (JSON, nullable) di migration `data_clients` jika perlu kolom terpisah
- [x] Update model `DataClient` → tambah cast `'agency_brands' => 'array'`

---

### 1.3 Instagram Tidak Wajib untuk Agency

**Status:** 🟢 Selesai  
**Prioritas:** Low

**Kondisi saat ini:**

- Field `instagram` (`DataClientForm.php:100–101`) bersifat `->required()` untuk **semua** tipe client.

**Permintaan:**

- Field `instagram` hanya wajib untuk `direct brand`, tidak wajib untuk `agency`.

**To-Do:**

- [x] Ubah validasi `instagram` menjadi `->required(fn(Get $get) => $get('type') === 'direct')`

---

### 1.4 Fix Error: Add Agency — `nama_brand` doesn't have default value

**Status:** 🟢 Selesai  
**Prioritas:** Critical (Bug)

**Kondisi saat ini:**

- Kolom `nama_brand` di tabel `data_clients` bersifat `NOT NULL` tanpa default value (migration line: `$table->string('nama_brand')`).
- Ketika create client bertipe `agency`, field `nama_brand` tidak ditampilkan dan tidak diisi → SQL error.

**Error SQL:**

```
SQLSTATE[HY000]: General error: 1364 Field 'nama_brand' doesn't have a default value
```

**To-Do:**

- [x] Ubah kolom `nama_brand` di migration menjadi `$table->string('nama_brand')->nullable()`
- [x] Alternatif: set default value pada model DataClient sebelum save ketika type === agency (misalnya pakai nama parent_brand atau agency pertama)
- [x] Pastikan form agency tetap bisa disimpan tanpa field `nama_brand`

---

### 1.5 Kategori Client Error dari Campaign / Sales Activity Tracker

**Status:** 🟢 Selesai (Tidak ada broken reference)  
**Prioritas:** Medium (Bug)

**Kondisi saat ini:**

- Terdapat error saat memilih kategori client dari halaman Sales Activity Tracker / Campaign.
- Kemungkinan terkait dengan penghapusan/perubahan field category di Modul 1.1.

**Hasil investigasi:**

- Semua referensi `category` di `BvSalesForm.php`, `SalesDashboard.php`, `DataClientKanban.php`, dan `ClientDemographyChart.php` sudah menggunakan null-safe operator (`??`).
- Kolom `category` tetap ada di database (nullable), hanya disembunyikan dari form untuk tipe agency.
- Tidak ditemukan broken reference — error asli kemungkinan terjadi sebelum field di-set nullable.

**To-Do:**

- [x] Investigasi error pada filter/referensi `category` di `SalesKanban.php` atau `BvSalesForm.php`
- [x] Sesuaikan atau hapus referensi `client.category` di semua resource yang menggunakannya
- [x] Pastikan setelah kategori dihapus, tidak ada broken reference

---

## Modul 2: Sales Activity Tracker

### 2.1 Tambah Filter di Sales Activity Tracker

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Halaman Sales Activity Tracker (`SalesKanban.php`) memiliki dua mode: List dan Kanban.
- Mode **list** sudah memiliki filter: `status`, `campaign_year`, `bv_sales_list_id` (Sales).
- Mode **kanban** filter dikomentari (line 109–121 di-comment out).

**Permintaan:**

- Tambahkan filter tambahan di mode list, dan aktifkan kembali filter di kanban.

**To-Do:**

- [x] Aktifkan kembali filter searchable dan filters di kanban board (`SalesKanban.php:109–121`)
- [x] Tambah filter tambahan yang relevan: `company_name`, `campaign_month`, `client_type`
- [x] Pertimbangkan menambah filter `sales_division` jika field tersebut sudah ditambahkan (lihat Modul 2.2)

---

### 2.2 Sales Structure Division untuk Detail Campaign

**Status:** 🔴 Belum dikerjakan  
**Prioritas:** Medium

**Kondisi saat ini:**

- Tidak ada field `division` di model `BvSales` atau form Sales Activity.
- Field `sales_division` digunakan untuk menentukan detail campaign yang meng-handle.

**Permintaan:**

- Tambahkan field `sales_division` untuk menentukan divisi yang handle campaign.

**To-Do:**

- [ ] Tambah kolom `sales_division` (string, nullable) di migration `bv_sales`
- [ ] Tambah field `Select::make('sales_division')` di `BvSalesForm.php` dengan opsi divisi yang relevan
- [ ] Tampilkan di tabel list view `SalesKanban.php`

---

## Modul 3: Media Plan Internal

### 3.1 Assign Tugas Brief Ke (PIC) — Multiple PIC

**Status:** 🟢 Selesai  
**Prioritas:** High

**Kondisi saat ini:**

- Field `pic_campaign_id` (`MediaPlanForm.php:246–252`) berupa `Select` single value.
- Hanya bisa assign 1 PIC Campaign/Sales.

**Permintaan:**

- PIC bisa multiple: 1 PIC utama + sub-PIC lebih dari 1.

**To-Do:**

- [x] Ubah field `pic_campaign_id` menjadi Select single (PIC Utama tetap 1)
- [x] Tambah field baru `sub_pic_campaign_ids` (Select multiple) untuk sub-PIC
- [x] Tambah kolom `sub_pic_campaign_ids` (JSON, nullable) di migration `media_plans`
- [x] Update model `MediaPlan` → tambah cast `'sub_pic_campaign_ids' => 'array'`

---

### 3.2 Tab Brief Setelah Campaign Information

**Status:** 🟢 Selesai  
**Prioritas:** Low

**Kondisi saat ini:**

- Wizard step "Brief" sudah ada di posisi ke-3 (setelah "Campaign Information" dan "Select KOL") di `MediaPlanForm.php:1275–1283`.
- Menampilkan view `media-plan-brief`.

**Permintaan:**

- Tab Brief harus berada **setelah Campaign Information** (posisi ke-2, sebelum Select KOL).

**To-Do:**

- [x] Pindahkan `Step::make('Brief')` ke posisi setelah `Step::make('Campaign Information')` dan sebelum `Step::make('Select KOL')`

---

### 3.3 Hapus Auto Margin di Media Plan Internal

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Di step "Margin Setting" (`MediaPlanForm.php:1285–1374`), terdapat opsi `margin_type` = `auto` yang menghitung margin otomatis berdasarkan Master Margin.
- Ada juga opsi `custom` untuk set margin manual.

**Permintaan:**

- Hapus opsi "Auto" dari margin. Margin selalu diinput manual (custom).

**To-Do:**

- [x] Hapus opsi `'auto' => 'Auto (Based on Budget Range)'` dari `ToggleButtons margin_type`
- [x] Set default `margin_type` ke `'custom'`
- [x] Ubah kolom `margin_type` di migration agar default-nya `'custom'`
- [x] Sesuaikan logika kalkulasi di `InternalBudgetForm.php` yang merujuk ke `margin_type === 'auto'`

---

### 3.4 Fix Error: Margin Custom — Column `kol_margins` Not Found

**Status:** 🟢 Selesai  
**Prioritas:** Critical (Bug)

**Kondisi saat ini:**

- Saat update media plan dengan margin custom, terjadi error karena kolom `kol_margins` tidak ada di tabel `media_plans`.

**Error SQL:**

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'kol_margins' in 'field list'
```

**Analisis:**

- Repeater `kol_margins` (`MediaPlanForm.php:1348–1369`) di-dehydrate ke form data tapi tidak ada kolom fisik di database.
- `CreateMediaPlan.php` sudah benar — unset `kol_margins` sebelum create.
- **`EditMediaPlan.php` TIDAK unset `kol_margins` sebelum save** → menyebabkan SQL error.

**To-Do:**

- [x] Tambah `unset($data['kol_margins'])` di `EditMediaPlan::mutateFormDataBeforeSave()`
- [x] Konsistenkan handling dengan `CreateMediaPlan.php` yang sudah melakukan unset

---

### 3.5 Plan Status (Planning, To Client, Ongoing)

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Kolom `status` di tabel `media_plans` hanya punya 2 opsi: `Planning` dan `Ongoing`.

**Permintaan:**

- Tambahkan status baru: `To Client` (di antara Planning dan Ongoing).

**To-Do:**

- [x] Ubah kolom `status` di migration dari `enum` ke `string` agar fleksibel menampung status baru
- [x] Tambah opsi `'To Client' => 'To Client'` di badge color, filter, dan action buttons
- [x] Tambah action "Send to Client" (Planning → To Client) dan update flow action buttons
- [x] Logika `tryActivateCampaign()` di model `MediaPlan` tetap pada status `Ongoing` (tidak perlu diubah)

---

### 3.6 Tambah Note Ketika Rate / Harga Berubah

**Status:** 🔴 Belum dikerjakan  
**Prioritas:** Mediumm

**Kondisi saat ini:**

- Field `notes` ada di KOL repeater, tapi tidak ada mekanisme log otomatis saat rate berubah.

**Permintaan:**

- Ketika rate/harga KOL berubah (di media plan internal), otomatis tambahkan catatan perubahan.

**To-Do:**

- [ ] Tambah observer atau `afterStateUpdated` pada field `rate` di KOL repeater yang mencatat perubahan harga ke field `notes`
- [ ] Format catatan: `"[tanggal] Rate diubah dari Rp X ke Rp Y"`
- [ ] Alternatif: buat kolom `rate_change_history` (JSON) di `media_plan_kols` untuk menyimpan riwayat

---

## Modul 4: Media Plan External (Internal Budget)

### 4.1 Generate Quotation di Media Plan External

**Status:** 🟢 Selesai  
**Prioritas:** High

**Kondisi saat ini:**

- Resource `BvQuotations` sudah ada tapi belum terintegrasi dengan flow media plan external.
- Di Internal Budget form tidak ada tombol/aksi untuk generate quotation.

**Permintaan:**

- Tambahkan fitur generate quotation dari media plan external (Internal Budget) section, ketika KOL di approve

**To-Do:**

- [x] Tambah action button "Generate Quotation" di halaman edit `InternalBudget` / `EditInternalBudget.php`
- [x] Quotation auto-generate berdasarkan data budget items (menggunakan `total_rounded`)
- [x] Gunakan `QuotationNumberGenerator` yang sudah ada
- [x] Tambah kolom `internal_budget_id` di `bv_quotations` untuk link ke budget sumbernya
- [x] Tambah tombol "View Quotation" otomatis muncul jika quotation sudah ada

---

### 4.2 Flow: Brief → KOL Listing → Media Plan Internal → External → Approve/Reject

**Status:** 🟢 Selesai  
**Prioritas:** High

**Kondisi saat ini:**

- Flow saat ini: Campaign dibuat di Sales Activity → Media Plan Internal dibuat → Internal Budget (External) dibuat manual terpisah.
- Belum ada mekanisme approve/reject di external yang terkoneksi ke internal.

**Permintaan:**

- Flow yang benar:
    1. **Brief** → diterima dari client
    2. **KOL Listing** → pilih KOL di media plan internal
    3. **Media Plan Internal** → isi SOW dan rate
    4. **Media Plan External** → sudah fix dari internal, tinggal review
    5. **Approve/Reject** → tombol action ada di external (Internal Budget)

**To-Do:**

- [x] Tambah tombol **Approve** dan **Reject** di halaman `EditInternalBudget.php`
- [x] Saat approve: update status internal budget → `approved`, trigger `tryActivateCampaign()`
- [x] Saat reject: update status → `rejected`, tambah field `rejection_notes`
- [x] Auto-generate data External Budget dari Internal Media Plan saat status berubah ke "To Client"

---

## Modul 5: KOL Database

### 5.1 Category KOL Bisa Lebih dari 1

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Field `category` di `DataKolForm.php:209–233` berupa `Select` single value dengan opsi statis.
- Tipe kolom `category` di DB: `string` (single value).

**Permintaan:**

- KOL bisa punya lebih dari 1 kategori.

**To-Do:**

- [x] Ubah `Select::make('category')` menjadi `Select::make('category')->multiple()` di semua form (DataKolForm, MediaPlanForm new KOL modal)
- [x] Ubah kolom `category` di migration `data_kols` dari `string` menjadi `json` nullable
- [x] Update model `DataKol` → tambah cast `'category' => 'array'`
- [x] Update query filter di `MediaPlanForm.php` dari `where('category', ...)` menjadi `whereJsonContains`
- [x] Update categories dropdown options → flatten JSON array values dari DB
- [x] Update display di `DataKolsTable.php`, `DataKolResource.php` global search
- [x] Wrap API auto-fill category ke array di semua scraping handlers

---

### 5.2 Additional Info KOL — Tambah Nama, Email, No WA

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Section "Additional Info" di `DataKolForm.php:294–309` hanya berisi: `contact` (email), `terakhir_update`, `notes`.
- Tidak ada field untuk nama lengkap KOL dan nomor WhatsApp.

**Permintaan:**

- Tambahkan field: **Nama** (nama asli KOL), **Email**, **No WA** di Additional Info.

**To-Do:**

- [x] Tambah kolom `full_name`, `email`, `wa_number` (string, nullable) di migration `data_kols`
- [x] Tambah field `TextInput::make('full_name')` → label "Nama Lengkap KOL" dengan icon user
- [x] Tambah field `TextInput::make('email')` → label "Email" dengan validasi email dan icon envelope
- [x] Tambah field `TextInput::make('wa_number')` → label "No WhatsApp" dengan validasi tel dan icon phone
- [x] Field `contact` (legacy) tetap dipertahankan — disabled, hanya tampil jika ada data lama
- [x] Update API auto-fill: `full_name` dari profile, `email` dari business_email, `wa_number` dari business_phone_number
- [x] Update di semua form: DataKolForm, ListDataKols auto-import, MediaPlanForm new KOL modal + create action

---

### 5.3 Rate Card Per Channel — Section Baru + Upload File

**Status:** 🟢 Selesai  
**Prioritas:** High

**Kondisi saat ini:**

- Field `rate_card` sudah ada di section "Rate Card & SOW" (`DataKolForm.php:37–50`) sebagai single numeric field.
- Hanya 1 rate card per KOL, tanpa pembagian per channel.
- Tidak ada opsi upload file rate card.

**Permintaan:**

- Rate card dibuat **section baru per channel** (sesuai channel yang dimiliki KOL).
- Ada opsi **upload file** atau **input manual** per field.
- Rate card per SOW sudah dimasukkan di awal saat tambah KOL baru.
- Ada **historikal rate card** (riwayat perubahan rate).
- Saat tambah KOL baru, input rate card **tidak wajib**.

**To-Do:**

- [x] Ubah field `rate_card` menjadi repeater `rateCards` yang berisi: `channel`, `sow`, `rate`, `valid_from`, `notes`
- [x] Tambah field `FileUpload` untuk upload file rate card (PDF/image, maks 5MB)
- [x] Buat tabel baru `kol_rate_cards` (id, data_kol_id, channel, sow, rate, file_path, valid_from, notes, timestamps)
- [x] Buat model `KolRateCard` dan tambah relasi `hasMany('rateCards')` di model `DataKol`
- [x] Implementasi historikal: setiap entry rate card tersimpan sebagai record terpisah, di-order by `valid_from` desc
- [x] Rate card tidak wajib (defaultItems: 0, semua field nullable kecuali channel)

---

## Modul 6: Campaign Ongoing (Campaign Tracker)

### 6.1 Campaign Tracker = Campaign Ongoing External

**Status:** 🟢 Selesai  
**Prioritas:** Medium

**Kondisi saat ini:**

- Resource `BvCampigns` (Campaign Ongoing) sudah ada dan muncul otomatis saat campaign live. tapi rename dengan (campaign ongoing internal)
- Buatkan lagi model dan resource (camapign ongoing external) isinya sama kaya campaign on oging internal, bedanya yang external bisa di view untuk external, saja, mungin buatkan page khusus di frontend

**Permintaan:**

- "Campaign Ongoing External" — Buatkan lagi model dan resource (camapign ongoing external) isinya sama kaya campaign on oging internal, bedanya yang external bisa di view untuk external, saja, mungin buatkan page khusus di frontend

**Pendekatan implementasi:**

- Tidak membuat model/resource baru yang redundan — data tetap di satu tabel `bv_campaigns`
- Akses external dikontrol via `public_token` (unique random string) + flag `is_public`
- Admin generate link dari tombol di halaman edit campaign; link bisa dicabut kapan saja

**To-Do:**

- [x] Rename label navigasi dari "Campaign Ongoing" ke "Campaign Ongoing Internal" (`BvCampignResource.php`)
- [x] Tambah kolom `public_token` dan `is_public` ke tabel `bv_campaigns` (migration baru)
- [x] Tambah method `generatePublicToken()` dan `revokePublicToken()` di model `BvCampign`
- [x] Buat resource Filament baru `CampaignExternalResource` — menu "Campaign Ongoing External" di sidebar
- [x] Resource external: list campaign + aksi generate/copy/revoke link per record, dan halaman View detail
- [x] Buat `CampaignPublicController` — serve halaman publik berdasarkan token
- [x] Tambah route `GET /campaign/{token}` → `campaign.public` (no auth required)
- [x] Buat view `resources/views/campaign/public.blade.php` — halaman tracking untuk client
---

### 6.2 Followers di Ongoing — Dikomentari Saja

**Status:** 🟢 Selesai  
**Prioritas:** Low

**Kondisi saat ini:**

- Terdapat kolom `followers` di tabel campaign ongoing KOL.

**Permintaan:**

- Kolom `followers` di campaign ongoing dikomentari (di-hide/non-aktifkan), tidak ditampilkan ke user.

**To-Do:**

- [x] Sembunyikan kolom/field `followers` di resource Campaign Ongoing (comment out, bukan hapus)

---

## Modul 7: Import CSV KOL Database

### 7.1 Data Terbalik Saat Import CSV (Sorting Z-A atau A-Z)

**Status:** 🟢 Selesai  
**Prioritas:** Medium (Bug)

**Kondisi saat ini:**

- Fungsi import CSV (`MediaPlanForm.php:1498–1626`) membaca file CSV baris per baris dan meng-append ke array existing KOLs.
- Tidak ada mekanisme sorting eksplisit setelah import.
- Kemungkinan data tampil terbalik karena urutan baris CSV atau penambahan `row_number`.

**Permintaan:**

- Pastikan data yang diimport tampil dengan urutan yang benar (A-Z sesuai urutan di CSV).

**To-Do:**

- [x] Review logika `importKolsFromCsv()` — pastikan `row_number` increment sesuai urutan baris CSV
- [x] Tambah sorting eksplisit setelah import jika diperlukan
- [x] Test dengan file CSV untuk memastikan urutan benar

---

## Ringkasan Todo per Prioritas

### 🔴 Critical (Bug Fix)

| #   | Modul       | Deskripsi                                                         | File Terkait                                   |
| --- | ----------- | ----------------------------------------------------------------- | ---------------------------------------------- |
| 1   | Data Client | Fix error `nama_brand` doesn't have default value saat add agency | `DataClientForm.php`, migration `data_clients` |
| 2   | Media Plan  | Fix error `kol_margins` column not found                          | `MediaPlanForm.php`, migration `media_plans`   |

### 🟠 High Priority

| #   | Modul               | Deskripsi                                                  | File Terkait                                    |
| --- | ------------------- | ---------------------------------------------------------- | ----------------------------------------------- |
| 3   | Data Client         | ✅ Agency: tambah field brand yang di-handle (multi brand) | `DataClientForm.php`, migration                 |
| 4   | Media Plan          | ✅ PIC Campaign/Sales bisa multiple                        | `MediaPlanForm.php`, migration `media_plans`    |
| 5   | Media Plan External | ✅ Generate Quotation dari external                        | `EditInternalBudget.php`, `BvQuotationResource` |
| 6   | Media Plan          | ✅ Flow Brief → KOL → Internal → External + Approve/Reject    | `EditInternalBudget.php`, `MediaPlan.php`       |
| 7   | KOL Database        | ✅ Rate Card per channel + upload file + historikal        | `DataKolForm.php`, migration baru               |

### 🟡 Medium Priority

| #   | Modul          | Deskripsi                                     | File Terkait                                  |
| --- | -------------- | --------------------------------------------- | --------------------------------------------- |
| 8   | Data Client    | ✅ Hapus field kategori di Client Information | `DataClientForm.php`                          |
| 9   | Data Client    | Fix kategori client error dari Sales Activity | `SalesKanban.php`                             |
| 10  | Sales Activity | Tambah filter di Sales Activity Tracker       | `SalesKanban.php`                             |
| 11  | Sales Activity | Sales Structure Division                      | `BvSalesForm.php`, migration `bv_sales`       |
| 12  | Media Plan     | ✅ Hapus auto margin, selalu custom           | `MediaPlanForm.php`, `InternalBudgetForm.php` |
| 13  | Media Plan     | Plan Status tambah "To Client"                | Migration `media_plans`                       |
| 14  | Media Plan     | Note otomatis saat rate berubah               | `MediaPlanForm.php`                           |
| 15  | KOL Database   | Category KOL bisa lebih dari 1                | `DataKolForm.php`, migration `data_kols`      |
| 16  | KOL Database   | Additional Info: nama, email, WA              | `DataKolForm.php`, migration `data_kols`      |
| 17  | Import CSV     | ✅ Fix data terbalik saat import              | `MediaPlanForm.php`                           |

### 🟢 Low Priority

| #   | Modul            | Deskripsi                                              | File Terkait              |
| --- | ---------------- | ------------------------------------------------------ | ------------------------- |
| 18  | Data Client      | Instagram tidak wajib untuk agency                     | `DataClientForm.php`      |
| 19  | Media Plan       | ✅ Pindahkan tab Brief ke posisi setelah Campaign Info | `MediaPlanForm.php`       |
| 20  | Campaign Ongoing | Rename ke "Campaign Tracker"                           | `BvCampignResource.php`   |
| 21  | Campaign Ongoing | ✅ Hide kolom followers                                | Resource Campaign Ongoing |

---

## Catatan Teknis

### File yang Paling Banyak Terdampak

1. **`DataClientForm.php`** — Modul 1 (4 perubahan)
2. **`MediaPlanForm.php`** — Modul 3 (5 perubahan)
3. **`DataKolForm.php`** — Modul 5 (3 perubahan)
4. **`InternalBudgetForm.php`** — Modul 4 (2 perubahan)
5. **`SalesKanban.php`** — Modul 2 (2 perubahan)

### Migration yang Perlu Diubah

1. `create_data_clients_table.php` — kolom `nama_brand` → nullable, tambah `agency_brands`
2. `create_media_plans_table.php` — tambah `kol_margins`, `sub_pic_campaign_ids`, ubah enum `status`
3. `create_data_kols_table.php` — ubah `category` ke JSON, tambah `full_name`, `wa_number`
4. Buat migration baru: `create_kol_rate_cards_table.php`
5. Tambah kolom `sales_division` di migration `bv_sales`
