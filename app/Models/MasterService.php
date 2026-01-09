<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MasterService extends Model
{
    protected $table = 'master_services';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_coming_soon' => 'boolean',
    ];

    /**
     * Scope untuk filter hanya service yang aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter hanya service yang available (aktif dan bukan coming soon)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_coming_soon', false);
    }

    /**
     * Scope untuk urut berdasarkan urutan
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama_service');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Nonaktif';
        }

        if ($this->is_coming_soon) {
            return 'Coming Soon';
        }

        return 'Aktif';
    }

    /**
     * Get status color for badges
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'danger';
        }

        if ($this->is_coming_soon) {
            return 'warning';
        }

        return 'success';
    }
}
