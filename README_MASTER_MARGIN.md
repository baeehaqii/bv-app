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

### 4. Cara Kerja di Internal Budget

Ketika membuat/edit Internal Budget Item:

1. System akan hitung **Subtotal** (Qty × Rate Base)
2. System akan cari **Master Margin** yang sesuai berdasarkan subtotal
3. Margin yang cocok akan otomatis diaplikasikan ke kalkulasi
4. Jika "Use Flexible Margin" diaktifkan, user bisa override margin secara manual

## Model Methods

### `MasterMargin::getMarginForAmount(float $subtotal): float`

Method static untuk mendapatkan margin percentage berdasarkan subtotal amount.

**Contoh:**

```php
$margin = MasterMargin::getMarginForAmount(5000000); // Returns 40.0
$margin = MasterMargin::getMarginForAmount(60000000); // Returns 30.0
```

## Integration

### Internal Budget Form

File: `app/Filament/Resources/InternalBudgets/Schemas/InternalBudgetForm.php`

Logic lama (hardcoded):

```php
if ($subtotal > 50000000) {
    $targetMargin = 30.0;
} elseif ($subtotal >= 3000000) {
    $targetMargin = 40.0;
} else {
    $targetMargin = 80.0;
}
```

Logic baru (dynamic dari database):

```php
$targetMargin = \App\Models\MasterMargin::getMarginForAmount($subtotal);
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
