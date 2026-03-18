<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvBussinesDirector;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvBussinesDirectorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvBussinesDirector');
    }

    public function view(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('View:BvBussinesDirector');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvBussinesDirector');
    }

    public function update(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('Update:BvBussinesDirector');
    }

    public function delete(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('Delete:BvBussinesDirector');
    }

    public function restore(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('Restore:BvBussinesDirector');
    }

    public function forceDelete(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('ForceDelete:BvBussinesDirector');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvBussinesDirector');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvBussinesDirector');
    }

    public function replicate(AuthUser $authUser, BvBussinesDirector $bvBussinesDirector): bool
    {
        return $authUser->can('Replicate:BvBussinesDirector');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvBussinesDirector');
    }

}