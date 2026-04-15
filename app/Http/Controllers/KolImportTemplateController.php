<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class KolImportTemplateController extends Controller
{
    public function download(): StreamedResponse
    {
        $rows = [
            ['creator_name', 'channel', 'url', 'price'],
            ['@namakreator1', 'instagram_reels', 'https://instagram.com/p/xxx', '5000000'],
            ['@namakreator2', 'tiktok_video', 'https://tiktok.com/@user/video/123', '3000000'],
            ['@namakreator3', 'youtube_short', 'https://youtube.com/shorts/abc', '2000000'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'template-kol-import.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
