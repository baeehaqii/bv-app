<?php

namespace App\Filament\Resources\MeetingIssues\Pages;

use App\Filament\Resources\MeetingIssues\MeetingIssueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeetingIssue extends CreateRecord
{
    protected static string $resource = MeetingIssueResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
