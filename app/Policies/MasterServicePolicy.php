<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MasterService;
use Illuminate\Auth\Access\HandlesAuthorization;

class MasterServicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MasterService');
    }

    public function view(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('View:MasterService');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MasterService');
    }

    public function update(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('Update:MasterService');
    }

    public function delete(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('Delete:MasterService');
    }

    public function restore(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('Restore:MasterService');
    }

    public function forceDelete(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('ForceDelete:MasterService');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MasterService');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MasterService');
    }

    public function replicate(AuthUser $authUser, MasterService $masterService): bool
    {
        return $authUser->can('Replicate:MasterService');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MasterService');
    }

}