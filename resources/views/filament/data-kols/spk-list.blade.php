{{-- Riwayat SPK 1 KOL, digabung dari semua baris channel-nya. $spks = Collection<BvSPK> --}}
@php
    $statusTone = [
        'draft' => ['Draft', 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
        'active' => ['Menunggu TTD', 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'],
        'signed' => ['Signed', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'],
        'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'],
    ];
@endphp

@if ($spks->isEmpty())
    <p class="py-6 text-center text-sm text-gray-500">
        Belum ada SPK. SPK terbit otomatis dari Media Plan External setelah client approve KOL ini.
    </p>
@else
    <div style="overflow-x:auto;">
        <table class="w-full text-left text-sm" style="min-width:44rem;">
            <thead>
                <tr class="border-b text-xs uppercase text-gray-400">
                    <th class="py-2 pr-4">Nomor SPK</th>
                    <th class="py-2 pr-4">Campaign</th>
                    <th class="py-2 pr-4">Tanggal</th>
                    <th class="py-2 pr-4 text-right">Nominal</th>
                    <th class="py-2 pr-4">Status</th>
                    <th class="py-2">Dokumen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($spks as $spk)
                    @php [$label, $tone] = $statusTone[$spk->status] ?? [ucfirst((string) $spk->status), $statusTone['draft'][1]]; @endphp
                    <tr class="border-b last:border-0">
                        <td class="py-2 pr-4 font-medium">{{ $spk->spk_number }}</td>
                        <td class="py-2 pr-4">{{ $spk->nama_campaign ?: '—' }}</td>
                        <td class="py-2 pr-4">{{ $spk->tanggal_perjanjian?->format('d M Y') ?? '—' }}</td>
                        <td class="py-2 pr-4 text-right font-semibold">
                            Rp{{ number_format((float) $spk->nominal_kesepakatan, 0, ',', '.') }}
                        </td>
                        <td class="py-2 pr-4">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ $label }}</span>
                        </td>
                        <td class="py-2">
                            <a href="{{ \App\Filament\Resources\Spks\SpkResource::getUrl('document', ['record' => $spk]) }}"
                               target="_blank" class="text-primary-600 hover:text-primary-500" title="Buka dokumen SPK">
                                <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 font-semibold">
                    <td colspan="3" class="py-2 pr-4 text-right">Total</td>
                    <td class="py-2 pr-4 text-right">
                        Rp{{ number_format((float) $spks->sum('nominal_kesepakatan'), 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
