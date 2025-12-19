<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MasterMargin;
use Illuminate\Auth\Access\HandlesAuthorization;

class MasterMarginPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MasterMargin');
    }

    public function view(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('View:MasterMargin');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MasterMargin');
    }

    public function update(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('Update:MasterMargin');
    }

    public function delete(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('Delete:MasterMargin');
    }

    public function restore(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('Restore:MasterMargin');
    }

    public function forceDelete(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('ForceDelete:MasterMargin');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MasterMargin');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MasterMargin');
    }

    public function replicate(AuthUser $authUser, MasterMargin $masterMargin): bool
    {
        return $authUser->can('Replicate:MasterMargin');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MasterMargin');
    }

}