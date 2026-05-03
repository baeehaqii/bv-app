<?php

namespace App\Filament\Pages;

use App\Enums\SalesStatus;
use App\Filament\Forms\BvSalesForm;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\DataClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid as InfolistGrid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use UnitEnum;

class SalesKanban extends BoardPage implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $title = 'Sales Activity Tracker';

    protected static ?string $navigationLabel = 'Sales Activity Tracker';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sales-activity';

    #[Url]
    public string $viewMode = 'list';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        return [
            'viewMode' => $this->viewMode,
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('list_view')
                ->label('List')
                ->icon('heroicon-o-list-bullet')
                ->color('white')
                ->outlined(fn() => $this->viewMode !== 'list')
                ->action(fn() => $this->viewMode = 'list'),

            Action::make('kanban_view')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('white')
                ->outlined(fn() => $this->viewMode !== 'kanban')
                ->action(fn() => $this->viewMode = 'kanban'),

            CreateAction::make()
                ->label('Add Campaign')
                ->model(BvSales::class)
                ->form(BvSalesForm::getFormComponents())
                ->createAnother(false)
                ->color('white')
                ->icon('heroicon-o-plus')
                ->modalWidth('4xl')
                ->modalHeading('Create Sales Activity')
                ->slideOver()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = $data['status'] ?? SalesStatus::NOT_STARTED->value;
                    $data['position'] = (int) BvSales::where('status', $data['status'])->max('position') + 1;
                    return $data;
                }),
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(BvSales::query()->with(['salesList', 'salesComments', 'formBrief', 'campaign']))
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->recordTitleAttribute('event_name')
            ->columns($this->getKanbanColumns())
            // Notion-style: card hanya tampil judul, metadata di-render langsung di card template
            ->cardSchema(fn(Schema $schema) => $schema->components([]))
            ->searchable(['event_name', 'company_name', 'detail'])
            ->filters([
                SelectFilter::make('campaign_year')
                    ->label('Campaign Year')
                    ->options(
                        fn() => BvSales::query()
                            ->whereNotNull('campaign_year')
                            ->distinct()
                            ->pluck('campaign_year', 'campaign_year')
                            ->toArray()
                    ),

                SelectFilter::make('campaign_month')
                    ->label('Campaign Month')
                    ->options(fn() => collect(range(1, 12))
                        ->mapWithKeys(fn($m) => [
                            $m => \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F'),
                        ])->toArray()),

                SelectFilter::make('bv_sales_list_id')
                    ->label('Sales')
                    ->relationship('salesList', 'nama_sales'),
            ])
            /*
            ->columnActions([
                CreateAction::make()
                    ->label('Add')
                    ->model(BvSales::class)
                    ->form(BvSalesForm::getFormComponents())
                    ->createAnother(false)
                    ->modalWidth('2xl')
                    ->modalHeading('Create Sales Activity')
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data, array $arguments): array {
                        if (isset($arguments['column'])) {
                            $data['status'] = $arguments['column'];
                            $data['position'] = $this->getBoardPositionInColumn($arguments['column']);
                        }
                        return $data;
                    }),
            ])
            */
            ->cardActions([
                \Filament\Actions\EditAction::make()
                    ->model(BvSales::class)
                    ->form(BvSalesForm::getFormComponents())
                    ->modalWidth('4xl')
                    ->modalHeading('Edit Sales Activity')
                    ->slideOver()
                    ->extraModalFooterActions(fn(\Filament\Actions\EditAction $action): array => [
                        \Filament\Actions\DeleteAction::make('delete_from_modal')
                            ->model(BvSales::class)
                            ->record(fn() => $action->getRecord())
                            ->hidden(fn() => $action->getRecord() === null)
                            ->color('danger')
                            ->icon('heroicon-o-trash')
                            ->after(fn() => $action->cancel()),
                    ])
                    ->modalFooter(fn($record) => view('filament.pages.partials.sales-comments-footer', ['record' => $record])),
            ])
            ->cardAction('edit');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BvSales::query()->with(['salesList', 'client']))
            ->columns([
                TextColumn::make('event_name')
                    ->label('Event/Campaign')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('salesList.nama_sales')
                    ->label('Sales')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.type')
                    ->label('Client Type')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'agency' => 'Agency',
                        'direct' => 'Direct Brand',
                        default => null,
                    })
                    ->color(fn($state) => match ($state) {
                        'agency' => 'warning',
                        'direct' => 'info',
                        default => 'gray',
                    })
                    ->action(
                        Action::make('viewClientType')
                            ->modalHeading(fn($record) => $record->client?->nama_brand ?? $record->company_name)
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalWidth('lg')
                            ->infolist(fn($record): array => [
                                InfolistGrid::make(3)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label('Client Type')
                                            ->getStateUsing(fn() => $record->client?->type ? ucfirst($record->client->type) : '-')
                                            ->badge()
                                            ->color(fn() => match ($record->client?->type) {
                                                'agency' => 'warning',
                                                'direct' => 'info',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('nama_brand')
                                            ->label('Nama Brand')
                                            ->getStateUsing(fn() => $record->client?->nama_brand ?? '-'),

                                        TextEntry::make('parent_brand')
                                            ->label('Parent Brand')
                                            ->getStateUsing(fn() => $record->client?->parent_brand ?: '-'),
                                    ]),

                                RepeatableEntry::make('pics')
                                    ->label('List Agency')
                                    ->getStateUsing(fn() => collect($record->client?->pics ?? [])
                                        ->filter(fn($p) => !empty($p['agency']))
                                        ->values()
                                        ->toArray())
                                    ->schema([
                                        TextEntry::make('agency')
                                            ->label('Nama Agency'),
                                        TextEntry::make('name')
                                            ->label('PIC Agency'),
                                        TextEntry::make('email')
                                            ->label('Email'),
                                        TextEntry::make('wa_number')
                                            ->label('WhatsApp'),
                                    ])
                                    ->columns(4)
                                    ->visible(fn() => collect($record->client?->pics ?? [])->filter(fn($p) => !empty($p['agency']))->isNotEmpty()),
                            ])
                    ),

                TextColumn::make('campaign_items')
                    ->label('Campaign')
                    ->badge()
                    ->separator(','),

                TextColumn::make('campaign_month')
                    ->label('Period')
                    ->formatStateUsing(function ($state, $record) {
                        return $state && $record->campaign_year
                            ? \Carbon\Carbon::createFromDate(null, $state, 1)->translatedFormat('F') . ' ' . $record->campaign_year
                            : '-';
                    })
                    ->sortable(),

                TextColumn::make('budget_propose')
                    ->label('Budget Propose')
                    ->money('IDR')
                    ->sortable()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('deal_value')
                    ->label('Deal Value')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('margin')
                    ->label('Margin')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof SalesStatus) {
                            return $state->getLabel();
                        }
                        return SalesStatus::tryFrom($state)?->getLabel() ?? $state;
                    })
                    ->color(function ($state) {
                        if ($state instanceof SalesStatus) {
                            return $state->getColor();
                        }
                        return SalesStatus::tryFrom($state)?->getColor() ?? 'gray';
                    })
                    ->sortable(),

                TextColumn::make('close_date')
                    ->label('Close Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(SalesStatus::toArray()),

                SelectFilter::make('campaign_year')
                    ->label('Campaign Year')
                    ->options(
                        fn() => BvSales::query()
                            ->whereNotNull('campaign_year')
                            ->distinct()
                            ->pluck('campaign_year', 'campaign_year')
                            ->toArray()
                    ),

                SelectFilter::make('campaign_month')
                    ->label('Campaign Month')
                    ->options(fn() => collect(range(1, 12))
                        ->mapWithKeys(fn($m) => [
                            $m => \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F'),
                        ])->toArray()),

                SelectFilter::make('bv_sales_list_id')
                    ->label('Sales')
                    ->relationship('salesList', 'nama_sales'),

                SelectFilter::make('company_name')
                    ->label('Company / Client')
                    ->searchable()
                    ->options(
                        fn() => BvSales::query()
                            ->whereNotNull('company_name')
                            ->distinct()
                            ->orderBy('company_name')
                            ->pluck('company_name', 'company_name')
                            ->toArray()
                    ),

                Filter::make('client_type')
                    ->label('Client Type')
                    ->form([
                        \Filament\Forms\Components\Select::make('client_type')
                            ->label('Client Type')
                            ->options([
                                'direct' => 'Direct Brand',
                                'agency' => 'Agency',
                            ])
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['client_type'] ?? null,
                            fn(Builder $q, string $type) => $q->whereHas(
                                'client',
                                fn(Builder $cq) => $cq->where('type', $type)
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['client_type'] ?? null)) {
                            return null;
                        }
                        return 'Client Type: ' . match ($data['client_type']) {
                            'direct' => 'Direct Brand',
                            'agency' => 'Agency',
                            default => $data['client_type'],
                        };
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form(BvSalesForm::getFormComponents())
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->modalFooter(fn($record) => view('filament.pages.partials.sales-comments-footer', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    protected function getKanbanColumns(): array
    {
        return [
            Column::make(SalesStatus::NOT_STARTED->value)
                ->label(SalesStatus::NOT_STARTED->getLabel())
                ->color(Color::Gray)
                ->icon(SalesStatus::NOT_STARTED->getIcon()),

            Column::make(SalesStatus::PITCHING->value)
                ->label(SalesStatus::PITCHING->getLabel())
                ->color(Color::Gray)
                ->icon(SalesStatus::PITCHING->getIcon()),

            Column::make(SalesStatus::BRIEFING->value)
                ->label(SalesStatus::BRIEFING->getLabel())
                ->color(Color::Yellow)
                ->icon(SalesStatus::BRIEFING->getIcon()),

            Column::make(SalesStatus::PROPOSAL_BUILDING->value)
                ->label(SalesStatus::PROPOSAL_BUILDING->getLabel())
                ->color(Color::Purple)
                ->icon(SalesStatus::PROPOSAL_BUILDING->getIcon()),

            Column::make(SalesStatus::NEGOTIATION->value)
                ->label(SalesStatus::NEGOTIATION->getLabel())
                ->color(Color::Blue)
                ->icon(SalesStatus::NEGOTIATION->getIcon()),

            Column::make(SalesStatus::CAMPAIGN_LIVE->value)
                ->label(SalesStatus::CAMPAIGN_LIVE->getLabel())
                ->color(Color::Green)
                ->icon(SalesStatus::CAMPAIGN_LIVE->getIcon()),

            Column::make(SalesStatus::REPORTING->value)
                ->label(SalesStatus::REPORTING->getLabel())
                ->color(Color::Amber)
                ->icon(SalesStatus::REPORTING->getIcon()),

            Column::make(SalesStatus::CLOSE_LOSE->value)
                ->label(SalesStatus::CLOSE_LOSE->getLabel())
                ->color(Color::Gray)
                ->icon(SalesStatus::CLOSE_LOSE->getIcon()),

            Column::make(SalesStatus::INVOICING->value)
                ->label(SalesStatus::INVOICING->getLabel())
                ->color(Color::Red)
                ->icon(SalesStatus::INVOICING->getIcon()),

            Column::make(SalesStatus::PAID->value)
                ->label(SalesStatus::PAID->getLabel())
                ->color(Color::Green)
                ->icon(SalesStatus::PAID->getIcon()),
        ];
    }

    public function getView(): string
    {
        if ($this->viewMode === 'list') {
            return 'filament.pages.sales-kanban';
        }

        // Return custom view with white background
        return 'filament.pages.sales-kanban-board';
    }
}
