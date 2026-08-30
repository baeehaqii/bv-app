# Migrasi Data (Spreadsheet → BV App)

Menarik data dari Google Spreadsheet privat ke BV App. Tiga jenis data yang
didukung, dipilih lewat dropdown **Jenis data** di halaman migrasi:

| Jenis | Tab bawaan | Masuk ke |
|---|---|---|
| Data Client | Pipeline | `data_clients` (Database Client) |
| Sales Activity Tracker | Pipeline | `bv_sales` |
| Campaign | Campaigns | `bv_campaigns` (Campaign Ongoing Internal) |
| KOL List | (pilih tab tier) | `media_plan_kols` (Media Plan Internal) |

Profil Sales Activity Tracker dan Campaign masing-masing melayani DUA bentuk
sheet: sheet Pipeline/Campaigns yang lama, dan sheet `(KOL) Project - Planning`
/ `(AM) Project - Running` yang judul kolomnya berbeda. Alias judulnya digabung
dalam satu profil karena tabel tujuannya sama.

**Pull, bukan push**: Laravel yang membaca sheet-nya sendiri lewat service
account, jadi tidak perlu Apps Script yang ditempel di tiap file.

Diporting dari service migrasi SOP Siproper
(`docs/sync/migrasi-sop-service.md` di project itu).

## Komponen

| Berkas | Peran |
|---|---|
| `app/Service/GoogleSheetReader.php` | Baca sheet privat via service account |
| `app/Service/SheetMigration.php` | Dasar bersama: peta judul kolom, parse, pembersih nilai |
| `app/Service/ClientSheetMigration.php` | Profil Data Client |
| `app/Service/PipelineSheetMigration.php` | Profil Pipeline → BvSales |
| `app/Service/CampaignSheetMigration.php` | Profil Campaign → BvCampign |
| `app/Filament/Pages/MigrasiData.php` + view | UI: preview & migrasi per-chunk |

Alur: **Sheet → GoogleSheetReader (baris 0-based) → parseRows (item) → persist (upsert)**.

## Setup service account (sekali saja)

1. Google Cloud Console → buat **Service Account** → buat **JSON key**.
2. Aktifkan **Google Sheets API** pada project tersebut. Aktifkan juga **Google
   Drive API** bila ingin memakai mode "pilih dari daftar" — Sheets API saja
   cukup untuk mode tempel link.
3. Simpan JSON ke `storage/app/google/service-account.json`.
   Foldernya sudah masuk `.gitignore` — **jangan** di-commit.
4. Share spreadsheet-nya ke `client_email` yang ada di dalam JSON, minimal
   **Viewer**. Untuk banyak sheet pilih salah satu cara sekali-jalan:
   share **satu folder induk** (semua isinya ikut terbaca), pindahkan ke
   **Shared Drive** lalu tambahkan `client_email` sebagai member, atau pakai
   **Domain-Wide Delegation** lewat `GOOGLE_IMPERSONATE_EMAIL`.

   Daftar spreadsheet di-cache 10 menit; tombol **Muat Ulang Daftar** memaksa
   ambil ulang setelah ada file baru di-share.

Override path hanya bila perlu, pakai path absolut. Biarkan tetap dikomentari
untuk memakai default — kalau diisi kosong, nilai kosongnya yang menang:

```
#GOOGLE_SERVICE_ACCOUNT=/var/www/bv-app/storage/app/google/service-account.json
#GOOGLE_IMPERSONATE_EMAIL=akun@domain-workspace.com
```

`GOOGLE_IMPERSONATE_EMAIL` hanya untuk Domain-Wide Delegation (service account
menyamar jadi user Workspace → baca semua file miliknya tanpa share apa pun).
Perlu di-authorize dulu di Admin Console → Security → API Controls →
Domain-wide Delegation, dengan scope `.../auth/spreadsheets.readonly`.

> Selama kredensialnya belum ada, menu **Migrasi Data** tidak muncul di
> sidebar — halamannya cuma bisa menampilkan error, jadi tidak ada gunanya.

## Cara pakai

1. Panel → **Sales → Migrasi Data**.
2. Pilih **Sumber spreadsheet**:
   - **Pilih dari yang di-share** — dropdown berisi semua spreadsheet yang bisa
     dibaca service account (hasil share per-file, share folder, atau Shared
     Drive). Butuh **Google Drive API** aktif, bukan cuma Sheets API.
   - **Tempel link** — tempel URL Google Sheets-nya langsung. Tidak butuh Drive API.
3. Pilih **Jenis data**. Daftar tab terisi otomatis (sekalian menguji akses).
3. **Preview Data** → tabel baris, jumlah baris terbaca, dan daftar kolom sheet
   yang tidak dikenali (kolom itu tidak ikut dimigrasi).
4. **Migrasi Sekarang** → progress bar jalan per chunk (25 baris/request).
   **Jangan tutup tab** selama proses berjalan.

## Pemetaan kolom

Lewat **judul kolom**, bukan huruf kolom tetap — urutan kolom paling sering
berubah. Besar-kecil huruf dan tanda baca diabaikan.

Baris judulnya **dicari sendiri**, tidak diasumsikan baris pertama: banyak sheet
diawali judul besar, baris TOTAL, dan catatan "*Di isi oleh BD". Yang dipakai
adalah baris dengan kolom terkenali terbanyak dalam 12 baris pertama.

