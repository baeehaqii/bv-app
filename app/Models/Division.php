<?php

namespace App\Models;

use App\Enums\DivisionSyncType;
use App\Observers\DivisionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(DivisionObserver::class)]
class Division extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sync_type' => DivisionSyncType::class,
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(BvEmploye::class, 'position_id', 'id')
            ->join('positions', 'bv_employes.position_id', '=', 'positions.id')
            ->join('departments', 'positions.department_id', '=', 'departments.id')
            ->where('departments.division_id', $this->id);
    }
}
