<?php

namespace App\Filament\Resources\DataClients\Pages;

use App\Filament\Resources\DataClients\DataClientResource;
use App\Models\BvSalesList;
use App\Models\DataClient;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

class DataClientKanban extends BoardResourcePage
{
    protected static string $resource = DataClientResource::class;

    protected static ?string $title = 'Outreach Kanban';

    protected static ?string $navigationLabel = 'Outreach Kanban';

    protected string $view = 'filament.resources.data-clients.pages.kanban';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Client')
                ->model(DataClient::class)
                ->form(self::getKanbanFormComponents())
                ->createAnother(false)
                ->modalWidth('2xl')
                ->slideOver()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = $data['status'] ?? 'Newest';
                    $data['position'] = str_pad(
                        (string) ((int) DataClient::where('status', $data['status'])->max('position') + 1000),
                        10, '0', STR_PAD_LEFT
                    );
                    return $data;
                }),
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(DataClient::query()->with('picInternalSales'))
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->recordTitleAttribute('nama_brand')
            ->columns($this->getKanbanColumns())
            ->cardSchema(fn(Schema $schema) => $schema->components([]))
            ->cardActions([
                EditAction::make()
                    ->model(DataClient::class)
                    ->form(self::getKanbanFormComponents())
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->extraModalFooterActions(fn(EditAction $action): array => [
                        DeleteAction::make('delete_from_modal')
                            ->model(DataClient::class)
                            ->record(fn() => $action->getRecord())
                            ->color('danger')
                            ->icon('heroicon-o-trash')
                            ->after(fn() => $action->cancel()),
                    ]),
            ])
            ->cardAction('edit');
    }

    protected function getKanbanColumns(): array
    {
        return [
            Column::make('Newest')
                ->label('Newest')
                ->color(Color::Blue)
                ->icon('heroicon-o-star'),

            Column::make('Number of Meeting')
                ->label('Meeting')
                ->color(Color::Indigo)
                ->icon('heroicon-o-users'),

            Column::make('Brief')
                ->label('Brief')
                ->color(Color::Yellow)
                ->icon('heroicon-o-document-text'),

            Column::make('Waiting Feedback')
                ->label('Waiting Feedback')
                ->color(Color::Orange)
                ->icon('heroicon-o-clock'),

            Column::make('On Going')
                ->label('On Going')
                ->color(Color::Green)
                ->icon('heroicon-o-play'),

            Column::make('Not Available')
                ->label('Not Available')
                ->color(Color::Gray)
                ->icon('heroicon-o-x-circle'),
        ];
    }

    public static function getKanbanFormComponents(): array
    {
        return [
            Select::make('type')
                ->label('Client Type')
                ->options([
                    'direct' => 'Direct Brand',
                    'agency' => 'Agency',
                ])
                ->default('direct')
                ->native(false)
                ->required(),

            TextInput::make('nama_brand')
                ->label('Nama Brand')
                ->placeholder('Masukan nama brand...')
                ->required(),

            Select::make('status')
                ->label('Status Outreach')
                ->options([
                    'Newest' => 'Newest',
                    'Number of Meeting' => 'Number of Meeting',
                    'Brief' => 'Brief',
                    'Waiting Feedback' => 'Waiting Feedback',
                    'On Going' => 'On Going',
                    'Not Available' => 'Not Available',
                ])
                ->default('Newest')
                ->native(false)
                ->required(),

            Select::make('priority')
                ->label('Prioritas')
                ->options([
                    'P0' => 'P0',
                    'P1' => 'P1',
                    'P2' => 'P2',
                    'P3' => 'P3',
                ])
                ->native(false),

            Select::make('category')
                ->label('Kategori')
                ->options([
                    'FMCG' => 'FMCG',
                    'E-Commerce & Tech' => 'E-Commerce & Tech',
                    'Fintech & Banking' => 'Fintech & Banking',
                    'Beauty & Skincare' => 'Beauty & Skincare',
                    'Automotive' => 'Automotive',
                    'Telecommunication' => 'Telecommunication',
                    'Pharmaceuticals' => 'Pharmaceuticals',
                    'Retail & Fashion' => 'Retail & Fashion',
                ])
                ->searchable()
                ->native(false),

            Select::make('pic_internal_sales_id')
                ->label('PIC Internal (Sales)')
                ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                ->searchable()
                ->native(false)
                ->nullable(),

            TextInput::make('nama_pic')
                ->label('Nama PIC')
                ->placeholder('Nama Person in Charge...'),

            DatePicker::make('date_outreach')
                ->label('Tanggal Outreach')
                ->default(now())
                ->native(false),

            DatePicker::make('date_follow_up')
                ->label('Tanggal Follow Up')
                ->native(false),

            Textarea::make('notes')
                ->label('Catatan')
                ->placeholder('Catatan tambahan...')
                ->columnSpanFull(),
        ];
    }
}
