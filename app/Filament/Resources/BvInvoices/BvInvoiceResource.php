<?php

namespace App\Filament\Resources\BvInvoices;

use App\Filament\Resources\BvInvoices\Pages\CreateBvInvoice;
use App\Filament\Resources\BvInvoices\Pages\EditBvInvoice;
use App\Filament\Resources\BvInvoices\Pages\ListBvInvoices;
use App\Filament\Resources\BvInvoices\Schemas\BvInvoiceForm;
use App\Filament\Resources\BvInvoices\Tables\BvInvoicesTable;
use App\Models\BvInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvInvoiceResource extends Resource
{
    protected static ?string $model = BvInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Invoice & Piutang';
    protected static ?string $modelLabel = 'Invoice';
    protected static ?string $pluralModelLabel = 'Invoice & Piutang';
    protected static ?string $slug = 'invoice';
    protected static ?string $recordTitleAttribute = 'invoice_number';

    /** Badge = jumlah invoice yang lewat jatuh tempo, biar ketagih. */
    public static function getNavigationBadge(): ?string
    {
        $overdue = BvInvoice::query()->overdue()->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return BvInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBvInvoices::route('/'),
            'create' => CreateBvInvoice::route('/create'),
            'edit' => EditBvInvoice::route('/{record}/edit'),
        ];
    }
}
