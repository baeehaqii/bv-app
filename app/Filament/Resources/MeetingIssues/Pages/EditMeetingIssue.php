<?php

namespace App\Filament\Resources\MeetingIssues\Pages;

use App\Filament\Resources\MeetingIssues\MeetingIssueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeetingIssue extends EditRecord
{
    protected static string $resource = MeetingIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
