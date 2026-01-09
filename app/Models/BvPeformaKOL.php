<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BvPeformaKOL extends Model
{
    protected $table = 'bv_peforma_k_o_l_s';

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_posting' => 'date',
        'tiktok_views' => 'integer',
        'tiktok_likes' => 'integer',
        'tiktok_comments' => 'integer',
        'tiktok_saves' => 'integer',
        'tiktok_shares' => 'integer',
        'tiktok_total_engagement' => 'integer',
        'instagram_views' => 'integer',
        'instagram_likes' => 'integer',
        'instagram_comments' => 'integer',
        'instagram_saves' => 'integer',
        'instagram_shares' => 'integer',
        'instagram_total_engagement' => 'integer',
    ];

    /**
     * Calculate and return total engagement across all platforms
     */
    public function getTotalEngagementAttribute(): int
    {
        return $this->tiktok_total_engagement + $this->instagram_total_engagement;
    }

    /**
     * Calculate and return total views across all platforms
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->tiktok_views + $this->instagram_views;
    }
}
