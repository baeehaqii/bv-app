<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Plan External - {{ $internalBudget->mediaPlan->campaign_name ?? 'Beyond Viral' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            background-color: #ffffff;
            padding: 15px;
        }

        .container {
            max-width: 100%;
            background-color: #ffffff;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 25%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 75%;
            vertical-align: top;
        }

        /* Logo Styling */
        .logo-image {
            max-height: 120px;
            width: auto;
            margin-left: 30px;
            margin-top: 5px;
        }

        /* Details Tables */
        .details-wrapper {
            display: table;
            width: 100%;
        }

        .details-table {
            display: table-cell;
            width: 50%;
            padding-left: 10px;
            vertical-align: top;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .info-table th {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            text-align: left;
            font-weight: bold;
        }

        .info-table td {
            border: 1px solid #d1d5db;
            padding: 4px 8px;
        }

        .info-table td:first-child {
            width: 40%;
            font-weight: 600;
        }

        /* Main Title */
        .main-title {
            background-color: #49009F;
            color: #DAFF01;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            text-transform: uppercase;
            border: 1px solid #000;
            margin-bottom: 0;
        }

        .subtitle {
            background-color: #f5e6ff;
            color: #49009F;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 10px;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
        }

        /* Data Table */
        .data-table-container {
            border: 1px solid #000;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            table-layout: fixed;
        }

        .data-table thead {
            background-color: #dbeafe;
        }

        .data-table thead tr:first-child th {
            background-color: #93c5fd;
            font-weight: bold;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            color: #1e3a8a;
        }

        .data-table thead tr:nth-child(2) th {
            background-color: #dbeafe;
            font-weight: bold;
            border: 1px solid #6b7280;
            padding: 5px 3px;
            text-align: center;
            color: #1e40af;
            font-size: 7px;
        }

        .data-table tbody tr {
            background-color: #ffffff;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .data-table tbody tr.row-highlight {
            background-color: #fef3c7;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .bg-warning {
            background-color: #fef3c7;
        }

        .bg-info {
            background-color: #dbeafe;
        }

        .bg-success {
            background-color: #d1fae5;
        }

        .text-red {
            color: #dc2626;
        }

        .text-green {
            color: #16a34a;
        }

        /* Column widths */
        .col-qty {
            width: 3%;
        }

        .col-scope {
            width: 8%;
        }

        .col-item {
            width: 10%;
        }

        .col-rate {
            width: 7%;
        }

        .col-subtotal {
            width: 7%;
        }

        .col-coefficient {
            width: 5%;
        }

        .col-tax {
            width: 4%;
        }

        .col-mu-pph {
            width: 8%;
        }

        .col-mu {
            width: 8%;
        }

        .col-published {
            width: 8%;
        }

        .col-rounded {
            width: 8%;
        }

        .col-margin {
            width: 6%;
        }

        /* Summary section */
        .summary-section {
            margin-top: 15px;
            border: 1px solid #000;
        }

        .summary-title {
            background-color: #49009F;
            color: #DAFF01;
            padding: 6px;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 6px;
            font-size: 10px;
        }

        .summary-label {
            font-weight: 600;
            background-color: #f3f4f6;
            width: 70%;
        }

        .summary-value {
            width: 30%;
            text-align: right;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
        }

        /* Page break handling for PDF */
        @media print {
            .data-table tbody tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-left">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Beyond Viral Logo" class="logo-image">
                @else
                    <img src="{{ public_path('images/logo-bv.png') }}" alt="Beyond Viral Logo" class="logo-image">
                @endif
            </div>
            <div class="header-right">
                <div class="details-wrapper">
                    <div class="details-table">
                        <table class="info-table">
                            <tr>
                                <th colspan="2" style="background-color: #49009F; color: #DAFF01; text-align: center;">
                                    CAMPAIGN INFORMATION
                                </th>
                            </tr>
                            <tr>
                                <td>Campaign Name</td>
                                <td>{{ $internalBudget->mediaPlan->campaign_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Brand/Client</td>
                                <td>{{ $internalBudget->mediaPlan->brand ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Campaign Type</td>
                                <td>{{ $internalBudget->mediaPlan->campaign_type ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Platform</td>
                                <td>{{ $internalBudget->mediaPlan->platform ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="details-table">
                        <table class="info-table">
                            <tr>
                                <th colspan="2" style="background-color: #49009F; color: #DAFF01; text-align: center;">
                                    BUDGET INFORMATION
                                </th>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td><strong
                                        style="text-transform: uppercase;">{{ $internalBudget->status ?? 'Draft' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Total Items</td>
                                <td><strong>{{ $internalBudget->items->count() }}</strong></td>
                            </tr>
                            <tr>
                                <td>Created Date</td>
                                <td>{{ $internalBudget->created_at?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Last Updated</td>
                                <td>{{ $internalBudget->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Title -->
        <div class="main-title">
            MEDIA PLAN EXTERNAL
        </div>
        <div class="subtitle">
            (INTERNAL PURPOSES PLEASE DO NOT SHARE)
        </div>

        <!-- Data Table -->
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-qty">Qty</th>
                        <th colspan="2" style="border-bottom: 1px solid #000;">Scope of Work</th>
                        <th rowspan="2" class="col-rate">Rate</th>
                        <th rowspan="2" class="col-subtotal">Subtotal Rate</th>
                        <th rowspan="2" class="col-coefficient">Gross Up PPH<br>Coefficient</th>
                        <th rowspan="2" class="col-tax">Tax</th>
                        <th rowspan="2" class="col-mu-pph">MU PPh*</th>
                        <th rowspan="2" class="col-mu">MU**</th>
                        <th rowspan="2" class="col-published">Published Rate***</th>
                        <th rowspan="2" class="col-rounded">Rounded</th>
                        <th rowspan="2" class="col-margin">Margin %</th>
                    </tr>
                    <tr>
                        <th class="col-scope">KOL/Item</th>
                        <th class="col-item">Item</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $currentKolId = null;
                        $rowNumber = 0;
                    @endphp

                    @foreach($internalBudget->items as $item)
                        @php
                            $isNewKol = $currentKolId !== $item->media_plan_kol_id;
                            $currentKolId = $item->media_plan_kol_id;
                            $kol = $item->mediaPlanKol;

                            // Get tax info
                            $masterPph = $item->masterPph;
                            // Kolom ini meniru kolom X sheet client = koefisien gross-up MENTAH (0.98),
                            // PPN-nya berdiri sendiri di kolom "Tax". Jangan pakai
                            // getCalculatedCoefficient() — itu pembagi gabungan untuk hitung MU PPh.
                            $coefficient = $masterPph ? (float) $masterPph->coefficient : 0.975;
                            $taxRate = $item->taxRateDecimal();

                            // Format numbers
                            $qty = $item->qty ?? 1;
                            $rateBase = $item->rate_base ?? 0;
                            $subtotal = $item->subtotal ?? 0;
                            $muPph = $item->mu_pph ?? 0;
                            $muTarget = $item->mu_target ?? 0;
                            $publishedRate = $item->published_rate ?? 0;
                            $rounded = $item->rounded ?? 0;
                            $marginPercent = $item->actual_margin_percent ?? 0;

                            $rowNumber++;
                        @endphp

                        <tr class="{{ $isNewKol ? 'row-highlight' : '' }}">
                            <td>{{ $qty }}</td>
                            <td class="text-left">
                                @if($isNewKol && $kol)
                                    <strong>{{ $kol->name }}</strong>
                                @endif
                            </td>
                            <td class="text-left">{{ $item->scope_item ?? '-' }}</td>
                            <td class="text-right">Rp{{ number_format($rateBase, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                            <td>{{ number_format($coefficient, 2) }}</td>
                            <td>{{ number_format($taxRate, 3) }}</td>
                            <td class="text-right text-red">{{ number_format($muPph, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($muTarget, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($publishedRate, 0, ',', '.') }}</td>
                            <td class="text-right text-bold">{{ number_format($rounded, 0, ',', '.') }}</td>
                            <td class="text-bold {{ $marginPercent >= 30 ? 'text-green' : 'text-red' }}">
                                {{ number_format($marginPercent, 2) }}%
                            </td>
                        </tr>
                    @endforeach

                    @if($internalBudget->items->isEmpty())
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 20px; color: #6b7280;">
                                No budget items available
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-title">
                BUDGET SUMMARY
            </div>
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Total Subtotal (Rate Base × Qty)</td>
                    <td class="summary-value">Rp {{ number_format($internalBudget->total_subtotal ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
                <tr style="background-color: #fef2f2;">
                    <td class="summary-label" style="background-color: #fef2f2;"><strong>Total MU PPh (Real Cost)
                            *</strong></td>
                    <td class="summary-value text-red"><strong>Rp
                            {{ number_format($internalBudget->total_mu_pph ?? 0, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td class="summary-label">Total MU Target **</td>
                    <td class="summary-value">Rp {{ number_format($internalBudget->total_mu_target ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="summary-label">Total Published Rate ***</td>
                    <td class="summary-value">Rp
                        {{ number_format($internalBudget->total_published_rate ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr style="background-color: #d1fae5;">
                    <td class="summary-label" style="background-color: #d1fae5;"><strong>Total Rounded (Final
                            Budget)</strong></td>
                    <td class="summary-value text-green"><strong>Rp
                            {{ number_format($internalBudget->total_rounded ?? 0, 0, ',', '.') }}</strong></td>
                </tr>
                <tr style="background-color: #dbeafe;">
                    <td class="summary-label" style="background-color: #dbeafe;"><strong>Average Margin</strong></td>
                    <td class="summary-value">
                        <strong>{{ number_format($internalBudget->average_margin_percent ?? 0, 2) }}%</strong></td>
                </tr>
                <tr style="background-color: #dbeafe;">
                    <td class="summary-label" style="background-color: #dbeafe;"><strong>Total Profit (Rounded - MU
                            PPh)</strong></td>
                    <td class="summary-value text-green">
                        <strong>Rp
                            {{ number_format(($internalBudget->total_rounded ?? 0) - ($internalBudget->total_mu_pph ?? 0), 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notes Section -->
        <div
            style="margin-top: 15px; padding: 10px; background-color: #fef3c7; border: 1px solid #f59e0b; font-size: 8px;">
            <strong style="color: #92400e;">NOTES:</strong><br>
            <strong>* MU PPh (Mark Up PPh)</strong> = Real cost after tax calculation (Subtotal / Coefficient)<br>
            <strong>** MU (Mark Up Target)</strong> = Target price with margin (MU PPh / (1 - Margin%))<br>
            <strong>*** Published Rate</strong> = Proposed rate to client (can be adjusted manually)<br>
            <strong>Rounded</strong> = Final budget rounded up to nearest 100,000<br>
            <strong>Margin %</strong> = Actual profit margin ((Rounded - MU PPh) / Rounded × 100)
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on {{ now()->format('d F Y, H:i') }} | <strong>CONFIDENTIAL - INTERNAL USE ONLY</strong></p>
            <p style="margin-top: 5px; color: #dc2626; font-weight: bold;">⚠️ This document contains sensitive financial
                information. Do not share with clients or external parties.</p>
        </div>
    </div>
</body>

</html>