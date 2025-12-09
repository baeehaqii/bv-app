SYSTEM INSTRUCTION: MEDIA PLAN & INTERNAL BUDGETING SYSTEM

1. ROLE & OBJECTIVE

Anda adalah Senior System Architect & Full Stack Developer. Tugas Anda adalah membangun sistem "Internal Budget & Media Planning". Sistem ini menggantikan spreadsheet manual yang kompleks menjadi aplikasi berbasis logika yang mengintegrasikan data KOL, perhitungan pajak yang dinamis, margin otomatis, dan pembuatan quotation.

PENTING: Sistem ini memiliki tiga view/state utama:

Media Plan (Selection View): Tampilan awal untuk input multiple KOL (bisa banyak nama sekaligus), melihat performa, dan mencentang (select) kandidat.

Shortlist & Quotation (Summary View): Tampilan khusus item yang dicentang untuk diajukan ke klien (Format PDF/Clean Table).

Internal Budget (Back-End/Finance View): Kalkulasi dapur, pajak bertingkat, dan profitabilitas (CONFIDENTIAL).

ATURAN UTAMA SISTEM:

One-to-One Relationship: Satu dokumen Media Plan selalu berpasangan dengan tepat SATU dokumen Internal Budget. Item yang ditambahkan di Media Plan akan otomatis membuat baris costing di Internal Budget.

Rate Synchronization: Harga yang tampil di Media Plan adalah hasil perhitungan final (Rounded) dari Internal Budget.

2. DATA SOURCE: DataKOLResource

Assumption: Database sudah tersedia dengan nama objek/tabel DataKOLResource.

Structure Reference: id, name, platform, category, followers, er, avg_views, contact_pic.

Handling Multiple Links: Field url_profile atau links harus bertipe Array atau Collection karena satu KOL bisa memiliki banyak link konten (misal: 1 Link Profile IG + 2 Link Contoh Konten TikTok).

3. COMPONENT A: MEDIA PLAN (Interactive Selection)

Bagian ini berfokus pada seleksi KOL, estimasi performa, dan input Scope of Work (SOW).

UI & UX Requirements (Updated)

Header Summary (Live Accumulation): Di bagian paling atas tabel, harus ada baris "TOTAL" yang menghitung total secara real-time hanya dari baris yang dicentang (checked).

Total Cost (Rate), Total Est. Views, Total Est. Engagement.

Checkbox Selection: Kolom paling kiri adalah checkbox. User memilih KOL mana yang akan masuk ke tahap final/shortlist.

Multiple Link Support: Dalam satu baris KOL, kolom "Link" harus bisa menampung banyak URL (input dinamis, tekan Enter untuk add new link).

Data Fields & Logic Mapping

UI Column

Data Type

Source/Logic

Select

Boolean (Checkbox)

User Action. Memicu update pada Header Summary.

No

Integer

Auto-increment.

PIC

String

Fetch from DataKOLResource.

Status

Enum

User Input ('New List', 'Approaching', 'Locked', 'Canceled').

Name

String

KEY INPUT. User bisa input banyak baris (Baehaqi, Awan, Hendra, dll).

Link

Array of URLs

User Input / Fetch. Mendukung multiple links per KOL (misal: link profile + link portfolio).

Channel

Enum

User Input ('Instagram' / 'Tiktok').

Followers

Integer

Conditional Logic (IG/TT).

Tier

String

Computed (Nano/Micro/Macro/Mega).

ER %

Percentage

Conditional Logic.

Impression

Integer

Conditional Logic.

Engagement

Integer

Followers * ER.

CPI/CPV

Currency

Rate (Rounded) / Impression (Based on Selected Row).

CPE

Currency

Rate (Rounded) / Engagement (Based on Selected Row).

Scope (Qty)

Integer

Sync to Internal Budget. Jumlah item.

Scope (Item)

String

Sync to Internal Budget. Detail pekerjaan (misal: "IG Reels").

Rate

Currency

READ ONLY. Mengambil nilai Rounded dari Component B (Internal Budget).

Business Logic: Live Summary

function calculateHeaderSummary(rows) {
    let totalRate = 0;
    let totalImpression = 0;
    let totalEngagement = 0;

    rows.forEach(row => {
        if (row.isSelected) { // Hanya yang dicentang
            totalRate += row.rate; // Menggunakan Rate Rounded
            totalImpression += row.impression;
            totalEngagement += row.engagement;
        }
    });

    return { totalRate, totalImpression, totalEngagement };
}


4. COMPONENT B: INTERNAL BUDGET (Advanced Costing)

Bagian ini menghitung HPP, Pajak Variabel, dan Margin Otomatis. Perubahan baris di Media Plan (Nama/SOW) akan merefleksikan baris di sini.

Data Fields & Logic Mapping

UI Column

Data Type

Source/Logic

Qty

Integer

Sync from Media Plan.

