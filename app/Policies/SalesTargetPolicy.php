<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SalesTarget;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesTargetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalesTarget');
    }

    public function view(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('View:SalesTarget');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalesTarget');
    }

    public function update(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('Update:SalesTarget');
    }

    public function delete(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('Delete:SalesTarget');
    }

    public function restore(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('Restore:SalesTarget');
    }

    public function forceDelete(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('ForceDelete:SalesTarget');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalesTarget');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalesTarget');
    }

    public function replicate(AuthUser $authUser, SalesTarget $salesTarget): bool
    {
        return $authUser->can('Replicate:SalesTarget');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalesTarget');
    }

}