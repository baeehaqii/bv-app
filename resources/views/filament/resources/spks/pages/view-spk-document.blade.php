{{--
    Preview dokumen SPK. Isi kontrak di-render dari resources/views/pdf/kol-contract.blade.php
    lewat route kol-contract.html, supaya teks legal cuma ada di SATU tempat.
    Dulu halaman ini menyalin ulang seluruh pasal — dan sudah sempat beda dengan PDF-nya
    (penandatangan di sini "Syelinda Pratiwi", di PDF "Gerry Hutomo").
--}}
<x-filament-panels::page>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700">
        <iframe
            src="{{ route('kol-contract.html', $this->getRecord()) }}"
            title="Dokumen SPK {{ $this->getRecord()->spk_number }}"
            class="w-full bg-white"
            style="height: 80vh; border: 0;"
        ></iframe>
    </div>
</x-filament-panels::page>
