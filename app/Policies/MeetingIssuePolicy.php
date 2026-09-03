<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MeetingIssue;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingIssuePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MeetingIssue');
    }

    public function view(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('View:MeetingIssue');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MeetingIssue');
    }

    public function update(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('Update:MeetingIssue');
    }

    public function delete(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('Delete:MeetingIssue');
    }

    public function restore(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('Restore:MeetingIssue');
    }

    public function forceDelete(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('ForceDelete:MeetingIssue');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MeetingIssue');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MeetingIssue');
    }

    public function replicate(AuthUser $authUser, MeetingIssue $meetingIssue): bool
    {
        return $authUser->can('Replicate:MeetingIssue');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MeetingIssue');
    }

}