<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataVendor extends Model
{
    protected $guarded = [];

    /**
     * Get the title attribute for this model (used in Filament UI)
     */
    public function getFilamentRecordTitle(): ?string
    {
        return $this->nama_vendor;
    }
}
