<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Brief Telah Diterima</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #7c3aed, #4f46e5); padding: 32px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,.8); margin: 6px 0 0; font-size: 14px; }
        .body { padding: 32px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .info-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px 24px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; font-weight: 500; min-width: 140px; }
        .info-value { color: #111827; font-weight: 600; text-align: right; }
        .footer { background: #f9fafb; padding: 20px 40px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Beyond Viral Indonesia</h1>
        <p>Form Brief Campaign — Konfirmasi Penerimaan</p>
    </div>

    <div class="body">
        <p>Halo <strong>{{ $pic }}</strong>,</p>

        <p>
            Terima kasih! Form Brief untuk campaign <strong>{{ $campaignName }}</strong>
            telah berhasil kami terima dan sedang diproses oleh tim Beyond Viral Indonesia.
        </p>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Campaign</span>
                <span class="info-value">{{ $campaignName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Brand</span>
                <span class="info-value">{{ $brand ?: '-' }}</span>
            </div>
            @if($budget)
            <div class="info-row">
                <span class="info-label">Budget</span>
                <span class="info-value">Rp {{ number_format($budget, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($deadline)
            <div class="info-row">
                <span class="info-label">Deadline</span>
                <span class="info-value">{{ $deadline }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Tanggal Submit</span>
                <span class="info-value">{{ $submittedAt }}</span>
            </div>
        </div>

        <p>
            Tim kami akan segera menindaklanjuti brief ini. Jika ada pertanyaan,
            silakan hubungi kami melalui email ini atau langsung ke PIC yang menangani campaign Anda.
        </p>

        <p>Salam hangat,<br><strong>Tim Beyond Viral Indonesia</strong></p>
    </div>

    <div class="footer">
        <p>PT Beyond Viral Indonesia · Jakarta, Indonesia</p>
        <p>Email ini dikirim otomatis, mohon tidak membalas langsung.</p>
    </div>
</div>
</body>
</html>
