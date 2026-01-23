<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvEmploye;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvEmployePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvEmploye');
    }

    public function view(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('View:BvEmploye');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvEmploye');
    }

    public function update(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('Update:BvEmploye');
    }

    public function delete(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('Delete:BvEmploye');
    }

    public function restore(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('Restore:BvEmploye');
    }

    public function forceDelete(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('ForceDelete:BvEmploye');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvEmploye');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvEmploye');
    }

    public function replicate(AuthUser $authUser, BvEmploye $bvEmploye): bool
    {
        return $authUser->can('Replicate:BvEmploye');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvEmploye');
    }

}