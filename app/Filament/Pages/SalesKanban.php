<?php

namespace App\Filament\Pages;

use App\Enums\SalesStatus;
use App\Filament\Forms\BvSalesForm;
use App\Models\BvSales;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\SelectFilter;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;
use UnitEnum;

class SalesKanban extends BoardPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $title = 'Sales Activity Tracker';

    protected static ?string $navigationLabel = 'Sales Activity Tracker';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sales-activity';

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

    protected function getKanbanColumns(): array
    {
        return [
            Column::make(SalesStatus::PITCHING->value)
                ->label(SalesStatus::PITCHING->getLabel())
                ->color(Color::Gray)
                ->icon(SalesStatus::PITCHING->getIcon()),

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
}
