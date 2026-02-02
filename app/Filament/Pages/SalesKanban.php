<?php

namespace App\Filament\Pages;

use App\Enums\SalesStatus;
use App\Filament\Forms\BvSalesForm;
use App\Models\BvSales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
    public string $viewMode = 'kanban';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\Sales\SalesStatsWidget::class,
        ];
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
            Action::make('kanban_view')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('white')
                ->action(fn() => $this->viewMode = 'kanban'),

            Action::make('list_view')
                ->label('List')
                ->icon('heroicon-o-list-bullet')
                ->color('white')
                ->action(fn() => $this->viewMode = 'list'),

            CreateAction::make()
                ->label('Add Campaign')
                ->model(BvSales::class)
                ->form(BvSalesForm::getFormComponents())
                ->createAnother(false)
                ->color('white')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl')
                ->modalHeading('Create Sales Activity')
                ->slideOver()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = $data['status'] ?? SalesStatus::BRIEFING->value;
                    $data['position'] = (int) BvSales::where('status', $data['status'])->max('position') + 1;
                    return $data;
                }),
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(BvSales::query()->with('salesList'))
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->recordTitleAttribute('event_name')
            ->columns($this->getKanbanColumns())
            ->cardSchema(fn(Schema $schema) => $schema->components([
                \Filament\Schemas\Components\Grid::make(2)
                    ->schema([
                        TextEntry::make('salesList.nama_sales')
                            ->label('Sales')
                            ->icon('heroicon-o-user')
                            ->hidden(fn($record) => empty($record->salesList)),
                        TextEntry::make('margin')
                            ->label('Margin')
                            ->suffix('%')
                            ->badge()
                            ->color('warning')
                            ->hidden(fn($record) => empty($record->margin) || $record->margin == 0),
                    ]),
                TextEntry::make('company_name')
                    ->label('Company')
                    ->icon('heroicon-o-building-office')
                    ->hidden(fn($record) => empty($record->company_name)),
                TextEntry::make('campaign_items')
                    ->label('Campaign')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn($record) => $record->campaign_items ?? [])
                    ->hidden(fn($record) => empty($record->campaign_items)),
                TextEntry::make('campaign_periode')
                    ->label('Campaign Period')
                    ->icon('heroicon-o-calendar-days')
                    ->formatStateUsing(fn($state, $record) => $state ? strtoupper($state) . ' ' . $record->campaign_year : null)
                    ->hidden(fn($record) => empty($record->campaign_periode)),
                TextEntry::make('deal_value')
                    ->label('Deal Value')
                    ->icon('heroicon-o-banknotes')
                    ->money('IDR')
                    ->weight(FontWeight::SemiBold)
                    ->color('success')
                    ->hidden(fn($record) => empty($record->deal_value) || $record->deal_value == 0),
                TextEntry::make('close_date')
                    ->label('Close Date')
                    ->icon('heroicon-o-calendar')
                    ->date('d M Y')
                    ->hidden(fn($record) => empty($record->close_date)),
            ]))
            /*
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
            ])
            */
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
                    ->modalWidth('2xl')
                    ->modalHeading('Edit Sales Activity')
                    ->slideOver(),
                \Filament\Actions\DeleteAction::make()
                    ->model(BvSales::class),
            ])
            ->cardAction('edit');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BvSales::query()->with('salesList'))
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

                TextColumn::make('campaign_items')
                    ->label('Campaign')
                    ->badge()
                    ->separator(','),

                TextColumn::make('campaign_periode')
                    ->label('Period')
                    ->formatStateUsing(fn($state, $record) => $state ? strtoupper($state) . ' ' . $record->campaign_year : '-')
                    ->sortable(),

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
                        $statusValue = $state instanceof SalesStatus ? $state->value : $state;
                        return match ($statusValue) {

                            SalesStatus::BRIEFING->value => 'info',
                            SalesStatus::PROPOSAL_BUILDING->value => 'warning',
                            SalesStatus::NEGOTIATION->value => 'purple',
                            SalesStatus::CAMPAIGN_LIVE->value => 'indigo',
                            SalesStatus::REPORTING->value => 'orange',
                            SalesStatus::CLOSE_LOSE->value => 'danger',
                            SalesStatus::INVOICING->value => 'cyan',
                            SalesStatus::PAID->value => 'success',
                            default => 'gray',
                        };
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

                SelectFilter::make('bv_sales_list_id')
                    ->label('Sales')
                    ->relationship('salesList', 'nama_sales'),
            ])
            ->actions([
                EditAction::make()
                    ->form(BvSalesForm::getFormComponents())
                    ->modalWidth('2xl')
                    ->slideOver(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    protected function getKanbanColumns(): array
    {
        return [

            Column::make(SalesStatus::BRIEFING->value)
                ->label(SalesStatus::BRIEFING->getLabel())
                ->color(Color::Blue)
                ->icon(SalesStatus::BRIEFING->getIcon()),

            Column::make(SalesStatus::PROPOSAL_BUILDING->value)
                ->label(SalesStatus::PROPOSAL_BUILDING->getLabel())
                ->color(Color::Amber)
                ->icon(SalesStatus::PROPOSAL_BUILDING->getIcon()),

            Column::make(SalesStatus::NEGOTIATION->value)
                ->label(SalesStatus::NEGOTIATION->getLabel())
                ->color(Color::Purple)
                ->icon(SalesStatus::NEGOTIATION->getIcon()),

            Column::make(SalesStatus::CAMPAIGN_LIVE->value)
                ->label(SalesStatus::CAMPAIGN_LIVE->getLabel())
                ->color(Color::Indigo)
                ->icon(SalesStatus::CAMPAIGN_LIVE->getIcon()),

            Column::make(SalesStatus::REPORTING->value)
                ->label(SalesStatus::REPORTING->getLabel())
                ->color(Color::Orange)
                ->icon(SalesStatus::REPORTING->getIcon()),

            Column::make(SalesStatus::CLOSE_LOSE->value)
                ->label(SalesStatus::CLOSE_LOSE->getLabel())
                ->color(Color::Red)
                ->icon(SalesStatus::CLOSE_LOSE->getIcon()),

            Column::make(SalesStatus::INVOICING->value)
                ->label(SalesStatus::INVOICING->getLabel())
                ->color(Color::Cyan)
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
