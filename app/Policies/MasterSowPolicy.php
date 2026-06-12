<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MasterSow;
use Illuminate\Auth\Access\HandlesAuthorization;

class MasterSowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MasterSow');
    }

    public function view(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('View:MasterSow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MasterSow');
    }

    public function update(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('Update:MasterSow');
    }

    public function delete(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('Delete:MasterSow');
    }

    public function restore(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('Restore:MasterSow');
    }

    public function forceDelete(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('ForceDelete:MasterSow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MasterSow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MasterSow');
    }

    public function replicate(AuthUser $authUser, MasterSow $masterSow): bool
    {
        return $authUser->can('Replicate:MasterSow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MasterSow');
    }

}