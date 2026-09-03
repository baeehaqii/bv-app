<?php

namespace App\Filament\Resources\MeetingIssues\Pages;

use App\Filament\Resources\MeetingIssues\MeetingIssueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMeetingIssues extends ListRecords
{
    protected static string $resource = MeetingIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Agendanya sama tiap minggu, jadi cukup ditampilkan — bukan tabel
            // yang bisa diubah dan lambat laun beda dengan yang dijalankan.
            Action::make('agenda')
                ->label('Agenda & Aturan')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->modalHeading('Weekly Meeting — Agenda 90 Menit')
                ->modalContent(fn(): View => view('filament.resources.meeting-issues.agenda'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            CreateAction::make()->label('Tambah Issue'),
        ];
    }
}
