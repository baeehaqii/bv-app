<?php

namespace App\Http\Controllers;

use App\Support\KolImageProxy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan thumbnail KOL dari CDN media sosial lewat domain sendiri.
 * Lihat KolImageProxy untuk alasan & pengamanannya.
 */
class KolImageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $source = (string) $request->query('src');

        // Tanda tangan sudah dicek middleware; ini pagar kedua terhadap SSRF.
        abort_unless(KolImageProxy::isAllowed($source), 404);

        $path = KolImageProxy::cachePath($source);
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            $response = Http::withHeaders([
                'Referer' => KolImageProxy::refererFor($source),
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 '
                    . '(KHTML, like Gecko) Chrome/125.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/*,*/*;q=0.8',
            ])->timeout(10)->get($source);

            // CDN tetap menolak / gambar sudah kedaluwarsa → 404, biar <img> memakai
            // fallback-nya sendiri alih-alih menampilkan halaman error.
            abort_unless($response->successful(), 404);

            $bytes = $response->body();

            abort_if(strlen($bytes) > KolImageProxy::MAX_BYTES, 404);
            abort_unless(str_starts_with((string) $response->header('Content-Type'), 'image/'), 404);

            $disk->put($path, $bytes);
            $disk->put($path . '.type', $response->header('Content-Type'));
        }

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->exists($path . '.type') ? $disk->get($path . '.type') : 'image/jpeg',
            'Cache-Control' => 'public, max-age=' . KolImageProxy::BROWSER_TTL . ', immutable',
        ]);
    }
}
