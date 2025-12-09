<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\InternalBudgetItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class InternalBudgetItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InternalBudgetItem');
    }

    public function view(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('View:InternalBudgetItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InternalBudgetItem');
    }

    public function update(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('Update:InternalBudgetItem');
    }

    public function delete(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('Delete:InternalBudgetItem');
    }

    public function restore(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('Restore:InternalBudgetItem');
    }

    public function forceDelete(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('ForceDelete:InternalBudgetItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InternalBudgetItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InternalBudgetItem');
    }

    public function replicate(AuthUser $authUser, InternalBudgetItem $internalBudgetItem): bool
    {
        return $authUser->can('Replicate:InternalBudgetItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InternalBudgetItem');
    }

}