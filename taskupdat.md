# Task Update - Revisi QA BV System

> Dibuat: 28 Februari 2026 | Sumber: Catatan QA Tim

---

## Modul 1: Target & Gross Profit

- [x] **[GP-01]** Buat halaman/form untuk input Target All Gross Profit (bulanan) — hanya bisa diedit oleh Super Admin, C Level & Finance
- [x] **[GP-02]** Target Quarter otomatis terupdate (akumulasi) ketika target bulanan diubah
- [x] **[GP-03]** Target Tahunan otomatis terupdate (akumulasi) ketika target bulanan diubah
- [x] **[GP-04]** Tampilkan summary Target GP Bulanan, Quarter, dan Tahunan di dashboard/widget

---

## Modul 2: Target Per Sales

- [x] **[TS-01]** Buat struktur awal (rumah/scaffold) modul Target Per Sales — Resource, Model, Migration
- [x] **[TS-02]** Tambahkan relasi ke tabel Sales/Employee
- [x] **[TS-03]** Form input target per sales (minumum: periode, nilai target)

---

## Modul 3: Database Client (`DataClients`)

- [x] **[DC-01]** Tambahkan field **Client Type** dengan pilihan `Agency` / `Direct`
- [x] **[DC-02]** Ketika Client Type = `Agency`, tampilkan field tambahan untuk memilih/menambah nama Agency
- [x] **[DC-03]** Ganti field `PIC Internal` menjadi dua field terpisah:
    - `PIC Internal (Sales)`
    - `PIC sesuai Client Type` (misal: PIC Agency atau PIC Direct)
- [x] **[DC-04]** Tambahkan kolom **Campaign** di tabel Database Client (seperti di dashboard)
- [x] **[DC-05]** Ketika kolom Campaign diklik, tampilkan list campaign yang dimiliki client tersebut (modal/panel)

---

## Modul 4: Campaign (`BvCampigns`)

- [x] **[CP-01]** Tambahkan field **Client Type** (`Agency` / `Direct`) di form Create Campaign — tampilkan Agency yang sudah ditambahkan (ambil dari data client ketika klik tambah, jika existing tinggal pilih data yang ada saja)
- [x] **[CP-02]** **Hapus field Margin** dari form Create Campaign (margin dipindah ke Media Plan Internal yang sudah ada saat ini)
- [x] **[CP-03]** Tambahkan field **Bulan Campaign** dan **Tanggal Campaign**
- [x] **[CP-04]** Pindahkan posisi **Close Date** berdekatan dengan field **Deal Value**
- [x] **[CP-05]** Tambahkan field **Tanggal Dapat Brief** saat membuat campaign baru
- [x] **[CP-06]** Tambahkan field **PIC** di section Media Plan Internal pada form campaign
- [x] **[CP-07]** Ketika campaign berhasil dibuat, kirim **notifikasi Email & WhatsApp** (buat service whatsapp dulu saja):
    - Jika campaign item = `Influencer` → kirim ke **Manager KOL** & **Email Grup**
    - Jika campaign item = `Social Media` → kirim ke **Media Creative**

---

## Modul 5: Kanban View (`BvCampignUpcomings` / Kanban)

- [x] **[KB-01]** Tambahkan field **Client Type** (`Agency` / `Direct`) di card Kanban
- [x] **[KB-02]** Tampilkan nama Agency di card jika Client Type = Agency
- [x] **[KB-03]** Redesign tampilan card Kanban menyerupai gaya **Notion** (clean, minimalis, informatif)

---

## Modul 6: Media Plan Internal (`MediaPlans` / `InternalBudgets`)

- [ ] **[MP-01]** Ketika campaign baru dibuat, otomatis muncul/terbuat entri di **Media Plan Internal**
- [ ] **[MP-02]** Set field **Margin** di Media Plan Internal (setelah dihapus dari form Campaign)
- [ ] **[MP-03]** Tambahkan fitur **Assign Tugas Brief** di Media Plan Internal untuk PIC Campaign
- [ ] **[MP-04]** Buat **Child Media Plan Internal** dengan dua sub-status:
    - `Planning`
    - `Ongoing`

---

## Modul 7: Report Widget (BD Manager)

- [x] **[RW-01]** Buat report widget yang menampilkan data berdasarkan **BD Manager**
- [x] **[RW-02]** Tentukan metrik yang ditampilkan: jumlah campaign, total deal value, gross profit, dll.
- [x] **[RW-03]** Tambahkan filter periode (bulanan/quarter/tahunan) pada widget

---

## Modul 8: Form Brief (Modul Baru) ⚠️ _Nunggu format baru_

- [x] **[FB-01]** Buat modul **Form Brief** — scaffold Resource, Model, Migration
- [x] **[FB-02]** Buat tampilan form brief untuk **Client** (public/portal)
- [x] **[FB-03]** Buat tampilan form brief untuk **Admin Panel** (internal)
- [x] **[FB-04]** Integrasi ke Create Campaign — tambahkan field untuk **select Form Brief** yang sudah ada
- [ ] **[FB-05]** _(Hold)_ Tunggu format/template form brief baru dari tim

---

## Legenda Status

| Simbol | Keterangan                        |
| ------ | --------------------------------- |
| `[ ]`  | Belum dikerjakan                  |
| `[~]`  | Sedang dikerjakan                 |
| `[x]`  | Selesai                           |
| ⚠️     | Ada dependensi / perlu konfirmasi |

---

> **Catatan malam ini:** Prioritaskan modul Campaign (CP-01 s/d CP-06), Database Client (DC-01 s/d DC-05), dan Kanban (KB-01 s/d KB-03) terlebih dahulu.
