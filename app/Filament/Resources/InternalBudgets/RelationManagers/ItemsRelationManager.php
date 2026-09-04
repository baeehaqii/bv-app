<?php

namespace App\Filament\Resources\InternalBudgets\RelationManagers;

use App\Models\MediaPlanKol;
use App\Models\InternalBudgetItem;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Budget Items (Costing)';

    protected static ?string $recordTitleAttribute = 'scope_item';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Information')
                    ->schema([
                        Select::make('media_plan_kol_id')
                            ->label('KOL')
                            ->options(function () {
                                $mediaPlanId = $this->getOwnerRecord()->media_plan_id;
                                return MediaPlanKol::where('media_plan_id', $mediaPlanId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Link to specific KOL'),

                        TextInput::make('scope_item')
                            ->label('Scope Item')
                            ->placeholder('e.g., IG Reels, TT Video, Visit Alfa')
                            ->required(),

                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('For ordering items in list'),
                    ])->columns(2),

                Section::make('💵 Rate & Cost Calculation')
                    ->description('Input Rate Base (Modal), system will auto-calculate the rest')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('rate_base')
                                    ->label('Rate (Base) - Modal/HPP')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->helperText('Harga net sebelum pajak'),

                                TextInput::make('subtotal')
                                    ->label('Subtotal Rate')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Qty × Rate'),

                                // Pilihan & koefisiennya datang dari Master PPH.
                                Select::make('master_pph_id')
                                    ->label('Tipe Pajak Vendor')
                                    ->options(fn() => \App\Models\MasterPph::getActiveOptions())
                                    ->default(fn() => \App\Models\MasterPph::defaultId())
                                    ->required()
                                    ->live(onBlur: true)
                                    ->helperText('Diatur di Master Data → Master PPH'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('gross_up_coeff')
                                    ->label('Gross Up Coefficient')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Based on Tax Type'),

                                TextInput::make('tax_value')
                                    ->label('Tax Reference')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Visual reference'),

                                TextInput::make('mu_pph')
                                    ->label('MU PPh (Real Cost)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Total uang keluar (Modal + Pajak)')
                                    ->extraAttributes(['style' => 'font-weight: bold; color: #dc2626;']),
                            ]),
                    ]),

                Section::make('📊 Pricing Strategy')
                    ->description('System auto-calculates target margin based on subtotal range')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('target_margin_percent')
                                    ->label('Target Margin %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Auto: 80% (<3jt), 40% (3-50jt), 30% (>50jt)'),

                                TextInput::make('mu_target')
                                    ->label('MU Target (Guideline Price)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('MU PPh / (1 - Target Margin)'),

                                TextInput::make('published_rate')
                                    ->label('Published Rate (Harga Jual)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->live(onBlur: true)
                                    ->helperText('Default from MU Target, bisa diedit manual'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('rounded')
                                    ->label('Rounded (Client Price)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Dibulatkan ke 100k terdekat')
                                    ->extraAttributes(['style' => 'font-weight: bold; color: #16a34a;']),

                                TextInput::make('actual_margin_percent')
                                    ->label('Actual Margin %')
                                    ->numeric()
                                    ->suffix('%')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('(Rounded - MU PPh) / Rounded')
                                    ->extraAttributes(function ($state) {
                                        if ($state && $state < 30) {
                                            return ['style' => 'color: #dc2626; font-weight: bold;'];
                                        }
                                        return ['style' => 'color: #16a34a; font-weight: bold;'];
                                    }),
                            ]),

                        // Item Profit disembunyikan di Media Plan External (data internal saja).
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->placeholder('e.g., Exclude pembelian produk'),
                    ])->collapsible()->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scope_item')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('mediaPlanKol.name')
                    ->label('KOL')
                    ->searchable()
                    ->default('-')
                    ->limit(15),

                Tables\Columns\TextColumn::make('scope_item')
                    ->label('Scope Item')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rate_base')
                    ->label('Rate (Base)')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('masterPph.name')
                    ->label('Tipe Pajak')
                    ->badge()
                    ->color('info')
                    ->placeholder(fn($record) => $record->vendor_tax_type ?: '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('mu_pph')
                    ->label('MU PPh')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('published_rate')
                    ->label('Published')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rounded')
                    ->label('Rounded')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                // Target % & Actual Margin disembunyikan di Media Plan External (data internal saja).
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('master_pph_id')
                    ->label('Tipe Pajak')
                    ->options(fn() => \App\Models\MasterPph::getActiveOptions()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label('Add Budget Item'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc');
    }
}
