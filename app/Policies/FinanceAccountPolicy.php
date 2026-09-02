<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FinanceAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinanceAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinanceAccount');
    }

    public function view(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('View:FinanceAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinanceAccount');
    }

    public function update(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('Update:FinanceAccount');
    }

    public function delete(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('Delete:FinanceAccount');
    }

    public function restore(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('Restore:FinanceAccount');
    }

    public function forceDelete(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('ForceDelete:FinanceAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinanceAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinanceAccount');
    }

    public function replicate(AuthUser $authUser, FinanceAccount $financeAccount): bool
    {
        return $authUser->can('Replicate:FinanceAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinanceAccount');
    }

}