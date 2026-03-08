<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOL Needs Form — {{ $brief->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .input-field {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
            transition: box-shadow 0.15s, border-color 0.15s;
        }
        .input-field:focus {
            border-color: transparent;
            box-shadow: 0 0 0 2px #7c3aed;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">KOL Needs Form</h1>
                    <p class="text-sm text-gray-500">Beyond Viral</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-8 sm:px-6">

        {{-- Flash Success --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Brief Info Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $brief->title }}</h2>
                    @if($brief->brand)
                        <p class="text-sm text-purple-600 font-medium mt-0.5">{{ $brief->brand }}</p>
                    @endif
                    @if($brief->campaign_name)
                        <p class="text-sm text-gray-500 mt-0.5">Campaign: {{ $brief->campaign_name }}</p>
                    @endif
                    @if($brief->timeline)
                        <p class="text-sm text-gray-500">Timeline: {{ $brief->timeline }}</p>
                    @endif
                </div>
                @if($brief->deadline)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex-shrink-0 text-center">
                        <p class="text-xs text-amber-600 font-medium uppercase tracking-wide">Deadline</p>
                        <p class="text-sm font-semibold text-amber-800">{{ $brief->deadline }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('form-brief.submit', $brief->token) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6">
            @csrf

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Identitas --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Identitas Anda</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="submitted_by_name" value="{{ old('submitted_by_name') }}" required
                            class="input-field" placeholder="Nama Anda">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="submitted_by_email" value="{{ old('submitted_by_email') }}" required
                            class="input-field" placeholder="email@domain.com">
                    </div>
                </div>
            </div>

            {{-- Campaign Objective --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Campaign Objective</h3>
                <textarea name="campaign_objective" rows="4"
                    placeholder="Apa tujuan utama campaign ini? (awareness, engagement, conversion, dll)"
                    class="input-field">{{ old('campaign_objective', $brief->campaign_objective) }}</textarea>
            </div>

            {{-- Criteria of KOL --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-1 uppercase tracking-wide">Criteria of KOL</h3>
                <p class="text-xs text-gray-400 mb-4">Tuliskan kriteria KOL yang dibutuhkan. Bisa mencakup Main KOL, Opsi Macro, Nano, dll.</p>
                <textarea name="criteria_of_kol" rows="6"
                    placeholder="Contoh:&#10;MAIN KOL&#10;- Mega&#10;- Artis yang emang ngerokok&#10;1. Jefry Nichole&#10;2. Ariel Tatum&#10;&#10;Opsi Macro&#10;1. Mohan Hazian"
                    class="input-field">{{ old('criteria_of_kol', $brief->criteria_of_kol) }}</textarea>
            </div>

            {{-- SOW --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-1 uppercase tracking-wide">SOW (Scope of Work)</h3>
                <p class="text-xs text-gray-400 mb-4">Ruang lingkup pekerjaan yang diharapkan dari KOL.</p>
                <textarea name="sow" rows="5"
                    placeholder="Contoh:&#10;• As a Talent (4-5 Jam) for Digital Video&#10;• Content Production by Flux Creative&#10;• Post IG Reels collab with Flux Creative&#10;• 2x IG Story Tap Link"
                    class="input-field">{{ old('sow', $brief->sow) }}</textarea>
            </div>

            {{-- Budget --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Budget</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget Main KOL</label>
                        <input type="text" name="budget_main_kol" value="{{ old('budget_main_kol', $brief->budget_main_kol) }}"
                            placeholder="e.g. 1M - 1,5M" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget Macro KOL</label>
                        <input type="text" name="budget_macro_kol" value="{{ old('budget_macro_kol', $brief->budget_macro_kol) }}"
                            placeholder="e.g. 250JT - 300JT" class="input-field">
                    </div>
                </div>
            </div>

            {{-- Deadline --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Deadline</h3>
                <input type="text" name="deadline" value="{{ old('deadline', $brief->deadline) }}"
                    placeholder="e.g. January 2026" class="input-field">
            </div>

            {{-- Catatan & Lampiran --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">Catatan & Lampiran</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload File Pendukung</label>
                        <input type="file" name="attachments[]" multiple
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                        <p class="text-xs text-gray-400 mt-1">PDF, Word, Excel, PowerPoint, Gambar (maks. 10MB per file)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                        <textarea name="additional_notes" rows="3" placeholder="Catatan lainnya..."
                            class="input-field">{{ old('additional_notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors">
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
    <div class="border-t border-gray-200 mt-12">
        <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6">
            <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} Beyond Viral. All rights reserved.</p>
        </div>
    </div>
</body>

</html>