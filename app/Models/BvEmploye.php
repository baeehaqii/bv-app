<?php

namespace App\Models;

use App\Observers\BvEmployeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(BvEmployeObserver::class)]
class BvEmploye extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** Data sales (pipeline) yang ter-sync dari karyawan ini */
    public function salesList(): HasOne
    {
        return $this->hasOne(BvSalesList::class, 'bv_employe_id');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(BvEmploye::class, 'reports_to_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(BvEmploye::class, 'reports_to_id');
    }
}
