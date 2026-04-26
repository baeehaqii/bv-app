<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\Component;

/**
 * Custom layout component untuk menampilkan KOL Details
 * dalam format row horizontal scroll dengan group headers.
 *
 * Gunakan bersama Fieldset groups sebagai child schema:
 * KolDetailsRow::make([
 *     Fieldset::make('Detail KOL')->schema([...]),
 *     Fieldset::make('Performance')->schema([...]),
 *     Fieldset::make('Jadwal Bayar')->schema([...]),
 *     Fieldset::make('Select Quotation')->schema([...]),
 * ])
 */
class KolDetailsRow extends Component
{
    protected string $view = 'filament.forms.components.kol-details-row';

    final public function __construct(array $schema = [])
    {
        $this->schema($schema);
    }

    public static function make(array $schema = []): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }
}
