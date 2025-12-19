<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Plan - {{ $mediaPlan->campaign_name ?? 'Beyond Viral' }}</title>
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

        /* Data Table */
        .data-table-container {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            table-layout: fixed;
        }

        .data-table thead {
            background-color: #d8b4fe;
        }

        .data-table th {
            border: 1px solid #9ca3af;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            color: #581c87;
        }

        .data-table tbody tr {
            background-color: #ffffff;
        }

        .data-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        /* Platform-specific background colors */
        .bg-instagram {
            background-color: #fdf2f8;
        }

        .bg-tiktok {
            background-color: #eff6ff;
        }

        .bg-youtube {
            background-color: #fef3c7;
        }

        .bg-channel {
            background-color: #f3f4f6;
        }

        /* Text styles */
        .text-bold {
            font-weight: bold;
        }

        .text-link {
            color: #2563eb;
            text-decoration: underline;
            word-break: break-all;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .font-semibold {
            font-weight: 600;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
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
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 6px;
        }

        .summary-label {
            font-weight: 600;
            background-color: #f3f4f6;
            width: 30%;
        }

        .summary-value {
            width: 20%;
            text-align: right;
            font-weight: bold;
        }

        /* Column widths for data table */
        .col-no {
            width: 2%;
        }

        .col-domisili {
            width: 8%;
        }

        .col-username {
            width: 8%;
        }

        .col-link {
            width: 7%;
        }

        .col-channel {
            width: 6%;
        }

        .col-category {
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
            width: 7%;
        }

        .col-cpe {
            width: 7%;
        }

        .col-scope {
            width: 10%;
        }

        .col-rate {
            width: 8%;
        }

        .col-notes {
            width: 12%;
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

            <!-- Logo Area -->
            <div class="header-left">
                @if(isset($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Beyond Viral Logo" class="logo-image">
                @else
                    <span style="font-size: 18px; font-weight: bold;">Beyond Viral</span>
                @endif
            </div>

            <!-- Client & Campaign Details Tables -->
            <div class="header-right">
                <div class="details-wrapper">

                    <!-- Client Details -->
                    <div class="details-table">
                        <table class="info-table">
                            <thead>
                                <tr>
                                    <th colspan="2">Client Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Brand</td>
                                    <td>{{ $mediaPlan->brand ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>PIC Client</td>
                                    <td>{{ $mediaPlan->pic_client ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Quotation Number</td>
                                    <td>{{ $mediaPlan->quotation_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Date</td>
                                    <td>{{ $mediaPlan->created_at ? $mediaPlan->created_at->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Campaign Details -->
                    <div class="details-table">
                        <table class="info-table">
                            <thead>
                                <tr>
                                    <th colspan="2">Campaign Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Campaign Type</td>
                                    <td>{{ $mediaPlan->campaign_type ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Platform</td>
                                    <td>{{ $mediaPlan->platform ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Campaign Name</td>
                                    <td>{{ $mediaPlan->campaign_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Campaign Period</td>
                                    <td class="text-right">
                                        @if($mediaPlan->campaign_period_start && $mediaPlan->campaign_period_end)
                                            {{ $mediaPlan->campaign_period_start }} - {{ $mediaPlan->campaign_period_end }}
                                        @elseif($mediaPlan->campaign_period_start)
                                            {{ $mediaPlan->campaign_period_start }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Media Plan Title -->
        <div class="main-title">
            MEDIA PLAN - {{ strtoupper($mediaPlan->campaign_name ?? 'CAMPAIGN') }}
        </div>

        <!-- The Big Data Table -->
        <div class="data-table-container">
            <table class="data-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-domisili">Domisili</th>
                        <th class="col-username">Username</th>
                        <th class="col-link">Link</th>
                        <th class="col-channel">Channel</th>
                        <th class="col-category">Categories</th>
                        <th class="col-followers">Followers</th>
                        <th class="col-tier">Tier</th>
                        <th class="col-er">ER %</th>
                        <th class="col-views">Avg Views</th>
                        <th class="col-engagement">Engagement</th>
                        <th class="col-cpi">CPI/CPV</th>
                        <th class="col-cpe">CPE</th>
                        <th class="col-scope">Scope of Work</th>
                        <th class="col-rate">Rate</th>
                        <th class="col-notes">Notes</th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody>
                    @forelse($mediaPlan->kols as $index => $kol)
                        @php
                            // Determine background color based on channel
                            $channelBg = match (strtolower($kol->channel)) {
                                'instagram' => 'bg-instagram',
                                'tiktok' => 'bg-tiktok',
                                'youtube channels', 'youtube shorts' => 'bg-youtube',
                                default => ''
                            };

                            // Get links array
                            $links = is_array($kol->links) ? $kol->links : [];

                            // Get budget items (scope of work from internal budget)
                            $budgetItems = $kol->internalBudgetItems ?? collect([]);
                            $itemCount = $budgetItems->count();

                            // If no budget items, use scope_items as fallback
                            $fallbackScopeItems = is_array($kol->scope_items) ? $kol->scope_items : [];

                            // Get category - first from model, fallback to DataKol relation
                            $category = $kol->categories ?? $kol->dataKol?->category ?? '-';

                            // Get domisili - from model
                            $domisili = $kol->domisili ?? '-';

                            // Calculate total rate from all budget items (rounded value)
                            $totalRateFromBudget = $budgetItems->sum('rounded');

                            // Determine row count for rowspan
                            $rowCount = max($itemCount, 1);
                        @endphp

                        @if($itemCount > 0)
                            {{-- Multiple rows for budget items --}}
                            @foreach($budgetItems as $itemIndex => $budgetItem)
                                <tr>
                                    @if($itemIndex === 0)
                                        {{-- First row - with rowspan for shared columns --}}
                                        <td class="text-bold" rowspan="{{ $rowCount }}">{{ $index + 1 }}</td>
                                        <td rowspan="{{ $rowCount }}">{{ $domisili }}</td>
                                        <td class="font-semibold" rowspan="{{ $rowCount }}">{{ $kol->name ?? '-' }}</td>
                                    @endif

                                    {{-- Link column - different for each scope item --}}
                                    <td class="text-link text-left">
                                        @if(isset($links[$itemIndex]))
                                            <a href="{{ $links[$itemIndex] }}">{{ Str::limit($links[$itemIndex], 25) }}</a>
                                        @elseif($itemIndex === 0 && count($links) > 0)
                                            <a href="{{ $links[0] }}">{{ Str::limit($links[0], 25) }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    @if($itemIndex === 0)
                                        <td class="bg-channel" rowspan="{{ $rowCount }}">{{ $kol->channel ?? '-' }}</td>
                                        <td rowspan="{{ $rowCount }}">{{ $category }}</td>
                                        <td class="{{ $channelBg }}" rowspan="{{ $rowCount }}">
                                            {{ number_format($kol->followers ?? 0, 0, ',', '.') }}</td>
                                        <td class="{{ $channelBg }}" rowspan="{{ $rowCount }}">{{ $kol->tier ?? '-' }}</td>
                                        <td class="{{ $channelBg }}" rowspan="{{ $rowCount }}">
                                            {{ number_format($kol->er_percent ?? 0, 2) }}%</td>
                                        <td class="{{ $channelBg }}" rowspan="{{ $rowCount }}">
                                            {{ number_format($kol->impression ?? 0, 0, ',', '.') }}</td>
                                        <td class="{{ $channelBg }}" rowspan="{{ $rowCount }}">
                                            {{ number_format($kol->engagement ?? 0, 0, ',', '.') }}</td>
                                        <td rowspan="{{ $rowCount }}">Rp {{ number_format($kol->cpi_cpv ?? 0, 0, ',', '.') }}</td>
                                        <td rowspan="{{ $rowCount }}">Rp {{ number_format($kol->cpe ?? 0, 0, ',', '.') }}</td>
                                    @endif

                                    {{-- Scope item and rate from budget --}}
                                    <td class="text-left">
                                        {{ $budgetItem->qty ?? 1 }}x {{ $budgetItem->scope_item ?? '-' }}
                                    </td>
                                    <td class="text-bold">Rp {{ number_format($budgetItem->rounded ?? 0, 0, ',', '.') }}</td>

                                    @if($itemIndex === 0)
                                        <td class="text-left" rowspan="{{ $rowCount }}">{{ $kol->notes ?? '' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            {{-- Single row fallback when no budget items --}}
                            <tr>
                                <td class="text-bold">{{ $index + 1 }}</td>
                                <td>{{ $domisili }}</td>
                                <td class="font-semibold">{{ $kol->name ?? '-' }}</td>
                                <td class="text-link text-left">
                                    @if(count($links) > 0)
                                        @foreach($links as $link)
                                            <a href="{{ $link }}">{{ Str::limit($link, 25) }}</a>
                                            @if(!$loop->last)<br>@endif
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bg-channel">{{ $kol->channel ?? '-' }}</td>
                                <td>{{ $category }}</td>
                                <td class="{{ $channelBg }}">{{ number_format($kol->followers ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ $channelBg }}">{{ $kol->tier ?? '-' }}</td>
                                <td class="{{ $channelBg }}">{{ number_format($kol->er_percent ?? 0, 2) }}%</td>
                                <td class="{{ $channelBg }}">{{ number_format($kol->impression ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ $channelBg }}">{{ number_format($kol->engagement ?? 0, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($kol->cpi_cpv ?? 0, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($kol->cpe ?? 0, 0, ',', '.') }}</td>
                                <td class="text-left">
                                    @if(count($fallbackScopeItems) > 0)
                                        {{ implode(', ', $fallbackScopeItems) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-bold">Rp {{ number_format($kol->rate ?? 0, 0, ',', '.') }}</td>
                                <td class="text-left">{{ $kol->notes ?? '' }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="16" style="text-align: center; padding: 20px;">
                                Tidak ada data KOL yang dipilih
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        @if($mediaPlan->kols->count() > 0)
            @php
                $totalFollowers = $mediaPlan->kols->sum('followers');
                $totalImpressions = $mediaPlan->kols->sum('impression');
                $totalEngagement = $mediaPlan->kols->sum('engagement');
                $kolCount = $mediaPlan->kols->count();
                
                // Calculate total rate from all InternalBudgetItems (rounded value)
                $totalRateFromBudget = 0;
                $totalScopeItems = 0;
                foreach ($mediaPlan->kols as $kol) {
                    $kolBudgetItems = $kol->internalBudgetItems ?? collect([]);
                    $totalRateFromBudget += $kolBudgetItems->sum('rounded');
                    $totalScopeItems += $kolBudgetItems->count();
                }
                
                // Use rate from budget items if available, otherwise fallback to kol rate
                $displayRate = $totalRateFromBudget > 0 ? $totalRateFromBudget : $mediaPlan->kols->sum('rate');
            @endphp
            <div class="summary-section">
                <div class="summary-title">SUMMARY</div>
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Total KOLs</td>
                        <td class="summary-value">{{ $kolCount }} KOL(s)</td>
                        <td class="summary-label">Total Est. Views</td>
                        <td class="summary-value">{{ number_format($totalImpressions, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Total Followers</td>
                        <td class="summary-value">{{ number_format($totalFollowers, 0, ',', '.') }}</td>
                        <td class="summary-label">Total Est. Engagement</td>
                        <td class="summary-value">{{ number_format($totalEngagement, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Total Scope Items</td>
                        <td class="summary-value">{{ $totalScopeItems }} Item(s)</td>
                        <td class="summary-label">Total Rate (from Budget)</td>
                        <td class="summary-value" style="font-size: 12px; color: #059669;">
                            Rp {{ number_format($displayRate, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if($totalBudget > 0)
                        <tr>
                            <td class="summary-label" colspan="2">Total Budget (Rounded)</td>
                            <td class="summary-value" colspan="2" style="text-align: right; font-size: 12px; color: #49009F;">
                                Rp {{ number_format($totalBudget, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        <div class="footer">
            <p>Generated on {{ now()->format('d M Y H:i') }} | {{ $mediaPlan->quotation_number ?? '-' }}</p>
        </div>
    </div>

</body>

</html>