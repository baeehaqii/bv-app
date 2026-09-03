<?php

namespace App\Filament\Resources\MeetingIssues;

use App\Filament\Resources\MeetingIssues\Pages\CreateMeetingIssue;
use App\Filament\Resources\MeetingIssues\Pages\EditMeetingIssue;
use App\Filament\Resources\MeetingIssues\Pages\ListMeetingIssues;
use App\Filament\Resources\MeetingIssues\Schemas\MeetingIssueForm;
use App\Filament\Resources\MeetingIssues\Tables\MeetingIssuesTable;
use App\Models\MeetingIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Sheet "Issues To Discuss" dari dokumen Weekly Meeting (format Level 10).
 *
 * Agendanya sendiri tidak jadi data — isinya sama tiap minggu, jadi ditampilkan
 * sebagai keterangan di halaman daftar, bukan tabel yang bisa diubah-ubah.
 */
class MeetingIssueResource extends Resource
{
    protected static ?string $model = MeetingIssue::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Human Capital ';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Weekly Meeting';

    protected static ?string $modelLabel = 'Issue';

    protected static ?string $pluralModelLabel = 'Issues To Discuss';

    protected static ?string $slug = 'weekly-meeting';

    protected static ?string $recordTitleAttribute = 'issue';

    public static function form(Schema $schema): Schema
    {
        return MeetingIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingIssuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingIssues::route('/'),
            'create' => CreateMeetingIssue::route('/create'),
            'edit' => EditMeetingIssue::route('/{record}/edit'),
        ];
    }
}
