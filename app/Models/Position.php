<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $guarded = [];

    public const LEVELS = [
        'director' => 'Director',
        'manager'  => 'Manager',
        'senior'   => 'Senior',
        'staff'    => 'Staff',
        'junior'   => 'Junior',
        'intern'   => 'Intern',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(BvEmploye::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->department->division->name
            . ' › ' . $this->department->name
            . ' › ' . $this->name;
    }
}
