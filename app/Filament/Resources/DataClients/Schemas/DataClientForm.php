<?php

namespace App\Filament\Resources\DataClients\Schemas;

use App\Models\BvSalesList;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DataClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            // ─── Client Information ───────────────────────────────────────────
            Section::make('Client Information')
                ->description('Detail dasar mengenai client')
                ->schema([
                    Select::make('type')
                        ->label('Client Type')
                        ->options([
                            'direct' => 'Direct Brand',
                            'agency' => 'Agency',
                        ])
                        ->default('direct')
                        ->live()
                        ->native(false)
                        ->required(),

                    TextInput::make('nama_brand')
                        ->label('Nama Brand')->placeholder('Masukan nama brand...')
                        ->visible(fn(Get $get) => $get('type') === 'direct')
                        ->required(),

                    // Nama Agency (Client Type 5: Detail Agency bisa diisi untuk semua tipe)
                    \Filament\Forms\Components\TagsInput::make('agency_name')
                        ->label('Daftar Agency')
                        ->placeholder('Tambah agency lalu tekan Enter...')
                        ->helperText('Bisa memasukkan lebih dari satu nama agency')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set, $state, $old, \Livewire\Component $livewire, $component) {
                            $tags = is_array($state) ? $state : [];
                            $oldTags = is_array($old) ? $old : [];

                            $existingPics = is_array($get('pics')) ? collect($get('pics')) : collect();
                            $newPics = [];
                            foreach ($tags as $tag) {
                                $existing = $existingPics->firstWhere('agency', $tag);
                                $newPics[] = [
                                    'agency' => $tag,
                                    'name' => $existing['name'] ?? null,
                                    'wa_number' => $existing['wa_number'] ?? null,
                                    'email' => $existing['email'] ?? null,
                                    'description' => $existing['description'] ?? null,
                                ];
                            }
                            $set('pics', array_values($newPics));
                        })
                        ->suffixAction(
                            \Filament\Actions\Action::make('managePics')
                                ->label('Detail PIC')
                                ->icon('heroicon-m-user-plus')
                                ->slideOver()
                                ->modalHeading('Detail PIC Agency')
                                ->modalDescription('Lengkapi informasi PIC untuk masing-masing agency yang dipilih.')
                                ->form([
                                    \Filament\Forms\Components\Repeater::make('pics_modal')
                                        ->label('')
                                        ->schema([
                                            \Filament\Forms\Components\TextInput::make('agency')
                                                ->label('Agency')
                                                ->readOnly()
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('name')
                                                ->label('Nama PIC Agency')
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('email')
                                                ->label('Alamat Email PIC Agency')
                                                ->email()
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('wa_number')
                                                ->label('Nomor WhatsApp PIC Agency')
                                                ->tel()
                                                ->required(),
                                            \Filament\Forms\Components\Textarea::make('description')
                                                ->label('Deskripsi PIC Agency')
                                                ->required(),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->columns(1)
                                ])
                                ->mountUsing(function ($form, $get) {
                                    $form->fill([
                                        'pics_modal' => is_array($get('pics')) ? $get('pics') : [],
                                    ]);
                                })
                                ->action(function (array $data, $set) {
                                    $set('pics', $data['pics_modal'] ?? []);
                                })
                        ),

                    Select::make('category')
                        ->label('Kategori')->required()
                        ->options([
                            'FMCG' => 'FMCG',
                            'E-Commerce & Tech' => 'E-Commerce & Tech',
                            'Fintech & Banking' => 'Fintech & Banking',
                            'Beauty & Skincare' => 'Beauty & Skincare',
                            'Automotive' => 'Automotive',
                            'Telecommunication' => 'Telecommunication',
                            'Pharmaceuticals' => 'Pharmaceuticals',
                            'Retail & Fashion' => 'Retail & Fashion',
                        ])
                        ->searchable()
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('category')
                                ->label('Kategori Baru')
                                ->required(),
                        ])
                        ->createOptionUsing(fn(array $data): string => $data['category'])
                        ->createOptionAction(fn($action) => $action->label('Tambah Kategori')),
                    Select::make('priority')
                        ->label('Prioritas')->required()
                        ->options([
                            'P0' => 'P0',
                            'P1' => 'P1',
                            'P2' => 'P2',
                            'P3' => 'P3',
                        ])
                        ->searchable()
                        ->native(false),
                    TextInput::make('website')->required()
                        ->label('Website')->placeholder('https://www.contohwebsite.com')
                        ->url(),
                    TextInput::make('parent_brand')
                        ->placeholder('Jika ini sub-brand, isi nama brand induknya')
                        ->label('Parent Brand')->required(),
                    TextInput::make('instagram')
                        ->label('Instagram')->placeholder('@contohbrand')->required(),
                    TextInput::make('tiktok')->placeholder('@contohbrand')
                        ->label('TikTok'),
                    TextInput::make('youtube')->placeholder('@contohchannel')
                        ->label('YouTube'),
                    TextInput::make('threads')->placeholder('@contohbrand')
                        ->label('Threads'),
                    TextInput::make('top')
                        ->label('Term of Payment (hari)')
                        ->numeric()->placeholder('Term of Payment')
                        ->suffix('hari')->required(),
                    Select::make(name: 'status_client')
                        ->label('Status Client')->required()
                        ->options([
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                        ])
                ])
                ->collapsible()->columns(2),

            // ─── PIC Section ─────────────────────────────────────────────────
            Section::make('PIC')
                ->description('Informasi Person in Charge')
                ->schema([

                    // DC-03: PIC Internal (Sales) — selalu tampil, pilih dari data sales BV
                    Select::make('pic_internal_sales_id')
                        ->label('PIC Internal (Sales)')
                        ->helperText('Sales person internal BV yang bertanggung jawab untuk client ini')
                        ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    // DC-03: PIC sesuai Client Type — Direct
                    Group::make([
                        TextInput::make('nama_pic')
                            ->label('Nama PIC Direct Brand')->placeholder('Nama PIC untuk Direct Brand...')->required(),
                        TextInput::make('role_pic')
                            ->label('Jabatan PIC')->placeholder('Jabatan PIC untuk Direct Brand...')->required(),
                        TextInput::make('email_pic')
                            ->email()->placeholder('email@contoh.com')->required()
                            ->label('Email PIC'),
                    ])
                        ->columns(3)
                        ->visible(fn(Get $get) => $get('type') !== 'agency'),

                    // DC-06: PIC ke-2 — hanya untuk Direct Brand, opsional
                    Group::make([
                        TextInput::make('nama_pic_2')
                            ->label('Nama PIC ke-2')->placeholder('Nama PIC kedua (opsional)...'),
                        TextInput::make('role_pic_2')
                            ->label('Jabatan PIC ke-2')->placeholder('Jabatan PIC kedua...'),
                        TextInput::make('email_pic_2')
                            ->email()->placeholder('email@contoh.com')
                            ->label('Email PIC ke-2'),
                    ])
                        ->columns(3)
                        ->visible(fn(Get $get) => $get('type') !== 'agency'),

                    // DC-03: PIC sesuai Client Type — Agency (repeater)
                    Repeater::make('pics')
                        ->label('Daftar PIC Masing-Masing Agency')
                        ->schema([
                            TextInput::make('agency')
                                ->label('Agency')
                                ->readOnly()
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama')->placeholder('Nama PIC Agency...')
                                ->required(),
                            TextInput::make('email')
                                ->email()->placeholder('email@contoh.com')
                                ->label('Email')->required(),
                            TextInput::make('wa_number')
                                ->label('Nomor WhatsApp')->placeholder('081234567890')
                                ->tel()
                                ->required(),
                            Textarea::make('description')
                                ->label('Deskripsi')->required()->placeholder('Deskripsi PIC Agency...'),
                        ])
                        ->columns(2)
                        ->visible(fn(Get $get) => is_array($get('agency_name')) && count($get('agency_name')) > 0)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->collapsible(),

            // ─── Tracking & Notes ────────────────────────────────────────────
            Section::make('Tracking & Catatan')
                ->description('Status, jadwal outreach, dan catatan tambahan')
                ->schema([
                    Select::make('status')
                        ->label('Status Outreach')->required()
                        ->options([
                            'Newest' => 'Newest',
                            'Number of Meeting' => 'Number of Meeting',
                            'Brief' => 'Brief',
                            'Waiting Feedback' => 'Waiting Feedback',
                            'On Going' => 'On Going',
                            'Not Available' => 'Not Available',
                        ])
                        ->default('Newest')
                        ->live()
                        ->native(false),
                    DatePicker::make('date_outreach')
                        ->label('Tanggal Outreach')->required()->default(now()),
                    DatePicker::make('date_follow_up')
                        ->label('Tanggal Follow Up')->required()->visible(fn(Get $get) => in_array($get('status'), ['Number of Meeting', 'Brief', 'Waiting Feedback'])),
                    Textarea::make('notes')
                        ->label('Catatan')->columnSpanFull()
                        ->placeholder('Catatan tambahan mengenai client, hasil meeting, dll...'),
                ])->columns(3)
                ->collapsible(),
        ];
    }
}
