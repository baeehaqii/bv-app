<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BvSalesComment extends Model
{
    protected $table = 'bv_sales_comments';

    protected $fillable = [
        'bv_sales_id',
        'user_id',
        'parent_id',
        'body',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(BvSales::class, 'bv_sales_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->with(['user', 'replies'])->oldest();
    }
}