Satu field hanya boleh diisi SATU kolom, yang paling kiri. Ini penting karena
judul yang berbeda bisa menyusut jadi sama setelah tanda baca dibuang — tanda
`%` karena itu diubah jadi kata "persen", supaya "Projected Nett Margin" (rupiah)
dan "Projected Nett Margin %" tetap dua kolom berbeda. Daftar alias
lengkapnya ada di `ClientSheetMigration::ALIASES`; menambah alias baru cukup
menambah satu string di sana.

Minimal harus ada kolom **Nama Brand**. Kalau tidak ada judul yang dikenali
sama sekali, preview menolak dengan pesan yang jelas.

## Aturan yang perlu diketahui

- **Idempoten.** Kunci baris = `nama_brand` + `type`, sama dengan import CSV
  (`DataClientImporter::resolveRecord()`). Menjalankan ulang memperbarui baris
  yang sama, bukan menggandakan.
- **Sel kosong tidak menimpa.** Sheet sering hanya mengisi sebagian kolom;
  migrasi ulang tidak boleh mengosongkan data yang sudah ada.
- **Tanggal** dibaca sebagai serial Google (`UNFORMATTED_VALUE` +
  `SERIAL_NUMBER`), bukan teks — "01/02/2026" ambigu antara 1 Februari dan
  2 Januari. Sel rusak (angka telanjang, tahun di luar 2000–2100) jadi `null`.
- **Sales / agency yang tidak ada di master** tidak menggagalkan baris; barisnya
  tetap tersimpan dan dicatat di log migrasi.
- **Urutan agency.** Kolom "Dihandel Agency" hanya nyambung kalau baris agency-nya
  sudah ada. Migrasikan sheet agency dulu, atau jalankan ulang setelahnya —
  aman karena idempoten.

## Tanpa queue

Item hasil parse disimpan di **cache** (TTL 30 menit), lalu Alpine memanggil
`processChunk()` berulang; tiap panggilan satu request HTTP pendek. Server
produksi tidak menjalankan worker, dan satu request panjang untuk ratusan baris
pasti kena timeout.

## Aturan khas tiap profil

**Data Client** — kunci baris `nama_brand` + `type`. Kolom "Brand / Agency" berisi
"Direct" atau nama agency yang menangani brand itu; agency-nya dibuat lalu brand
didaftarkan ke `agency_brands` milik agency (itu sumber relasinya di app ini,
lihat `DataClient::syncAgencyBrands()`). Satu brand yang muncul di banyak bulan
menyimpan bulan **paling awal**.

**Sales Activity Tracker** — kunci baris nama campaign + client. Nama PIC di
sheet dipetakan lewat `BvSalesList::ALIAS` (mis. "Sita" dan "Gress" → Gressita,
"Febi" → Febby); tanpa itu tiap ejaan jadi baris sales sendiri dan laporan
per-PIC-nya pecah. `BvSales.margin` menyimpan PERSEN, jadi yang dibaca kolom
"%" — bukan kolom rupiah di sebelahnya. Kolom Stage/Status dipetakan ke
`SalesStatus`; nilai yang tidak dikenali dibiarkan null supaya tidak mendarat di
kolom kanban yang salah. Perlu diingat `BvSales` punya boot hook: perubahan status
ke Briefing/Campaign Live memicu pembuatan FormBrief, Media Plan, atau Campaign.
Hook itu hanya berjalan saat UPDATE, jadi migrasi pertama aman — tapi migrasi ulang
yang mengubah status bisa memicunya.

**KOL List** — bentuk sheet-nya berbeda dari yang lain: **satu KOL menempati
beberapa baris**. Baris pertama berisi identitas KOL plus scope of work
pertamanya; baris di bawahnya hanya SOW tambahan dengan kolom identitas kosong.
Jadi baris tanpa username bukan baris kosong — ia digabung ke KOL di atasnya.
`qty` dan `rate` KOL adalah JUMLAH seluruh SOW-nya, karena sheet menaruh
angkanya per baris SOW. Tiap tier (Nano/Micro/Macro/Homeless Media) satu tab
dengan susunan kolom yang sama, jadi tab-nya dipilih manual dan dijalankan
bergantian. Wajib memilih **Media Plan tujuan** dulu; tanpa itu tidak ada yang
disimpan. Kunci baris = nama + channel.

Tombol pintasnya ada di header tabel **Media Plan Internal** ("Migrasi KOL dari
Spreadsheet") — membuka halaman ini dengan jenisnya sudah terpilih.

**Campaign** — kunci baris nama campaign + client. Client yang belum ada dibuat,
tapi nama yang cuma beda tipis dari client yang sudah ada (mis. "ITDC - Injouney"
vs "ITDC - Injourney") DILAPORKAN sebagai kemungkinan salah ketik, tidak digabung
diam-diam. Ambang kemiripannya sengaja mengabaikan nama pendek.

## Menambah jenis data baru

Buat kelas `extends SheetMigration` — isi `label()`, `defaultSheetName()`,
`aliases()`, `previewColumns()`, `persist()`, dan `refine()` bila perlu
normalisasi khusus. Daftarkan di konstanta `MigrasiData::PROFIL`. Halaman dan
pembaca sheet tidak perlu diubah.
