<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaignName }} — Quotation | Beyond Viral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .bg-gray-50 { background: white !important; }
            .rounded-2xl { border-radius: 0 !important; }
            .shadow-sm { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                     alt="Beyond Viral" class="h-8 object-contain">
                <span class="text-gray-300">|</span>
                <span class="text-sm text-gray-500 font-medium">Quotation Review</span>
            </div>
            <button onclick="window.print()"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">
                🖨️ Print / Save PDF
            </button>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        {{-- Document Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                         alt="Beyond Viral" class="h-10 object-contain mb-4">
                    <p class="text-xs font-semibold text-gray-700">PT Beyond Viral Indonesia</p>
                    <p class="text-xs text-gray-500">Jl. Kemang Raya No. 12, Kemang</p>
                    <p class="text-xs text-gray-500">Jakarta Selatan, DKI Jakarta 12730</p>
                    <p class="text-xs text-gray-500">hello@beyondviral.id</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-2xl font-bold text-gray-900">QUOTATION</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $quotation->quotation_number }}</p>
                    <p class="text-xs text-gray-400 mt-1">Tanggal: {{ $quotation->quotation_date?->format('d M Y') ?? '-' }}</p>
                    @if ($quotation->expiry_date)
                        <p class="text-xs text-gray-400">Berlaku s.d.: {{ $quotation->expiry_date->format('d M Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Client</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $quotation->client_name }}</p>
                    @if ($quotation->client_address)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $quotation->client_address }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Campaign</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $campaignName }}</p>
                    @if ($quotation->mediaPlan?->bvSales?->start_date)
                        <p class="text-xs text-gray-500 mt-0.5">
                            Periode: {{ $quotation->mediaPlan->bvSales->start_date->format('d M Y') }}
                            — {{ $quotation->mediaPlan->bvSales->end_date?->format('d M Y') ?? '-' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Detail Penawaran</h2>
                <p class="text-xs text-gray-500 mt-0.5">Rincian SOW dan harga per KOL</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-8">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">KOL / Creator</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Scope of Work</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($groupedItems as $index => $group)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-4 py-4 text-gray-400 text-xs">{{ $index + 1 }}</td>
                                <td class="px-4 py-4">
                                    <span class="font-medium text-gray-900">{{ $group['kol_name'] }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($group['sow_list'] as $sow)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                                {{ $sow }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-gray-900">
                                    Rp {{ number_format($group['total_rate'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">
                                    Belum ada item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50">
                            <td colspan="3" class="px-4 py-4 text-sm font-semibold text-gray-700 text-right">
                                Total Penawaran
                            </td>
                            <td class="px-4 py-4 text-right text-base font-bold text-gray-900">
                                Rp {{ number_format($totalAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Notes --}}
        @if ($quotation->notes || $quotation->terms_conditions)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if ($quotation->notes)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Catatan</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $quotation->notes }}</p>
                    </div>
                @endif
                @if ($quotation->terms_conditions)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Syarat & Ketentuan</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $quotation->terms_conditions }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Pengesahan: alur tanda tangan CEO → Business Development → Client --}}
        @include('quotation.partials.signature-flow', ['quotation' => $quotation])

        {{-- Footer --}}
        <div class="text-center py-4 no-print">
            <p class="text-xs text-gray-400">Dokumen ini dibuat secara digital oleh sistem Beyond Viral.</p>
            <p class="text-xs text-gray-300 mt-0.5">Quotation #{{ $quotation->quotation_number }}</p>
        </div>

    </main>

</body>
</html>
