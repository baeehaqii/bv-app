<?php

namespace Database\Seeders;

use App\Models\DataClient;
use Illuminate\Database\Seeder;

class DataClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Data dari Google Sheets: Data Leads Beyond Viral - BD Teams
     */
    public function run(): void
    {
        $clients = [
            [
                'nama_brand' => 'Nabati',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.nabatisnack.co.id/',
                'nama_pic' => 'Bernardus Randyanto',
                'role_pic' => 'Senior Marketing Manager',
                'email_pic' => 'bernardus.randyanto@nabatisnack.co.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-10-22',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Garudafood',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.garudafood.co.id/',
                'nama_pic' => 'Puspita Ismawati',
                'role_pic' => 'Customer Marketing Brand Manager',
                'email_pic' => 'puspita.ismawati@garudafood.co.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => 'email bounced back',
            ],
            [
                'nama_brand' => 'Orang Tua',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.ot.id/',
                'nama_pic' => 'Timothy Jo',
                'role_pic' => 'Digital Marketing Manager',
                'email_pic' => 'timothy.jo@ot.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-10-27',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Mayora',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.mayoraindah.co.id/',
                'nama_pic' => 'Charina Nasution',
                'role_pic' => 'Digital Marketing & PR Lead',
                'email_pic' => 'charina.nasution@mayora.co.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => 'email bounced back',
            ],
            [
                'nama_brand' => 'Paragon',
                'category' => 'Beauty & Skincare',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.paragon-innovation.co.id/',
                'nama_pic' => 'Muhammad Imtaqin',
                'role_pic' => 'Brand Digital Deputy Manager of Kahf',
                'email_pic' => 'muhammad.imtaqin@pti-cosmetics.com',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-10-27',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Cimory',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://cimory.com/',
                'nama_pic' => 'Olga Wawoh',
                'role_pic' => 'Digital Marketing Supervisor',
                'email_pic' => 'olga.raisa@cimory.com',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => 'email bounced back',
            ],
            [
                'nama_brand' => 'Jaipra',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://jaipra.com/',
                'nama_pic' => 'Diandra Siwanto',
                'role_pic' => 'Digital Marketing',
                'email_pic' => 'diandra.siwanto@jaipra.com',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-10-27',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Godrej',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.godreindonesia.com/',
                'nama_pic' => '-',
                'role_pic' => null,
                'email_pic' => null,
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => null,
            ],
            [
                'nama_brand' => 'Delfi',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.delfimarketing.com/',
                'nama_pic' => 'John Wilang',
                'role_pic' => 'Group Product Marketing Manager',
                'email_pic' => 'john.soh@delfimarketing.com',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-11-05',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Nutrifood',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.nutrifood.co.id/',
                'nama_pic' => 'Fransiska Hadiman',
                'role_pic' => 'Head of Media and Digital Business',
                'email_pic' => 'elvina@nutrifood.co.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-11-06',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Ultrajaya',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://www.ultrajaya.co.id/',
                'nama_pic' => 'Resti Herdirayani',
                'role_pic' => 'F&B Marketing Manager',
                'email_pic' => 'resti.herdirayani@ultrajaya.co.id',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => '2024-10-16',
                'notes' => null,
            ],
            [
                'nama_brand' => 'Greenfields',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://greenfielddairy.com/',
                'nama_pic' => 'Adi Reformawardhani',
                'role_pic' => 'Digital & CRM Executive',
                'email_pic' => 'adi.reformawardhani@greenfielddairy.com',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => null,
            ],
            [
                'nama_brand' => 'CooVita',
                'category' => 'FMCG',
                'priority' => 'P4 - 20%',
                'website' => 'https://coolvita.co.id/',
                'nama_pic' => 'Dian Fitrdhawati',
                'role_pic' => 'Head of Media and Digital Marketing Management',
                'email_pic' => '-',
                'status' => 'Sent',
                'date_outreach' => '2024-10-16',
                'date_follow_up' => null,
                'notes' => null,
            ],
        ];

        foreach ($clients as $client) {
            // Konversi nama_pic/role_pic/email_pic lama ke format pic_clients JSON baru
            $picClients = [];
            if (!empty($client['nama_pic']) && $client['nama_pic'] !== '-') {
                $picClients[] = [
                    'nama_pic' => $client['nama_pic'],
                    'role_pic' => $client['role_pic'] ?? null,
                    'email_pic' => $client['email_pic'] ?? null,
                    'wa_pic' => null,
                    'is_leads' => false,
                ];
            }

            unset($client['nama_pic'], $client['role_pic'], $client['email_pic']);

            $client['pic_clients'] = !empty($picClients) ? $picClients : null;

            DataClient::create($client);
        }
    }
}
