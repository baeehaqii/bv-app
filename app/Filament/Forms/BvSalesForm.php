<?php

namespace App\Filament\Forms;

use App\Enums\SalesStatus;
use App\Models\BvSalesList;
use App\Models\DataClient;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BvSalesForm
{
    public static function getFormComponents(): array
    {
        return [
            Section::make('Campaign Information')
            ->description('Informasi campaign yang akan di kerjakan')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('event_name')
                                ->label('Event/Campaign Name')
                                ->placeholder('e.g. Campaign Ramadan 2026')
                                ->required()
                                ->maxLength(255),

                            Select::make('bv_sales_list_id')
                                ->label('Sales Name')
                                ->relationship('salesList', 'nama_sales')
                                ->searchable()
                                ->preload()
                                ->required(),


                            Placeholder::make('company_display')
                                ->label('Company / Client')
                                ->content(fn($record) => $record?->company_name ?? '-')
                                ->hintAction(
                                    Action::make('viewClient')
                                        ->label('Detail')
                                        ->icon('heroicon-o-eye')
                                        ->color('primary')
                                        ->modalHeading(fn($record) => $record?->company_name ?? 'Client Detail')
                                        ->modalWidth('2xl')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Tutup')
                                        ->infolist(function ($record) {
                                            $client = DataClient::where('nama_brand', $record?->company_name)->first();
                                            if (!$client) {
                                                return [
                                                    TextEntry::make('not_found')
                                                        ->label('')
                                                        ->getStateUsing(fn() => 'Data client tidak ditemukan')
                                                        ->color('danger'),
                                                ];
                                            }
                                            return [
                                                \Filament\Schemas\Components\Section::make('Client Information')
                                                    ->schema([
                                                        Grid::make(2)->schema([
                                                            TextEntry::make('type')
                                                                ->label('Client Type')
                                                                ->getStateUsing(fn() => ucfirst($client->type ?? '-'))
                                                                ->badge()
                                                                ->color(fn() => $client->type === 'agency' ? 'warning' : 'info'),
                                                            TextEntry::make('nama_brand')
                                                                ->label('Brand Name')
                                                                ->getStateUsing(fn() => $client->nama_brand ?? '-'),
                                                            TextEntry::make('produk')
                                                                ->label('Product')
                                                                ->getStateUsing(fn() => $client->produk ?? '-'),
                                                            TextEntry::make('category')
                                                                ->label('Category')
                                                                ->getStateUsing(fn() => $client->category ?? '-'),
                                                            TextEntry::make('priority')
                                                                ->label('Priority')
                                                                ->getStateUsing(fn() => $client->priority ?? '-'),
                                                            TextEntry::make('website')
                                                                ->label('Website')
                                                                ->getStateUsing(fn() => $client->website ?? '-')
                                                                ->url(fn() => $client->website),
                                                        ]),
                                                    ]),
                                                \Filament\Schemas\Components\Section::make('PIC Details')
                                                    ->schema([
                                                        Grid::make(2)->schema(
                                                            $client->type === 'agency' && !empty($client->pics)
                                                            ? collect($client->pics)->flatMap(fn($pic, $i) => [
                                                                TextEntry::make("pic_{$i}_name")
                                                                    ->label('Name')
                                                                    ->getStateUsing(fn() => $pic['name'] ?? '-'),
                                                                TextEntry::make("pic_{$i}_wa")
                                                                    ->label('WhatsApp')
                                                                    ->getStateUsing(fn() => $pic['wa_number'] ?? '-'),
                                                                TextEntry::make("pic_{$i}_email")
                                                                    ->label('Email')
                                                                    ->getStateUsing(fn() => $pic['email'] ?? '-'),
                                                                TextEntry::make("pic_{$i}_role")
                                                                    ->label('Role')
                                                                    ->getStateUsing(fn() => $pic['role'] ?? '-'),
                                                            ])->toArray()
                                                            : [
                                                                TextEntry::make('nama_pic')
                                                                    ->label('PIC Name')
                                                                    ->getStateUsing(fn() => $client->nama_pic ?? '-'),
                                                                TextEntry::make('role_pic')
                                                                    ->label('PIC Role')
                                                                    ->getStateUsing(fn() => $client->role_pic ?? '-'),
                                                                TextEntry::make('email_pic')
                                                                    ->label('PIC Email')
                                                                    ->getStateUsing(fn() => $client->email_pic ?? '-')
                                                                    ->columnSpanFull(),
                                                            ]
                                                        ),
                                                    ]),
                                                \Filament\Schemas\Components\Section::make('Tracking')
                                                    ->schema([
                                                        Grid::make(2)->schema([
                                                            TextEntry::make('status')
                                                                ->label('Status')
                                                                ->getStateUsing(fn() => $client->status ?? '-')
                                                                ->badge(),
                                                            TextEntry::make('date_outreach')
                                                                ->label('Date Outreach')
                                                                ->getStateUsing(fn() => $client->date_outreach ?? '-'),
                                                            TextEntry::make('date_follow_up')
                                                                ->label('Date Follow Up')
                                                                ->getStateUsing(fn() => $client->date_follow_up ?? '-'),
                                                            TextEntry::make('notes')
                                                                ->label('Notes')
                                                                ->getStateUsing(fn() => $client->notes ?? '-')
                                                                ->columnSpanFull(),
                                                        ]),
                                                    ]),
                                            ];
                                        })
                                )
                                ->hidden(fn(string $operation): bool => $operation === 'create'),

                            Select::make('campaign_items')
                                ->label('Campaign Items')
                                ->multiple()
                                ->options([
                                    'Active Services' => [
                                        'influencer' => 'Influencer',
                                        'social_media_mgmt' => 'Social Media Management',
                                    ],
                                    'Coming Soon' => [
                                        'affiliate' => 'Affiliate (Soon)',
                                        'smm' => 'SMM (Soon)',
                                        'tiktok_clippers' => 'TikTok Clipper (Soon)',
                                        'digital_video' => 'Digital Video (Soon)',
                                    ],
                                ])
                                ->searchable(),

                            TextInput::make('budget_propose')
                                ->label('Budget Propose')
                                ->hintIcon('heroicon-m-information-circle', tooltip: 'Budget awal yang diajukan ke klien')
                                ->prefix('Rp')
                                ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                                ->stripCharacters(['.'])
                                ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                                ->default(0),

                            TextInput::make('deal_value')
                                ->label('Deal Value')
                                ->hintIcon('heroicon-m-information-circle', tooltip: 'Nilai akhir yang disepakati')
                                ->prefix('Rp')
                                ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                                ->stripCharacters(['.'])
                                ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                                ->default(0),

                            Select::make('company_name')
                                ->label('Company Name')
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => DataClient::where('nama_brand', 'like', "%{$search}%")->limit(50)->pluck('nama_brand', 'nama_brand')->toArray())
                                ->options(DataClient::limit(50)->pluck('nama_brand', 'nama_brand'))
                                ->createOptionForm(\App\Filament\Resources\DataClients\Schemas\DataClientForm::getFormSchema())
                                ->createOptionUsing(function (array $data): string {
                                    $client = DataClient::create($data);
                                    return $client->nama_brand;
                                })
                                ->placeholder('Pilih atau buat')
                                ->required()
                                ->hidden(fn(string $operation): bool => $operation === 'edit'),
                        ]),
                ]),

            Section::make('Campaign Schedule')
            ->description('Informasi timeline campaign yang akan di kerjakan')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('campaign_year')
                                ->label('Campaign Year')
                                ->options(function () {
                                    $currentYear = now()->year;
                                    $years = [];
                                    for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                        $years[$i] = (string) $i;
                                    }
                                    return $years;
                                })
                                ->default(now()->year),

                            Select::make('campaign_month')
                                ->label('Bulan Campaign')
                                ->placeholder('Pilih Bulan Campaign')
                                ->options(function () {
                                    $months = [];
                                    for ($i = 1; $i <= 12; $i++) {
                                        $months[$i] = \Carbon\Carbon::createFromDate(null, $i, 1)->translatedFormat('F');
                                    }
                                    return $months;
                                })
                                ->native(false),

                            DatePicker::make('campaign_date')
                                ->label('Tanggal Campaign')
                                ->placeholder('Pilih Tanggal Campaign')
                                ->native(false)
                                ->displayFormat('d M Y'),

                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->placeholder('Pilih Start Date')
                                ->native(false)
                                ->displayFormat('d M Y'),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->placeholder('Pilih End Date')
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->afterOrEqual('start_date'),

                            DatePicker::make('close_date')
                                ->native(false)
                                ->placeholder('Pilih Close Date')
                                ->displayFormat('d M Y')
                                ->label('Close Date'),

                            TextInput::make('pic_media_plan')
                                ->label('PIC Media Plan / Internal')
                                ->placeholder('Masukkan PIC Media Plan...'),

                            DatePicker::make('brief_submit_date')
                                ->label('Tanggal Dapat Brief')
                                ->placeholder('Pilih Tanggal Dapat Brief')
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->helperText('Tanggal menerima brief dari client'),
                        ]),

                    Select::make('form_brief_id')
                        ->label('Select from Brief')
                        ->placeholder('Pilih brief dari client...')
                        ->options(function () {
                            return \App\Models\FormBrief::where('status', 'submitted')
                                ->orWhere('status', 'reviewed')
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(fn(\App\Models\FormBrief $brief) => [
                                    $brief->id => $brief->title . ' — ' . ($brief->submitted_by_name ?? 'Unknown'),
                                ]);
                        })
                        ->searchable()->columnSpanFull()
                        ->preload(),
                ]),

            Section::make('Status & Detail Campaign')
            ->description('Informasi status dan detail campaign')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(SalesStatus::toArray()) ->columnSpanFull()
                                ->default(SalesStatus::NOT_STARTED->value)
                                ->required(),

                            Textarea::make('detail')
                                ->label('Detail')
                                ->placeholder('Masukan detail jika ada...')
                                ->rows(3)
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('Brief & History')
                ->description('Upload brief file dan pencatatan tanggal')
                ->icon('heroicon-o-document-arrow-up')
                ->collapsible()
                ->collapsed()
                ->hidden(fn(string $operation): bool => $operation === 'create')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            FileUpload::make('brief_files')
                                ->label('Brief Files')
                                ->multiple()
                                ->directory('sales-briefs')
                                ->acceptedFileTypes(['application/pdf', 'text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/png', 'image/jpeg'])
                                ->maxSize(10240)
                                ->downloadable()
                                ->openable()
                                ->reorderable()
                                ->columnSpan(2),

                            TextInput::make('brief_link')
                                ->label('Brief Link')
                                ->placeholder('https://...')
                                ->url()
                                ->suffixIcon('heroicon-o-link'),
                        ]),
                ]),
        ];
    }
}
