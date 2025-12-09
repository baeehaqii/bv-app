<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InternalBudget;
use Illuminate\Auth\Access\HandlesAuthorization;

class InternalBudgetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InternalBudget');
    }

    public function view(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('View:InternalBudget');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InternalBudget');
    }

    public function update(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('Update:InternalBudget');
    }

    public function delete(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('Delete:InternalBudget');
    }

    public function restore(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('Restore:InternalBudget');
    }

    public function forceDelete(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('ForceDelete:InternalBudget');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InternalBudget');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InternalBudget');
    }

    public function replicate(AuthUser $authUser, InternalBudget $internalBudget): bool
    {
        return $authUser->can('Replicate:InternalBudget');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InternalBudget');
    }

}