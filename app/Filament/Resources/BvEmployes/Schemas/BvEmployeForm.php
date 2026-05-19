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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
