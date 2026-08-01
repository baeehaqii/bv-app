{{-- Hasil scraping massal. $hasil dari KolProfileImporter::importMany(), $reloadUrl untuk memuat ulang halaman. --}}
@php
    $gagal = collect($hasil['rows'] ?? [])->where('ok', false);
    $berhasil = collect($hasil['rows'] ?? [])->where('ok', true);
@endphp

<div class="space-y-4 text-sm">
    <div class="flex flex-wrap gap-2">
        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
            {{ $berhasil->count() }} berhasil
        </span>
        @if ($gagal->isNotEmpty())
            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                {{ $gagal->count() }} gagal
            </span>
        @endif
        @foreach (($hasil['errors'] ?? []) as $error)
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                {{ $error }}
            </span>
        @endforeach
    </div>

    {{-- Yang gagal ditaruh paling atas: itu yang perlu ditindaklanjuti user. --}}
    @if ($gagal->isNotEmpty())
        <div class="rounded-lg border border-rose-200 dark:border-rose-900">
            <div class="border-b border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">
                Gagal di-scrape — perbaiki URL-nya lalu ulangi
            </div>
            <table class="w-full text-left">
                <tbody>
                    @foreach ($gagal as $row)
                        <tr class="border-b border-rose-100 last:border-0 dark:border-rose-950">
                            <td class="px-3 py-2 align-top text-xs font-medium">{{ $row['channel'] }}</td>
                            <td class="px-3 py-2 align-top break-all text-xs">{{ $row['url'] }}</td>
                            <td class="px-3 py-2 align-top text-xs text-rose-600 dark:text-rose-400">{{ $row['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($berhasil->isNotEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800">
                Berhasil disimpan
            </div>
            <table class="w-full text-left">
                <tbody>
                    @foreach ($berhasil as $row)
                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                            <td class="px-3 py-2 align-top text-xs font-medium">{{ $row['channel'] }}</td>
                            <td class="px-3 py-2 align-top text-xs">&#64;{{ $row['username'] }}</td>
                            <td class="px-3 py-2 align-top text-xs">{{ number_format((int) $row['followers']) }} followers</td>
                            <td class="px-3 py-2 align-top text-xs text-amber-600 dark:text-amber-400">{{ $row['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($berhasil->isNotEmpty())
        <a href="{{ $reloadUrl }}" class="inline-block text-xs font-medium text-primary-600 underline hover:text-primary-500">
            Muat ulang halaman untuk melihat channel yang baru masuk
        </a>
    @endif
</div>
