<?php

namespace Database\Seeders;

use App\Models\BvEmploye;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data karyawan dari form "List Data Karyawan BV Network & Offstage!"
 * (docs/List Data Karyawan BV Network & Offstage! (Responses).xlsx, 10 responden).
 *
 * Alamat dipecah manual jadi alamat/kota/provinsi/kode_pos karena form hanya
 * menyediakan satu kolom "Alamat Domisili" berupa teks bebas. Yang tidak tertulis
 * di form dibiarkan kosong, TIDAK dikarang.
 *
 * Kolom 'bank' hanya diisi nama banknya: di kesepuluh baris, nama penerima yang
 * ditulis responden sama dengan nama lengkapnya sendiri.
 *
 * Yang sengaja tidak diisi:
 * - whatsapp : tidak ditanyakan di form.
 * - position : form tidak menanyakan jabatan; isi lewat menu Karyawan.
 * - npwp     : form hanya meminta unggahan file NPWP (tersimpan di Google Drive),
 *              bukan nomornya. Isi manual lewat menu Karyawan.
 *
 * Email mengikuti pola nama panggilan @bvnetwork.net, sama seperti BvTeamSeeder,
 * supaya baris karyawan langsung nyambung ke akun user yang sudah ada.
 */
class BvEmployeSeeder extends Seeder
{
    /**
     * 'panggilan' dipakai jadi email (panggilan@bvnetwork.net) sekaligus nama akun.
     *
     * @var array<int, array<string, string|null>>
     */
    private const KARYAWAN = [
        [
            'panggilan' => 'gressita',
            'nama_lengkap' => 'Gressita Melli Aryati',
            'alamat' => 'Jl. Kalibata Utara II No.25A, RT.12/RW.2, Duren Tiga, Kec. Pancoran',
            'kota' => 'Jakarta Selatan',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '12760',
            'bank' => 'BCA',
            'no_rekening' => '0660552723',
            'bpjs_kesehatan' => '0001526097137',
        ],
        [
            'panggilan' => 'aliy',
            'nama_lengkap' => 'Aliy Ahmad Kurnia',
            'alamat' => 'Jl. Narogong Cantik Raya Blok D144 No. 4-5, Kel. Pengasinan, Kec. Rawalumbu',
            'kota' => 'Bekasi',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '',
            'bank' => 'BCA',
            'no_rekening' => '5271431695',
            'bpjs_kesehatan' => '0000043673376',
        ],
        [
            'panggilan' => 'sheila',
            'nama_lengkap' => 'Sheila Salma Az-zahra',
            'alamat' => 'Cluster Scandy House 9 Kav. 11H, Jl. Cipedak V, Srengseng Sawah, Jagakarsa',
            'kota' => 'Jakarta Selatan',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '12630',
            'bank' => 'OCBC',
            'no_rekening' => '722810059682',
            'bpjs_kesehatan' => '0001626176889',
        ],
        [
            // Nama panggilan di form: Febby.
            'panggilan' => 'febby',
            'nama_lengkap' => 'Ruqayah Haniar Herfibri Alif',
            'alamat' => 'Bukit Nusa Indah, Jl. Cemara Kav. 1404, Serua, Ciputat',
            'kota' => 'Tangerang Selatan',
            'provinsi' => 'Banten',
            'kode_pos' => '',
            'bank' => 'BCA',
            'no_rekening' => '7340107408',
            'bpjs_kesehatan' => null,
        ],
        [
            'panggilan' => 'salwa',
            'nama_lengkap' => 'Salwa Khatya Zulfa Lestari',
            'alamat' => 'Jl. Cigadung Kaler 3 RT 3 RW 4 No. 8, Kel. Cigadung, Kec. Cibeunying Kaler',
            'kota' => 'Bandung',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '40191',
            'bank' => 'BNI',
            'no_rekening' => '1230042917',
            'bpjs_kesehatan' => '0001773280721',
        ],
        [
            // Nama panggilan di form: RIYAD.
            'panggilan' => 'riyad',
            'nama_lengkap' => 'Riadhus Shalihin',
            'alamat' => 'Jl. Mutumanikam No. 23',
            'kota' => 'Bandung',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '',
            'bank' => 'BCA',
            'no_rekening' => '7751161780',
            'bpjs_kesehatan' => null,
        ],
        [
            'panggilan' => 'fahma',
            'nama_lengkap' => 'Fahma Shadrina',
            'alamat' => 'Jl. Cilandak KKO Gg. Gotong Royong No. 51, Kel. Ragunan, Kec. Pasar Minggu',
            'kota' => 'Jakarta Selatan',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '',
            'bank' => 'Mandiri',
            'no_rekening' => '0060010552572',
            'bpjs_kesehatan' => '0000055638562',
        ],
        [
            // Nama panggilan di form: dhini.
            'panggilan' => 'dhini',
            'nama_lengkap' => 'Andhini Putri L',
            'alamat' => 'Jl. Taman Wijaya Kusuma V Blok D No. 8B RT 9 RW 2, Cilandak Barat, Cilandak',
            'kota' => 'Jakarta Selatan',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '12430',
            'bank' => 'BCA',
            'no_rekening' => '0751418972',
            'bpjs_kesehatan' => null,
        ],
        [
            // Nama panggilan di form: Sora. Alamat tidak menyebut kota/provinsi.
            // BPJS yang diisi 16 digit — panjangnya seperti NIK, bukan BPJS (13 digit).
            // Disimpan apa adanya sesuai isian form; konfirmasi ke yang bersangkutan.
            'panggilan' => 'sora',
            'nama_lengkap' => 'Rahmadea Anwar',
            'alamat' => 'Jl. Diamond Residence Crown Blok Z No. 7',
            'kota' => '',
            'provinsi' => '',
            'kode_pos' => '',
            'bank' => 'BCA',
            'no_rekening' => '7310989457',
            'bpjs_kesehatan' => '3276044202980002',
        ],
        [
            'panggilan' => 'desma',
            'nama_lengkap' => 'Desma Nur Cahya Giani',
            'alamat' => 'Kp. Mutiara Baru, Bojonggede',
            'kota' => 'Bogor',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '',
            'bank' => 'BCA',
            'no_rekening' => '6830869733',
            'bpjs_kesehatan' => null,
        ],
    ];

    public function run(): void
    {
        foreach (self::KARYAWAN as $data) {
            $panggilan = $data['panggilan'];
            $email = $panggilan . '@bvnetwork.net';
            unset($data['panggilan']);

            $user = User::firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->name = Str::ucfirst($panggilan);
                // Acak & tidak pernah ditampilkan — pemiliknya set sendiri lewat
                // "Lupa Password". Jangan diganti jadi password seragam.
                $user->password = Hash::make(Str::random(32));
                $user->save();

                $this->command?->warn("User baru dibuat tanpa role: {$email} — set role-nya di menu User.");
            }

            BvEmploye::updateOrCreate(
                ['email' => $email],
                $data + ['user_id' => $user->id],
            );
        }

        $this->command?->info(count(self::KARYAWAN) . ' karyawan ter-seed dan terhubung ke akun user.');
    }
}
