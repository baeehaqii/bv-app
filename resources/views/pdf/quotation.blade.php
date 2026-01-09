<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $mediaPlan->campaign_name ?? 'Beyond Viral' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: white;
            color: #000;
            padding: 20px 30px;
        }
        
        /* Header */
        .header-table {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .logo-cell {
            width: 50%;
            vertical-align: top;
        }
        
        .logo-image {
            max-height: 80px;
            width: auto;
        }
        
        .company-name {
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .quotation-cell {
            width: 50%;
            text-align: right;
            vertical-align: top;
            padding-right: 20px;
        }
        
        .quotation-title {
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #000;
            padding: 10px 30px;
            display: inline-block;
        }
        
        /* Info Section */
        .info-section-table {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        
        .details-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        
        .details-table td:first-child {
            font-weight: bold;
            width: 40%;
            background: #f0f0f0;
        }
        
        /* Campaign Header */
        .campaign-header {
            background: #49009F;
            color: #DAFF01;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #000;
            margin: 10px 0;
        }
        
        /* Main Table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        .main-table th {
            background: #d8b4fe;
            color: #581c87;
            padding: 6px 3px;
            border: 1px solid #4a3a6e;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
        }
        
        .main-table td {
            border: 1px solid #ccc;
            padding: 5px 3px;
            text-align: center;
            font-size: 9px;
            vertical-align: top;
        }
        
        .main-table tbody tr {
            background: #ffe6e6;
        }
        
        .main-table td.left {
            text-align: left;
        }
        
        .main-table td.link-cell {
            font-size: 7px;
            word-break: break-all;
            text-align: left;
        }
        
        /* Cost Table */
        .cost-wrapper {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .cost-table {
            width: 220px;
            border-collapse: collapse;
            font-size: 10px;
            float: right;
        }
        
        .cost-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        
        .cost-table td:first-child {
            font-weight: bold;
            background: #f0f0f0;
            text-align: left;
            width: 120px;
        }
        
        .cost-table td:last-child {
            text-align: right;
        }
        
        .cost-table tr:last-child td {
            font-weight: bold;
            background: #d9d9d9;
        }
        
        /* Terms */
        .terms {
            clear: both;
            font-size: 8px;
            margin: 15px 0 10px 0;
            line-height: 1.6;
        }
        
        .terms-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        /* Signature Section */
        .signature-wrapper {
            width: 100%;
            margin-top: 15px;
        }
        
        .signature-left {
            width: 45%;
            vertical-align: top;
        }
        
        .signature-right {
            width: 45%;
            vertical-align: top;
        }
        
        .signature-spacer {
            width: 10%;
        }
        
        /* Prepared By Box */
        .prepared-box {
            border: 2px solid #000;
            width: 100%;
        }
        
        .prepared-header {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            font-size: 11px;
            border-bottom: 2px solid #000;
        }
        
        .prepared-space {
            height: 80px;
            padding: 10px;
        }
        
        .prepared-name {
            text-align: center;
            font-size: 10px;
            padding: 5px;
            border-top: 1px solid #000;
        }
        
        .prepared-title {
            text-align: center;
            font-size: 10px;
            padding: 5px;
            border-top: 1px solid #000;
        }
        
        .prepared-company {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            padding: 5px;
            border-top: 1px solid #000;
        }
        
        /* Client Approval Box */
        .approval-box {
            border: 2px solid #000;
            width: 100%;
        }
        
        .approval-header {
            background: #d9d9d9;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            font-size: 11px;
            border-bottom: 2px solid #000;
        }
        
        .approval-content {
            width: 100%;
            border-collapse: collapse;
        }
        
        .approval-content td {
            padding: 0;
        }
        
        .approval-row {
            width: 100%;
            border-collapse: collapse;
        }
        
        .approval-row td {
            padding: 10px;
            height: 40px;
            vertical-align: middle;
            border-bottom: 1px solid #000;
        }
        
        .approval-row td:first-child {
            width: 60px;
            font-weight: bold;
            font-size: 10px;
            border-right: 1px solid #000;
        }
        
        .approval-footer-row {
            width: 100%;
            border-collapse: collapse;
        }
        
        .approval-footer-row td {
            width: 50%;
            text-align: center;
            padding: 8px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .approval-footer-row td:first-child {
            border-right: 1px solid #000;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(isset($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Beyond Viral Logo" class="logo-image">
                @else
                    <span style="font-size: 18px; font-weight: bold;">Beyond Viral</span>
                @endif
                <div class="company-name">PT. Bisa Viral Butuh Usaha (BEYOND VIRAL)</div>
            </td>
            <td class="quotation-cell">
                <div class="quotation-title">QUOTATION</div>
            </td>
        </tr>
    </table>
    
    <!-- Info Section -->
    <table class="info-section-table">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <div class="section-title">Client Details</div>
                <table class="details-table">
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
                        <td>{{ $quotationDate }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; vertical-align: top;">
                <div class="section-title">Campaign Details</div>
                <table class="details-table">
                    <tr>
                        <td>Campaign Type</td>
                        <td>{{ $mediaPlan->campaign_type ?? 'Influencer' }}</td>
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
                        <td>{{ $mediaPlan->campaign_period ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Campaign Header -->
    <div class="campaign-header">
        {{ strtoupper($mediaPlan->brand ?? 'BRAND') }} - {{ $mediaPlan->campaign_name ?? 'Campaign' }}
    </div>
    
    <!-- Main Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 9%;">Username</th>
                <th style="width: 12%;">Link</th>
                <th style="width: 7%;">Channel</th>
                <th style="width: 7%;">Category</th>
                <th style="width: 7%;">Followers</th>
                <th style="width: 5%;">Tier</th>
                <th style="width: 5%;">ER %</th>
                <th style="width: 7%;">Avg Views</th>
                <th style="width: 6%;">Engagement</th>
                <th style="width: 3%;">Qty</th>
                <th style="width: 12%;">Scope of Work</th>
                <th style="width: 9%;">Rate</th>
                <th style="width: 8%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($selectedKols as $index => $kol)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="left">{{ $kol->name ?? $kol->username ?? '-' }}</td>
                <td class="link-cell">
                    @if(is_array($kol->links) && count($kol->links) > 0)
                        {{ \Illuminate\Support\Str::limit($kol->links[0] ?? '-', 30) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $kol->channel ?? '-' }}</td>
                <td>{{ $kol->category ?? $kol->categories ?? '-' }}</td>
                <td>{{ number_format($kol->followers ?? 0, 0, ',', '.') }}</td>
                <td>{{ $kol->tier ?? \App\Models\MediaPlanKol::calculateTier($kol->followers ?? 0) }}</td>
                <td>{{ number_format($kol->er_percent ?? 0, 2) }}%</td>
                <td>{{ number_format($kol->impression ?? 0, 0, ',', '.') }}</td>
                <td>{{ number_format($kol->engagement ?? 0, 0, ',', '.') }}</td>
                <td>
                    @if(is_array($kol->scope_items))
                        {{ count($kol->scope_items) }}
                    @else
                        1
                    @endif
                </td>
                <td class="left">
                    @if(is_array($kol->scope_items) && count($kol->scope_items) > 0)
                        {{ implode(', ', $kol->scope_items) }}
                    @else
                        {{ $kol->scope_of_work ?? '-' }}
                    @endif
                </td>
                <td>Rp{{ number_format($kol->rate ?? 0, 0, ',', '.') }}</td>
                <td class="left" style="font-size: 7px;">{{ \Illuminate\Support\Str::limit($kol->notes ?? '', 50) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" style="text-align: center; padding: 20px;">No KOLs selected</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <!-- Cost Summary -->
    <div class="cost-wrapper">
        <table class="cost-table">
            <tr>
                <td>Sub Total Cost:</td>
                <td>Rp. {{ number_format($subTotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PPN ({{ $ppnPercent }}%):</td>
                <td>Rp. {{ number_format($ppnAmount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Grand Total:</td>
                <td>Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    
    <!-- Terms -->
    <div class="terms">
        <div class="terms-title">Term & Condition</div>
        <div>1. Term of payment 30 days after all documents and projects complete 100%</div>
        <div>2. If this quotation has been signed, then the contract is void, the client must pay 50% of the contract value by gross number</div>
        <div>3. For long-term contracts, campaign will be billed every 1 month based on absorption</div>
    </div>
    
    <!-- Signature Section -->
    <table class="signature-wrapper">
        <tr>
            <td class="signature-left">
                <!-- Prepared By Box -->
                <table class="prepared-box">
                    <tr>
                        <td class="prepared-header">Prepared by:</td>
                    </tr>
                    <tr>
                        <td class="prepared-space"></td>
                    </tr>
                    <tr>
                        <td class="prepared-name">{{ $preparedBy }}</td>
                    </tr>
                    <tr>
                        <td class="prepared-title">Business Head</td>
                    </tr>
                    <tr>
                        <td class="prepared-company">BEYOND VIRAL</td>
                    </tr>
                </table>
            </td>
            <td class="signature-spacer"></td>
            <td class="signature-right">
                <!-- Client Approval Box -->
                <table class="approval-box">
                    <tr>
                        <td class="approval-header">Client Approval:</td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <table class="approval-row">
                                <tr>
                                    <td>Name:</td>
                                    <td></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <table class="approval-row">
                                <tr>
                                    <td>Title:</td>
                                    <td></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <table class="approval-footer-row">
                                <tr>
                                    <td>{{ $quotationDate }}</td>
                                    <td>Signature</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>