<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Brief Beyond Viral —
        {{ $brief->campaign_name ?: preg_replace('/^KOL Needs\s*[—-]\s*/u', '', $brief->title) }}
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .input-field {
            width: 100%;
            border-radius: 0.625rem;
            border: 1px solid #e5e7eb;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            outline: none;
            transition: box-shadow 0.15s, border-color 0.15s;
            background: #fff;
        }

        .input-field:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }

        .field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.375rem;
        }

        .field-hint {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

@php
    $websiteLogoUrl = 'https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif';
    $displayBriefTitle = preg_replace('/^KOL Needs\s*[—-]\s*/u', '', $brief->title);

    $steps = [
        ['key' => 'identitas', 'label' => 'Identitas'],
        ['key' => 'campaign', 'label' => 'Campaign'],
        ['key' => 'kol', 'label' => 'KOL & SOW'],
        ['key' => 'guideline', 'label' => 'Guideline'],
        ['key' => 'penutup', 'label' => 'Budget & Lampiran'],
    ];
@endphp

<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-2xl mx-auto px-4 py-5 sm:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ $websiteLogoUrl }}" alt="Beyond Viral" class="h-9 w-auto flex-shrink-0 object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Formulir Brief</h1>
                    <p class="text-xs text-gray-500">Beyond Viral</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-4 py-6 sm:px-6"
        x-data="{
            step: 1,
            total: {{ count($steps) }},
            next() {
                if (this.step === 1 && !this.validateIdentitas()) return;
                if (this.step < this.total) this.step++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            prev() {
                if (this.step > 1) this.step--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            validateIdentitas() {
                const name = document.querySelector('[name=submitted_by_name]');
                const email = document.querySelector('[name=submitted_by_email]');
                if (!name.value.trim() || !email.value.trim()) {
                    alert('Mohon lengkapi Nama dan Email terlebih dahulu.');
                    return false;
                }
                return true;
            }
        }">

        {{-- Brief Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
            <h2 class="text-base font-semibold text-gray-900">{{ $displayBriefTitle }}</h2>
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                @if($brief->brand)
                    <span class="text-purple-600 font-medium">{{ $brief->brand }}</span>
                @endif
                @if($brief->campaign_name)
                    <span>Campaign: {{ $brief->campaign_name }}</span>
                @endif
                @if($brief->pic)
                    <span>PIC: {{ $brief->pic }}</span>
                @endif
            </div>
        </div>

        {{-- Stepper --}}
        <div class="mb-5">
            <div class="flex items-center justify-between">
                @foreach($steps as $i => $s)
                    <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-colors"
                                :class="step >= {{ $i + 1 }} ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-500'">
                                {{ $i + 1 }}
                            </div>
                            <span class="mt-1 text-[10px] font-medium hidden sm:block"
                                :class="step >= {{ $i + 1 }} ? 'text-purple-600' : 'text-gray-400'">{{ $s['label'] }}</span>
                        </div>
                        @if($i < count($steps) - 1)
                            <div class="flex-1 h-0.5 mx-1.5 transition-colors"
                                :class="step > {{ $i + 1 }} ? 'bg-purple-600' : 'bg-gray-200'"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Flash Success --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Error Messages --}}
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-5">
                <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('form-brief.submit', $brief->token) }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- STEP 1: Identitas --}}
            <div x-show="step === 1" x-cloak class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Identitas Anda</h3>
                    <p class="field-hint">Agar tim kami tahu siapa yang mengisi brief ini.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="submitted_by_name" value="{{ old('submitted_by_name') }}"
                            class="input-field" placeholder="Nama Anda">
                    </div>
                    <div>
                        <label class="field-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="submitted_by_email" value="{{ old('submitted_by_email') }}"
                            class="input-field" placeholder="email@domain.com">
                    </div>
                </div>
            </div>

            {{-- STEP 2: Campaign --}}
            <div x-show="step === 2" x-cloak class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Detail Campaign</h3>
                <div>
                    <label class="field-label">Product</label>
                    <input type="text" name="product" value="{{ old('product', $brief->product) }}" class="input-field"
                        placeholder="e.g. Parfume Manfa">
                </div>
                <div>
                    <label class="field-label">Background</label>
                    <textarea name="background" rows="3" class="input-field"
                        placeholder="Latar belakang brand / campaign...">{{ old('background', $brief->background) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Campaign Objective / Goals</label>
                    <textarea name="campaign_objective" rows="3" class="input-field"
                        placeholder="Tujuan utama campaign (awareness, engagement, conversion...)">{{ old('campaign_objective', $brief->campaign_objective) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Call to Action (CTA)</label>
                    <input type="text" name="cta" value="{{ old('cta', $brief->cta) }}" class="input-field"
                        placeholder="Aksi yang diharapkan dari audience">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Target Audience</label>
                        <input type="text" name="target_audience" value="{{ old('target_audience', $brief->target_audience) }}"
                            class="input-field" placeholder="e.g. GenZ">
                    </div>
                    <div>
                        <label class="field-label">Key Messages</label>
                        <input type="text" name="key_messages" value="{{ old('key_messages', $brief->key_messages) }}"
                            class="input-field" placeholder="Pesan kunci campaign">
                    </div>
                </div>
            </div>

            {{-- STEP 3: KOL & SOW --}}
            <div x-show="step === 3" x-cloak class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Request KOL & Deliverables</h3>
                <div>
                    <label class="field-label">Request KOL</label>
                    <textarea name="request_kol" rows="3" class="input-field"
                        placeholder="e.g. 80 KOLs: 80% Micro (70% cewe - 30% cowok), 20% Macro...">{{ old('request_kol', $brief->request_kol) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Persona KOL</label>
                    <textarea name="persona_kol" rows="2" class="input-field"
                        placeholder="e.g. Beauty, Lifestyle, Office worker">{{ old('persona_kol', $brief->persona_kol) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Deliverables / SOW</label>
                    <textarea name="sow" rows="3" class="input-field"
                        placeholder="Ruang lingkup pekerjaan KOL (mis. IG Reels & TikTok per akun...)">{{ old('sow', $brief->sow) }}</textarea>
                </div>
            </div>

            {{-- STEP 4: Guideline --}}
            <div x-show="step === 4" x-cloak class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Guideline & KPI</h3>
                <div>
                    <label class="field-label">Do</label>
                    <textarea name="brief_do" rows="3" class="input-field"
                        placeholder="Hal yang harus dilakukan (hashtag utama, jumlah influencer per minggu...)">{{ old('brief_do', $brief->brief_do) }}</textarea>
                </div>
                <div>
                    <label class="field-label">Don'ts</label>
                    <textarea name="brief_dont" rows="2" class="input-field"
                        placeholder="Hal yang harus dihindari">{{ old('brief_dont', $brief->brief_dont) }}</textarea>
                </div>
                <div>
                    <label class="field-label">KPI</label>
                    <textarea name="kpi" rows="2" class="input-field"
                        placeholder="Target / indikator keberhasilan">{{ old('kpi', $brief->kpi) }}</textarea>
                </div>
            </div>

            {{-- STEP 5: Budget & Lampiran --}}
            <div x-show="step === 5" x-cloak class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Budget, Timeline & Lampiran</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Budget Campaign (Rp)</label>
                        <input type="number" name="budget" value="{{ old('budget', $brief->budget) }}" min="0"
                            class="input-field" placeholder="Kosongkan jika open budget">
                    </div>
                    <div>
                        <label class="field-label">Timeline</label>
                        <input type="text" name="timeline" value="{{ old('timeline', $brief->timeline) }}"
                            class="input-field" placeholder="e.g. Januari - Maret 2026">
                    </div>
                    <div>
                        <label class="field-label">Delivery Date</label>
                        <input type="date" name="delivery_date"
                            value="{{ old('delivery_date', optional($brief->delivery_date)->format('Y-m-d')) }}"
                            class="input-field">
                    </div>
                    <div>
                        <label class="field-label">Supporting Doc (Link)</label>
                        <input type="url" name="supporting_doc" value="{{ old('supporting_doc', $brief->supporting_doc) }}"
                            class="input-field" placeholder="https://...">
                    </div>
                </div>
                <div>
                    <label class="field-label">Upload File Pendukung</label>
                    <input type="file" name="attachments[]" multiple
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <p class="field-hint">PDF, Word, Excel, PowerPoint, Gambar (maks. 10MB per file)</p>
                </div>
                <div>
                    <label class="field-label">Catatan Tambahan</label>
                    <textarea name="additional_notes" rows="2" class="input-field"
                        placeholder="Catatan lainnya...">{{ old('additional_notes', $brief->additional_notes) }}</textarea>
                </div>
            </div>

            {{-- Navigation --}}
            <div class="flex items-center justify-between mt-5">
                <button type="button" @click="prev()" x-show="step > 1" x-cloak
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    Kembali
                </button>
                <div x-show="step === 1" x-cloak></div>

                <button type="button" @click="next()" x-show="step < total" x-cloak
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors ml-auto">
                    Lanjut
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                <button type="button" @click="openSubmitConfirmationModal()" x-show="step === total" x-cloak
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors ml-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Submit Brief
                </button>
            </div>
        </form>
    </div>

    {{-- Footer --}}
    <div class="border-t border-gray-200 mt-10">
        <div class="max-w-2xl mx-auto px-4 py-5 sm:px-6">
            <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} Beyond Viral. All rights reserved.</p>
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    <div id="submit-confirmation-modal" class="hidden fixed inset-0 z-50" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50" onclick="closeSubmitConfirmationModal()"></div>

        <div class="relative min-h-full flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Submit Brief</h3>
                    <p class="text-sm text-gray-500 mt-1">Silakan cek kembali sebelum data dikirim.</p>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div
                        class="rounded-lg border border-purple-200 bg-purple-50 p-4 text-sm text-gray-700 leading-relaxed">
                        Data yang akan Anda submit akan dikerjakan oleh tim Beyond Viral. Jika ada penambahan atau
                        revisi, silakan hubungi sales yang menangani campaign ini.
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold tracking-wide uppercase text-gray-500">Sales PIC Campaign</p>
                        <p class="text-base font-semibold text-gray-900 mt-1">{{ $salesName ?? 'Tim Beyond Viral' }}</p>
                        @if(!empty($salesWhatsapp))
                            <p class="text-sm text-gray-600 mt-1">WhatsApp: +{{ $salesWhatsapp }}</p>
                        @else
                            <p class="text-sm text-amber-600 mt-1">Nomor WhatsApp sales belum tersedia.</p>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <button type="button" onclick="closeSubmitConfirmationModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                        Batal
                    </button>

                    @if(!empty($salesWhatsappUrl))
                        <a href="{{ $salesWhatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors">
                            Hubungi {{ $salesName ?? 'Sales' }} via WhatsApp (+{{ $salesWhatsapp }})
                        </a>
                    @else
                        <button type="button" disabled
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-gray-300 text-gray-600 text-sm font-medium cursor-not-allowed">
                            Nomor WhatsApp Sales Belum Tersedia
                        </button>
                    @endif

                    <button type="button" onclick="confirmSubmitBrief()"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-purple-600 text-white text-sm font-medium hover:bg-purple-700 transition-colors">
                        Ya, Submit Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openSubmitConfirmationModal() {
            const modal = document.getElementById('submit-confirmation-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function closeSubmitConfirmationModal() {
            const modal = document.getElementById('submit-confirmation-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }
        }

        function confirmSubmitBrief() {
            closeSubmitConfirmationModal();
            const form = document.querySelector('form');
            if (form) {
                form.submit();
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSubmitConfirmationModal();
            }
        });
    </script>
</body>

</html>
