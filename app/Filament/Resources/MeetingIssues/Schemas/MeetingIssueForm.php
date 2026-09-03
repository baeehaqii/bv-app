<?php

namespace App\Filament\Resources\MeetingIssues\Schemas;

use App\Enums\IssueStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Issue')
                ->columns(3)
                ->schema([
                    DatePicker::make('meeting_date')
                        ->label('Tanggal Rapat')
                        ->required()
                        ->default(fn() => now()),

                    TextInput::make('pic')
                        ->label('PIC')
                        ->maxLength(255),

                    TextInput::make('priority_score')
                        ->label('Priority Score')
                        ->helperText('1 = prioritas tertinggi.')
                        ->numeric()
                        ->minValue(1)
                        ->step(0.01),

                    Textarea::make('issue')
                        ->label('Issue To Discuss')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('resolution')
                        ->label('Resolution')
                        ->helperText('Keputusan rapat, bukan rencana pembahasan.')
                        ->rows(3)
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label('Resolution Status')
                        ->options(IssueStatus::toArray())
                        ->required()
                        ->native(false)
                        ->default(IssueStatus::OPEN->value),
                ]),
        ]);
    }
}
