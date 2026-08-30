<?php

namespace Database\Seeders;

use App\Models\BvSalesList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Akun tim BV per Agustus 2026 (revisi client R16).
 *
 * Satu orang bisa berada di dua tim — Febby memegang AM sekaligus KOL — jadi
 * kuncinya EMAIL, bukan nama tim. Baris BvSalesList hanya dibuat untuk yang
 * benar-benar memegang deal, karena tabel itu yang dipakai kolom PIC Internal
 * di Data Client dan Sales Activity Tracker.
 *
 * Baris BvEmploye sengaja TIDAK dibuat: tabel itu mewajibkan whatsapp, alamat,
 * kota, provinsi, dan kode pos, dan data itu tidak diberikan. Mengisinya dengan
 * "-" cuma menghasilkan data karyawan palsu yang harus dibersihkan belakangan.
 *
 * Password TIDAK ditentukan di sini: tiap akun dibuat dengan password acak yang
 * tidak pernah ditampilkan, jadi pemiliknya harus lewat "Lupa Password". Menaruh
 * password seragam di seeder berarti password itu ikut masuk git selamanya.
 */
class BvTeamSeeder extends Seeder
{
    /**
     * nama, email, jabatan, role panel, ikut jadi baris sales?
     *
     * @var array<int, array{0:string,1:string,2:string,3:string,4:bool}>
     */
    private const TIM = [
        ['Gerry',    'gerry@bvnetwork.net',    'Director / CEO',                  'super_admin',              true],
        ['Wina',     'wina@bvnetwork.net',     'Head of Sales',                   'Sales/BD',                 true],
        ['Febby',    'febby@bvnetwork.net',    'AM Manager / Head of Operations', 'Operation KOL & Creative', true],
        ['Gressita', 'gressita@bvnetwork.net', 'Account Management',              'Sales/BD',                 true],
        ['Salwa',    'salwa@bvnetwork.net',    'KOL Specialist',                  'Operation KOL & Creative', true],
        ['Sheila',   'sheila@bvnetwork.net',   'KOL Specialist',                  'Operation KOL & Creative', true],
        ['Desma',    'desma@bvnetwork.net',    'KOL Specialist',                  'Operation KOL & Creative', true],
        ['Fahma',    'fahma@bvnetwork.net',    'Data Analyst',                    'Creative',                 false],
        // Aliy tidak ada di daftar struktur, tapi muncul sebagai PIC KOL di
        // spreadsheet. Emailnya mengikuti pola nama depan; ganti bila keliru.
        ['Aliy',     'aliy@bvnetwork.net',     'KOL Specialist',                  'Operation KOL & Creative', true],
    ];

    public function run(): void
    {
        foreach (self::TIM as [$nama, $email, $jabatan, $role, $jadiSales]) {
            Role::firstOrCreate(['name' => $role]);

            $user = User::firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->name = $nama;
                // Acak & tidak pernah ditampilkan — pemiliknya set sendiri lewat
                // "Lupa Password". Jangan diganti jadi password seragam.
                $user->password = Hash::make(Str::random(32));
                $user->save();
            }

            $user->syncRoles([$role]);

            if ($jadiSales) {
                // Nama depan dipakai apa adanya: itu yang tertulis di kolom PIC
                // pada spreadsheet, jadi pencocokannya langsung kena.
                $sales = BvSalesList::firstOrNew(['nama_sales' => $nama]);
                $sales->user_id = $user->id;
                $sales->keterangan = $jabatan;
                $sales->save();
            }
        }
    }
}
