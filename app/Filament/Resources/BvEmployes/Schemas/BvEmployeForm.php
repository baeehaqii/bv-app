<?php

namespace App\Filament\Resources\BvEmployes\Schemas;

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
                            ->required(),

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
                        // Divisi – hanya untuk filter cascading, tidak disimpan di DB
                        Select::make('_division_id')
                            ->label('Divisi')
                            ->options(Division::pluck('name', 'id'))
                            ->live()
                            ->dehydrated(false)   // tidak ikut disimpan
                            ->afterStateUpdated(fn (callable $set) => $set('position_id', null)),

                        // Departemen – filter cascading
                        Select::make('_department_id')
                            ->label('Departemen')
                            ->options(function (callable $get) {
                                $divisionId = $get('_division_id');
                                if (! $divisionId) {
                                    return Department::with('division')
                                        ->get()
                                        ->mapWithKeys(fn ($d) => [$d->id => $d->division->name . ' › ' . $d->name]);
                                }
                                return Department::where('division_id', $divisionId)
                                    ->pluck('name', 'id');
                            })
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (callable $set) => $set('position_id', null)),

                        // Jabatan – yang disimpan sebagai FK
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
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => $p->department->division->name
                                            . ' › ' . $p->department->name
                                            . ' › ' . $p->name,
                                    ]);
                            })
                            ->searchable()
                            ->nullable()
                            ->columnSpan(2),

                        // Divis (field lama – tetap disimpan untuk backward compat)
                        TextInput::make('divis')
                            ->label('Divisi (lama)')
                            ->helperText('Legacy field. Isi otomatis atau biarkan kosong.')
                            ->nullable(),

                        // Atasan langsung
                        Select::make('reports_to_id')
                            ->label('Melapor Kepada')
                            ->options(
                                BvEmploye::with('position.department.division')
                                    ->whereNotNull('position_id')
                                    ->get()
                                    ->mapWithKeys(fn ($e) => [
                                        $e->id => $e->nama_lengkap
                                            . ($e->position ? ' (' . $e->position->name . ')' : ''),
                                    ])
                            )
                            ->searchable()
                            ->nullable()
                            ->columnSpan(2),
                    ]),
            ]);
    }
}
