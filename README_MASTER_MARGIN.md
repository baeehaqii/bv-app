# Master Margin Feature

## Overview

Master Margin adalah fitur untuk mengelola persentase margin secara dinamis melalui UI, menggantikan hard-coded logic di Internal Budget calculation.

## Struktur Database

### Tabel: `master_margins`

-   `id` - Primary key
-   `name` - Nama range (e.g., "Low Budget", "Medium Budget", "High Budget")
-   `min_amount` - Jumlah minimum subtotal
-   `max_amount` - Jumlah maksimum subtotal (null = unlimited)
-   `margin_percent` - Persentase margin
-   `order` - Urutan tampilan
-   `is_active` - Status aktif/nonaktif
-   `created_at` & `updated_at` - Timestamps

### Tabel: `media_plans` (Margin Fields)

-   `margin_type` - Tipe margin: 'auto' atau 'custom'
-   `margin_percent` - Persentase margin kustom (jika margin_type = 'custom')
-   `use_global_margin` - Apakah margin ini berlaku untuk semua KOL

## Default Data

Setelah migration dan seeder, akan ada 3 range default:

1. **Low Budget** (100,000 - 2,999,999) = 80%
2. **Medium Budget** (3,000,000 - 50,000,000) = 40%
3. **High Budget** (50,000,001+) = 30%

## Cara Menggunakan

### 1. Akses Menu

-   Login ke Filament admin panel
-   Buka menu **Settings** → **Master Margins**

### 2. Mengelola Margin

-   **Create**: Tambah range baru dengan klik tombol "New Master Margin"
-   **Edit**: Klik baris untuk edit range yang ada
-   **Delete**: Hapus range yang tidak diperlukan
-   **Active/Inactive**: Toggle status untuk mengaktifkan/nonaktifkan range

### 3. Konfigurasi Range

-   **Range Name**: Nama untuk identifikasi (contoh: "Very High Budget")
-   **Order**: Urutan tampilan di tabel (angka lebih kecil = lebih atas)
-   **Min Amount**: Nilai minimum subtotal untuk range ini
-   **Max Amount**: Nilai maksimum subtotal (kosongkan untuk unlimited)
-   **Margin %**: Persentase margin yang akan diaplikasikan
-   **Active**: Hanya range yang aktif yang akan digunakan dalam kalkulasi

### 4. Margin Setting di Media Plan External

Saat membuat/edit Media Plan External, ada step "Margin Setting" di wizard:

1. **Margin Type**:
   - **Auto**: Margin dihitung otomatis berdasarkan Master Margin
   - **Custom**: Anda tentukan sendiri persentase margin

2. **Custom Margin %**: Jika margin_type = 'custom', input persentase margin yang diinginkan

3. **Apply to All KOLs**: Jika aktif, margin akan diterapkan ke semua KOL dalam campaign

### 5. Cara Kerja di Internal Budget

Ketika membuat/edit Internal Budget Item:

1. System akan hitung **Subtotal** (Qty × Rate Base)
2. System akan cek **Margin dari Media Plan** terlebih dahulu:
   - Jika `margin_type = 'custom'` dan `use_global_margin = true`, gunakan margin dari Media Plan
   - Jika tidak, lanjut ke langkah berikutnya
3. Jika tidak ada global margin, cek **per-item flexible margin**
4. Jika tidak ada juga, gunakan **Master Margin** yang sesuai berdasarkan subtotal
5. Margin yang cocok akan otomatis diaplikasikan ke kalkulasi

## Model Methods

### `MasterMargin::getMarginForAmount(float $subtotal): float`

Method static untuk mendapatkan margin percentage berdasarkan subtotal amount.

**Contoh:**

```php
$margin = MasterMargin::getMarginForAmount(5000000); // Returns 40.0
$margin = MasterMargin::getMarginForAmount(60000000); // Returns 30.0
```

## Integration

### Media Plan Form

File: `app/Filament/Resources/MediaPlans/Schemas/MediaPlanForm.php`

Step "Margin Setting" di wizard memungkinkan user untuk:
- Memilih antara margin otomatis atau custom
- Set persentase margin kustom
- Mengaktifkan global margin untuk semua KOL

### Internal Budget Form

File: `app/Filament/Resources/InternalBudgets/Schemas/InternalBudgetForm.php`

Prioritas margin calculation:
1. Media Plan Global Margin (jika aktif)
2. Per-item Flexible Margin
3. Auto dari MasterMargin

```php
// Priority: 1. Media Plan Global Margin, 2. Item Flexible Margin, 3. Auto from MasterMargin
$mediaPlan = \App\Models\MediaPlan::find($mediaPlanId);
if ($mediaPlan && $mediaPlan->use_global_margin && $mediaPlan->margin_type === 'custom') {
    $targetMargin = (float) $mediaPlan->margin_percent;
} elseif ($useFlexibleMargin) {
    $targetMargin = $marginOverride;
} else {
    $targetMargin = \App\Models\MasterMargin::getMarginForAmount($subtotal);
}
```

## Permissions

Menggunakan Filament Shield untuk permission management:

-   `view_any_master::margin` - Lihat list
-   `view_master::margin` - Lihat detail
-   `create_master::margin` - Buat baru
-   `update_master::margin` - Edit
-   `delete_master::margin` - Hapus
-   `restore_master::margin` - Restore yang dihapus
-   `force_delete_master::margin` - Hapus permanen

## Notes

-   Range tidak boleh overlap (sistem akan gunakan yang pertama match)
-   Jika tidak ada range yang cocok, fallback margin = 30%
-   Range yang inactive tidak akan digunakan dalam kalkulasi
-   Gunakan field `order` untuk mengatur prioritas jika ada overlap
-   Jika Media Plan menggunakan global margin custom, per-item margin controls akan disembunyikan
