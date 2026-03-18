# 🧪 Panduan Quality Control (QC) Revisi Sistem BV

Dokumen ini berisi langkah-langkah pengujian untuk tim QC guna memastikan semua revisi yang diminta oleh klien telah diimplementasikan dengan benar dan berfungsi sebagaimana mestinya.

---

### 📌 1. Target All Gross Profit (Bulanan, Quarter, Tahunan) & Target Per Sales
**Tujuan QC:** Memastikan perhitungan akumulasi quarter dan tahunan berjalan, hak akses sesuai, dan modul target sales tersedia.

*   **Langkah Pengujian:**
    1. Login sebagai user dengan role `Super Admin`, `C Level`, atau `Finance`.
    2. Buka menu **Finance > Target Gross Profit**.
    3. Input target bulanan baru (misal: Tahun 2026, Bulan 1). Simpan.
    4. Input bulan lain di quarter yang sama (Bulan 2 dan 3).
    5. Perhatikan pada tabel list, kolom **Target Quarter** dan **Target Tahunan**. Nilainya harus otomatis mengakumulasi (menjumlahkan) target bulanan yang ada pada Quarter/Tahun tersebut.
    6. Buka menu **Master Data > Sales Targets** (jika ada di navigasi) untuk memvalidasi bahwa "rumah" input Target Per Sales sudah tersedia.

---

### 📌 2. Fitur Client Type (Direct/Agency) & Detail Multiple Agency
**Tujuan QC:** Menguji apakah input multiple agency berhasil untuk semua tipe klien dan bisa ditampilkan di Kanban Card.

*   **Langkah Pengujian (Data Client):**
    1. Buka menu **Database > Data Clients**.
    2. Klik **Create Client** atau edit client yang sudah ada.
    3. Pilih *Client Type* = `Direct Brand`.
    4. Perhatikan field **Daftar Agency**. Masukkan beberapa nama agency dengan mengetik lalu menekan `Enter` (karena berupa tags input).
    5. Simpan dan cek di tabel `Data Clients`, kolom Agency harus memunculkan daftar agency yang digabungkan dengan koma.
*   **Langkah Pengujian (Kanban Card):**
    1. Buat **Sales Activity** baru yang terkait dengan client yang barusan diedit/dibuat.
    2. Buka menu **Sales Kanban** (Board view).
    3. Lihat *card* dari sales activity tersebut. Di bagian bawah card harus ada badge tipe client (warna cyan `Direct` atau amber `Agency`) diikuti dengan nama-nama agencynya jika ada.

---

### 📌 3. Informasi PIC di Data Client
**Tujuan QC:** Memastikan pemisahan PIC Internal (Sales) dan dinamisnya form PIC eksternal.

*   **Langkah Pengujian:**
    1. Buka **Database > Data Clients**, buat/edit client.
    2. Cek bagian `PIC Internal (Sales)`. Harus menampilkan dropdown daftar sales internal.
    3. Ganti tipe klien ke `Direct Brand`. Form di bawahnya harus meminta 1 form PIC statis (Nama, Jabatan, Email).
    4. Ganti tipe klien ke `Agency`. Form di bawahnya harus berubah menjadi **Repeater** (Bisa ditambah lebih dari 1 PIC Agency).
    5. Simpan dan pastikan tersimpan sempurna.

---

### 📌 4. Widget Report BD Manager & Kolom History Campaign Client
**Tujuan QC:** Validasi keberadaan widget dan pop-up history campaign di database client.

*   **Langkah Pengujian:**
    1. Buka **Dashboard** utama, cek ketersediaan widget laporan `BD Manager`.
    2. Buka menu **Data Clients**.
    3. Lihat kolom **Total Campaigns** (biasanya berupa angka dengan *badge* abu-abu).
    4. Klik baris/badge tersebut, pop-up/modal berisi detail *history* campaign client tersebut harus muncul.

---

### 📌 5. Pembuatan Campaign Baru (Validasi Form & Automasi)
**Tujuan QC:** Memastikan field di form campaign sesuai revisi, serta integrasi auto-create ke Media Plan dan notifikasi berjalan.

*   **Langkah Pengujian Form:**
    1. Buka menu **Campaign Area > Campaign Ongoing**. Klik **Create**.
    2. Pastikan field **Margin** TIDAK ADA (sudah dihapus).
    3. Cek struktur form: kombinasi `Campaign Month`, `Campaign Date`, `Start Date`, `End Date` semuanya tersedia.
    4. Cek kedekatan layout field `Close Date` dan `Deal Value`. Mereka harus bersebelahan/satu barisan.
    5. Cek field `Brief Received Date` dan `PIC Media Plan` (tersedia).
    6. Pada Step 1 form, cek dropdown **Form Brief**. Harus bisa memilih Form Brief klien.
*   **Langkah Pengujian Automasi (Media Plan Internal & Notif):**
    1. Isi lengkap form Create Campaign tersebut. (Pilih channel `Instagram` untuk memicu trigger "Influencer").
    2. Klik Submit/Simpan.
    3. **(Penting - Automasi Media Plan):** Pindah ke menu **Media Plans > Media Plan Internal**.
    4. Cek apakah ada record baru otomatis dengan `Campaign Name` yang barusan Anda buat.
    5. Buka (Edit) Media Plan tersebut. Status sistem anggarannya (Internal Budget) harus berada pada mode "Draft".
    6. **(Penting - Automasi Notifikasi):** Pastikan mengecek *log error laravel* ([storage/logs/laravel.log](file:///Volumes/DATA/Dev/bv-app/storage/logs/laravel.log)) untuk memastikan tidak ada pesan error notifikasi, atau jika layanan email/WA disetel aktif di test-server, pastikan pesan terkirim.

---

### 📌 6. Child Status Media Plan Internal (Planning & Ongoing) & Assign Tugas Brief
**Tujuan QC:** Menguji fitur assignment PIC Campaign dan tabbed view Planning vs Ongoing.

*   **Langkah Pengujian:**
    1. Buka menu **Media Plans > Media Plan Internal**.
    2. Di atas tabel, pastikan ada **Tab (Semua, Planning, Ongoing)** untuk filter cepat.
    3. Awalnya, Media Plan baru yang ter-*auto create* akan masuk ke tab **Planning**.
    4. Pada setiap baris data di tabel, klik tombol panah kanan *(Action: Mark as Ongoing)*.
    5. Tabel akan me-refresh dan state data akan berpindah ke tab **Ongoing**.
    6. Di halaman edit Media Plan (atau modal create), periksa pada *Step 1: Campaign Information*. Harus ada dropdown **Assign Tugas Brief Ke (PIC Campaign/Sales)** yang berisi daftar anggota sales untuk penugasan.

---

### 📌 7. Kanban View Notion Style
**Tujuan QC:** Validasi library Flowforge (Notion board) bekerja mulus.

*   **Langkah Pengujian:**
    1. Buka menu **Sales Kanban**.
    2. Geser / _Drag and drop_ kartu antar kolom status.
    3. Pastikan tidak ada _error javascript_ saat *drop* kartu (warna berubah, posisi tersimpan).
    4. *Klik* salah satu card. Modal detil (Edit Sales/View) harus muncul layaknya sistem Notion.

---

**Selesai.** Pastikan melakukan QC menggunakan *Browser* cache terbersih (Hard Refresh) `Ctrl + Shift + R` atau `Cmd + Shift + R` untuk menghindari cache *component Livewire/Filament*.
