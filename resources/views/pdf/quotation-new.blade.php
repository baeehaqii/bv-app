<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_number ?? 'Quotation' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; background: white; color: #000; padding: 15px 20px; }

        /* Header */
        .logo-image { max-height: 70px; width: auto; }
        .company-name { font-size: 11px; font-weight: bold; margin-top: 4px; }
        .quotation-title { font-size: 18px; font-weight: bold; border: 2px solid #000; padding: 10px 30px; display: inline-block; }

        /* Info tables */
        .section-title { font-size: 11px; font-weight: bold; margin-bottom: 4px; }
        .details-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .details-table td { border: 1px solid #000; padding: 3px 6px; }
        .details-table td:first-child { background: #f0f0f0; width: 38%; }

        /* Campaign banner */
        .campaign-header { background: #3B3580; color: #DAFF01; text-align: center; padding: 7px; font-size: 13px; font-weight: bold; margin: 10px 0 0 0; }

        /* KOL table */
        .main-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 10px; }
        .main-table th { background-color: #3B3580; color: #fff; padding: 5px 3px; border: 1px solid #6b64b0; font-weight: bold; text-align: center; vertical-align: middle; }
        .main-table td { border: 1px solid #ccc; padding: 4px 3px; text-align: center; vertical-align: middle; }
        .main-table td.left { text-align: left; padding-left: 4px; }
        .main-table td.link-cell { font-size: 7px; word-break: break-all; text-align: left; padding-left: 3px; }

        /* Cost summary */
        .cost-table { width: 240px; border-collapse: collapse; font-size: 9px; float: right; }
        .cost-table td { border: 1px solid #000; padding: 3px 8px; }
        .cost-table td:first-child { font-weight: bold; background: #f0f0f0; text-align: left; }
        .cost-table td:last-child { text-align: right; }
        .cost-table tr:last-child td { font-weight: bold; background: #d9d9d9; }

        /* Terms */
        .terms { clear: both; font-size: 8px; margin: 12px 0 10px; line-height: 1.6; }
        .terms-title { font-weight: bold; margin-bottom: 2px; }

        /* Signature: Prepared By box */
        .sig-box { border: 1.5px solid #000; width: 100%; }
        .sig-header { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; font-size: 10px; border-bottom: 1.5px solid #000; }
        .sig-space { height: 70px; }
        .sig-row { border-top: 1px solid #000; padding: 4px 6px; text-align: center; font-size: 9px; }
        .sig-image { max-height: 60px; max-width: 100%; display: block; margin: 5px auto; }

        /* Signature: Client Approval box */
        .approval-inner { width: 100%; border-collapse: collapse; }
        .approval-inner td { padding: 5px 8px; vertical-align: middle; border-bottom: 1px solid #000; font-size: 9px; }
        .approval-inner td.label { border-right: 1px solid #000; font-weight: bold; width: 50%; }
        .approval-footer { width: 100%; border-collapse: collapse; }
        .approval-footer td { width: 50%; text-align: center; padding: 5px 8px; font-size: 9px; font-weight: bold; }
        .approval-footer td:first-child { border-right: 1px solid #000; }
    </style>
</head>
<body>

{{-- Header --}}
<table style="width:100%;margin-bottom:12px;">
    <tr>
        <td style="width:50%;vertical-align:top;">
            @if(isset($logoBase64))
                <img src="{{ $logoBase64 }}" alt="BV Logo" class="logo-image">
            @else
                <span style="font-size:18px;font-weight:bold;">Beyond Viral</span>
            @endif
            <div class="company-name">PT. Bisa Viral Butuh Usaha (BEYOND VIRAL)</div>
        </td>
        <td style="width:50%;text-align:right;vertical-align:top;padding-right:20px;">
            <div class="quotation-title">QUOTATION</div>
        </td>
    </tr>
</table>

{{-- Client Details + Campaign Details --}}
<table style="width:100%;margin-bottom:10px;">
    <tr>
        <td style="width:45%;vertical-align:top;">
            <div class="section-title">Client Details</div>
            <table class="details-table">
                <tr><td>Brand</td><td>{{ $quotation->client_brand ?? $mediaPlan?->brand ?? '-' }}</td></tr>
                <tr><td>PIC Client</td><td>{{ $mediaPlan?->pic_client ?? '-' }}</td></tr>
                <tr><td>Quotation Number</td><td>{{ $quotation->quotation_number ?? '-' }}</td></tr>
                <tr><td>Date</td><td>{{ $quotation->quotation_date?->format('d M Y') ?? '-' }}</td></tr>
            </table>
        </td>
        <td style="width:10%;"></td>
        <td style="width:45%;vertical-align:top;">
            <div class="section-title">Campaign Details</div>
            <table class="details-table">
                <tr><td>Campaign Type</td><td>{{ $mediaPlan?->campaign_type ?? 'Influencer' }}</td></tr>
                <tr><td>Platform</td><td>{{ $mediaPlan?->platform ?? '-' }}</td></tr>
                <tr><td>Campaign Name</td><td>{{ $mediaPlan?->campaign_name ?? '-' }}</td></tr>
                <tr><td>Campaign Period</td><td>{{ $mediaPlan?->campaign_period ?? ($mediaPlan?->campaign_period_start ?? '-') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Campaign Banner --}}
<div class="campaign-header">
    {{ strtoupper($quotation->client_brand ?? $mediaPlan?->brand ?? 'BRAND') }}
    @if($mediaPlan?->campaign_name)
        &nbsp;-&nbsp;{{ $mediaPlan->campaign_name }}
    @endif
</div>

{{-- KOL Table --}}
<table class="main-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:3%;">No</th>
            <th rowspan="2" style="width:9%;">Username</th>
            <th rowspan="2" style="width:11%;">Link</th>
            <th rowspan="2" style="width:7%;">Channel</th>
            <th rowspan="2" style="width:7%;">Category</th>
            <th rowspan="2" style="width:7%;">Followers</th>
            <th rowspan="2" style="width:5%;">Tier</th>
            <th rowspan="2" style="width:5%;">ER %</th>
            <th rowspan="2" style="width:7%;">Avg<br>Views</th>
            <th rowspan="2" style="width:6%;">Engagement</th>
            <th colspan="2" style="width:17%;background-color:#3B3580;border:1px solid #6b64b0;">Scope of Work</th>
            <th rowspan="2" style="width:9%;">Rate</th>
            <th rowspan="2" style="width:7%;">Notes</th>
        </tr>
        <tr>
            <th style="width:4%;">Qty</th>
            <th style="width:13%;">Item</th>
        </tr>
    </thead>
    <tbody>
        @forelse($kolGroups as $items)
            @php $rowBg = $loop->iteration % 2 === 0 ? '#f5f5f5' : '#ffffff'; @endphp
            @foreach($items as $item)
            <tr>
                @if($loop->first)
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ $loop->parent->iteration }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}" class="left">{{ $item->mediaPlanKol?->name ?? '-' }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}" class="link-cell">{{ \Illuminate\Support\Str::limit(($item->mediaPlanKol?->links[0] ?? ''), 35) }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ $item->mediaPlanKol?->channel ?? '-' }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}" class="left">{{ $item->mediaPlanKol?->categories ?? '-' }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ number_format($item->mediaPlanKol?->followers ?? 0, 0, ',', '.') }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ $item->mediaPlanKol?->tier ?? '-' }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ number_format($item->mediaPlanKol?->er_percent ?? 0, 2) }}%</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ number_format($item->mediaPlanKol?->impression ?? 0, 0, ',', '.') }}</td>
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}">{{ number_format($item->mediaPlanKol?->engagement ?? 0, 0, ',', '.') }}</td>
                @endif
                <td style="background:{{ $rowBg }}">{{ $item->qty ?? 1 }}</td>
                <td style="background:{{ $rowBg }}" class="left">{{ $item->scope_item ?? '-' }}</td>
                <td style="background:{{ $rowBg }}">Rp{{ number_format($item->rounded ?? 0, 0, ',', '.') }}</td>
                @if($loop->first)
                <td rowspan="{{ $loop->count }}" style="background:{{ $rowBg }}" class="left" style="font-size:7px;">{{ $items->first()->notes ?? '' }}</td>
                @endif
            </tr>
            @endforeach
        @empty
        <tr><td colspan="14" style="text-align:center;padding:15px;color:#999;">No KOL items.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Cost Summary --}}
<div style="overflow:hidden;margin-bottom:8px;">
    <table class="cost-table">
        <tr>
            <td>Sub Total Cost</td>
            <td>Rp. {{ number_format($subTotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>PPH Final</td>
            <td>Rp. {{ number_format($pphFinal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Grand Total</td>
            <td>Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

{{-- Terms & Conditions --}}
<div class="terms">
    <div class="terms-title">Term &amp; Condition</div>
    @if($quotation->terms_conditions)
        {!! nl2br(e($quotation->terms_conditions)) !!}
    @else
        <div>1. Term of payment 30 working days after all documents and projects complete 100%</div>
        <div>2. If this quotation has been signed, then the contract is void, the client must pay 50% of the contract value by gross number</div>
        <div>3. For long-term contracts, campaign will be billed every 1 month based on absorbsion</div>
    @endif
</div>

{{-- Signature Section --}}
@php
    $preparedBy = $signatories[0] ?? ['name' => auth()->user()?->name ?? 'Beyond Viral Team', 'role' => 'Team', 'signature_base64' => null];
    $clientSig  = $signatories[1] ?? ['name' => null, 'role' => null, 'signature_base64' => null];
@endphp
<table style="width:100%;margin-top:12px;">
    <tr>
        {{-- Prepared By --}}
        <td style="width:45%;vertical-align:top;">
            <table class="sig-box">
                <tr><td class="sig-header">Prepared by:</td></tr>
                <tr>
                    <td class="sig-space" style="text-align:center;">
                        @if(!empty($preparedBy['signature_base64']))
                            <img src="{{ $preparedBy['signature_base64'] }}" class="sig-image" alt="TTD">
                        @endif
                    </td>
                </tr>
                <tr><td class="sig-row">{{ $preparedBy['name'] ?? '' }}</td></tr>
                <tr><td class="sig-row">{{ $preparedBy['role'] ?? '' }}</td></tr>
                <tr><td class="sig-row" style="font-weight:bold;">BV Network</td></tr>
            </table>
        </td>
        <td style="width:10%;"></td>
        {{-- Client Approval --}}
        <td style="width:45%;vertical-align:top;">
            <table class="sig-box">
                <tr><td class="sig-header" colspan="2">Client Approval:</td></tr>
                <tr>
                    <td style="padding:0;" colspan="2">
                        <table class="approval-inner">
                            <tr>
                                <td class="label">Name: {{ $clientSig['name'] ?? '' }}</td>
                                <td>
                                    @if(!empty($clientSig['signature_base64']))
                                        <img src="{{ $clientSig['signature_base64'] }}" class="sig-image" alt="TTD Client">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Title: {{ $clientSig['role'] ?? '' }}</td>
                                <td></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;" colspan="2">
                        <table class="approval-footer">
                            <tr>
                                <td>{{ $quotation->quotation_date?->format('d M Y') ?? '-' }}</td>
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
