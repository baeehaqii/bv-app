<?php

namespace App\Filament\Resources\BvEmployes\Tables;

use App\Models\Position;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BvEmployesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position.department.division.name')
                    ->label('Divisi')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('position.department.name')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('position.name')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position.level')
                    ->label('Level')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Position::LEVELS[$state] ?? $state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'director' => 'danger',
                        'manager'  => 'warning',
                        'senior'   => 'info',
                        'staff'    => 'success',
                        'junior'   => 'gray',
                        'intern'   => 'gray',
                        default    => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('reportsTo.nama_lengkap')
                    ->label('Melapor Kepada')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('bank')
                    ->label('Bank')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('no_rekening')
                    ->label('No. Rekening')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('npwp')
                    ->label('NPWP')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bpjs_kesehatan')
                    ->label('BPJS Kesehatan')
                    ->placeholder('-')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('division')
                    ->label('Divisi')
                    ->relationship('position.department.division', 'name'),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->relationship('position.department', 'name'),

                SelectFilter::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama_lengkap');
    }
}
