# Migrasi Data Client (Spreadsheet → BV App)

Menarik data client dari Google Spreadsheet privat ke tabel `data_clients`.
**Pull, bukan push**: Laravel yang membaca sheet-nya sendiri lewat service
account, jadi tidak perlu Apps Script yang ditempel di tiap file.

Diporting dari service migrasi SOP Siproper
(`docs/sync/migrasi-sop-service.md` di project itu).

## Komponen

| Berkas | Peran |
|---|---|
| `app/Service/GoogleSheetReader.php` | Baca sheet privat via service account |
| `app/Service/ClientSheetMigration.php` | Peta judul kolom → field, parse, upsert |
| `app/Filament/Pages/MigrasiDataClient.php` + view | UI: preview & migrasi per-chunk |

Alur: **Sheet → GoogleSheetReader (baris 0-based) → parseRows (item) → persist (upsert)**.

## Setup service account (sekali saja)

1. Google Cloud Console → buat **Service Account** → buat **JSON key**.
2. Aktifkan **Google Sheets API** pada project tersebut.
3. Simpan JSON ke `storage/app/google/service-account.json`.
   Foldernya sudah masuk `.gitignore` — **jangan** di-commit.
4. Share spreadsheet-nya ke `client_email` yang ada di dalam JSON, minimal
   **Viewer**. Untuk banyak sheet, share satu folder induk saja.

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

> Selama kredensialnya belum ada, menu **Migrasi Data Client** tidak muncul di
> sidebar — halamannya cuma bisa menampilkan error, jadi tidak ada gunanya.

## Cara pakai

1. Panel → **Sales → Migrasi Data Client**.
2. Tempel link Google Sheets. Daftar tab terisi otomatis (sekalian menguji akses).
3. **Preview Data** → tabel baris, jumlah baris terbaca, dan daftar kolom sheet
   yang tidak dikenali (kolom itu tidak ikut dimigrasi).
4. **Migrasi Sekarang** → progress bar jalan per chunk (25 baris/request).
   **Jangan tutup tab** selama proses berjalan.

## Pemetaan kolom

Lewat **judul di baris pertama**, bukan huruf kolom tetap — urutan kolom paling
sering berubah. Besar-kecil huruf dan tanda baca diabaikan. Daftar alias
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

## Menambah entitas lain (KOL, Sales, dst)

Saat ini baru Data Client. Untuk entitas kedua, tiru `ClientSheetMigration`
(punya `ALIASES`, `parseRows()`, `persist()`) dan halaman Filament-nya —
`GoogleSheetReader` bisa dipakai apa adanya. Ekstrak interface bersama **setelah**
profil kedua ada, bukan sebelumnya, supaya bentuknya ditentukan kebutuhan nyata.
