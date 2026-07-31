<?php

namespace App\Filament\Resources\BvInvoices\Tables;

use App\Models\BvInvoice;
use App\Models\FinanceAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class BvInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client_name')
                    ->label('Client')
                    ->description(fn($record) => $record->term_label)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('amount')
                    ->label('Nilai Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')->money('IDR', locale: 'id')),

                TextColumn::make('paid_amount')
                    ->label('Diterima')
                    ->money('IDR', locale: 'id')
                    ->placeholder('—')
                    ->toggleable(),

                // Piutang = tagihan − diterima, dihitung di SQL supaya bisa disortir
                // & dijumlahkan (AR total muncul di baris Summary).
                TextColumn::make('outstanding')
                    ->label('Piutang')
                    ->money('IDR', locale: 'id')
                    ->color(fn($record) => $record->outstanding > 0 ? 'warning' : 'gray')
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Piutang')
                            ->using(fn(QueryBuilder $query) => $query
                                ->whereIn('status', BvInvoice::OUTSTANDING_STATUSES)
                                ->selectRaw('COALESCE(SUM(amount - COALESCE(paid_amount, 0)), 0) as agg')
                                ->value('agg'))
                            ->money('IDR', locale: 'id')
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // Lewat jatuh tempo lebih penting dilihat daripada label "Terkirim".
                    ->state(fn($record) => $record->isOverdue() ? 'Jatuh Tempo' : (BvInvoice::STATUSES[$record->status] ?? $record->status))
                    ->color(fn($record) => match (true) {
                        $record->isOverdue() => 'danger',
                        $record->status === 'paid' => 'success',
                        $record->status === 'partially_paid' => 'warning',
                        $record->status === 'void' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('issue_date')->label('Tgl Invoice')->date('d M Y')->sortable()->toggleable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn($record) => $record->isOverdue()
                        ? 'Lewat ' . $record->due_date->diffInDays(now()) . ' hari'
                        : null)
                    ->color(fn($record) => $record->isOverdue() ? 'danger' : null),

                TextColumn::make('financeAccount.name')->label('Masuk ke')->placeholder('—')->toggleable(),
                TextColumn::make('quotation.quotation_number')->label('Quotation')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(BvInvoice::STATUSES),

                Filter::make('overdue')
                    ->label('Hanya lewat jatuh tempo')
                    ->query(fn(Builder $query) => $query->overdue()),

                Filter::make('belum_lunas')
                    ->label('Masih ada piutang')
                    ->query(fn(Builder $query) => $query->outstanding()),

                Filter::make('periode')
                    ->label('Periode Invoice')
                    ->schema([
                        DatePicker::make('dari')->label('Dari Tanggal')->native(false),
                        DatePicker::make('sampai')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn($q, $d) => $q->whereDate('issue_date', '>=', $d))
                        ->when($data['sampai'] ?? null, fn($q, $d) => $q->whereDate('issue_date', '<=', $d))),
            ])
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn($record) => $record->outstanding > 0 && $record->status !== 'void')
                    ->modalHeading(fn($record) => "Catat Pembayaran {$record->invoice_number}")
                    ->modalDescription('Penerimaan kas otomatis masuk Cashflow pada pos SAK "Penerimaan dari Pelanggan".')
                    ->modalSubmitActionLabel('Catat Pembayaran')
                    ->fillForm(fn($record) => [
                        'amount' => $record->amount,
                        'paid_at' => now()->toDateString(),
                        'finance_account_id' => $record->finance_account_id ?? FinanceAccount::defaultId(),
                    ])
                    ->schema([
                        TextInput::make('amount')
                            ->label('Nominal Diterima')
                            ->prefix('Rp')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn($record) => $record->amount)
                            ->helperText('Boleh kurang dari tagihan — sisanya tetap tercatat sebagai piutang.')
                            ->required(),

                        DatePicker::make('paid_at')->label('Tanggal Kas Diterima')->native(false)->required(),

                        Select::make('finance_account_id')
                            ->label('Masuk ke Akun')
                            ->options(fn() => FinanceAccount::query()->where('is_active', true)->pluck('name', 'id'))
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->markPaid((float) $data['amount'], $data['paid_at'], (int) $data['finance_account_id']);
                        } catch (\Throwable $e) {
                            Notification::make()->title('Gagal Catat Pembayaran')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Pembayaran dicatat')->body('Penerimaan kas sudah masuk ke Cashflow.')->success()->send();
                    }),

                Action::make('unmark_paid')
                    ->label('Batalkan Pembayaran')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn($record) => filled($record->paid_at))
                    ->requiresConfirmation()
                    ->modalDescription('Baris penerimaan kas di Cashflow akan dihapus. Gunakan hanya bila salah input.')
                    ->action(function ($record) {
                        $record->unmarkPaid();

                        Notification::make()->title('Catatan pembayaran dibatalkan')->warning()->send();
                    }),

                Action::make('void')
                    ->label('Batalkan Invoice')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status !== 'void')
                    ->requiresConfirmation()
                    ->modalDescription('Piutang jadi nol dan penerimaan kasnya ditarik kembali. Invoice tetap tersimpan sebagai jejak.')
                    ->action(function ($record) {
                        $record->void();

                        Notification::make()->title('Invoice dibatalkan')->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc')
            ->emptyStateHeading('Belum ada invoice')
            ->emptyStateDescription('Terbitkan invoice dari Quotation yang sudah ditandatangani lengkap.')
            ->emptyStateIcon('heroicon-o-document-currency-dollar');
    }
}
