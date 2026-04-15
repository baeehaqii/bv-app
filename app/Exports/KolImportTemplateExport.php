<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KolImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new KolImportDataSheet(),
            new KolImportChannelRefSheet(),
        ];
    }
}
