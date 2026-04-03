<?php

namespace App\Enums;

enum SalesStatus: string
{
    case NOT_STARTED = 'not_started';
    case PITCHING = 'pitching';
    case BRIEFING = 'briefing';
    case PROPOSAL_BUILDING = 'proposal_building';
    case NEGOTIATION = 'negotiation';
    case CAMPAIGN_LIVE = 'campaign_live';
    case REPORTING = 'reporting';
    case CLOSE_LOSE = 'close_lose';
    case INVOICING = 'invoicing';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'New Leads',
            self::PITCHING => 'Contacted',
            self::BRIEFING => 'Briefing',
            self::PROPOSAL_BUILDING => 'Proposal Building',
            self::NEGOTIATION => 'Negotiation',
            self::CAMPAIGN_LIVE => 'Campaign Live',
            self::REPORTING => 'Reporting',
            self::CLOSE_LOSE => 'Close Lost',
            self::INVOICING => 'Invoicing',
            self::PAID => 'Paid',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::PITCHING => 'gray',
            self::BRIEFING => 'warning',
            self::PROPOSAL_BUILDING => 'purple',
            self::NEGOTIATION => 'info',
            self::CAMPAIGN_LIVE => 'success',
            self::REPORTING => 'orange',
            self::CLOSE_LOSE => 'gray',
            self::INVOICING => 'danger',
            self::PAID => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'heroicon-o-user-plus',
            self::PITCHING => 'heroicon-o-phone',
            self::BRIEFING => 'heroicon-o-document-text',
            self::PROPOSAL_BUILDING => 'heroicon-o-document-plus',
            self::NEGOTIATION => 'heroicon-o-chat-bubble-left-right',
            self::CAMPAIGN_LIVE => 'heroicon-o-rocket-launch',
            self::REPORTING => 'heroicon-o-chart-bar',
            self::CLOSE_LOSE => 'heroicon-o-x-circle',
            self::INVOICING => 'heroicon-o-document-currency-dollar',
            self::PAID => 'heroicon-o-check-circle',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}