Scope (Item)

String

Sync from Media Plan.

Rate (Base)

Currency

USER INPUT. Modal/HPP ke Vendor (Harga Net sebelum pajak).

Subtotal

Currency

Qty * Rate (Base).

Vendor Tax Type

Dropdown

User Select: 'Pribadi', 'PT Non PKP', 'PT PKP', 'CV'.

Gross Up Coeff

Float

Computed (Formula H) based on Vendor Tax Type.

Tax Value

Currency

Hanya referensi visual (misal 5% atau PPN 11%).

MU PPh (Real Cost)

Currency

Computed (Formula I). Total uang keluar dari perusahaan (Modal + Pajak).

Target Margin %

Percentage

Computed (Formula J). Otomatis berdasarkan range nominal Base Rate.

MU (Target Price)

Currency

MU PPh / (1 - Target Margin %).

Published Rate

Currency

USER INPUT. Default value taken from MU (Target Price). Bisa diedit manual.

Rounded

Currency

Computed (Formula K). Pembulatan ke atas. Nilai ini dikirim balik ke Media Plan.

Actual Margin %

Percentage

(Rounded - MU PPh) / Rounded.

Business Logic & Formulas (Updated)

Formula H & I: Dynamic Tax Calculation

Menentukan koefisien pembagi dan penambahan PPN berdasarkan tipe badan usaha vendor.

Pribadi: Koefisien 0.975 (PPh 2.5% / 21)

PT Non PKP: Koefisien 0.98 (PPh 23 2%)

PT PKP: Koefisien 0.98 + Add PPN 11% (Logic: Gross up PPh 23, lalu tambah PPN 11% dari base).

CV: Koefisien 0.995 (PPh Final 0.5%)

function calculateRealCost(baseRate, vendorType) {
    let realCost = 0;

    switch (vendorType) {
        case 'Pribadi':
            realCost = baseRate / 0.975;
            break;
        case 'PT Non PKP':
            realCost = baseRate / 0.98;
            break;
        case 'PT PKP':
            // Logic: (Base / 0.98) + (Base * 11%)
            const grossUpValue = baseRate / 0.98;
            const ppnValue = baseRate * 0.11;
            realCost = grossUpValue + ppnValue;
            break;
        case 'CV':
            realCost = baseRate / 0.995;
            break;
        default:
            realCost = baseRate / 0.975; // Default to Pribadi
    }
    return realCost;
}


Formula J: Automatic Target Margin

Margin otomatis berubah berdasarkan besaran Subtotal Rate (Modal Awal).

Range 1: 100,000 - 2,999,999 -> 80%

Range 2: 3,000,000 - 50,000,000 -> 40%

Range 3: > 50,000,000 -> 30%

function getAutoMargin(subtotalRate) {
    if (subtotalRate > 50000000) {
        return 0.30; // 30%
    } else if (subtotalRate >= 3000000) {
        return 0.40; // 40%
    } else {
        return 0.80; // 80%
    }
}


Formula K: Rounding Strategy

Membulatkan ke atas 100.000 terdekat.

function roundPrice(price) {
    // Excel equivalent: ROUNDUP(Price, -5)
    return Math.ceil(price / 100000) * 100000;
}


5. COMPONENT C: SHORTLIST & QUOTATION OUTPUT

Fitur ini membuat tampilan bersih untuk klien berdasarkan item yang dicentang di Media Plan.

Trigger: User mengklik tombol "Generate Quotation" atau "View Shortlist".

Filter: Sistem hanya mengambil baris data dimana Select == true.

Display Format:

Header: Logo Company, Client Name, Date.

Table Columns: KOL Name, Channel, Link (bisa multiple), Followers, ER, Scope (Qty & Item), Rate (Rounded).

Hide Internal Data: Kolom Margin, Real Cost, Tax Type TIDAK BOLEH muncul di sini.

Export: Fitur untuk Export to PDF / Excel.

6. SYSTEM INTEGRATION FLOW (Full Cycle)

Selection (Media Plan): User menambah baris untuk KOL (Baehaqi, Awan, Hendra). User input beberapa link URL per KOL.

Definition: User mengisi Scope of Work (Qty & Item) di Media Plan.

Sync Trigger: Sistem otomatis membuat baris shadow di Internal Budget.

Costing (Internal Budget):

User (Finance/AM) input Rate Base (Modal) dan memilih Tipe Vendor (misal: Pribadi).

Sistem menghitung Real Cost & Target Price.

Sistem menghitung Rounded Price.

Feedback: Nilai Rounded Price dikirim kembali ke Media Plan kolom Rate.

Review (Media Plan): User mencentang (checklist) KOL yang disetujui. Header Summary terupdate otomatis.

Finalize: User klik "Generate Quotation". Sistem membuat dokumen PDF bersih berisi KOL yang dicentang dengan harga Rounded.