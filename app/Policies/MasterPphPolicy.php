<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MasterPph;
use Illuminate\Auth\Access\HandlesAuthorization;

class MasterPphPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MasterPph');
    }

    public function view(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('View:MasterPph');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MasterPph');
    }

    public function update(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('Update:MasterPph');
    }

    public function delete(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('Delete:MasterPph');
    }

    public function restore(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('Restore:MasterPph');
    }

    public function forceDelete(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('ForceDelete:MasterPph');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MasterPph');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MasterPph');
    }

    public function replicate(AuthUser $authUser, MasterPph $masterPph): bool
    {
        return $authUser->can('Replicate:MasterPph');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MasterPph');
    }

}