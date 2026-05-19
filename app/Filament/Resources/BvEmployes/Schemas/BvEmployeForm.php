<?php

namespace App\Filament\Resources\BvEmployes\Schemas;

use App\Filament\Resources\BvEmployes\Pages\CreateBvEmploye;
use App\Models\BvEmploye;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class BvEmployeForm
{
    private const PROVINSI_INDONESIA = [
        'Aceh'                    => 'Aceh',
        'Bali'                    => 'Bali',
        'Banten'                  => 'Banten',
        'Bengkulu'                => 'Bengkulu',
        'DI Yogyakarta'           => 'DI Yogyakarta',
        'DKI Jakarta'             => 'DKI Jakarta',
        'Gorontalo'               => 'Gorontalo',
        'Jambi'                   => 'Jambi',
        'Jawa Barat'              => 'Jawa Barat',
        'Jawa Tengah'             => 'Jawa Tengah',
        'Jawa Timur'              => 'Jawa Timur',
        'Kalimantan Barat'        => 'Kalimantan Barat',
        'Kalimantan Selatan'      => 'Kalimantan Selatan',
        'Kalimantan Tengah'       => 'Kalimantan Tengah',
        'Kalimantan Timur'        => 'Kalimantan Timur',
        'Kalimantan Utara'        => 'Kalimantan Utara',
        'Kepulauan Bangka Belitung' => 'Kepulauan Bangka Belitung',
        'Kepulauan Riau'          => 'Kepulauan Riau',
        'Lampung'                 => 'Lampung',
        'Maluku'                  => 'Maluku',
        'Maluku Utara'            => 'Maluku Utara',
        'Nusa Tenggara Barat'     => 'Nusa Tenggara Barat',
        'Nusa Tenggara Timur'     => 'Nusa Tenggara Timur',
        'Papua'                   => 'Papua',
        'Papua Barat'             => 'Papua Barat',
        'Papua Barat Daya'        => 'Papua Barat Daya',
        'Papua Pegunungan'        => 'Papua Pegunungan',
        'Papua Selatan'           => 'Papua Selatan',
        'Papua Tengah'            => 'Papua Tengah',
        'Riau'                    => 'Riau',
        'Sulawesi Barat'          => 'Sulawesi Barat',
        'Sulawesi Selatan'        => 'Sulawesi Selatan',
        'Sulawesi Tengah'         => 'Sulawesi Tengah',
        'Sulawesi Tenggara'       => 'Sulawesi Tenggara',
        'Sulawesi Utara'          => 'Sulawesi Utara',
        'Sumatera Barat'          => 'Sumatera Barat',
        'Sumatera Selatan'        => 'Sumatera Selatan',
        'Sumatera Utara'          => 'Sumatera Utara',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->placeholder('Contoh: Budi Santoso')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('nama.karyawan')
                            ->suffix('@bvnetwork.net')
                            ->required()
                            ->columnSpan(2)
                            ->formatStateUsing(
                                fn (?string $state) => $state
                                    ? preg_replace('/@bvnetwork\.net$/i', '', $state)
                                    : $state
                            )
                            ->dehydrateStateUsing(
                                fn (?string $state) => filled($state)
                                    ? (str_contains($state, '@') ? $state : trim($state) . '@bvnetwork.net')
                                    : $state
                            )
                            ->live(debounce: 600)
                            ->afterStateUpdated(function (?string $state, callable $set) {
                                // Jika user mengetik email lengkap, ambil bagian lokal saja
                                if (filled($state) && str_contains($state, '@')) {
                                    $set('email', preg_replace('/@.*$/', '', $state));
                                }
                            })
                            ->rule(function (Get $get, ?Model $record) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($record) {
                                    if (blank($value)) {
                                        return;
                                    }
                                    $email = str_contains($value, '@')
                                        ? $value
                                        : trim($value) . '@bvnetwork.net';

                                    $exists = BvEmploye::where('email', $email)
                                        ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->exists();

                                    if ($exists) {
                                        $fail('Email ini sudah terdaftar.');
                                    }
                                };
                            }),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->placeholder('08xxxxxxxxxx')
                            ->required(),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->placeholder('Jl. Contoh No. 123')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('kota')
                            ->label('Kota')
                            ->placeholder('Contoh: Jakarta Selatan')
                            ->required(),

                        Select::make('provinsi')
                            ->label('Provinsi')
                            ->placeholder('Pilih provinsi')
                            ->options(self::PROVINSI_INDONESIA)
                            ->searchable()
                            ->required(),

                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->placeholder('12345')
                            ->required(),

                        FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->nullable(),
                    ]),

                Section::make('Struktur Organisasi')
                    ->columns(2)
                    ->schema([
                        Select::make('_division_id')
                            ->label('Divisi')
                            ->placeholder('Pilih divisi')
                            ->options(Division::where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn(callable $set) => $set('_department_id', null) ?: $set('position_id', null)),

                        Select::make('_department_id')
                            ->label('Departemen')
                            ->placeholder('Pilih departemen')
                            ->options(function (callable $get) {
                                $divisionId = $get('_division_id');
                                if (! $divisionId) {
                                    return Department::with('division')
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn($d) => [$d->id => $d->division->name . ' › ' . $d->name]);
                                }
                                return Department::where('division_id', $divisionId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn(callable $set) => $set('position_id', null)),

                        Select::make('position_id')
                            ->label('Jabatan')
                            ->placeholder('Pilih jabatan')
                            ->options(function (callable $get) {
                                $deptId = $get('_department_id');
                                if ($deptId) {
                                    return Position::where('department_id', $deptId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                }
                                return Position::with('department.division')
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn($p) => [
                                        $p->id => $p->department->division->name
                                            . ' › ' . $p->department->name
                                            . ' › ' . $p->name,
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        Select::make('reports_to_id')
                            ->label('Melapor Kepada')
                            ->placeholder('Pilih atasan (opsional)')
                            ->options(
                                BvEmploye::with('position.department.division')
                                    ->whereNotNull('position_id')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => $e->nama_lengkap
                                            . ($e->position ? ' (' . $e->position->name . ')' : ''),
                                    ])
                            )
                            ->searchable()
                            ->nullable()
                            ->columnSpan(2),
                    ]),

                Section::make('Akses Sistem')
                    ->description('Password untuk login ke panel. Hanya diisi saat tambah karyawan baru.')
                    ->columns(2)
                    ->visibleOn(CreateBvEmploye::class)
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->placeholder('Minimal 8 karakter')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->placeholder('Ulangi password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ]);
    }
}


class BvEmployeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pribadi')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->endsWith('@bvnetwork.net')
                            ->validationMessages([
                                'ends_with' => 'Email harus menggunakan domain @bvnetwork.net',
                            ]),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->required(),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('kota')
                            ->label('Kota')
                            ->required(),

                        TextInput::make('provinsi')
                            ->label('Provinsi')
                            ->required(),

                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->required(),

                        FileUpload::make('photo')
                            ->label('Foto')
                            ->image()
                            ->nullable(),
                    ]),

                Section::make('Struktur Organisasi')
                    ->columns(2)
                    ->schema([
                        Select::make('_division_id')
                            ->label('Divisi')
                            ->options(Division::where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn(callable $set) => $set('_department_id', null) ?: $set('position_id', null)),

                        Select::make('_department_id')
                            ->label('Departemen')
                            ->options(function (callable $get) {
                                $divisionId = $get('_division_id');
                                if (! $divisionId) {
                                    return Department::with('division')
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn($d) => [$d->id => $d->division->name . ' › ' . $d->name]);
                                }
                                return Department::where('division_id', $divisionId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn(callable $set) => $set('position_id', null)),

                        Select::make('position_id')
                            ->label('Jabatan')
                            ->options(function (callable $get) {
                                $deptId = $get('_department_id');
                                if ($deptId) {
                                    return Position::where('department_id', $deptId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                }
                                return Position::with('department.division')
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn($p) => [
                                        $p->id => $p->department->division->name
                                            . ' › ' . $p->department->name
                                            . ' › ' . $p->name,
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        Select::make('reports_to_id')
                            ->label('Melapor Kepada')
                            ->options(
                                BvEmploye::with('position.department.division')
                                    ->whereNotNull('position_id')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => $e->nama_lengkap
                                            . ($e->position ? ' (' . $e->position->name . ')' : ''),
                                    ])
                            )
                            ->searchable()
                            ->nullable()
                            ->columnSpan(2),
                    ]),

                Section::make('Akses Sistem')
                    ->description('Password untuk login ke panel. Hanya diisi saat tambah karyawan baru.')
                    ->columns(2)
                    ->visibleOn(CreateBvEmploye::class)
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ]);
    }
}
