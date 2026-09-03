<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Model;

class MeetingIssue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meeting_date' => 'date',
        'priority_score' => 'decimal:2',
        'status' => IssueStatus::class,
    ];
}
