<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvSPK;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvSPKPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvSPK');
    }

    public function view(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('View:BvSPK');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvSPK');
    }

    public function update(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('Update:BvSPK');
    }

    public function delete(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('Delete:BvSPK');
    }

    public function restore(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('Restore:BvSPK');
    }

    public function forceDelete(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('ForceDelete:BvSPK');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvSPK');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvSPK');
    }

    public function replicate(AuthUser $authUser, BvSPK $bvSPK): bool
    {
        return $authUser->can('Replicate:BvSPK');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvSPK');
    }

}