<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GrossProfitTarget;
use Illuminate\Auth\Access\HandlesAuthorization;

class GrossProfitTargetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GrossProfitTarget');
    }

    public function view(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('View:GrossProfitTarget');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GrossProfitTarget');
    }

    public function update(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('Update:GrossProfitTarget');
    }

    public function delete(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('Delete:GrossProfitTarget');
    }

    public function restore(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('Restore:GrossProfitTarget');
    }

    public function forceDelete(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('ForceDelete:GrossProfitTarget');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GrossProfitTarget');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GrossProfitTarget');
    }

    public function replicate(AuthUser $authUser, GrossProfitTarget $grossProfitTarget): bool
    {
        return $authUser->can('Replicate:GrossProfitTarget');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GrossProfitTarget');
    }

}