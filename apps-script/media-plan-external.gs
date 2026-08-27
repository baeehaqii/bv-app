/**
 * Media Plan External generator.
 *
 * Cara pakai:
 * 1. Buka Google Sheet Media Plan INTERNAL (mis. "[INT] Bir Kawan Senja - KOL List").
 * 2. Extensions > Apps Script, tempel seluruh isi file ini, Save.
 * 3. Reload spreadsheet-nya. Menu "Media Plan" akan muncul di menu bar.
 * 4. Klik Media Plan > "Create EXT - Media Plan <nama file>" untuk generate
 *    file baru (copy) dengan kolom cost/margin internal sudah dihapus.
 */

// Sheet tier KOL yang punya kolom rate/cost (di luar sheet "Brief").
const KOL_SHEET_NAMES = ['Nano', 'Micro', 'Macro', 'Homeless Media'];

// Kolom yang wajib disembunyikan dari Media Plan External, berdasarkan
// struktur kolom template ini (header di baris 2, sheet Nano/Micro/Macro/
// Homeless Media):
//   V  = Rate                     -> rate_base (Modal/HPP)
//   W  = Subtotal Rate            -> subtotal
//   X  = Gross Up PPH Coefficient -> gross_up_coeff
//   Y  = Tax                      -> tax_value
//   Z  = MU PPh*                  -> mu_pph (Real Cost)
//   AA = MU**                     -> mu_target
//   AB = Published Rate***        -> published_rate
//   AD = Margin %                 -> actual_margin_percent
// AC (Rounded) SENGAJA TIDAK dihapus - itu harga final yang memang boleh
// dilihat client (sama seperti di link review & PDF export aplikasi).
//
// ponytail: pakai index kolom tetap, bukan cari berdasarkan nama header -
// header "Rate" dan "Scope of Work" muncul dobel di sheet ini (blok
// client-facing di P-S vs blok costing internal di V-AD), jadi match nama
// header saja ambigu. Kalau urutan kolom template berubah, update daftar
// HIDDEN_COLUMNS di bawah ini.
const HIDDEN_COLUMNS = ['V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AD'];

function onOpen() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  SpreadsheetApp.getUi()
    .createMenu('Media Plan')
    .addItem(`Create EXT - Media Plan ${ss.getName()}`, 'createExternalMediaPlan')
    .addToUi();
}

function createExternalMediaPlan() {
  const ui = SpreadsheetApp.getUi();
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const internalName = ss.getName();
  const externalName = /^\[INT\]/i.test(internalName)
    ? internalName.replace(/^\[INT\]/i, '[EXT]')
    : `[EXT] ${internalName}`;

  const internalFile = DriveApp.getFileById(ss.getId());
  const parents = internalFile.getParents();
  const parentFolder = parents.hasNext() ? parents.next() : DriveApp.getRootFolder();

  const copyFile = internalFile.makeCopy(externalName, parentFolder);
  const extSs = SpreadsheetApp.openById(copyFile.getId());

  KOL_SHEET_NAMES.forEach((sheetName) => {
    const sheet = extSs.getSheetByName(sheetName);
    if (sheet) stripHiddenColumns(sheet);
  });

  ui.alert(
    'Media Plan External dibuat',
    `File: ${externalName}\nLink: ${extSs.getUrl()}`,
    ui.ButtonSet.OK
  );
}

function stripHiddenColumns(sheet) {
  HIDDEN_COLUMNS
    .map(columnLetterToIndex)
    .sort((a, b) => b - a) // hapus dari kanan ke kiri biar index kolom lain tidak geser
    .forEach((col) => sheet.deleteColumn(col));
}

function columnLetterToIndex(letter) {
  let col = 0;
  for (let i = 0; i < letter.length; i++) {
    col = col * 26 + (letter.charCodeAt(i) - 64);
  }
  return col;
}

/**
 * Self-check manual: jalankan fungsi ini sekali dari Apps Script editor
 * (pilih runSelfCheck di dropdown function lalu Run) untuk memastikan
 * mapping kolom HIDDEN_COLUMNS benar sebelum dipakai di file asli.
 */
function runSelfCheck() {
  const cases = { V: 22, W: 23, AA: 27, AD: 30 };
  Object.keys(cases).forEach((letter) => {
    const actual = columnLetterToIndex(letter);
    const expected = cases[letter];
    if (actual !== expected) {
      throw new Error(`columnLetterToIndex('${letter}') = ${actual}, expected ${expected}`);
    }
  });
  Logger.log('Self-check OK: semua mapping kolom benar.');
}
