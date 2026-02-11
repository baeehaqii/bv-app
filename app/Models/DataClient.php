<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;

class DataClient extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pics' => 'array',
    ];

    /**
     * Get the title attribute for this model (used in Filament UI)
     */
    public function getFilamentRecordTitle(): ?string
    {
        return $this->nama_brand;
    }
}
