<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\BvEmploye;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi User')
                    ->description('Data dasar user')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto Profile')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->placeholder('Masukkan nama lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('user@example.com')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        DatePicker::make('created_at')
                            ->label('Tanggal Gabung')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ])->columns(2),

                Section::make('Password')
                    ->description('Set atau reset password user')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Masukkan password baru')
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->helperText(fn(string $operation) => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 8 karakter'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Ulangi password')
                            ->same('password')
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('Data Karyawan')
                    ->description('Tautkan akun ini ke satu baris data karyawan')
                    ->schema([
                        Select::make('bv_employe_id')
                            ->label('Karyawan')
                            ->placeholder('Belum ditautkan')
                            ->options(fn(?User $record) => BvEmploye::query()
                                ->where(fn($q) => $q->whereNull('user_id')
                                    ->when($record, fn($q) => $q->orWhere('user_id', $record->id)))
                                ->orderBy('nama_lengkap')
                                ->pluck('nama_lengkap', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Hanya karyawan yang belum punya akun yang muncul di sini.')
                            ->afterStateHydrated(fn($component, ?User $record) => $component->state($record?->bvEmploye?->getKey()))
                            ->dehydrated(false)
                            ->saveRelationshipsUsing(function (User $record, $state) {
                                if ($record->bvEmploye?->getKey() == $state) {
                                    return;
                                }
                                // Lewat model, bukan mass update, supaya BvEmployeObserver
                                // ikut memperbarui user_id di Sales List.
                                $record->bvEmploye?->update(['user_id' => null]);
                                BvEmploye::find($state)?->update(['user_id' => $record->id]);
                            }),
                    ]),

                Section::make('Role & Permissions')
                    ->description('Atur akses user')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Pilih satu atau lebih role untuk user'),
                    ]),
            ]);
    }
}
