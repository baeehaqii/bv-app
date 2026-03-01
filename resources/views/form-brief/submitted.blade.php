<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brief Submitted — {{ $brief->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Brief Berhasil Disubmit!</h1>
            <p class="text-sm text-gray-500 mb-4">
                Terima kasih telah mengisi brief <strong>{{ $brief->title }}</strong>.
                Tim kami akan segera meninjau dan menghubungi Anda.
            </p>
            @if($brief->submitted_by_email)
                <p class="text-xs text-gray-400">
                    Konfirmasi akan dikirim ke <strong>{{ $brief->submitted_by_email }}</strong>
                </p>
            @endif
        </div>
        <p class="text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} Beyond Viral</p>
    </div>
</body>

</html>