# Revisi BV - 5 April 2026

## Modul 1: Data Client

### Field & Validasi
- [x] Field **Term of Payment** (hari) tidak wajib diisi
- [x] Field **nama brand** ditampilkan/aktif ketika tipe client adalah "agency"
- [x] Field **website** dan **parent brand** tidak wajib ketika tipe client adalah "agency"
- [x] **Daftar Agency** dihapus dari tampilan ketika tipe client adalah "direct brand"

### Modal Slide Over Daftar Agency
- [x] Field wajib di modal agency: nama agency, nama PIC agency, alamat email agency, no WhatsApp

### PIC Client
- [x] Tambah field PIC client (saat ini belum ada) ketika tipe client adalah "agency"
- [x] PIC client dibuat **add more** (bisa lebih dari satu), berlaku untuk agency maupun direct brand
- [x] Tambah **PIC Leads** di PIC client — tidak wajib, berlaku untuk direct brand & agency

### Tracking & Catatan
- [x] Section Tracking & Catatan: status outreach menggunakan **status campaign**

---

## Modul 2: Create Campaign

- [x] Hapus field **Business Director**
- [x] Hapus field **PIC Media Plan / Internal**
- [x] Hapus field **Tanggal Campaign**
- [x] Rename field "Tanggal Dapat Brief" → **Date of Brief**
- [x] Tambah **label tipe client** di samping field Company Name

---

## Modul 3: Detail Assign / Media Plan

- [ ] **PIC Tim Kreatif & Tim KOL** (di menu campaign information / media plan external) bisa diisi
  lebih dari 2 orang
- [ ] Ketika client butuh proposal dan di-assign ke Tim Kreatif: saat edit media plan, tersedia
  upload link hasil kerja Tim Kreatif untuk update progres → hasil upload muncul di kanban
- [ ] Ketika client approve media plan external hanya sebagian KOL (contoh: 1 dari 5 penawaran):
  tambah tombol **Approval** dan **Edit** pada KOL yang dipilih

---

## Modul 4: KOL & Channel

### List Channel
- [ ] Channel yang tersedia: **Instagram, TikTok, Threads, YouTube, Facebook, Talent, X**

### Urutan Field KOL Information (KOL External)
- [ ] Urutan field: **Username → Channel → Kategori**

### Tambah KOL Baru
- [ ] Saat tambah KOL baru, langsung masukkan list channel-nya — proses scraping dilakukan
  satu per satu setelahnya

### Rate Card & SOW
- [ ] Tambah **Rate Card per channel** di card section KOL baru / database KOL
- [ ] **SOW dipindah ke bagian atas** form

---

## Modul 5: Nego & Payment (KOL)

- [ ] Tambah field **"After Nego"** — real cost setelah negosiasi
- [ ] Jadwal payment: **Jumat Week 1 & Week 3** setiap bulan, dibayarkan setelah KOL posting

---

## Modul 6: Kanban

- [x] Rename status **"Negotiation"** → **"Negotiation/Submit"**
- [x] Klik card kanban → tampilkan **summary progres** di atas, dan progres media plan
  → klik progres media plan diarahkan ke halaman media plan campaign terkait
- [x] Tambah **icon pensil kecil** di card kanban untuk edit data campaign langsung dari kanban
- [x] Tambah input **progres meeting** di card kanban (setelah selesai meeting)
- [x] Ketika campaign **live**: tersedia upload **Quotation Sign**

---

## Modul 7: Campaign Ongoing & SPK

- [x] Data campaign ongoing **muncul otomatis** ketika campaign live di kanban
  (syarat: Quotation Sign sudah diupload)
- [x] Halaman campaign ongoing berisi:
  - Summary campaign
  - Brief summary terbaru
  - Upload brief dari client
  - View PDF brief
- [x] **Media platform** terisi otomatis berdasarkan planning campaign saat campaign live
- [x] **Campaign area** hanya menampilkan: **Campaign Ongoing** dan **SPK**
- [x] Hapus menu model & migrasi **Upcoming Campaign**
- [x] Hapus menu model & migrasi **Tracker Progres KOL**

---

## Modul 8: Kontrak

- [ ] Kontrak bisa dibuat **per campaign** atau **per client**
- [ ] Referensi contoh kontrak:
  https://docs.google.com/document/d/16EMYEcjNMVigztcEKLpHrnFJx-At76G-kfKh7n2ekB8/edit?usp=sharing

---

## Modul 9: Target Sales & Target Company

- [x] Tampilan dibuat dalam bentuk **tabel** untuk mendukung bulk editing

---

## Modul 10: Master Data

- [x] Pindah **menu Form Brief** ke Master Data
