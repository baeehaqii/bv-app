<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Media Plan - {{ $mediaPlan->quotation_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }

        .container {
            width: 100%;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 10px;
        }

        .client-details,
        .campaign-details {
            font-size: 9px;
        }

        .client-details table,
        .campaign-details table {
            width: 100%;
        }

        .client-details td,
        .campaign-details td {
            padding: 2px 5px;
        }

        .client-details td:first-child,
        .campaign-details td:first-child {
            font-weight: bold;
            width: 120px;
        }

        /* Title */
        .title {
            background: linear-gradient(135deg, #4a2c7b 0%, #6b4ba3 100%);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .data-table th {
            background: linear-gradient(135deg, #4a2c7b 0%, #6b4ba3 100%);
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #3a1c6b;
        }

        .data-table td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background-color: #e8f4fc;
        }

        /* Column widths */
        .col-no {
            width: 3%;
        }

        .col-domisili {
            width: 8%;
        }

        .col-username {
            width: 10%;
        }

        .col-link {
            width: 10%;
        }

        .col-channel {
            width: 6%;
        }

        .col-categories {
            width: 7%;
        }

        .col-followers {
            width: 7%;
        }

        .col-tier {
            width: 5%;
        }

        .col-er {
            width: 5%;
        }

        .col-views {
            width: 7%;
        }

        .col-engagement {
            width: 7%;
        }

        .col-cpi {
            width: 6%;
        }

        .col-cpe {
            width: 6%;
        }

        .col-qty {
            width: 4%;
        }

        .col-item {
            width: 8%;
        }

        /* Text alignment */
        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Number formatting */
        .number {
            font-family: 'Courier New', monospace;
            text-align: right !important;
        }

        /* Link styling */
        a {
            color: #0066cc;
            text-decoration: none;
            font-size: 7px;
            word-break: break-all;
        }

        /* Tier badges */
        .tier-nano {
            color: #28a745;
        }

        .tier-micro {
            color: #17a2b8;
        }

        .tier-macro {
            color: #ffc107;
        }

        .tier-mega {
            color: #dc3545;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        /* Page break */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/logo-bv.png') }}" alt="Beyond Viral" class="logo"
                    onerror="this.style.display='none'">
                <div class="client-details">
                    <table>
                        <tr>
                            <td>Brand</td>
                            <td>: {{ $mediaPlan->brand ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>PIC Client</td>
                            <td>: {{ $mediaPlan->pic_client ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Quotation Number</td>
                            <td>: {{ $mediaPlan->quotation_number }}</td>
                        </tr>
                        <tr>
                            <td>Date</td>
                            <td>: {{ $mediaPlan->created_at->format('d M Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="header-right">
                <div class="campaign-details">
                    <table>
                        <tr>
                            <td>Campaign Type</td>
                            <td>: {{ $mediaPlan->campaign_type ?? 'Influencer' }}</td>
                        </tr>
                        <tr>
                            <td>Platform</td>
                            <td>: {{ $mediaPlan->platform ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Campaign Name</td>
                            <td>: {{ $mediaPlan->campaign_name }}</td>
                        </tr>
                        <tr>
                            <td>Campaign Period</td>
                            <td>: {{ $mediaPlan->period ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="title">
            MEDIA PLAN - {{ strtoupper($mediaPlan->campaign_name) }}
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-domisili">Domisili</th>
                    <th class="col-username">Username</th>
                    <th class="col-link">Link</th>
                    <th class="col-channel">Channel</th>
                    <th class="col-categories">Categories</th>
                    <th class="col-followers">Followers</th>
                    <th class="col-tier">Tier</th>
                    <th class="col-er">ER %</th>
                    <th class="col-views">Avg Views</th>
                    <th class="col-engagement">Engagement</th>
                    <th class="col-cpi">CPI/CPV</th>
                    <th class="col-cpe">CPE</th>
                    <th colspan="2">Scope of Work</th>
                </tr>
                <tr>
                    <th colspan="13"></th>
                    <th class="col-qty">Qty</th>
                    <th class="col-item">Item</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mediaPlan->kols->where('is_selected', true) as $index => $kol)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $kol->domisili ?? '-' }}</td>
                        <td class="text-left">{{ $kol->name }}</td>
                        <td class="text-left">
                            @if($kol->links && count($kol->links) > 0)
                                <a href="{{ $kol->links[0] }}" target="_blank">{{ Str::limit($kol->links[0], 30) }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $kol->channel ?? '-' }}</td>
                        <td>{{ $kol->category ?? 'Visual Art' }}</td>
                        <td class="number">{{ number_format($kol->followers ?? 0) }}</td>
                        <td class="tier-{{ strtolower($kol->tier ?? 'nano') }}">{{ $kol->tier ?? '-' }}</td>
                        <td class="number">{{ number_format($kol->er_percent ?? 0, 2) }}%</td>
                        <td class="number">{{ number_format($kol->impression ?? 0) }}</td>
                        <td class="number">{{ number_format($kol->engagement ?? 0) }}</td>
                        <td class="number">{{ $kol->cpi_cpv ? 'Rp ' . number_format($kol->cpi_cpv, 0) : '-' }}</td>
                        <td class="number">{{ $kol->cpe ? 'Rp ' . number_format($kol->cpe, 0) : '-' }}</td>
                        <td class="text-center">{{ is_array($kol->scope_items) ? count($kol->scope_items) : 1 }}</td>
                        <td class="text-left">
                            @if(is_array($kol->scope_items))
                                {{ implode(', ', $kol->scope_items) }}
                            @else
                                {{ $kol->scope_item ?? 'Bulked Rate' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div style="margin-top: 15px;">
            <table style="width: 300px; float: right; font-size: 9px;">
                <tr>
                    <td style="padding: 3px; font-weight: bold;">Total KOL:</td>
                    <td style="padding: 3px; text-align: right;">
                        {{ $mediaPlan->kols->where('is_selected', true)->count() }}</td>
                </tr>
                <tr>
                    <td style="padding: 3px; font-weight: bold;">Total Budget:</td>
                    <td style="padding: 3px; text-align: right;">Rp {{ number_format($totalBudget ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer" style="clear: both; padding-top: 30px;">
            Generated on {{ now()->format('d M Y H:i') }} | Beyond Viral Media Plan
        </div>
    </div>
</body>

</html>